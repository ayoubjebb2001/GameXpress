<?php

namespace App\Http\Controllers\V1\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['slug'] = $request->slug ?? Str::of($request->name)->slug('-');

        $validated = $request->validate([
            'name' => ['bail', 'required', 'unique:products,name', 'string', 'max:64'],
            'slug' => ['string', 'unique:products,slug'],
            'price' => ['decimal:2', 'required'],
            'stock' => ['integer', 'gte:0', 'required'],
            'status' => ['string', 'in:disponible,rupture,bientot disponible'],
            'category_id' => ['integer', 'exists:categories,id']
        ]);

        $product = Product::create($data);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->all();

        $validated = $request->validate([
            'name' => ['string', 'max:64', 'unique:products,name,' . $product->id],
            'slug' => ['string', 'unique:products,slug,' . $product->id],
            'price' => ['decimal:2'],
            'stock' => ['integer', 'gte:0'],
            'status' => ['string', 'in:disponible,rupture,bientot disponible'],
            'category_id' => ['integer', 'exists:categories,id']
        ]);

        $product->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}
