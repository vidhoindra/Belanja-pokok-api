<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover',
        'price',
        'weight',
        'stock',
        'status'
    ];

    public function categories()
    {
        return $this->belongsToMany('App\Models\Category', 'product_category');
    }
}
