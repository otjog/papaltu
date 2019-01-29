<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Shop\Product\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Services\Delivery;
use App\Models\Geo\GeoData;
use App\Models\Settings;

class AjaxController extends Controller{

    protected $settings;

    protected $data = [];

    public function __construct(){

        $this->settings = Settings::getInstance();

        //$this->data = $this->settings->getParameters();

    }

    public function index(Request $request){

        //Component-Header
        $component_template = $request->header('X-Component');

        if( isset( $component_template ) && $component_template !== null){

            list($section, $component)  = explode('|', $component_template );

            $this->data['inc_template']['com'] = [
                'section' => $section,
                'component' => $component,
            ];
        }

        //Module-Header
        $module_template =  $request->header('X-Module');

        if( isset( $module_template ) && $module_template !== null ){

            //todo вернуть $next если нет заголовка X-Module
            list($module, $viewReload)     = explode('|', $module_template );

            $this->data['inc_template']['mod'] = [
                'module' => $module,
                'viewReload' => $viewReload,
            ];

            switch($module){

                case 'delivery' :

                    $ds = new Delivery();

                    $this->data['global_data']['project_data'] = $this->settings->getParameters();

                    switch($viewReload){
                        case 'offers'       :
                            $this->data[$module] = $ds->getPrices($request->all());
                            break;
                        case 'offers-points':
                            $prices = $ds->getPrices($request->all());
                            $points = $ds->getPoints();
                            $this->data[$module] = array_merge($prices, $points);
                            break;
                        case 'map'          :
                            return response($data[$module] = $ds->getPoints());
                    }

                    return response()->view($this->data['global_data']['project_data']['template_name'] . '.modules.' . $module . '.reload.' . $viewReload, $this->data);

                case 'product_filter' :

                    $this->data['global_data']['project_data'] = $this->settings->getParameters();

                    $products = new Product();

                    $result = $products->getFilteredProducts([], $request->toArray());

                    $path = stristr($request->session()->previousUrl(), '?', true);

                    if($path === false){
                        $path = $request->session()->previousUrl();
                    }

                    $result->setPath($path);

                    $this->data['filtered_products'] = $result;

                    $this->data['data'] = ['parameters' => $request->toArray()];

                    return response()
                        ->view( $this->data['global_data']['project_data']['template_name'] . '.modules.' . $module . '.reload.' . $viewReload, $this->data)
                        ->header('Cache-Control', 'no-store');

                case 'geo'  :

                    $geoData = GeoData::getInstance();

                    $geoData->setGeoInput($request->address_json);

                    $this->data['global_data']['project_data'] = $this->settings->getParameters();

                    return response()->view($this->data['global_data']['project_data']['template_name'] . '.modules.' . $module . '.reload.' . $viewReload, $this->data);

            }

        }

    }

}
