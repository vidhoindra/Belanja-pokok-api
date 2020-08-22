<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_category';
    
    protected $fillable = [
        'product_id', 'category_id', 'status'
    ];
}
