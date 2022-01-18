<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $user_id
 * @property $name
 * @property $fee
 * @property $depth
 * @property $width
 * @property $height
 * @property $kg
 * @property $totalDWH
 * 
 *
 * @property User $user
 */
class ShippingFee extends Model
{
    protected $table = 'shipping_fee';
     public $timestamps = false;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
