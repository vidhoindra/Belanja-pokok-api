<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'image', 'status'
    ];

    public function products()
    {
        return $this->belongsToMany('App\Models\Product', 'product_category', 'category_id', 'product_id');
    }
}
