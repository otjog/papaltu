<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use App\Models\Settings;

class Image extends Model{

    protected $settings;

    public function __construct(array $attributes = []){
        parent::__construct($attributes);

        $this->settings = Settings::getInstance();

    }

    public function products(){
        return $this->belongsToMany('App\Models\Shop\Product\Product', 'product_has_image')->withTimestamps();
    }

    public function getAllImages(){
        return self::select(
            'images.id',
            'images.name',
            'images.src'
        )
            ->get();
    }

    public function createResizeImage($requestUri)
    {
        //получаем данные о требуемом изображении, Имя Размера (xl), Путь до папки, Путь до файла
        list($sizeName, $pathToDstFolder, $pathToDstImage) = $this->getRouteData($requestUri);

        //создаем папку, если еще не существует такой
        if (!is_dir($pathToDstFolder)) {
            mkdir($pathToDstFolder, 0777, true);
        }

        //Получаем запрошенные размеры в пикселях будущего изображения в Массив:
        $dstImageData = $this->getDstImageData($sizeName);

        //Получаем путь до оригинала файла в нашей файловой системе
        $pathToSrcImage = $this->getPathToSrcImageInFileSystem($pathToDstImage, $sizeName);

        if (is_file($pathToSrcImage)) {
            //Получаем данные об оригинальном изображении (Ширина, Высота, Расширение и пр.) либо FALSE
            $srcImageData = $this->getSrcImageData($pathToSrcImage);

            if ($srcImageData) {

                //Получаем ресурс оригинального изображения исходя из его расширения
                $image = $this->loadImageResource($pathToSrcImage, $srcImageData['const_ext']);

                if ($srcImageData['width'] === (int)$dstImageData['width'] && $srcImageData['height'] === (int)$dstImageData['height']) {
                    //т.к. запрашиваемое изображение имеет тот же размер, что и оригинал, вернем его
                    $newImage = $image;
                } else {
                    //Получаем ресурс нового изображения
                    $newImage = $this->createNewImageResource($image, $srcImageData, $dstImageData);
                }

                if ($newImage !== null) {

                    //Сохраняем новое изображение
                    $this->saveImageAsFile($newImage, $srcImageData['const_ext'], $pathToDstImage);

                    return response()->file($pathToDstImage);

                }
            }
        }

        return null;

        }

    private function getSrcImageData($src){

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

    private function getPathToSrcImageInFileSystem($requestUri, $sizeName){

        $originaLFolderName = $this->settings->getParameter('components.shop.images.original_folder');

        return str_ireplace('/' . $sizeName . '/', '/' . $originaLFolderName, $requestUri);
    }

    private function getDstImageData($sizeName){

        $sizeValue = $this->settings->getParameter('components.shop.images.size.'. $sizeName);

        //Возврашаем массив: запрошенные размеры будущего изображения
        list( $imageData['width'], $imageData['height'] ) = explode('x', $sizeValue);

        return $imageData;
    }

    private function loadImageResource($src, $constanta){
        switch($constanta){
            case 1  :	return imagecreatefromgif($src);
            case 2  :   return imagecreatefromjpeg($src);
            case 3  :   return imagecreatefrompng($src);
            case 18 :   return imagecreatefromwebp($src);
            default : return false;
        }
    }

    private function createNewImageResource($srcImage, $srcImageData, $dstImageData){
        //Создаем пустое изображение
        $dstImage = imagecreatetruecolor($dstImageData['width'], $dstImageData['height']);
        //Создаем белый цвет для нашего нового изображения
        $colorIndex = imagecolorallocate($dstImage, 255, 255,255);
        //Заполняем созданным цветом наше новое изображение
        imagefill($dstImage, 0, 0, $colorIndex);
        //Задаем нулевые отспуты для вставки изображения
        $margin = array_fill_keys(['x', 'y'], 0);

        list($margin, $dstImageData) = $this->getParametersForNewImage($margin, $srcImageData, $dstImageData);

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
        return null;

    }

    private function getParametersForNewImage( $margin, $srcImageData, $dstImageData ){

        //Вычисляем коэф. разницы ширины и высоты будущего изображения
        $dstRatio = $dstImageData['width']/$dstImageData['height'];
        //Вычисляем коэф. разницы ширины и высоты искомого изображения
        $srcRatio = $srcImageData['width'] / $srcImageData['height'];
        //Получаем разницу в пропорциональности изображений, где 1 - изображения пропорциональны
        $genRatio = $srcRatio/$dstRatio;

        $newDstImageData = $dstImageData;

        if($genRatio > 1){
            $coef = $srcImageData['width']/$dstImageData['width'];
            $newDstImageData['height'] = $srcImageData['height'] / $coef;
            $margin['y'] = ($dstImageData['height'] - $newDstImageData['height'])/2;

        }else if($genRatio < 1){
            $coef = $srcImageData['height']/$dstImageData['height'];
            $newDstImageData['width'] = $srcImageData['width'] / $coef;
            $margin['x'] = ($dstImageData['width'] - $newDstImageData['width'])/2;
        }

        return [$margin, $newDstImageData];
    }

    private function getExtensionImage($constanta){

        $mime = explode( '/', image_type_to_mime_type( $constanta ) ) ;

        return array_pop($mime);

    }

    private function saveImageAsFile($image, $constanta, $path){
        switch($constanta){
            case 1  :	return imagegif($image, $path);
            case 2  :   return imagejpeg($image, $path, 100);
            case 3  :   return imagepng($image, $path, 9);
            case 18 :   return imagewebp($image, $path, 100);
            default : return false;
        }
    }

    private function getRouteData($requestUri){

        $pathElements = explode('/', $requestUri);

        $size = $pathElements[5];

        $imgName = array_pop($pathElements);

        $dirName = str_ireplace($imgName, '', $requestUri);

        return [$size, public_path($dirName), public_path($requestUri)];

    }

}
