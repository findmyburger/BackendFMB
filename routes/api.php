<?php

use App\Http\Controllers\DishController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\UserController;
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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::prefix('users')->group(function(){
    Route::post('/updateData',[UserController::class,'updateData'])->middleware(['auth:sanctum']);
    Route::post('/register',[UserController::class,'register']);
    Route::post('/addRestaurantToFavourite',[UserController::class,'addRestaurantToFavourite'])->middleware(['auth:sanctum']);
    Route::post('/deleteRestaurantInFavourite',[UserController::class,'deleteRestaurantInFavourite'])->middleware(['auth:sanctum']);
    Route::get('/favouriteList',[UserController::class,'favouriteList'])->middleware(['auth:sanctum']);
    Route::post('/login',[UserController::class,'login']);
    Route::post('/sendEmail',[UserController::class,'sendEmail']);
    Route::post('/recoverPass',[UserController::class,'recoverPass']);
    Route::delete('/signOut',[UserController::class,'signOut'])->middleware(['auth:sanctum']);
    Route::get('/getData',[UserController::class,'getData'])->middleware(['auth:sanctum']);
});
Route::prefix('restaurants')->group(function(){
    Route::post('/filterRestaurants',[RestaurantController::class,'filterRestaurants'])->middleware(['auth:sanctum']);
    Route::get('/getAllRestaurants',[RestaurantController::class,'getAllRestaurants'])->middleware(['auth:sanctum']);
    Route::get('/getRecommended',[RestaurantController::class,'getRecommended'])->middleware(['auth:sanctum']);
    Route::get('/getRecentlyAdded',[RestaurantController::class,'getRecentlyAdded'])->middleware(['auth:sanctum']);
    Route::get('/show/{id}',[RestaurantController::class,'show'])->middleware(['auth:sanctum']);
    Route::put('/register',[RestaurantController::class,'register']);
});
Route::prefix('dishes')->group(function(){
    Route::post('/restaurantFilter',[DishController::class,'restaurantFilter'])->middleware(['auth:sanctum']);
    Route::get('/show/{id}',[DishController::class,'show'])->middleware(['auth:sanctum']);
    Route::put('/regist',[DishController::class,'regist']);
});
