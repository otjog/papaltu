<?php

use Illuminate\Database\Seeder;

class ShipmentTableSeeder extends Seeder{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        DB::table('shipments')->insert([
            [
                'active'        => 1,
                'alias'         => 'self',
                'name'          => 'Самовывоз',
                'description'   => 'Вы самостоятельно заберете заказ с нашего склада',
                'is_service'    => '0',
            ],
            [
                'active'        => 1,
                'alias'         => 'delivery',
                'name'          => 'Доставка до дверей',
                'description'   => 'Мы доставим заказ до вашего адреса',
                'is_service'    => '0',
            ],
        ]);
    }
}
