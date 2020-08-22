<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Categories as CategoriesResourceCollection;
use App\Http\Resources\Category as CategoryResourceCollection;
use App\Models\Category;

class CategoryController extends Controller
{
    public function random($count)
    {
        $criteria = Category::select('*')
            ->inRandomOrder()
            ->limit($count)
            ->get();

        return new CategoriesResourceCollection($criteria);
    }

    public function index()
    {
        $criteria = Category::paginate(4);
        return new CategoriesResourceCollection($criteria);
    }

    public function slug($slug)
    {
        $criteria = Category::where('slug', $slug)->first();
        return new CategoryResourceCollection($criteria);
    }
}
