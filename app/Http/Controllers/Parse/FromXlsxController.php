<?php

namespace App\Http\Controllers\Parse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Product;
use App\Price;
use Illuminate\Support\Facades\DB;

class FromXlsxController extends Controller{

    private $pathToFolder = '../public/storage/parse/';
    private $fileName;
    private $startRow;
    private $nameColumns;

    private $product;

    public function __construct(Request $request, Product $product){
        $this->fileName     = 'pumps.xlsx';
        $this->startRow     = '2';
        $this->nameColumns  = [
            'A' => 'products.category_id',
            'B' => 'products.name',
            'C' => 'products.scu',

            'D' => 'prices.wholesale',
            'E' => 'prices.retail'
        ];

        $this->product = $product;
    }

    public function parse(){
        $tables = $this->read();

        $this->store($tables);
    }

    private function read() {

        $tables = [
            'categories'    =>  [],
            'products'      =>  [],
            'prices'        =>  [],
        ];

        $iterator = $this->getIterator();

        foreach ($iterator as $item) {

            if($item->getRowIndex() >= $this->startRow){

                $parsedParameters = $this->getCurrentParameters($item);

                foreach($parsedParameters as $table_name => $currentParameters){

                    switch($table_name){
                        case 'categories' : /**/;break;

                        case 'products' :
                            $tables['products'][] = $currentParameters;
                            break;

                        case 'prices'  :
                            foreach($currentParameters as $price_name => $price_value) {
                                $tables['prices'][ $price_name ][ key( $price_value ) ] = ceil($price_value[ key( $price_value ) ]);
                            }
                            break;
                    }

                }

            }

        }

        return $tables;

    }

    private function store($tables){

        foreach ($tables as $table_name => $parameters){
            switch($table_name){

                case 'categories' : /**/; break;

                case 'products' :
                    $data = $this->getProductData($parameters);
                    //$this->deActivate....
                    $this->updateCurrentTable($data, $table_name);
                    break;

                case 'prices' :
                    $data = $this->getPriceData($parameters);
                    $this->deActiveOldPrice($data['columns']);
                    $this->updateCurrentTable($data['data'], 'product_has_price');
                    break;
            }
        }

    }

    private function getIterator(){
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load(
            $this->pathToFolder.$this->fileName
        );

        $worksheet = $spreadsheet->getActiveSheet();

        return $worksheet->getRowIterator();
    }

    private function getCurrentParameters($row){

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);

        $currentParameters = [
            'categories'    =>  [],

            'products'      => [
                'active'            => '1',
                'manufacturer_id'   => '1',
            ],

            'prices'        => []
        ];

        foreach ($cellIterator as $cell) {
            if( isset( $this->nameColumns[$cell->getColumn()] ) ){

                list($name_table, $name_column) = explode('.',  $this->nameColumns[$cell->getColumn()] );

                switch($name_table){
                    case 'categories' :
                        break;

                    case 'products' :
                        $currentParameters[$name_table][$name_column] = (string) $cell->getValue();
                        break;

                    case 'prices'   :
                        $currentParameters[$name_table][$name_column]['scu'] = (string) $cell->getValue();
                        break;
                }
            }

        }

        $currentParameters = $this->addScuToPriceParameters($currentParameters);

        return $currentParameters;

    }

    private function getProductData($parameters){

        $data = [
            'new'       => [],
            'update'    => []
        ];

        $productsCollection = $this->product->getAllProducts();

        foreach ($parameters as $currentParameters) {

            $product = $this->getCurrentProduct($productsCollection, $currentParameters['scu']);

            if($product !== null){

                $data['update'] = $this->getArrayForUpdate($currentParameters, $product, $data['update']);

            }else{

                $data['new'][] = $this->getArrayForInsert($currentParameters);

            }
        }

        return $data;
    }

    private function getPriceData($parameters, $productPrices = []){

        $data = [
            'new'   => []
        ];

        $columns = [
            'prices'    => [],
            'products'  => [],
        ];

        foreach($parameters as $price_name => $priceData){

            //todo переписать функцию, а то там '*'
            $price = Price::firstOrCreate([
                'name' =>  $price_name
            ]);

            $columns['prices'][] = $price->id;

            if( count( $priceData ) > 0){

                $productsCollection = $this->product->getAllProducts();

                foreach($priceData as $scu => $value) {

                    $product = $this->getCurrentProduct($productsCollection, (string)$scu);

                    if($product !== null){

                        $columns['products'][] = $product->id;

                        $currentParameters = [
                            'active'        => '1',
                            'product_id'    => $product->id,
                            'price_id'      => $price->id,
                            'value'         => $value
                        ];

                        $currentParameters = $this->addTimeStamp($currentParameters);

                        $data['new'][] = $currentParameters;

                    }
                }

            }elseif( count( $productPrices ) > 0 ){

                foreach($productPrices as $productPrice){

                    $productPrice->price_id    = $price->id;
                    $productPrice->value       = ceil($productPrice->value * $productPrice->quotation);
                    unset( $productPrice->quotation );

                    $productPrice = $this->addTimeStamp($productPrice->toArray());

                    $data['new'][] = $productPrice;

                }
            }


        }

        return ['data' => $data, 'columns' => $columns];
    }


    /******** Helpers *********/

    private function getCurrentProduct($products, $scu){
        return $products->first(function($value, $key) use ($scu){
            return $value->scu === $scu;
        });
    }

    private function addTimeStamp($currentParameters){
        //todo неверная локализация даты!
        $currentParameters['created_at'] = date('Y-m-d H:i:s',time());
        $currentParameters['updated_at'] = date('Y-m-d H:i:s',time());

        return $currentParameters;
    }

    private function getArrayForUpdate($currentParameters, $product, $data){

        foreach($currentParameters as $name_param => $value ){
            switch($name_param){
                case 'active'           :
                case 'original_name'    :
                case 'name'             :
                case 'thumbnail'        :
                case 'image'            :

                    if($value !== $product[$name_param]){
                        $data[$name_param][$product['id']] = $value;
                    }
                    break;
            }
        }
        return $data;
    }

    private function getArrayForInsert($currentParameters){
        $currentParameters = $this->addTimeStamp($currentParameters);

        return $currentParameters;
    }

    private function updateCurrentTable($data, $currentTableName){
        foreach($data as $condition => $parameters){
            if( count($parameters) > 0 ){
                switch($condition){
                    case 'new'      :   $this->insertRowsInTable($data[$condition], $currentTableName); break;
                    case 'update'   :   $this->updateRowsInTable($data[$condition], $currentTableName); break;
                }
            }
        }
    }

    private function insertRowsInTable($data, $currentTableName){

        DB::table($currentTableName)->insert(
            $data
        );
    }

    private function updateRowsInTable($data, $currentTableName){
        $sqlQueryString = "UPDATE " . $currentTableName . " SET";
        $cntParams = 0;
        $arrayIds = [];
        $params = [];
        foreach($data as $name_param => $array_params){
            if($cntParams !== 0){
                $sqlQueryString .=",";
            }
            $cntParams++;

            $sqlQueryString .= " " . $name_param . " = CASE ";
            foreach($array_params as $id => $param){
                $sqlQueryString .= "WHEN id = " . $id . " THEN ? ";

                if(!(in_array($id, $arrayIds))){
                    $arrayIds[] = $id;
                }

                $params[] = $param;
            }
            $sqlQueryString .= "ELSE " . $name_param . " END";
        }

        $cnt = 0;
        $ids = "";
        foreach($arrayIds as $id){
            if($cnt !== 0){
                $ids .= ", ";
            }
            $ids .= $id;
            $cnt++;
        }

        $sqlQueryString .= " WHERE id IN (" . $ids . ")";

        return DB::update($sqlQueryString, $params);
    }

    private function deActiveOldPrice($columns){

        DB::table('product_has_price')
            ->where('active', 1)
            ->whereIn('product_id',    $columns['products'])
            ->whereIn('price_id',      $columns['prices'])
            ->update(['active' => 0]
            );
    }



    private function addScuToPriceParameters($currentParameters){
        foreach ($currentParameters['prices'] as $key => $price) {

            $scu = $currentParameters['products']['scu'];

            $currentParameters['prices'][$key][$scu] = $price['scu'];

            unset($currentParameters['prices'][$key]['scu']);
        }
        return $currentParameters;
    }


}
