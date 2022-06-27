<?php

use App\Http\Controllers\MainController;
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


Route::get('/build', [MainController::class, "buildData"]);

Route::get('/levels', [MainController::class, "associateTypes"]);

Route::get('/csv', [MainController::class, "writeCsv"]);

Route::get('/json', [MainController::class, "writeJson"]);

Route::get('/', [MainController::class, "main"]);



