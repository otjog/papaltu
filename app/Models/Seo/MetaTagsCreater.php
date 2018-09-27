<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class MetaTagsCreater extends Model{

    public $siteName;

    public function getMetaTags($data){

        $this->siteName = $siteName = env('APP_NAME');

        switch($data['template']['resource']){
            case 'category' : return $this->getTagsForShopProductList($data['data'], $data['template']['resource']);
            case 'brand'    : return $this->getTagsForShopProductList($data['data'], $data['template']['resource']);
            case 'product'  : return $this->getTagsForShopProduct($data['data']);
        }

        return null;
    }

    private function getTagsForShopProductList($data, $resource){

        $resourceName = mb_strtolower($data[$resource][0]->name);

        $title = $data[$resource][0]->name . ' для дома, коттеджа и дачи, купить ' .  $resourceName . ' в Белгороде, Москве, СПб и РФ - цены, отзывы, видео, фото и характеристики в интернет-магазине ' . $this->siteName;

        $description = 'Купить ' . $resourceName . ' для коттеджа, дома и дачи в Белгороде, Москве и РФ - цены, отзывы, видео, фото и характеристики в интернет-магазине ' . $this->siteName . '. В продаже имеются ' . $resourceName . ' всех типов по лучшей цене. Гарантия, доставка во все регионы.';

        $keywords = $resourceName .' ' . $resourceName . ' цены характеристики, ' . $resourceName . ' отзывы, ' . $resourceName . ' купить, ' . $resourceName . ' цена, ' . $resourceName . ' цены характеристики отзывы';

        return ['title' => $title, 'description' => $description, 'keywords' => $keywords];
    }

    private function getTagsForShopProduct($data){

        $productName = mb_strtolower($data['product']->name);

        $manufacturerName = mb_strtolower($data['product']['manufacturer']['name']);

        $categoryName = mb_strtolower($data['product']['category']['name']);

        $title = $data['product']->name . ' ' . $manufacturerName . ' - купить, цена, инструкция и фото в интернет-магазине ' . $this->siteName;

        $description = $data['product']->name . ' ' . $manufacturerName . ' - купить в Москве, Белгороде и России: цена, инструкция по эксплуатации и характеристики в интернет-магазине ' . $this->siteName . '. Доставка в любой регион России, гарантия - 12 мес.';

        $keywords = $data['product']->name . ' ' . $manufacturerName . ', ' . $categoryName . ', ' . $productName . ' купить, ' . $productName . ' характеристики, ' . $productName . ' отзывы, ' . $manufacturerName . ' купить, ' . $categoryName . ' купить, ';

        return ['title' => $title, 'description' => $description, 'keywords' => $keywords];
    }

}
