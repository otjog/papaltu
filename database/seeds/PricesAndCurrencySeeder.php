<?php

use Illuminate\Database\Seeder;

class PricesAndCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        DB::table('currency')->insert([
            [
                'name'      => 'Рубль РФ',
                'char_code' => 'RUR',
                'value'     => 1.0000
            ],
            [
                'name'      => 'Доллар США',
                'char_code' => 'USD',
                'value'     => 63.0000
            ],
            [
                'name'      => 'Евро',
                'char_code' => 'EUR',
                'value'     => 74.0000
            ],
        ]);

        DB::table('prices')->insert([
            [
                'name'      => 'retail',
                'comment'   => 'Наша цена продажи'
            ],
            [
                'name'      => 'recommended',
                'comment'   => 'Цены рекомендованные поставщиком'
            ],
            [
                'name'      => 'wholesale',
                'comment'   => 'Закупочная'
            ],
        ]);
    }
}
