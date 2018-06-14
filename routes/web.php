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

Route::get('/web/*/{url}', 'WebController@calendar')
    ->where('url', '.+?')
    ->name('calendar');

Route::get('/web/{timestamp}/{url}', 'WebController@view')
    ->where('url', '.+?')
    ->where('timestamp', '^\\d{14}$')
    ->name('view');

Route::get('/get/{timestamp}/{url}', 'WebController@get')
    ->where('url', '.+?')
    ->where('timestamp', '^\\d{14}$')
    ->name('get');

Route::get('/search/{query}', 'WebController@searchText')->name('searchText');

Route::get('/diff/{timestamp_from}..{timestamp_to}/{type}/{url}', 'WebController@diff')
    ->where('url', '.+?')
    ->where('timestamp_from', '^\\d{14}$')
    ->where('timestamp_to', '^\\d{14}$')
    ->where('type', 'html|html\\-formatted|html\\-rendered|text')
    ->name('diff');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

