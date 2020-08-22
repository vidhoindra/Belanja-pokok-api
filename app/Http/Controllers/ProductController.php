<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Products as ProductsResourceCollection;
use App\Http\Resources\Product as ProductResourceCollection;
use App\Models\Product;

class ProductController extends Controller
{
    public function top($count)
    {
        $criteria = Product::select('*')
            ->orderBy('views', 'DESC')
            ->limit($count)
            ->get();

        return new ProductsResourceCollection($criteria);
    }

    public function index()
    {
        $criteria = Product::paginate(4);
        return new ProductsResourceCollection($criteria);
    }

    public function slug($slug)
    {
        $criteria = Product::where('slug', $slug)->first();
        return new ProductResourceCollection($criteria);
    }

    public function search($keyword)
    {
        $criteria = Product::select('*')->where('name', 'LIKE', "%" . $keyword . "%")
                        ->orderBy('views', 'DESC')
                        ->get();
        return new ProductsResourceCollection($criteria);
    }
}
