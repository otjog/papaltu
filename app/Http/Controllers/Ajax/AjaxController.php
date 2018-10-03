<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Shop\Product\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Services\Delivery;
use App\Models\Geo\GeoData;
use App\Models\Settings;

class AjaxController extends Controller{

    protected $data;

    public function __construct(){

        $settings = Settings::getInstance();

        $this->data = $settings->getParameters();
    }

    public function index(Request $request){

        //Component-Header
        $component_template = $request->header('X-Component');

        if( isset( $component_template ) && $component_template !== null){

            list($section, $component)  = explode('|', $component_template );

            $this->data['template']['com'] = [
                'section' => $section,
                'component' => $component,
            ];
        }

        //Module-Header
        $module_template =  $request->header('X-Module');

        if( isset( $module_template ) && $module_template !== null ){

            //todo вернуть $next если нет заголовка X-Module
            list($module, $viewReload)     = explode('|', $module_template );

            $this->data['template']['mod'] = [
                'module' => $module,
                'viewReload' => $viewReload,
            ];

            switch($module){

                case 'delivery' :

                    $ds = new Delivery();

                    if( count($request->all()) > 0 ){
                        $parcels = [];

                        foreach($request->all() as $name => $params) {

                            $arr = explode('|', $params);

                            foreach ($arr as $key => $param) {

                                $parcels[$key][$name] = $param;

                            }

                        }
                    }

                    switch($viewReload){
                        case 'best-offer'   : $this->data[ $module ] =  $ds->getBestPrice( $parcels ); break;
                        case 'offers'       : $this->data[ $module ] =  $ds->getPrices( $parcels ); break;
                        case 'offers-points':
                            $prices = $ds->getPrices( $parcels );
                            $points = $ds->getPoints();
                            $this->data[ $module ] = array_merge($prices, $points);
                            break;
                        case 'map'          : return response( $this->data[ $module ] = $ds->getPoints() );
                    }

                    return response()->view($this->data['template_name'] . '.modules.' . $module . '.reload.' . $viewReload, $this->data);

                case 'product_filter' :

                    $products = new Product();

                    $result = $products->getFilteredProducts($request->toArray());

                    $path = stristr($request->session()->previousUrl(), '?', true);

                    if($path === false){
                        $path = $request->session()->previousUrl();
                    }

                    $result->setPath($path);

                    $this->data['filtered_products'] = $result;

                    $this->data['data'] = ['parameters' => $request->toArray()];

                    return response()
                        ->view( $this->data['template_name'] . '.modules.' . $module . '.reload.' . $viewReload, $this->data)
                        ->header('Cache-Control', 'no-store');

                case 'geo'  :

                    $geoData = new GeoData();

                    $geoData->setGeoInput($request->address_json);

                    break;

            }

        }

    }

}
