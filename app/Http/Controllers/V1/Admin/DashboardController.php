<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'total_users' => User::count(),
            'total_categories' => Category::count(),
            'low_stock_products' => Product::where('stock', '<', 10)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function testLowStock(){
        $product = Product::first();
        $product->stock = 5;
        $product->save();

        // get super_admin and product_manager 
        $notifiables = Role::whereIn('name', ['super_admin', 'product_manager'])->get();
        $product->notify($notifiables, new LowStockNotification($product));

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent'
        ]);
    }
}
