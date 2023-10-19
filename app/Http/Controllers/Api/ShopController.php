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

        $product = (new Product)->newQuery();

    if ($request->has('category')) {
        $products->category($request->category);
    }

    if ($request->has('storages')) {
        $products->storage($request->storages);
    }

    if ($request->has('processors')) {
        $products->processor($request->processors);
    }

    if ($request->has('rams')) {
        $products->ram($request->rams);
    }

    if ($request->has('storages')) {
        $products->storage($request->storages);
    }

    if ($request->has('sort')) {
        $products->sort($request->sort);
    }

    if ($request->has('price')) {
        $products->filterByPrice($request->price[0], $request->price[1], $request->search_all);
    }

    if ($request->has('search_all')) {
        $products->searchAll($request->search_all);
    }

    // Add the pagination condition
    $rows = $request->has('rows') ? $request->rows : 10; // Assuming 10 rows by default
    $page = $request->has('page') ? $request->page : 1; // Assuming first page by default

    $paginatedResults = $products->paginate($rows, ['*'], 'page', $page);

    return $paginatedResults;
         //$products->paginate($request->rows, ['*'], 'page', $request->page);

        // $products = Product::brand($request->brand)
        //     ->category($request->category)
        //     ->storage($request->storages)
        //     ->processor($request->processors)
        //     ->ram($request->rams)
        //     ->sort($request->sort)
        //     ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
        //     ->orderByRaw("availability = 1 DESC")
        //     ->inRandomOrder()
        //     ->searchAll($request->search_all)
        //     ->paginate($request->rows, ['*'], 'page', $request->page);

        // $youtube = Youtube::first()->youtubeid;

        // return response()->json(compact('products', 'youtube'));
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
