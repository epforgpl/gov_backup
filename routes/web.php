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
Route::get('/web//{url}', 'WebController@view')->where('url', '.*?')->name('view');
Route::get('/get//{url}', 'WebController@get')->where('url', '.*?')->name('get');
Route::get('/web/{hash?}/{url}', 'WebController@view');
Route::get('/thumb//{id}', 'WebController@thumb');

Route::get('/search/text/{query}', 'WebController@searchText')->name('searchText');
Route::get('/search/url/{query}', 'WebController@searchUrl')->name('searchUrl');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

