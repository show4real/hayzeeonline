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
            ->model($request->models)
            ->subtype($request->subtypes)
            ->condition($request->conditions)
            ->numberOfCores($request->cores)
            ->storageType($request->storage_types)
            ->displaySize($request->display_sizes)
            ->graphicsCard($request->graphics_cards)
            ->graphicsCardMemory($request->graphics_card_memories)
            ->operatingSystem($request->operating_systems)
            ->color($request->colors)
            ->exchangePossible($request->exchange)
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

        // Return the 2 best matching categories alongside the products.
        $categories = Category::searchAll($request->search_all)
            ->take(2)
            ->get();

        return response()->json(compact('products', 'categories'));

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

        $products = Product::sort($request->sorting)
            ->where('category_id', [26,27,28,29,30,38])
            ->searchAll($request->search_all)
            ->brand($request->brand)
            //->category($request->category)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->model($request->models)
            ->subtype($request->subtypes)
            ->condition($request->conditions)
            ->numberOfCores($request->cores)
            ->storageType($request->storage_types)
            ->displaySize($request->display_sizes)
            ->graphicsCard($request->graphics_cards)
            ->graphicsCardMemory($request->graphics_card_memories)
            ->operatingSystem($request->operating_systems)
            ->color($request->colors)
            ->exchangePossible($request->exchange)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            //->orderByRaw("availability = 1 DESC")
            //->inRandomOrder()
            ->paginate($request->rows, ['*'], 'page', $request->page);

             $notice = Notice::first()->notice;


          return response()->json(compact('products','notice'));
    }

    public function products(Request $request)
    {

        $products = Product::sort($request->sort)
        ->searchAll($request->search_all)
            ->brand($request->brand)
            ->category($request->category)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->model($request->models)
            ->subtype($request->subtypes)
            ->condition($request->conditions)
            ->numberOfCores($request->cores)
            ->storageType($request->storage_types)
            ->displaySize($request->display_sizes)
            ->graphicsCard($request->graphics_cards)
            ->graphicsCardMemory($request->graphics_card_memories)
            ->operatingSystem($request->operating_systems)
            ->color($request->colors)
            ->exchangePossible($request->exchange)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            ->orderByRaw("availability = 1 DESC")
            //->inRandomOrder()
            ->paginate($request->rows, ['*'], 'page', $request->page);
    

        $youtube = Youtube::first()->youtubeid;
         $notice = Notice::first()->notice;


        return response()->json(compact('products', 'youtube', 'notice'));
    }

    public function categoryProducts(Request $request)
    {


        $products = Product::sort($request->sorting)
            ->searchAll($request->search_all)
            ->catProduct($request->categoryslug)
            ->brandProduct($request->brandslug)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->model($request->models)
            ->subtype($request->subtypes)
            ->condition($request->conditions)
            ->numberOfCores($request->cores)
            ->storageType($request->storage_types)
            ->displaySize($request->display_sizes)
            ->graphicsCard($request->graphics_cards)
            ->graphicsCardMemory($request->graphics_card_memories)
            ->operatingSystem($request->operating_systems)
            ->color($request->colors)
            ->exchangePossible($request->exchange)
            ->filterByPrice($request->price[0], $request->price[1], $request->search_all)
            ->paginate($request->rows, ['*'], 'page', $request->page);

        $youtube = Youtube::first()->youtubeid;
         $notice = Notice::first()->notice;


        return response()->json(compact('products', 'youtube', 'notice'));
    }

    public function shopProducts(Request $request)
    {


        $products = Product::sort($request->sorting)
            ->searchAll($request->search_all)
            ->brand($request->brand)
            ->category($request->category)
            ->catProduct($request->categoryslug)
            ->brandProduct($request->brandslug)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->model($request->models)
            ->subtype($request->subtypes)
            ->condition($request->conditions)
            ->numberOfCores($request->cores)
            ->storageType($request->storage_types)
            ->displaySize($request->display_sizes)
            ->graphicsCard($request->graphics_cards)
            ->graphicsCardMemory($request->graphics_card_memories)
            ->operatingSystem($request->operating_systems)
            ->color($request->colors)
            ->exchangePossible($request->exchange)
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

    /**
     * AI/ChatGPT-facing product feed.
     *
     * Accepts array filters — search, brand[], category[], processor[], ram[],
     * storage[], gpu[], condition[] — plus min_price, max_price (each applies
     * independently), sort, page, rows, and returns an assistant-friendly
     * `data` array (brand name, specs, and a shareable product link).
     */
    public function chatgptProducts(Request $request)
    {
        $perPage = min((int) ($request->rows ?? 5), 100);

        // Map each incoming filter to the column it constrains. Every filter is
        // multi-value (whereIn); scalars are tolerated via the (array) cast.
        $facets = [
            'brand_id'      => $request->brand,
            'category_id'   => $request->category,
            'processor'     => $request->processor,
            'ram'           => $request->ram,
            'storage'       => $request->storage,
            'graphics_card' => $request->gpu,
            'condition'     => $request->condition,
        ];

        $query = Product::searchAll($request->search);
        foreach ($facets as $column => $value) {
            if (! empty($value)) {
                $query->whereIn($column, (array) $value);
            }
        }

        // Only in-stock products are exposed on this endpoint.
        $paginator = $query
            ->where('availability', 1)
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', (float) $request->min_price))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', (float) $request->max_price))
            ->sort($request->sort)
            ->paginate($perPage, ['*'], 'page', $request->page);

        // Resolve brand names for this page in a single query (no N+1).
        $brandMap = Brand::whereIn('id', collect($paginator->items())->pluck('brand_id')->filter()->unique())
            ->pluck('name', 'id');

        $storeUrl = rtrim(config('app.store_url', 'https://hayzeeonline.com'), '/');

        $paginator->getCollection()->transform(function ($product) use ($brandMap, $storeUrl) {
            return [
                'name' => $product->name,
                'price' => $product->price,
                'brand' => $brandMap[$product->brand_id] ?? null,
                'processor' => $product->processor,
                'ram' => $product->ram,
                'storage' => $product->storage,
                'display' => $product->display_size,
                'condition' => $product->condition,
                'availability' => $product->availability,
                'link' => $storeUrl . '/products/' . $product->slug,
            ];
        });

        return response()->json([
            'data' => $paginator->items(),
        ]);
    }


}
