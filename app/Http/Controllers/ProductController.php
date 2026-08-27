<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function products()
    {
        $products = Product::with('category', 'brand')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.products', compact('products'));
    }
}
