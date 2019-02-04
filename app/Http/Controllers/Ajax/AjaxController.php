<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Shop\Product\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shop\Services\Delivery;
use App\Models\Geo\GeoData;
use App\Models\Settings;

class AjaxController extends Controller{

    //Инициализируем массив для хранения заголовков данных
    protected $data = [];

    //Инициализируем массив для хранения заголовков ответа
    protected $headers = [];

    //Инициализируем переменную Ответ Сервера
    protected $response;

    //Тип вответа. Возвращаем либо представление, либо данные.
    protected $responseType = 'view'; // view or json

    //Заголовок с данными ajax-запроса. Из него мы получаем имя Модуля и имя View для отправки ответа
    protected $ajaxHeader;

    //Имя модуля в который будет посылаться ajax-запрос
    protected $module;

    //Имя представления в которое будет посылаться ajax-запрос
    protected $viewReload;

    public function index(Request $request){

        /* Зачем это здесь???
        //Component-Header
        $component_template = $request->header('X-Component');

        if( isset( $component_template ) && $component_template !== null){

            list($section, $component)  = explode('|', $component_template );

            $this->data['inc_template']['com'] = [
                'section' => $section,
                'component' => $component,
            ];
        }
        */

        //Module-Header
        $this->ajaxHeader =  $request->header('X-Module');

        if($this->ajaxHeader !== null){

            //todo вернуть $next если нет заголовка X-Module
            list($this->module, $this->viewReload)     = explode('|', $this->ajaxHeader );

            switch($this->module){

                case 'delivery' :

                    $ds = new Delivery();

                    switch($this->viewReload){
                        case 'offers'       :
                            $this->data[$this->module] = $ds->getPrices($request->all());
                            break;
                        case 'map'          :
                            $this->responseType = 'json';
                            $this->data = $ds->getPoints();
                            break;
                    }

                    break;

                case 'product_filter' :

                    $products = new Product();

                    $result = $products->getFilteredProducts([], $request->toArray());

                    $url = stristr($request->session()->previousUrl(), '?', true);

                    if($url === false){
                        $url = $request->session()->previousUrl();
                    }

                    //Настройка URI для вывода ссылок. Для работы постраничного вывода отфильтрованных товаров
                    $result->setPath($url);

                    $this->data['filtered_products'] = $result;

                    $this->data['data'] = ['parameters' => $request->toArray()];

                    //Получаем обновленные данные из Глобального массива для передачи во фронт
                    $settings = Settings::getInstance();
                    $this->data['global_data']['project_data'] = $settings->getParameters();

                    //Добавляем заголовки в массив
                    $this->headers['Cache-Control'] = 'no-store';

                    break;

                case 'geo'  :

                    //Записываем введенную пользователем Геолокацию в Сессию
                    $geoDataObj = GeoData::getInstance();
                    $geoDataObj->setGeoInput($request->address_json);

                    //Получаем обновленную геолокацию. ГЛУПО???
                    $geoData = $geoDataObj->getGeoData();

                    //Записываем обновленные данные в Глобальный массив
                    $settings = Settings::getInstance();
                    $settings->addParameter('geo', $geoData);

                    break;

            }

            return $this->sendResponse();

        }

    }

    private function sendResponse(){

        //Получаем обновленные данные из Глобального массива для передачи во фронт
        $settings = Settings::getInstance();
        $this->data['global_data']['project_data'] = $settings->getParameters();

        //Присваиваем переменной экземпляр Ответа Сервера
        $this->response = response();

        if($this->responseType === 'json') {
            $this->response = $this->response->json($this->data);
        }

        if($this->responseType === 'view'){
            $this->data['inc_template']['mod'] = [
                'module' => $this->module,
                'viewReload' => $this->viewReload,
            ];

            $view = $this->data['global_data']['project_data']['template_name'] . '.modules.' . $this->module . '.reload.' . $this->viewReload;
            //Добавляем к ответу Представление и обновленную переменную с данными
            $this->response = $this->response->view( $view, $this->data);
        }

        if( count($this->headers) > 0){
            $this->response = $this->response->withHeaders($this->headers);
        }

        return $this->response;
    }

}
