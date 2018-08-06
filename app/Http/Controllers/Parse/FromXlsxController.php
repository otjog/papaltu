<?php

namespace App\Http\Controllers\Parse;

use App\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Product;
use Illuminate\Support\Facades\DB;

class FromXlsxController extends Controller{

    private $pathToFile;
    private $startRow;

    private $tables;
    private $pivotTable;
    private $compareColumn;

    private $product;
    private $category;
    private $reader;

    public function __construct(Request $request, Product $product, Category $category){
        $this->pathToFile = '../public/storage/parse/pumps.xlsx';
        $this->startRow     = '2';

        $this->tables = [
            'categories'        => [
                'default'   =>  [ 'active'  => '1' ],
                'columns'   =>  [ 'name'   => 'A' ],
            ],
            'products'          => [
                'default'   =>  [ 'active' => '1', 'manufacturer_id' => '1' ],
                'columns'   =>  [ 'name'   => 'B' ],
            ],
            'product_has_price.wholesale' => [
                'default'   =>  [ 'active' => '1', 'price_id' => '3' ],
                'columns'   =>  [ 'value'  => 'C' ],
            ],
            'product_has_price.retail' => [
                'default'   =>  [ 'active' => '1', 'price_id' => '2' ],
                'columns'   =>  [ 'value'  => 'D' ],
            ],
            'images'            => [
                'default'   =>  [],
                'columns'   =>  [],
            ],
        ];

        $this->pivotTable       = 'products';
        $this->compareColumn    = 'name';

        $this->product = $product;
        $this->category = $category;

        $this->reader = $this->getReader();
    }

    public function parse(){
        $newDataInTables = $this->read();

        $this->store($newDataInTables);
    }

    private function read(){

        $newDataInTables = [];

        $groupIteraror = $this->getGroupIterator();

        foreach($groupIteraror as $group){

            $itemIterator = $this->getItemIterator( $group );

            foreach ($itemIterator as $item) {

                $parsedParameters = $this->getCurrentParameters( $item );

                if(isset ($parsedParameters[ $this->pivotTable ][ $this->compareColumn ] ) ){
                    $sc_value = $parsedParameters[ $this->pivotTable ][ $this->compareColumn ];
                }else{
                    break;
                }

                foreach($parsedParameters as $tableName => $currentParameters){

                    if( count( $currentParameters ) > 0 ){
                        $newDataInTables[$tableName][ $sc_value ] = $currentParameters;
                    }

                }
            }
        }

        return $newDataInTables;
    }

    private function store($newDataInTables){

        foreach ($newDataInTables as $tableName => $parameters){

            list($clearTableName) = explode('.', $tableName);

            switch($clearTableName){

                case 'categories' :
                    $newDataInTables[ $this->pivotTable ] = $this->storeCategoriesAndGetIds($parameters, $newDataInTables[ $this->pivotTable ]);
                    break;

                case 'products' :
                    $this->storeProducts($newDataInTables[ $this->pivotTable ]);
                    break;

                case 'images' :
                    $this->storeImages($parameters);
                    break;

                case 'product_has_price' :
                    $this->storePrices($parameters);
                    break;
            }
        }
    }

    private function getGroupIterator(){

        return $this->reader->getSheetNames();

    }

    private function getItemIterator( $group ){

        $sheetName = $group;

        $worksheet = $this->reader->getSheetByName($sheetName);

        return $worksheet->getRowIterator($this->startRow);
    }

    private function getCurrentParameters( $item ){

        $currentParameters = [];

        foreach($this->tables as $tableName => $tableData) {

            $currentParameters[$tableName] = $tableData['default'];

            foreach($tableData['columns'] as $columnName => $key){

                $value = $this->getValue($item, $key);

                if( $value !== null ){

                    $currentParameters[$tableName][$columnName] = trim($value);

                }

            }

        }

        return $currentParameters;

    }

    private function getValue($row, $key){

        return $this->getRawValue($row, $key);

    }

    private function getRawValue($row, $key){

        $rawValue = $row->getWorksheet()->getCell($key.$row->getRowIndex());

        return $rawValue->getValue();
    }

    private function getCategoriesData($parameters){

        $data = [
            'new'       => [],
            'update'    => []
        ];

        $products = [];

        $collection = $this->category->getAllCategories();

        foreach ($parameters as $sc_value => $currentParameters) {

            $tableRow = $this->getCurrentTableRow($collection, 'name', $currentParameters['name']);

            if($tableRow !== null){

                $data['update'] = $this->getArrayForUpdate($currentParameters, $tableRow, $data['update']);

                $products[$sc_value]['category_id'] = $tableRow->id;

            }else{

                if($this->getArrayForInsert($currentParameters, $data['new']) !== false){
                    $data['new'][] = $this->getArrayForInsert($currentParameters, $data['new']);
                }

            }
        }

        return ['data' => $data, 'products' => $products];
    }

    private function storeCategoriesAndGetIds($categoryParameters, $productParameters){
        $data = $this->getCategoriesData($categoryParameters);
        //$this->deActivate....
        $this->updateCurrentTable($data['data'], 'categories');

        if( count( $data['data']['new'] ) > 0 ){
            $data = $this->getCategoriesData($categoryParameters);
        }
        return array_merge_recursive($productParameters, $data[ $this->pivotTable ]);
    }

    private function getProductsData($parameters){

        $data = [
            'new'       => [],
            'update'    => []
        ];

        $productsCollection = $this->product->getAllProducts();

        foreach ($parameters as $currentParameters) {

            $product = $this->getCurrentTableRow($productsCollection, $this->compareColumn, $currentParameters[$this->compareColumn]);

            if($product !== null){

                $data['update'] = $this->getArrayForUpdate($currentParameters, $product, $data['update']);

            }else{

                if($this->getArrayForInsert($currentParameters, $data['new']) !== false){
                    $data['new'][] = $this->getArrayForInsert($currentParameters, $data['new']);
                }

            }
        }

        return $data;
    }

    private function storeProducts($productsParameters){
        $data = $this->getProductsData($productsParameters);
        //$this->deActivate....
        $this->updateCurrentTable($data, 'products');
    }

    private function getImagesData($parameters){

        $productsCollection = $this->product->getAllProducts();

        $thumbHeight    = 150;
        $thumbWidth     = 150;

        $imageHeight    = 500;
        $imageWidth     = 500;

        $data = [
            'new'       => [],
            'update'    => []
        ];

        foreach($parameters['main'] as $searchColumnValue => $src){

            $src = '../public/storage/img/shop/product/'.$src;

            $product = $this->getCurrentTableRow($productsCollection, $this->compareColumn, $searchColumnValue);

            if($product !== null){

                $newImageParameters = [
                    'thumb' => [
                        'height'    => $thumbHeight,
                        'width'     => $thumbWidth,
                        'name'      => $searchColumnValue .'-thumbnail-' . $thumbWidth . 'x'. $thumbHeight
                    ],
                    'image' => [
                        'height'    => $imageHeight,
                        'width'     => $imageWidth,
                        'name'      => $searchColumnValue
                    ]
                ];

                if($this->notExistImages($newImageParameters)){

                    $currentParameters = [
                        'image'     => null,
                        'thumbnail' => null
                    ];

                    $parsedUrl = parse_url($src);
                    $path = explode('/', $parsedUrl['path']);

                    $url = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
                    foreach ($path as $key => $folder) {

                        if( count($path)-1 === $key ){
                            $url .= rawurlencode( $folder );
                        }else{
                            $url .= $folder.'/';
                        }

                    }

                    $thumb = imagecreatetruecolor($newImageParameters['thumb']['width'], $newImageParameters['thumb']['height']);
                    $image = @imagecreatefromjpeg($url);

                    $sourceImageData =  getimagesize($url);

                    $sourceImageParameters = [
                        'width'     => $sourceImageData[0],
                        'height'    => $sourceImageData[1],
                        'type'      => explode('/', $sourceImageData['mime'])
                    ];

                    $newThumb = imagecopyresized(
                        $thumb,
                        $image,
                        0,
                        0,
                        0,
                        0,
                        $newImageParameters['thumb']['width'],
                        $newImageParameters['thumb']['height'],
                        $sourceImageParameters['width'],
                        $sourceImageParameters['height']);

                    $newThumbName = $newImageParameters['thumb']['name'] . '.' . $sourceImageParameters['type'][1];
                    $newImageName = $newImageParameters['image']['name'] . '.' . $sourceImageParameters['type'][1];

                    if($newThumb){
                        $res = imagejpeg($thumb, '../public/storage/img/shop/product/thumbnail/'.$newThumbName, 100);

                        if($res){
                            $currentParameters['thumbnail'] = $newThumbName;
                        }

                    }

                    if($image){
                        $res = imagejpeg($image, '../public/storage/img/shop/product/'.$newImageName, 100);

                        if($res){
                            $currentParameters['image'] = $newImageName;
                        }
                    }

                    foreach($currentParameters as $name_param => $value ){
                        switch($name_param){
                            case 'image'            :
                            case 'thumbnail'        :
                                if($value !== $product[$name_param]){
                                    $data['update'][$name_param][$product['id']] = $value;
                                }
                                break;
                        }
                    }
                }
            }
        }

        return $data;
    }

    private function storeImages($imagesParameters){
        $data = $this->getImagesData($imagesParameters);
        $this->updateCurrentTable($data, 'products');
    }

    private function getPricesData($parameters){

        $data = [
            'new'   => []
        ];

        $oldPrice = [
            'prices_id'    => [ $parameters[ key($parameters) ]['price_id'] ],
            'products_id'  => [],
        ];

        $productsCollection = $this->product->getAllProducts();

        foreach($parameters as $sc_value => $currentParameters) {

            $product = $this->getCurrentTableRow($productsCollection, $this->compareColumn, $sc_value);

            if($product !== null){

                $oldPrice['products_id'][] = $product->id;

                $currentParameters['product_id'] = $product->id;

                $currentParameters = $this->addTimeStamp($currentParameters);

                $data['new'][] = $currentParameters;

            }
        }

        return ['new_price' => $data, 'old_price' => $oldPrice];
    }

    private function storePrices($priceParameters){
        $data = $this->getPricesData($priceParameters);
        $this->deActiveOldPrice($data['old_price']);
        $this->updateCurrentTable($data['new_price'], 'product_has_price');
    }

    /******** Helpers *********/

    private function getCurrentTableRow($collection, $columnName, $columnValue){
        return $collection->first(function($value, $key) use ($columnName, $columnValue){
            return $value->$columnName === $columnValue;
        });
    }

    private function addTimeStamp($currentParameters){
        //todo неверная локализация даты!
        $currentParameters['created_at'] = date('Y-m-d H:i:s',time());
        $currentParameters['updated_at'] = date('Y-m-d H:i:s',time());

        return $currentParameters;
    }

    private function getArrayForUpdate($currentParameters, $tableRow, $data){

        foreach($currentParameters as $name_param => $value ){
            if($value !== $tableRow[$name_param]){
                $data[$name_param][$tableRow['id']] = $value;
            }
        }
        return $data;
    }

    private function getArrayForInsert($currentParameters, $data){

        foreach($data as $tableRow){
            if( $tableRow['name'] === $currentParameters['name'] ){
                return false;
            }
        }

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
            ->whereIn('product_id',    $columns['products_id'])
            ->whereIn('price_id',      $columns['prices_id'])
            ->update(['active' => 0]
            );
    }

    private function preparePriceValue($value, $priceType){
        $value = str_replace(' ', '', $value);

        return $value;
    }

    private function prepareImageValue($path){
        $path = str_replace('/resize.php?file=', '', $path);
        $path = str_replace('&size=300', '', $path);

        return $path;

    }

    /**************************************/
    private function getReader(){
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        return $reader->load(
            $this->pathToFile
        );
    }

}
