<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shop_basket_id');
            $table->integer('payment_id');
            $table->integer('shipment_id');
            $table->integer('customer_id');
            $table->string('address', 255);
            $table->string('comment', 255)->nullable();
            $table->tinyInteger('paid')->default(0);
            $table->string('pay_id', 20)->nullable();
            $table->json('products_json')->nullable();
            $table->json('address_json')->nullable();
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
        Schema::dropIfExists('shop_orders');
    }
}
