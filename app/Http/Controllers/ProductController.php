<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = Product::all();
        return view('products.index', ['products' => $products]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created product in the database.
     */
    public function store(Request $request)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'sku'   => 'required|string|unique:products|max:255', // SKU must be unique in the 'products' table
            'price' => 'required|integer|min:0',
        ]);

        // Create the new product
        Product::create($validated);

        // Redirect to the product list with a success message
        return redirect()->route('products.index')->with('success', '新しい商品が登録されました。');
    }
}