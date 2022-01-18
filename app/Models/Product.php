<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RAY\Amazon\ProductPageScraper;
/**
 * @property $id
 * @property $seller_id
 * @property $category_id
 * @property $name
 * @property $image
 * @property $related_images
 * @property $description
 * @property $headline
 * @property $in_stock
 * @property $availability
 * @property $is_published
 * @property $url
 * @property $condition
 * @property $jan_code
 * @property $price
 * @property $review_rate
 * @property $review_count
 * @property $created_at
 * @property $updated_at
 *
 * @property Seller $seller
 * @property Category $category
 * @property AmazonProduct $amazon_product
 */
class Product extends Model
{
    protected $table = 'product';
    public $incrementing = false;

    public function amazon_product()
    {
        return $this->hasOne(AmazonProduct::class, 'jan_code', 'jan_code');
    }

    public function seller()
    {
        return $this->hasOne(Seller::class, 'id', 'seller_id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function calculateSellerPrice(User $user)
    {
        $stockPrice = $this->amazon_product->price + $this->amazon_product->shipping_fee;
        
        $package_width=$this->amazon_product->package_width;
        $package_height=$this->amazon_product->package_height;
        $package_depth=$this->amazon_product->package_depth;
        $weight=$this->amazon_product->weight;
        
        
        $data['shippingMethodID'] = 0;
        $shippingPrice=0;
        if($package_width != null && $package_width != "" && $package_height !=null && $package_height != "" && $package_depth != null && $package_depth != "" && $weight != null && $weight != ""){
           
            $shippingFee = ShippingFee::query()
                ->where('user_id', $user->id)
                ->orderBy('fee')->get();
           
            $shippingMethodID=0;
           
            foreach($shippingFee as $method){
               
                if($method->depth != null && $method->depth > 0 && $method->width != null && $method->width > 0 && $method->height != null && $method->height > 0 && $method->kg != null && $method->kg > 0){
                  
                    if($package_depth <= $method->depth){
                        
                            if($package_width <= $method->width){
                                
                                    if($package_height <= $method->height){
                                       
                                            if($weight <= $method->kg){
                                                    if($shippingPrice > 0){
                                                        if($shippingPrice > $method->fee){  
                                                            $shippingPrice = $method->fee; 
                                                            $shippingMethodID = $method->id;
                                                        }
                                                    }
                                                    else
                                                    { $shippingPrice = $method->fee;
                                                    $shippingMethodID = $method->id;
                                                    }
                                            }
                                        
            
                                    }
                                

                            }
                        

                    }

                }
                if($shippingMethodID == 0){
                   
                    if($method->totalDWH != null && $method->totalDWH > 0 ){
                        
                        $total=$package_depth + $package_height + $package_width;
                        if($total <= $method->totalDWH){
                            if( $weight <= $method->kg){
                               
                                if($shippingPrice > 0){
                                    if($shippingPrice > $method->fee) {
                                        $shippingPrice = $method->fee;
                                        $shippingMethodID = $method->id;
                                    }
                                }
                                else{ $shippingPrice = $method->fee;
                                    $shippingMethodID = $method->id;
                                }
                            }

                        }

                    }
                }

            }
            if($shippingPrice > 0 && $shippingMethodID > 0)  $data['shippingMethodID'] = $shippingMethodID;

        }
        $stockPrice = $stockPrice + $shippingPrice;
        $minimumProfit = MinimumProfit::query()
            ->where('user_id', $user->id)
            ->whereRaw('? BETWEEN min_price AND max_price', [$stockPrice])
            ->orderBy('min_price')
            ->orderBy('max_price')
            ->first();
        $profit = 0;
        if ($minimumProfit instanceof MinimumProfit) {
            $profit = $minimumProfit->profit;
        }
        $calculatedPrice = $stockPrice + $profit;
        $data['calculatedPrice'] = round($calculatedPrice * (1 + ($user->rakuten_fee / 100)));
        
        //$data['calculatedPrice'] = $shippingPrice;
        return $data;
    }

    public function calculateSellerProfit(User $user)
    {
        $stockPrice = $this->amazon_product->price + $this->amazon_product->shipping_fee;
        $minimumProfit = MinimumProfit::query()
            ->where('user_id', $user->id)
            ->whereRaw('? BETWEEN min_price AND max_price', [$stockPrice])
            ->orderBy('min_price')
            ->orderBy('max_price')
            ->first();
        $profit = 0;
        if ($minimumProfit instanceof MinimumProfit) {
            $profit = $minimumProfit->profit;
        }
        return $profit;
    }
}
