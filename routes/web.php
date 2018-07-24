<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'MainPage\MainPageController@index');

Route::resource('index',        'MainPage\MainPageController',  [ 'only' => [ 'index' ]]);
Route::resource('categories',   'Shop\CategoryController',      [ 'only' => [ 'index', 'show' ]]);
Route::resource('products',     'Shop\ProductController',       [ 'only' => [ 'show' ]]);
Route::resource('orders',       'Shop\OrderController',         [ 'only' => [ 'store', 'create', 'show' ]]);
Route::resource('pages',        'Info\PageController',          [ 'only' => [ 'show' ]]);

Route::group(['prefix' => 'adminio', 'middleware' => ['auth']], function () {
    Route::resource('categories',   'Shop\CategoryController',      [ 'except' => [ 'show' ]]);
    Route::resource('products',     'Shop\ProductController',       [ 'except' => [ 'show' ]]);
    Route::resource('pages',        'Info\PageController',          [ 'except' => [ 'show' ]]);

});

Route::get('search', 'Search\SearchController@show')->name('search');

Route::get('/parse', 'Price\ParseController@parse');

Route::group(['prefix' => 'basket'], function () {
    Route::get('/', 'Shop\BasketController@getIndex')->name('showBaskets');
    Route::get('/show', 'Shop\BasketController@getShow')->name('showBasket');
    //Route::post('/add', 'Shop\BasketController@getAdd')->name('addToBasket');
    Route::get('/change', 'Shop\BasketController@getChange')->name('changeBasket');
    Route::delete('/destroy', 'Shop\BasketController@deleteDestory');
});

//addToBasket
Route::post('/products/{id}',   'Shop\ProductController@toBasket');
Route::post('/categories/{id}', 'Shop\CategoryController@productToBasket');
Route::post('/brands/{id}',     'Shop\BrandController@productToBasket');
Route::post('/search/',         'Search\SearchController@productToBasket');


Auth::routes();

Route::get('/adminio', 'Admin\AdminController@index')->name('admin');