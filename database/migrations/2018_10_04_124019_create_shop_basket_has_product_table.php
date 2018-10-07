<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopBasketHasProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_basket_has_product', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('basket_id');
            $table->integer('product_id');
            $table->integer('quantity')->unsigned();
            $table->string('order_attributes', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_basket_has_product');
    }
}
