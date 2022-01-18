<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Size extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('amazon_product', function (Blueprint $table) {
            $table->double('package_width')->unsigned()->default(0.00);
            $table->double('package_height')->unsigned()->default(0.00);
            $table->double('package_depth')->unsigned()->default(0.00);
            $table->double('weight')->unsigned()->default(0.00);
           
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
