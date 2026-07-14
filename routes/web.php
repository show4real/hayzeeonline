<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

// Privacy policy for the public AI/product API — the URL to supply when a
// GPT, connector, or app directory listing asks for a privacy policy.
Route::get('/privacy', function () {
    return view('privacy');
});
