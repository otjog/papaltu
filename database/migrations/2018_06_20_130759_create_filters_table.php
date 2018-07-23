<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filters', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('active')->unsigned()->default(0);
            $table->string('alias', 15);
            $table->string('name', 100);
            $table->smallInteger('sort')->unsigned()->default(999);
            $table->enum('type', ['slider', 'slider-range', 'checkbox', 'radio', 'phrase'])->nullable();
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
        Schema::dropIfExists('filters');
    }
}
