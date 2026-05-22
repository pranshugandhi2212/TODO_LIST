<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // GET all
    public function index()
    {
        return Product::all();
    }

    // POST create
    public function store(Request $request)
    {
        return Product::create($request->all());
    }

    // GET single
    public function show(Product $product)
    {
        return $product;
    }

    // PUT update
    public function update(Request $request, Product $product)
    {
        $product->update($request->all());
        return $product;
    }

    // DELETE
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['msg' => 'Deleted']);
    }
}
