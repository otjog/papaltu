<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMainCurrencyToCurrencyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('currency', function (Blueprint $table) {
            $table->tinyInteger('main')->unsigned()->default(0)->after('char_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('currency', function (Blueprint $table) {
            $table->dropColumn('main');
        });
    }
}
