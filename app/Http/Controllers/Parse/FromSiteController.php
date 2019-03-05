<?php

namespace App\Http\Controllers\Parse;

use App\Models\Shop\Category\Category;
use App\Http\Controllers\Controller;
use App\Models\Site\Image;
use Illuminate\Http\Request;
use phpQuery;
use App\Models\Shop\Product\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class FromSiteController extends Controller{

    private $host;

    private $startPathName;

    private $queryUrl;

    private $categoryLinkKey;

    private $productLinkKey;

    private $pagination;

    private $customGroupIterator;

    private $mainImageFolder;

    private $thumbImageFolder;

    private $imageParameters;

    private $tables;

    private $pivotTable;

    private $compareColumn;

    private $product;

    private $category;

    private $image;

    public function __construct(Request $request, Product $product, Category $category, Image $image){
        $this->host             = '';   //'http://aprilgroup.ru'

        $this->startPathName    = '';

        $this->queryUrl         = '';

        $this->categoryLinkKey  = '';   //'div#collapse11  a[href=#collapse28]';

        $this->productLinkKey   = '';   //'#shop-products > div.items > div.product > div.product-box a.product-title';

        $this->pagination       = '';   //?PAGEN_1=

        $this->imageParameters = [
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

        $this->tables = [

            'categories'        => [
                'default'   =>  [ 'active'  => '1' ],
                'columns'   =>  [ 'name'   => '/*jquery_lang*/' ],
            ],

            'images'            => [
                'default'   =>  [],
                'columns'   =>  [ 'src' => '/*jquery_lang*/'],
            ],
            'products'          => [
                'default'   =>  [ 'active' => '1', 'manufacturer_id' => '1' ],
                'columns'   =>  [
                    'scu'           => '/*jquery_lang*/',
                    'name'          => '/*jquery_lang*/',
                    'description'   => '/*jquery_lang*/',
                    'weight'        => '/*jquery_lang*/',
                    'length'        => '/*jquery_lang*/',
                    'width'         => '/*jquery_lang*/',
                    'height'        => '/*jquery_lang*/',
                ],
            ],

            'product_has_price.retail' => [
                'default'   =>  [ 'active' => '1', 'price_id' => '1', 'currency_id' => '1' ],
                'columns'   =>  [ 'value'  => '/*jquery_lang*/' ],
            ],

            'product_has_image'            => [
                'default'   =>  [],
                'columns'   =>  [],
            ],

        ];

        $this->pivotTable       = 'products';

        $this->compareColumn    = 'name';

        $this->product  = $product;

        $this->category = $category;

        $this->image    = $image;

        $this->mainImageFolder = 'storage/img/shop/product/';

        $this->thumbImageFolder = 'storage/img/shop/product/thumbnail/';

        $this->customGroupIterator = [
            // '/rasshiritelnye_baki_gidroakkumuljatory_mnogofunkcionalnye_baki_i_membrany/zapasnye_chasti_i_ehlementy_kreplenija_bakov'
        ];

    }

    public function parse(){

        $newDataInTables = $this->read();

        $this->store($newDataInTables);
    }

    private function read(){

        $newDataInTables = [];

        $groupIteraror = $this->getGroupIterator();

        foreach($groupIteraror as $anchor){

            $itemIterator = $this->getItemIterator( $anchor );

            foreach ($itemIterator as $item) {

                $parsedParameters = $this->getCurrentParameters( $item );

                if(isset ($parsedParameters[ $this->pivotTable ][ $this->compareColumn ] ) ){
                    $sc_value = $parsedParameters[ $this->pivotTable ][ $this->compareColumn ];
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
                    $relatedParameters = $this->storeCategoriesAndGetIds($parameters);
                    $newDataInTables = array_merge_recursive($newDataInTables, $relatedParameters);
                    break;

                case 'images' :
                    $relatedParameters = $this->storeImagesAndGetRelatedParameters($parameters);
                    $newDataInTables = array_merge_recursive($newDataInTables, $relatedParameters);
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

        if( count($this->customGroupIterator) > 0 ){

            return $this->customGroupIterator;

        }else{

            $url = $this->host . $this->startPathName;

            return $this->getIteratorElements($url, $this->categoryLinkKey);
        }

    }

    private function getItemIterator($anchor ){

        $url = $this->host . $anchor . $this->queryUrl;

        $result = [];

        for($i = 0; $i < 99; $i++){

            $nextUrl = $this->getNextUrl($url, $i+1);

            $pq_links = $this->getIteratorElements($nextUrl, $this->productLinkKey);

            if(count( $pq_links ) === 0){

                break;

            }

            $different = array_diff($pq_links, $result);

            if( count( $different ) === 0){

                break;

            }

            $result = array_merge($result, $pq_links);

        }

        return $result;

    }

    private function getIteratorElements($url, $linkName){

        $html = $this->getHtmlPage($url);

        $html_dom = phpQuery::newDocument($html);

        $links = $html_dom->find($linkName);

        $pq_links = array_map([$this, 'pickHref'], $links->elements);

        phpQuery::unloadDocuments($html_dom->documentID);

        return $pq_links;
    }

    private function getCurrentParameters($item){

        $html = $this->getHtmlPage($this->host . $item);

        $html_dom = phpQuery::newDocument($html);

        $currentParameters = [];

        foreach($this->tables as $tableName => $tableData) {

            $currentParameters[$tableName] = $tableData['default'];

            if(count( $tableData['columns'] ) > 0){
                foreach($tableData['columns'] as $columnName => $key){

                    $value = $this->getValue($tableName, $columnName, $html_dom, $key);

                    if( $value !== null || $value !== '' ){

                        $currentParameters[$tableName][$columnName] = $value;

                    }

                }
            }

        }

        phpQuery::unloadDocuments($html_dom->documentID);

        return $currentParameters;

    }

    private function getValue($tableName, $columnName, $html_dom, $key){
        list($clearTableName) = explode('.', $tableName);

        $searched = $html_dom->find($key);

        switch($clearTableName){

            case 'products' :

                if($columnName === 'name'){

                    return trim(str_replace('Aquatechnica ', '', trim($searched->text())));

                }elseif ($columnName === 'scu'){

                    return trim(str_replace('Артикул: ', '', trim($searched->text())));

                }elseif ($columnName === 'weight'){

                    return (float) $this->searchValueInTable($searched, 'Вес :', [' кг']);

                }elseif ($columnName === 'length'){

                    $sizeRaw = $this->searchValueInTable($searched, 'Габаритные размеры Д х Ш х В, мм:', [' ', 'Ø']);

                    if($sizeRaw === null){
                        $size =  $this->searchValueInTable($searched, 'Диаметр:', ' мм');
                        if($size !== null)
                            return (integer)$size / 10;
                        return null;
                    }else{
                        $sizeArray = explode('x', $sizeRaw);
                        return (integer)$sizeArray[0] / 10;
                    }

                }elseif ($columnName === 'width'){

                    $sizeRaw = $this->searchValueInTable($searched, 'Габаритные размеры Д х Ш х В, мм:', [' ', 'Ø']);

                    if($sizeRaw === null){
                        $size = $this->searchValueInTable($searched, 'Высота:', ' мм');
                        if($size !== null)
                            return (integer)$size / 10;
                        return null;
                    }else{
                        $sizeArray = explode('x', $sizeRaw);
                        return (integer)$sizeArray[ count($sizeArray) - 2 ] / 10;
                    }

                }elseif ($columnName === 'height'){

                    $sizeRaw = $this->searchValueInTable($searched, 'Габаритные размеры Д х Ш х В, мм:', [' ', 'Ø']);

                    if($sizeRaw === null){
                        $size = $this->searchValueInTable($searched, 'Высота:', ' мм');
                        if($size !== null)
                            return (integer)$size / 10;
                        return null;
                    }else{
                        $sizeArray = explode('x', $sizeRaw);
                        return (integer)$sizeArray[ count($sizeArray) - 1 ] / 10;
                    }
                }

                break;

                case 'product_has_price' :
                if($columnName === 'value'){
                    return str_replace([' ', 'руб.'], '', trim($searched->text()));
                }
                break;
            case 'images' :
                if($columnName === 'src'){
                    $images = [];
                    foreach($searched as $image){
                        $pq_image = pq($image);
                        $images[] = $pq_image->attr('src');
                    }
                    return $images;
                }
                break;
        }

        return trim($searched->text());

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

                $result = $this->getArrayForInsert($currentParameters, $data['new'], 'name');

                if($result !== false){
                    $data['new'][] = $result;
                }

            }
        }

        return ['categories' => $data, 'products' => $products];
    }

    private function storeCategoriesAndGetIds($categoryParameters){
        $data = $this->getCategoriesData($categoryParameters);
        //$this->deActivate....
        $categories = array_shift($data);
        $this->updateCurrentTable($categories, 'categories');

        if( count( $categories['new'] ) > 0 ){
            $data = $this->getCategoriesData($categoryParameters);
        }
        return $data;
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

                $result = $this->getArrayForInsert($currentParameters, $data['new'], $this->compareColumn);

                if($result !== false){
                    $data['new'][] = $result;
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

                $srcImageData = $this->getImageData($src);

                if($srcImageData){
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
                                    $data['product_has_image'][] = [ 'product_' . $this->compareColumn  => $sc_value, 'src' => $result['src']];
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

        }

        return $data;

    }

    private function storeImagesAndGetRelatedParameters($imagesParameters){
        $data = $this->getImagesData($imagesParameters);

        $this->updateCurrentTable(array_shift($data), 'images');

        return $data;
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

    private function getProductsImagesData($parameters){

        $data = [
            'new' => []
        ];

        $productsCollection = $this->product->getAllProducts();
        $imagesCollection   = $this->image->getAllImages();

        foreach ($parameters as $currentParameters) {

            $product = $this->getCurrentTableRow($productsCollection, $this->compareColumn, $currentParameters[ 'product_' . $this->compareColumn ] );

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

        try{

            list($imageData['width'], $imageData['height'], $imageData['const_ext']) =  getimagesize($src);

            $imageData['extension'] = $this->getExtensionImage($imageData['const_ext']);

            return $imageData;


        }catch (Exception $e){

            try{

                $filename='storage/img/shop/product/temporary.file';

                file_put_contents($filename,file_get_contents($src));

                list($imageData['width'], $imageData['height'], $imageData['const_ext']) =  getimagesize($src);

                $imageData['extension'] = $this->getExtensionImage($imageData['const_ext']);

                return $imageData;

            }catch (Exception $e){

                file_put_contents('storage/img/shop/product/exc.txt', $src, FILE_APPEND);

                return false;

            }

        }

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
            $dstImageData['width'] *= $ratio;
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

    /**************************************/
    private function getHtmlPage($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html_str = curl_exec($ch);
        curl_close($ch);

        return $html_str;
    }

    private function getNextUrl($url, $i){
        return $url . $this->pagination . $i;
    }

    private function searchValueInTable($searched, $prevSiblingValue, $replaceStr){
        foreach($searched as $key => $td){
            $pq_td = pq($td);
            if($pq_td->text() === $prevSiblingValue){
                return trim( str_replace($replaceStr, '',  trim( $pq_td->next()->text() ) ) );
            }
        }
    }

    private function pickHref($anchor){
        $anchor = pq($anchor);
        return $anchor->attr('href');
    }

}
