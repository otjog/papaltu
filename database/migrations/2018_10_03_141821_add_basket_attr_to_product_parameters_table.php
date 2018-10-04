<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBasketAttrToProductParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_parameters', function (Blueprint $table) {
            $table->tinyInteger('order_attr')->unsigned()->default(0)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_parameters', function (Blueprint $table) {
            $table->dropColumn('order_attr');
        });
    }
}
