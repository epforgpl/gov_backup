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

Route::get('/', 'WebController@home');
Route::get('/web//{url}', 'WebController@view')->where('url', '.*?');
Route::get('/get//{url}', 'WebController@get')->where('url', '.*?');
Route::get('/web/{hash?}/{url}', 'WebController@view');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

