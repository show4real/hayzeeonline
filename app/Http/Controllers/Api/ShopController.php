<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Youtube;
use App\Models\Notice;

class ShopController extends Controller
{

    public function trending(Request $request)
    {

        $products = Product::get();

        return response()->json(compact('products'));
    }

    public function searchProducts(Request $request){

        $searchTerm = $request->input('q');
        $searchResults = Product::search($request->search_all)->orderBy('availability', 'DESC')->take(1000)->get();
        $ids = $searchResults->pluck('id');

         $products = Product::whereIn('id', $ids)
            ->brand($request->brand)
            ->category($request->category)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->sort($request->sort)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            ->paginate(1000);


         $youtube = Youtube::first()->youtubeid;
         $notice = Notice::first()->notice;

        return response()->json(compact('products', 'youtube', 'notice'));

    }

    public function quickProductSearch(Request $request){

       $available = Product::search($request->search_all)
            ->where('availability', 1)
            ->take(10)
            ->get();

        $sort = Product::search($request->search_all)
            ->orderBy('availability', 'desc')
            ->take(10)
            ->get();

        // Combine the results with $available taking priority
        $products = $available->merge($sort)
            ->unique('id') // Remove duplicates
            ->values();    // Reindex the collection

        // Optionally limit the results to 10
        $products = $products->take(10);

        return response()->json(compact('products'));

    }

    public function searchAllProducts(Request $request){

       $available = Product::search($request->search_all)
            ->where('availability', 1)
            ->take(50)
            ->get();

        $sort = Product::search($request->search_all)
            ->orderBy('availability', 'desc')
            ->take(50)
            ->get();

        // Combine the results with $available taking priority
        $products = $available->merge($sort)
            ->unique('id') // Remove duplicates
            ->values();    // Reindex the collection

        // Optionally limit the results to 10
        $products = $products->take(50);

        return response()->json(compact('products'));

    }

    public function laptopProducts(Request $request){

        $products = Product::where('category_id', [26,27,28,29,30,38])
            ->searchAll($request->search_all)
            ->brand($request->brand)
            //->category($request->category)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->sort($request->sorting)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            //->orderByRaw("availability = 1 DESC")
            //->inRandomOrder()
            ->paginate($request->rows, ['*'], 'page', $request->page);

             $notice = Notice::first()->notice;


          return response()->json(compact('products','notice'));
    }

    public function products(Request $request)
    {

        // Accept the sort value under either key ('sort' here, 'sorting' on the
        // other listing endpoints) so the front-end can use either.
        $sort = $request->sort ?: $request->sorting;

        $query = Product::searchAll($request->search_all)
            ->brand($request->brand)
            ->category($request->category)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all);

        // Apply ordering explicitly so the chosen sort is always the primary
        // key. NULL/0 prices are pushed to the bottom so "low to high" doesn't
        // open with unpriced items.
        switch ($sort) {
            case 'low-price':
                $query->orderByRaw('(price IS NULL OR price = 0) ASC')->orderBy('price', 'asc');
                break;
            case 'high-price':
                $query->orderByRaw('(price IS NULL OR price = 0) ASC')->orderBy('price', 'desc');
                break;
            case 'name-asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name-desc':
                $query->orderBy('name', 'desc');
                break;
            case 'date-asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'date-desc':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                // No sort chosen: available items first, then most recent.
                $query->orderByRaw('FIELD(availability,1) DESC')->orderBy('updated_at', 'desc');
                break;
        }

        $products = $query->paginate($request->rows, ['*'], 'page', $request->page);


        $youtube = Youtube::first()->youtubeid;
         $notice = Notice::first()->notice;


        return response()->json(compact('products', 'youtube', 'notice'));
    }

    public function categoryProducts(Request $request)
    {


        $products = Product::
            searchAll($request->search_all)
            //->brand($request->brand)
            //->category($request->category)
            ->catProduct($request->categoryslug)
            ->brandProduct($request->brandslug)
            ->sort($request->sorting)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            //->orderByRaw("availability = 1 DESC")
           
            //->inRandomOrder()
            ->paginate($request->rows, ['*'], 'page', $request->page);

        $youtube = Youtube::first()->youtubeid;
         $notice = Notice::first()->notice;


        return response()->json(compact('products', 'youtube', 'notice'));
    }

    public function shopProducts(Request $request)
    {


        $products = Product::
            searchAll($request->search_all)
            ->brand($request->brand)
            ->category($request->category)
            ->catProduct($request->categoryslug)
            ->brandProduct($request->brandslug)
            ->sort($request->sorting)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            ->paginate($request->rows, ['*'], 'page', $request->page);

        return response()->json(compact('products'));
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        return response()->json(compact('product'));
    }

    public function brands()
    {
        $brands = Brand::get();
        return response()->json(compact('brands'));
        //return json_encode($brands);
    }
    
     public function otherSales(Request $request)
    {
        $products = Product::where('availability', 1)->where('other_sales', $request->sale_type)->take(16)->get();

        return response()->json(compact('products'));
    }

    
}
