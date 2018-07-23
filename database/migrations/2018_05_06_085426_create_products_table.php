<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('manufacturer_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->tinyInteger('active')->unsigned()->default(0);
            $table->string('name', 255)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('scu', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->string('url', 255)->nullable();
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
        Schema::dropIfExists('products');
    }
}
