<?php

namespace App\Http\Controllers\Price;

use App\Models\Shop\Product\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CurrencyController extends Controller{

    private $url = 'http://www.cbr.ru/scripts/XML_daily.asp';

    public function getCur(Product $products){

        $xmlString = $this->connectToSite();

        $sxml = simplexml_load_string($xmlString);

        foreach ($sxml->Valute as $cur){
            switch($cur->CharCode){
                case 'USD' :
                case 'EUR' : $this->updateCur($cur); break;
            }
        }

    }

    private function connectToSite(){
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function updateCur($curXml){
        $cur = DB::table('currency')->where('char_code', $curXml->CharCode)->first();

        $time = time();

        $floatValue = str_replace(',', '.', $curXml->Value);

        if($cur === null){
            DB::table('currency')->insert(
                [
                    'name' => $curXml->Name,
                    'char_code' => $curXml->CharCode,
                    'value' => $floatValue,
                    'created_at' => date('Y-m-d H:i:s',$time),
                    'updated_at' => date('Y-m-d H:i:s',$time)
                ]
            );
        }else{
            DB::table('currency')
                ->where('char_code', $curXml->CharCode)
                ->update(['value' => $floatValue, 'updated_at' => date('Y-m-d H:i:s',$time)]);
        }

    }
}
