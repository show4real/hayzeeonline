<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class OrderProduct extends Model
{
    use HasFactory;
    protected $table = "orders_products";
    protected $appends = ["product_name"];
    
      public function getProductNameAttribute()
    {
        $product = Product::where('id', $this->product_id)->first();
        if($product){
            return $product->name;
        }
       return null;
    }
}