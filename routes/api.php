<?php

use App\Http\Controllers\V1\Admin\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('v1/admin/register',[AuthController::class,'register']);
Route::post('v1/admin/login',[AuthController::class,'login']);
Route::post('v1/admin/logout',[AuthController::class,'logout'])->middleware('auth:sanctum');
