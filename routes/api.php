<?php

use App\Http\Controllers\V1\Admin\AuthController;
use App\Http\Controllers\V1\Admin\DashboardController;
use App\Http\Controllers\V1\Admin\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('v1/admin/register',[AuthController::class,'register']);
Route::post('v1/admin/login',[AuthController::class,'login']);
Route::post('v1/admin/logout',[AuthController::class,'logout'])->middleware('auth:sanctum');

Route::get('v1/admin/dashboard',[DashboardController::class,'index'])->middleware('auth:sanctum');
Route::get('v1/admin/test-low-stock',[DashboardController::class,'testLowStock'])->middleware('auth:sanctum');

Route::apiResource('v1/admin/products','App\Http\Controllers\V1\Admin\ProductController')->middleware(['auth:sanctum','role:product_manager|super_admin']);