<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Shipping extends Migration
{
   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_fee', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->unsignedInteger('fee');
            $table->double('depth')->nullable();
            $table->double('width')->nullable();
            $table->double('height')->nullable();
            $table->double('kg')->nullable();
            $table->double('totalDWH')->nullable();
        });
        Schema::table('export_product', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_fee_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
