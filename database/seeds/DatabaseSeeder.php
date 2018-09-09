<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        $this->call(PricesAndCurrencySeeder::class);
        $this->call(FilterTableSeeder::class);
        $this->call(PaymentTableSeeder::class);
        $this->call(ShipmentTableSeeder::class);
    }
}
