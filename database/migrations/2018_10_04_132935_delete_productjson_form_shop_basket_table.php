<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteProductjsonFormShopBasketTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::table('shop_baskets', function (Blueprint $table) {
            $table->dropColumn('products_json');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_baskets', function (Blueprint $table) {
            $table->json('products_json')->after('token');
        });
    }
}
