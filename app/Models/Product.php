<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function brand()
    {
        return $this->belongsTo(Brand::class);
        // return $this->belongsTo(Brand::class)->withDefault([
        //     'name' => 'N/A'
        // ]);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
        // return $this->belongsTo(Category::class)->withDefault([
        //     'name' => 'N/A'
        // ]);
        
    }

}
