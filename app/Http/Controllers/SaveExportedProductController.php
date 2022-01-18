<?php

namespace App\Http\Controllers;

use App\Models\AmazonProduct;
use App\Models\ExportProduct;
use App\Models\Product;
use App\Models\ShippingFee;
use App\Models\MinimumProfit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RAY\Yahoo\ItemLookupAction;

class SaveExportedProductController extends Controller
{
    protected ?Product $product = null;
    protected ?ExportProduct $exportProduct = null;
    protected ?AmazonProduct $amazonProduct = null;

   
    public function index(Request $request)
    {
        $this->loadProducts($request);
        if ($this->product == null || $this->exportProduct == null) {
            return redirect()->route('home');
        }
        $this->amazonProduct = AmazonProduct::query()->where('jan_code', $this->product->jan_code)->first();
        if ($request->isMethod('POST')) {
            return $this->saveExportProduct($request);
        }
        return view('export_product.save', [
            'product' => $this->product,
            'export_product' => $this->exportProduct,
            'images' => explode(', ', $this->product->related_images),
            'amazon_product' => $this->amazonProduct,
            'shipping_fees' => ShippingFee::query()->where('user_id', Auth::id())->orderBy('fee')->get()
        ]);
    }

    protected function loadProducts(Request $request)
    {
        if ($request->get('product_id')) {
            $this->product = Product::query()->where('id', $request->get('product_id'))->first();
            if ($this->product == null) {
                return;
            }
            $this->exportProduct = new ExportProduct();
            $this->exportProduct->product_id = $this->product->id;
            $data=$this->product->calculateSellerPrice(Auth::user());
            $this->exportProduct->price = $data["calculatedPrice"];
            
            if($data["shippingMethodID"] > 0){
            $this->exportProduct->shipping_fee_id = $data["shippingMethodID"];
           
            }
            
            
        } else {
            $this->exportProduct = ExportProduct::query()
                ->where('id', $request->get('id'))
                ->where('is_exported', 0)
                ->first();
            if ($this->exportProduct == null) {
                return;
            }
            $this->product = $this->exportProduct->product;
        }
    }

    protected function syncRelatedImages()
    {
        if($this->product->related_images == null) {
            $action = new ItemLookupAction(Auth::user()->yahoo_app_id);
            $response = $action->find([
                'itemcode' => $this->product->id,
                'image_size' => 600,
                'responsegroup' => 'large'
            ]);
            if ($response instanceof \stdClass
                && isset($response->Result)
                && isset($response->Result->Hit)
                && isset($response->Result->Hit->RelatedImages)
                && isset($response->Result->Hit->RelatedImages->Image)
                && is_array($response->Result->Hit->RelatedImages->Image)
            ) {
                $imageUrls = array_column($response->Result->Hit->RelatedImages->Image, 'Medium');
                $this->product->related_images = implode(', ', $imageUrls);
                $this->product->save();
            }
        }
    }

    protected function saveExportProduct(Request $request)
    {
        $isOld = $this->exportProduct->id > 0;
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'price' => 'required',
                'rakuten_genre_id' => ['required', Rule::exists('rakuten_genre', 'id')],
                'shipping_fee_id' => ['required', Rule::exists('shipping_fee', 'id')],
                'description' => 'required',
            ],
            [
                'name.required' => __(trans('Name is required.')),
                'price.required' => __(trans('Price is required.')),
                'rakuten_genre_id.exists' => __(trans('Selected genre is not exists.')),
                'rakuten_genre_id.required' => __(trans('Genre is required.')),
                'shipping_fee_id.exists' => __(trans('Selected shipping fee is not exists.')),
                'shipping_fee_id.required' => __(trans('Shipping fee is required.')),
                'description.required' => __(trans('Description is required.'))
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }
        $validated = $validator->validated();
        $this->exportProduct->user_id = Auth::id();
        $this->exportProduct->product_id = $this->product->id;
        $this->exportProduct->rakuten_genre_id = $validated['rakuten_genre_id'];
        $this->exportProduct->shipping_fee_id = $validated['shipping_fee_id'];
        $this->exportProduct->name = $validated['name'];
        $this->exportProduct->price = $validated['price'];
        $this->exportProduct->description = $validated['description'];
        if ($this->amazonProduct) {
            $this->exportProduct->stock_service = 'amazon';
            $this->exportProduct->stock_product_code = $this->amazonProduct->asin;
            $this->exportProduct->stock_shipping_fee = $this->amazonProduct->shipping_fee;
            $this->exportProduct->stock_price = $this->amazonProduct->price;
            $this->exportProduct->is_auto_stock_enabled = 1;
        } else {
            $this->exportProduct->stock_shipping_fee = 0;
            $this->exportProduct->stock_price = 0;
        }
        if ($request->get('is_published')) {
            $this->exportProduct->is_published = intval($request->get('is_published'));
        } elseif ($this->exportProduct->is_exported == 0) {
            $this->exportProduct->is_published = 0;
        }
        $this->exportProduct->save();
        if ($isOld) {
            return redirect()->back()->withInput()->with('success', __(trans('Product on the waiting list has been updated!')));
        } else {
            return redirect()->back()->with('success', __(trans('Product has been added to waiting list!')));
        }
    }
    public function SaveEmployeeRecord(Request $request)  
    {  
        $user = User::query()
        ->where('id', Auth::id())
        ->first();
        $stockPrice = $request->shipping_fee + $request->sellerPrice;
        $minimumProfit = MinimumProfit::query()
            ->where('user_id', Auth::id())
            ->whereRaw('? BETWEEN min_price AND max_price', [$stockPrice])
            ->orderBy('min_price')
            ->orderBy('max_price')
            ->first();
        $profit = 0;
        if ($minimumProfit instanceof MinimumProfit) {
            $profit = $minimumProfit->profit;
        }
        $calculatedPrice = $stockPrice + $profit;
        $result = round($calculatedPrice * (1 + ($user->rakuten_fee / 100)));
    // do here some operation  
    return $result;  
    }  
    
}
