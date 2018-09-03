<?php

use Illuminate\Database\Seeder;

class PaymentTableSeeder extends Seeder{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        DB::table('payments')->insert([
            [
                'active'        => 1,
                'alias'         => 'online',
                'name'          => 'Онлайн',
                'description'   => 'Вы будете перенаправлены на страницу для оплаты картой',
            ],
            [
                'active'        => 1,
                'alias'         => 'invoice',
                'name'          => 'Счет на оплату',
                'description'   => 'После оформления заказа, на вашу эл.почту придет счет для оплаты через банк',
            ],
            [
                'active'        => 1,
                'alias'         => 'cash',
                'name'          => 'При получении наличными',
                'description'   => 'Вы можете оплатить заказ наличными при получении',
            ],
            [
                'active'        => 1,
                'alias'         => 'card',
                'name'          => 'При получении картой',
                'description'   => 'Вы можете оплатить заказ картой при получении',
            ],
        ]);
    }
}
