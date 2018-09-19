<?php

namespace App\Http\Controllers\Parse;

use App\Models\Shop\Category\Category;
use App\Http\Controllers\Controller;
use App\Models\Shop\Product\Image;
use App\Models\Shop\Price\Currency;
use App\Models\Shop\Price\Price;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Shop\Product\Product;
use Illuminate\Support\Facades\DB;

class FromXlsxController extends Controller{

    private $pathToFile;

    private $startRow;

    //Порядок имеет значение!
    private $tables = [
            /*
            'categories'        => [
                'default'   =>  [ 'active'  => '1' ],
                'columns'   =>  [ 'id'   => 'D' ],
                'compare'   =>  'id'
            ],
            */
            'images'            => [
                'default'   =>  [],
                'columns'   =>  ['src' => 'L'],
            ],

            'products'          => [
                'default'   =>  [ 'manufacturer_id' => '1', 'thumbnail' => null, 'weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0, ],
                'columns'   =>  [ 'scu' => 'B', 'name' => 'C', 'weight' => 'F', 'length' => 'G', 'width' => 'H', 'height' => 'I', 'category_id' => 'J','active' => 'M' ],
                'compare'   =>  'scu'
            ],

            /*
            'product_has_price.wholesale' => [
                'default'   =>  [ 'active' => '1', 'price_id' => '3' ],
                'columns'   =>  [ 'value'  => 'C' ],
            ],
            */
            'product_has_price.retail' => [
                'default'   =>  [ 'active' => '1', 'price_id' => '1' ],
                'columns'   =>  [ 'value'  => 'D', 'currency_id' => 'E' ],//currency_id может принимать, как сам id так и код: RUB; EUR; USD и т.п.
            ],

            'product_has_image' => [
                'default'   =>  [],
                'columns'   =>  [],
            ],

    ];

    private $imageParameters = [
        'thumb' => [
            'action'    => 'replace', //replace|false = nothing
            'height'    => 150,
            'width'     => 150,
            'addition'  => '-thumbnail-' . 150 . 'x'. 150
            ],
        'big' => [
            'action'    => 'add',//replace|add|false = nothing
            'height'    => 1000,
            'width'     => 1000,
            'addition'  => ''
        ]
    ];

    private $mainImageFolder = 'storage/img/shop/product/';

    private $thumbImageFolder = 'storage/img/shop/product/thumbnail/';

    private $pivotTable;

    private $product;

    private $category;

    private $image;

    private $reader;

    public function __construct(Request $request, Product $product, Category $category, Image $image){

        $this->pathToFile   = public_path('/storage/parse/celtic.xlsx');

        $this->startRow     = '2';

        $this->pivotTable   = 'products';

        $this->product      = $product;

        $this->category     = $category;

        $this->image        = $image;

        $this->reader       = $this->getReader();
    }

    public function parse(){
        $newDataInTables = $this->read();

        $this->store($newDataInTables);
    }

    private function read(){

        $compareColumn = $this->tables[ $this->pivotTable ]['compare'];

        $newDataInTables = [];

        $groupIteraror = $this->getGroupIterator();

        foreach($groupIteraror as $group){

            $itemIterator = $this->getItemIterator( $group );

            foreach ($itemIterator as $item) {

                $parsedParameters = $this->getCurrentParameters( $item );

                if(isset ($parsedParameters[ $this->pivotTable ][ $compareColumn ] ) ){
                    $sc_value = $parsedParameters[ $this->pivotTable ][ $compareColumn ];
                }else{
                    break;
                }

                foreach($parsedParameters as $tableName => $currentParameters){

                    if( isset($newDataInTables[$tableName]) === false ){
                        $newDataInTables[$tableName] = [];
                    }

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

                case 'images' :
                    $relatedParameters = $this->storeImagesAndGetRelatedParameters($parameters);
                    $newDataInTables = array_replace_recursive($newDataInTables, $relatedParameters);
                    break;

                case 'products' :
                    $this->storeProducts($newDataInTables[ $this->pivotTable ]);
                    break;

                case 'product_has_price' :
                    $this->storePrices($parameters);
                    break;

                case 'product_has_image' :
                    $this->storeProductsImages($newDataInTables [ 'product_has_image' ] );
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

                $value = $this->getValue($tableName, $columnName, $item , $key);

                if( $value !== null ){

                    $currentParameters[$tableName][$columnName] = $value;

                }

            }

        }

        return $currentParameters;

    }

    private function getValue($tableName, $columnName, $row, $key){

        list($clearTableName) = explode('.', $tableName);

        $value =  $this->getRawValue($row, $key);

        switch($clearTableName){

            case 'images' :
                if($columnName === 'src'){
                    $images =  explode('|', $value);
                    if( count($images) > 0 && $images[0] !== '')
                        return $images;
                    return null;
                }
                break;
        }

        return $value;

    }

    private function getRawValue($row, $key){

        $rawValue = $row->getWorksheet()->getCell($key.$row->getRowIndex());

        return $rawValue->getValue();
    }

    private function getCategoriesData($parameters){

        $compareColumn = $this->tables[ 'categories' ]['compare'];

        $data = [
            'new'       => [],
            'update'    => []
        ];

        $products = [];

        $collection = $this->category->getAllCategories();

        foreach ($parameters as $sc_value => $currentParameters) {

            $tableRow = $this->getCurrentTableRow($collection, $compareColumn, $currentParameters[$compareColumn]);

            if($tableRow !== null){

                $data['update'] = $this->getArrayForUpdate($currentParameters, $tableRow, $data['update']);

                $products[$sc_value]['category_id'] = $tableRow->id;

            }else{

                if($this->getArrayForInsert($currentParameters, $data['new'], $compareColumn) !== false){
                    $data['new'][] = $this->getArrayForInsert($currentParameters, $data['new'], $compareColumn);
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

        $compareColumn = $this->tables[ 'products' ]['compare'];

        $data = [
            'new'       => [],
            'update'    => []
        ];

        $productsCollection = $this->product->getAllProducts();

        foreach ($parameters as $currentParameters) {

            $product = $this->getCurrentTableRow($productsCollection, $compareColumn, $currentParameters[$compareColumn]);

            if($product !== null){

                $data['update'] = $this->getArrayForUpdate($currentParameters, $product, $data['update']);

            }else{

                if($this->getArrayForInsert($currentParameters, $data['new'], $compareColumn) !== false){
                    $data['new'][] = $this->getArrayForInsert($currentParameters, $data['new'], $compareColumn);
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

        $compareColumn = $this->tables[ $this->pivotTable ]['compare'];

        $data = [
            'images' => [
                'new'       => [],
                'update'    => [],
            ],
            'products' => [],
            'product_has_image' => []
        ];

        $imagesCollection = $this->image->getAllImages();

        foreach($parameters as $sc_value => $currentParameters){

            foreach($currentParameters['src'] as $key => $src){

                $src = public_path('/storage/parse/images/'.$src);

                $srcImageData = $this->getImageData($src);

                /*****************Create Image************************/
                $image = $this->loadImage($src, $srcImageData['const_ext']);

                $squareImage = $this->createNewImage($image, $srcImageData, 'big');

                if($squareImage){

                    $imageName  = $this->getNewImageName($this->mainImageFolder, $sc_value . $this->imageParameters['big']['addition'], $srcImageData['extension']);

                    $newImage   = $this->saveImage($squareImage, $imageName, $this->mainImageFolder, $srcImageData['const_ext']);

                    if($newImage !== false){

                        $tableRow = $this->getCurrentTableRow($imagesCollection, 'src', $imageName);

                        if($tableRow !== null){

                            $data['images']['update'] = $this->getArrayForUpdate(['src' => $imageName], $tableRow, $data['images']['update']);

                        }else{

                            $result = $this->getArrayForInsert(['src' => $imageName], $data['images']['new'], 'src');

                            if($result !== false) {
                                $data['images']['new'][] = $result;
                                $data['product_has_image'][] = [ 'product_' . $compareColumn  => $sc_value, 'src' => $result['src']];
                            }

                        }

                    }

                }
                /*****************End Image***************************/


                /*****************Create Thumb************************/
                if($key === 0){

                    $thumb = $this->createNewImage($image, $srcImageData, 'thumb');

                    if($thumb){

                        $thumbName = $this->getNewImageName($this->thumbImageFolder, $sc_value . $this->imageParameters['thumb']['addition'], $srcImageData['extension']);
                        $newThumb = $this->saveImage($thumb, $thumbName, $this->thumbImageFolder, $srcImageData['const_ext']);

                        if($newThumb !== false){

                            $data['products'][$sc_value]['thumbnail'] = $thumbName;

                        }

                    }

                }
                /*****************End Thumb************************/
            }

        }

        return $data;
    }

    private function storeImagesAndGetRelatedParameters($imagesParameters){
        $data = $this->getImagesData($imagesParameters);

        $this->updateCurrentTable(array_shift($data), 'images');

        return $data;
    }

    private function getPricesData($parameters){

        $compareColumn = $this->tables[ $this->pivotTable ]['compare'];

        $data = [
            'new'   => []
        ];

        $oldPrice = [
            'prices_id'    => [ $parameters[ key($parameters) ]['price_id'] ],
            'products_id'  => [],
        ];

        $productsCollection = $this->product->getAllProducts();

        foreach($parameters as $sc_value => $currentParameters) {

            if( isset( $currentParameters['value'] ) && isset( $currentParameters['currency_id'] ) ){

                $currentParameters['value'] = round($currentParameters['value'], 2 );

                if( (int)$currentParameters['currency_id'] === 0 ){

                    $currencies = new Currency();

                    $currency = $currencies->getCurrencyIdByCode( $currentParameters['currency_id'] );
                    //todo проверка, что вернет
                    $currentParameters['currency_id'] = $currency[0]->id;

                }

                $product = $this->getCurrentTableRow($productsCollection, $compareColumn, $sc_value);

                if($product !== null){

                    $oldPrice['products_id'][] = $product->id;

                    $currentParameters['product_id'] = $product->id;

                    $currentParameters = $this->addTimeStamp($currentParameters);

                    $data['new'][] = $currentParameters;

                }

            }

        }

        return ['new_price' => $data, 'old_price' => $oldPrice];
    }

    private function storePrices($priceParameters){
        $data = $this->getPricesData($priceParameters);
        $this->deActiveOldPrice($data['old_price']);
        $this->updateCurrentTable($data['new_price'], 'product_has_price');
    }

    private function getProductsImagesData($parameters){

        $compareColumn = $this->tables[ $this->pivotTable ]['compare'];

        $data = [
            'new' => []
        ];

        $productsCollection = $this->product->getAllProducts();
        $imagesCollection   = $this->image->getAllImages();

        foreach ($parameters as $currentParameters) {

            $product = $this->getCurrentTableRow($productsCollection, $compareColumn, $currentParameters[ 'product_' . $compareColumn ] );

            if($product !== null){

                $image = $this->getCurrentTableRow($imagesCollection, 'src', $currentParameters[ 'src' ] );

                if($image !==  null){

                    $result = $this->getArrayForInsert([], $data['new']);

                    $result['product_id']    = $product->id;

                    $result['image_id']      = $image->id;

                    if($result !== false){
                        $data['new'][] = $result;
                    }

                }

            }

        }

        return $data;

    }

    private function storeProductsImages($parameters){

        $data = $this->getProductsImagesData($parameters);

        $this->updateCurrentTable($data, 'product_has_image');

    }

    /******** Helpers *********/

    private function getCurrentTableRow($collection, $columnName, $columnValue){
        return $collection->first(function($value, $key) use ($columnName, $columnValue){
            return $value->$columnName == $columnValue;
        });
        //todo не точное сравнение!!!
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

    private function getArrayForInsert($currentParameters, $data, $sc_value = null){

        if($sc_value !== null){
            foreach($data as $tableRow){

                if( $tableRow[$sc_value] === $currentParameters[$sc_value] ) {
                    return false;
                }

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

    //todo переименовать функцию
    private function getImageData($src){

        list($imageData['width'], $imageData['height'], $imageData['const_ext']) =  getimagesize($src);

        $imageData['extension'] = $this->getExtensionImage($imageData['const_ext']);

        return $imageData;

    }

    private function getExtensionImage($constanta){

        $mime = explode( '/', image_type_to_mime_type( $constanta ) ) ;

        return array_pop($mime);

    }

    private function loadImage($src, $constanta){
        switch($constanta){
            case 1  :	return imagecreatefromgif($src);
            case 2  :   return imagecreatefromjpeg($src);
            case 3  :   return imagecreatefrompng($src);
            case 18 :   return imagecreatefromwebp($src);
            default : return false;
        }
    }

    private function createNewImage($srcImage, $srcImageData, $roleImage){

        $dstImage = imagecreatetruecolor($this->imageParameters[$roleImage]['width'], $this->imageParameters[$roleImage]['height']);

        $colorIndex = imagecolorallocate($dstImage, 255, 255,255);

        imagefill($dstImage, 0, 0, $colorIndex);

        $margin = array_fill_keys(['x', 'y'], 0);

        $dstImageData = array_combine(['width', 'height'], [$this->imageParameters[$roleImage]['width'], $this->imageParameters[$roleImage]['height']]);

        $ratio = $srcImageData['width'] / $srcImageData['height'];

        if($ratio !== 1){
            list($margin, $dstImageData) = $this->getParametersForSquareImage($ratio, $margin, $dstImageData, $roleImage);
        }

        $result = imagecopyresampled(
            $dstImage,
            $srcImage,
            ''.$margin['x'],
            ''.$margin['y'],
            0,
            0,
            ''.$dstImageData['width'],
            ''.$dstImageData['height'],
            ''.$srcImageData['width'],
            ''.$srcImageData['height']);

        if($result)
            return $dstImage;
        return false;

    }

    private function getParametersForSquareImage($ratio, $margin, $dstImageData, $roleImage){

        if($ratio > 1){
            $dstImageData['height'] /= $ratio;
            $margin['y'] = ($this->imageParameters[$roleImage]['height'] - $dstImageData['height']) / 2;
        }else{
            $dstImageData['width'] /= $ratio;
            $margin['x'] = ($this->imageParameters[$roleImage]['width'] - $dstImageData['width']) / 2;
        }

        return [$margin, $dstImageData];
    }

    private function getNewImageName($path, $partName, $extension){

        $partName = $this->translit($partName);
        $partName = strtolower($partName);
        $partName = preg_replace('~[^-a-z0-9_]+~u', '-', $partName);
        $partName = trim($partName, "-");

        $fullName =  $partName . '.' . $extension;

        if( file_exists(public_path( $path ) . $fullName )){
            $newPartName = $this->changeSimilarName($partName);
            $fullName = $this->getNewImageName($path, $newPartName, $extension);
        }

        return $fullName;
    }

    private function saveImage($image, $imageName, $path, $constanta){
        switch($constanta){
            case 1  :	return imagegif($image, public_path( $path . $imageName ));
            case 2  :   return imagejpeg($image, public_path( $path . $imageName ));
            case 3  :   return imagepng($image, public_path( $path . $imageName ));
            case 18 :   return imagewebp($image, public_path( $path . $imageName ));
            default : return false;
        }
    }

    private function changeSimilarName($name){

        $isHasNumber = preg_match('/(__)([0-9]*)$/', $name,$matches);

        if($isHasNumber){
            $num = intval($matches[2]) + 1;

            return str_replace($matches[0], '__' . (string) $num , $name);

        }else{
            return $name . '__1';
        }

    }

    private function translit($string){
        $converter = array(
            'а' => 'a',     'б' => 'b',     'в' => 'v',
            'г' => 'g',     'д' => 'd',     'е' => 'e',
            'ё' => 'e',     'ж' => 'zh',    'з' => 'z',
            'и' => 'i',     'й' => 'y',     'к' => 'k',
            'л' => 'l',     'м' => 'm',     'н' => 'n',
            'о' => 'o',     'п' => 'p',     'р' => 'r',
            'с' => 's',     'т' => 't',     'у' => 'u',
            'ф' => 'f',     'х' => 'h',     'ц' => 'c',
            'ч' => 'ch',    'ш' => 'sh',    'щ' => 'sch',
            'ь' => '',      'ы' => 'y',     'ъ' => '',
            'э' => 'e',     'ю' => 'yu',    'я' => 'ya',

            'А' => 'A',     'Б' => 'B',     'В' => 'V',
            'Г' => 'G',     'Д' => 'D',     'Е' => 'E',
            'Ё' => 'E',     'Ж' => 'Zh',    'З' => 'Z',
            'И' => 'I',     'Й' => 'Y',     'К' => 'K',
            'Л' => 'L',     'М' => 'M',     'Н' => 'N',
            'О' => 'O',     'П' => 'P',     'Р' => 'R',
            'С' => 'S',     'Т' => 'T',     'У' => 'U',
            'Ф' => 'F',     'Х' => 'H',     'Ц' => 'C',
            'Ч' => 'Ch',    'Ш' => 'Sh',    'Щ' => 'Sch',
            'Ь' => '',      'Ы' => 'Y',     'Ъ' => '',
            'Э' => 'E',     'Ю' => 'Yu',    'Я' => 'Ya',
        );
        return strtr($string, $converter);
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
