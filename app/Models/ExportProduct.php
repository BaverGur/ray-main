<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $rakuten_id
 * @property $product_id
 * @property $user_id
 * @property $name
 * @property $rakuten_genre_id
 * @property $price
 * @property $quantity
 * @property $description
 * @property $is_published
 * @property $is_exported
 * @property $sync_status
 * @property $sync_time
 * @property $is_auto_stock_enabled
 * @property $stock_service
 * @property $stock_product_code
 * @property $stock_price
 * @property $stock_shipping_fee
 * @property $images
 * @property $shipping_fee_id
 * @property $created_at
 * @property $updated_at
 *
 * @property Product $product
 * @property User $user
 * @property RakutenGenre $genre
 * @property ShippingFee $shipping_fee
 * @property MinimumProfit $minimum_profit
 * @property int $profit
 * @property int $seller_price
 */
class ExportProduct extends Model
{
    const SYNC_WAITING = 0;
    const SYNC_SUCCEEDED = 1;
    const SYNC_FAILED = 2;

    protected $table = 'export_product';

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function genre()
    {
        return $this->hasOne(RakutenGenre::class, 'id', 'rakuten_genre_id');
    }

    public function shipping_fee()
    {
        return $this->hasOne(ShippingFee::class, 'id', 'shipping_fee_id');
    }

    public function getMinimumProfitAttribute()
    {
        $stockPrice = $this->stock_price + $this->stock_shipping_fee;
        return MinimumProfit::query()
            ->where('user_id', $this->user->id)
            ->whereRaw('? BETWEEN min_price AND max_price', [$stockPrice])
            ->orderBy('min_price')
            ->orderBy('max_price')
            ->first();
    }

    public function getSellerPriceAttribute()
    {
         $stockPrice = $this->stock_price + $this->stock_shipping_fee;
        $minimumProfit = MinimumProfit::query()
            ->where('user_id', $this->user->id)
            ->whereRaw('? BETWEEN min_price AND max_price', [$stockPrice])
            ->orderBy('min_price')
            ->orderBy('max_price')
            ->first();
        $profit = 0;
        if ($minimumProfit instanceof MinimumProfit) {
            $profit = $minimumProfit->profit;
        }
        $calculatedPrice = $stockPrice + $profit;
        return $calculatedPrice * (1 + ($this->user->rakuten_fee / 100));
    }

    public function getProfitAttribute()
    {
        $profit = $this->price
            - $this->shipping_fee->fee
            - $this->stock_price
            - $this->stock_shipping_fee
            - ($this->price * ($this->user->rakuten_fee / 100));
        return round($profit);
    }
   
}
