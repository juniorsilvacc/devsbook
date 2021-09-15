<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get("/ping", function () {
    return ['pong' => true];
});


Route::get('/401', 'AuthController@unauthorized')->name('login');

Route::post('/auth/login', 'AuthController@login');
Route::post('/auth/logout', 'AuthController@logout');
Route::post('/auth/refresh', 'AuthController@refresh');

Route::post('/user', 'AuthController@create');

Route::put('/user', 'UserController@update');
Route::post('/user/avatar', 'UserController@updateAvatar');
Route::post('/user/cover', 'UserController@updateCover');
/*
Route::get('/feed', 'FeedController@refresh');
Route::get('/user/feed', 'FeedController@refresh');
Route::get('/user/{id}/feed', 'FeedController@refresh');

Route::get('/user', 'FeedController@read');
Route::get('/user/{id}', 'FeedController@read');

Route::post('/feed', 'FeedController@create');

Route::post('/post/{id}', 'PostController@like');
Route::post('/post/{id}/{comment}', 'PostController@comment');

Route::get('/search', 'SearchController@search');
*/
