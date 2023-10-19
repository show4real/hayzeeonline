<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductImages;
use App\Models\ProductDescription;
use App\Models\Category;
use DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'category_id',
        'brand_id',
        'discount',
        'availability',
        'storage',
        'ram',
        'processor',
        'other_sales',
        'slug',
        'product_type'
    ];

    protected $appends = ['category', "stock", "image_hover"];

    // ALTER TABLE products ADD FULLTEXT INDEX fulltext_index_name (name, description);


    public function scopeSearchAll($query, $filter)
{
    $searchQuery = trim($filter);

    $query->when($filter != '', function ($query) use ($searchQuery) {
        return $query->select('*', DB::raw("MATCH(name) AGAINST('$searchQuery' IN BOOLEAN MODE) as relevance_score"))
            ->whereRaw("MATCH(name) AGAINST('$searchQuery' IN BOOLEAN MODE)")
            ->orderByDesc('relevance_score');
    });


}



    public function scopeSearch($query, $filter)
    {
        $searchQuery = trim($filter);
        $requestData = ['name', 'description'];
        $query->when($filter != '', function ($query) use ($requestData, $searchQuery) {
            return $query->where(function ($q) use ($requestData, $searchQuery) {
                foreach ($requestData as $field)
                    $q->orWhere($field, 'like', "%{$searchQuery}%");
            })->orderByRaw("FIELD(availability,1) DESC")->orderBy("updated_at", "DESC");
        });
    }


    public function scopeSort($query, $filter)
    {
        if ($filter == 'high-price') {
            return $query->orderBy('price', 'desc');
        } else if ($filter == 'low-price') {
            return $query->orderBy('price', 'asc');
        } else if ($filter == 'name-desc') {
            return $query->orderBy('name', 'desc');
        } else if ($filter == 'name-asc') {
            return $query->orderBy('name', 'asc');
        } else if ($filter == 'availability') {
            return $query->where('availability', 1);
        } else if ($filter == 'date-desc') {
            return $query->orderBy('created_at', 'desc');
        } else if ($filter == 'date-asc') {
            return $query->orderBy('created_at', 'asc');
        }
        return $query;
    }


    public function scopeBrand($query, $filter)
    {
        if ($filter) {
            return $query->where('brand_id', $filter);
        }
        return $query;
    }

    public function scopeCategory($query, $filter)
    {
        if ($filter) {
            return $query->where('category_id', $filter);
        }
        return $query;
    }

    public function scopeCatProduct($query, $filter)
    {
        $cat = Category::where('slug', $filter)->first();
        if ($cat) {
            return $query->where('category_id', $cat->id);
        }
        return $query;
    }

    public function scopeBrandProduct($query, $filter)
    {
        $brand = Brand::where('slug', $filter)->first();
        if ($brand) {
            return $query->where('brand_id', $brand->id);
        }
        return $query;
    }


    public function scopeStorage($query, $filter)
    {
        if ($filter) {
            return $query->whereIn('storage', $filter);
        }
        return $query;
    }

    public function scopeProcessor($query, $filter)
    {
        if ($filter) {
            return $query->whereIn('processor', $filter);
        }
        return $query;
    }

    public function scopeRam($query, $filter)
    {
        if ($filter) {
            return $query->whereIn('ram', $filter);
        }
        return $query;
    }



    // public function getImagesAttribute()
    // {
    //     $images = ProductImages::where('product_id', $this->id)->pluck('url');

    //     return $images;
    // }

    public function getCategoryAttribute()
    {
        $category = Category::where('id', $this->category_id)->first();
        if ($category) {
            return $category->name;
        }
    }

    public function getStockAttribute()
    {
        if ($this->availability == 1) {
            return "New";
        } else {
            return "Sold";
        }
    }
    public function getImageHoverAttribute()
    {
        $product_images = ProductImages::where('product_id', $this->id)->pluck('url');

        if ($product_images->isNotEmpty() && isset($product_images[1])) {
            return $product_images[1];
        }

        // Handle the case when $product_images is empty or index 1 does not exist
        return null; // Or you can set a default image URL or take other appropriate action
    }

    public function scopeFilterByPrice($query, $minPrice, $maxPrice, $searchAll)
    {
        
        if ($minPrice && $maxPrice) {
            return $query->whereBetween('price', [$minPrice, $maxPrice]);
        }
        return $query;
    }
}
