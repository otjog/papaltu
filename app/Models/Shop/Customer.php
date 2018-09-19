<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model{

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'address',
        'full_name_json',
        'address_json'
    ];

    public function shopOrders(){
        return $this->hasMany('App\Order');
    }


    public function storeCustomer($data){

        $data_customer = $this->prepareData($data);

        return self::create($data_customer);

    }

    public function updateCustomer(){

    }

    public function findOrCreateCustomer($data){

        $email = $data['email'];

        $customer = $this->getCustomerByEmail($email);

        if( isset( $customer[0] ) ){
            return $customer[0];
        }else{
            return $this->storeCustomer($data);
        }

    }

    public function getCustomerByEmail($email){
        return self::select(
            'id',
            'full_name',
            'email',
            'phone',
            'address',
            'full_name_json',
            'address_json'
        )
            ->where('email', $email)
            ->get();
    }

    public function prepareData($data){

        $data_customer = [];

        foreach( $data as $key => $value ){
            switch($key){
                case 'full_name'        :
                case 'email'            :
                case 'phone'            :
                case 'address'          :
                case 'address_json'     :
                case 'full_name_json'   : $data_customer[$key] = $value;
            }
        }

        return $data_customer;
    }

}
