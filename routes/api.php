<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('v1')->group(function() {
    // auth
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::middleware('auth:api')->group(function() {
        Route::post('logout', 'AuthController@logout');
    });

    // categories
    Route::get('categories/random/{count}', 'CategoryController@random');
    Route::get('categories', 'CategoryController@index');
    Route::get('categories/slug/{slug}', 'CategoryController@slug');
    
    // products
    Route::get('products/top/{count}', 'ProductController@top');
    Route::get('products', 'ProductController@index');
    Route::get('products/slug/{slug}', 'ProductController@slug');
    Route::get('products/search/{keyword}', 'ProductController@search');

    // shop
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('shipping', 'ShopController@shipping');
        Route::post('services', 'ShopController@services');
        Route::post('payment', 'ShopController@payment');
        Route::get('my-order', 'ShopController@myOrder');
        Route::get('detail-order/{orderId}', 'ShopController@detailOrder');
    });
    Route::get('provinces', 'ShopController@provinces');
    Route::get('cities', 'ShopController@cities');
    Route::get('couriers', 'ShopController@couriers');

    // user
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('update-profile', 'UserController@update');
    });
});
