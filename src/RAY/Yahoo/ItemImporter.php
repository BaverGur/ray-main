<?php

namespace RAY\Yahoo;

use App\Models\AmazonProduct;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RAY\Amazon\Exception\ProductLinkNotFoundException;
use RAY\Amazon\ProductPageScraper;
use RAY\Yahoo\Exception\JanCodeRequiredException;

class ItemImporter
{
    protected string $yahooAppId;
    protected bool $isJanCodeRequired = false;
    protected bool $isAmazonProductRequired = false;

    /**
     * @param $yahooAppId
     */
    public function __construct($yahooAppId)
    {
        $this->yahooAppId = $yahooAppId;
    }

    public function janCodeRequired()
    {
        $this->isJanCodeRequired = true;
        return $this;
    }

    public function amazonProductRequired()
    {
        $this->isAmazonProductRequired = true;
        return $this;
    }

    /**
     * @param $itemCode
     * @throws JanCodeRequiredException
     */
    public function importItemCode($itemCode)
    {
        $action = new ItemLookupAction($this->yahooAppId);
        $response = $action->find([
            'itemcode' => $itemCode,
            'image_size' => 600,
            'responsegroup' => 'large'
        ]);
        if ($this->isJanCodeRequired && is_string($response->Result->Hit->JanCode) === false) {
            throw new JanCodeRequiredException();
        }
        try {
            DB::beginTransaction();
            $seller = $this->importSeller($response->Result->Hit);
            $product = $this->importProduct($seller, $response->Result->Hit);
            $this->importAmazonProduct($product);
            DB::commit();
        } catch (ProductLinkNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception);
            DB::rollBack();
        }
    }

    protected function importSeller(\stdClass $hit): Seller
    {
        /**
         * @var Seller $seller
         */
        $seller = Seller::query()
            ->where('id', $hit->Store->Id)
            ->firstOrNew();
        $seller->id = $hit->Store->Id;
        $seller->name = $hit->Store->Name;
        $seller->url = $hit->Store->Url;
        $seller->review_rate = $hit->Store->Ratings->Rate;
        $seller->review_count = $hit->Store->Ratings->Count;
        $seller->save();
        return $seller;
    }

    protected function importProduct(Seller $seller, \stdClass $hit)
    {
        /**
         * @var Product $product
         */
        $product = Product::query()->where('id', $hit->Code)->firstOrNew();
        $product->id = $hit->Code;
        $product->seller_id = $seller->id;
        $product->category_id = $hit->ProductCategory->ID;
        $product->name = $hit->Name;
        $product->image = is_string($hit->Image->Id) ? $hit->Image->Id : null;
        $product->description = is_string($hit->Description) ? $hit->Description : null;
        $product->headline = is_string($hit->Headline) ? $hit->Headline : null;
        $product->in_stock = $hit->Availability === 'instock';
        $product->url = $hit->Url;
        $product->condition = $hit->Condition;
        $product->jan_code = is_string($hit->JanCode) ? $hit->JanCode : null;
        $product->price = $hit->Price;
        $product->review_rate = $hit->Review->Rate;
        $product->review_count = $hit->Review->Count;
        if (isset($hit->RelatedImages)
            && isset($hit->RelatedImages->Image)
            && is_array($hit->RelatedImages->Image)
        ) {
            $imageUrls = array_column($hit->RelatedImages->Image, 'Medium');
            $product->related_images = implode(', ', $imageUrls);
            
        }
        $product->save();
        return $product;
    }

    protected function importAmazonProduct(Product $product)
    {
        if ($product->jan_code == null) {
            return;
        }
        /**
         * @var AmazonProduct $amazonProduct
         */
        $amazonProductResult = ProductPageScraper::scrapeByJan($product->jan_code);
        $amazonProduct = AmazonProduct::query()
            ->where('asin', $amazonProductResult->asin)
            ->firstOrNew();
        $amazonProduct->jan_code = $product->jan_code;
        $amazonProduct->asin = $amazonProductResult->asin;
        $amazonProduct->name = $amazonProductResult->title;
        $amazonProduct->price = $amazonProductResult->price;
        $amazonProduct->shipping_fee = $amazonProductResult->shippingFee;
        $amazonProduct->url = 'https://amazon.co.jp/dp/' . $amazonProduct->asin;
        $amazonProduct->last_update = date('Y-m-d');
        $amazonProduct->save();
    }
}
