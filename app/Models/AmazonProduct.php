<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $jan_code
 * @property $asin
 * @property $name
 * @property $url
 * @property $in_stock
 * @property $shipping_fee
 * @property $price
 * @property $other_sellers_count
 * @property $last_update
 * @property $package_width
 * @property $package_height
 * @property $package_depth
 * @property $weight
 * @property $created_at
 * @property $updated_at
 *
 * @property Product $product
 */
class AmazonProduct extends Model
{
    protected $table = 'amazon_product';

    
    protected $fillable = [
        'last_update',
        'price'
    ];
}
