<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Youtube;

class ShopController extends Controller
{

    public function trending(Request $request)
    {

        $products = Product::get();

        return response()->json(compact('products'));
    }

    public function products(Request $request)
    {

        $products = Product::search($request->search,$request->price[0], $request->price[1])
            ->searchAll($request->search_all)
            ->brand($request->brand)
            ->category($request->category)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->sort($request->sort)
            //->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            ->orderByRaw("availability = 1 DESC")
            //->orderBy('updated_at', 'desc')
            ->inRandomOrder()
            ->paginate($request->rows, ['*'], 'page', $request->page);

        $youtube = Youtube::first()->youtubeid;

        return response()->json(compact('products', 'youtube'));
    }

    public function categoryProducts(Request $request)
    {


        $products = Product::search($request->search)
            ->searchAll($request->search_all)
            ->brand($request->brand)
            ->category($request->category)
            ->catProduct($request->categoryslug)
            ->brandProduct($request->brandslug)
            ->sort($request->sorting)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            ->orderByRaw("availability = 1 DESC")
           
            ->inRandomOrder()
            ->paginate($request->rows, ['*'], 'page', $request->page);

        $youtube = Youtube::first()->youtubeid;

        return response()->json(compact('products', 'youtube'));
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)->first();
        return response()->json(compact('product'));
    }

    public function brands()
    {
        $brands = Brand::get();
        return response()->json(compact('brands'));
    }
    
     public function otherSales(Request $request)
    {
        $products = Product::where('other_sales', $request->sale_type)->take(12)->get();

        return response()->json(compact('products'));
    }
}
