<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Shop\Product\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Services\Delivery;
use App\Models\Geo\GeoData;

class AjaxController extends Controller{

    public function index(Request $request){

        $template_name = env('SITE_TEMPLATE');

        //Return
        $data = ['template_name' => $template_name];

        //Component-Header
        $component_template = $request->header('X-Component');

        if( isset( $component_template ) && $component_template !== null){

            list($section, $component)  = explode('|', $component_template );

            $data['template']['com'] = [
                'section' => $section,
                'component' => $component,
            ];
        }

        //Module-Header
        $module_template =  $request->header('X-Module');

        if( isset( $module_template ) && $module_template !== null ){

            //todo вернуть $next если нет заголовка X-Module
            list($module, $viewReload)     = explode('|', $module_template );

            $data['template']['mod'] = [
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
                        case 'best-offer'   : $data[ $module ] =  $ds->getBestPrice( $parcels ); break;
                        case 'offers'       : $data[ $module ] =  $ds->getPrices( $parcels ); break;
                        case 'offers-points':
                            $prices = $ds->getPrices( $parcels );
                            $points = $ds->getPoints();
                            $data[ $module ] = array_merge($prices, $points);
                            break;
                        case 'map'          : return response( $data[ $module ] = $ds->getPoints() );
                    }

                    return response()->view($template_name . '.modules.' . $module . '.reload.' . $viewReload, $data);

                case 'product_filter' :

                    $products = new Product();

                    $result = $products->getFilteredProducts($request->toArray());

                    return response()->view( $template_name . '.modules.' . $module . '.reload.' . $viewReload, ['filtered_products' => $result, 'template_name' => $template_name])->header('Cache-Control', 'no-store');

                case 'geo'  :

                    $geoData = new GeoData();

                    $geoData->setGeoInput($request->address_json);

                    break;

            }

        }

    }

}
