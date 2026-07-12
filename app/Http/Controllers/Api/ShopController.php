<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImages;
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
     * Lightweight product feed shaped for LLM/ChatGPT consumption.
     * Returns a flat products array with only the fields an assistant needs,
     * alongside pagination metadata. Supports the same filters as the storefront:
     * search, sort, brand, category, price range and the spec facets (rams,
     * processors, storages, graphics_cards, ...).
     */
    public function chatgptProducts(Request $request)
    {
        $perPage = min((int) ($request->rows ?? 5), 100);
        $price = is_array($request->price) ? $request->price : [null, null];

        // Keep in-stock (availability = 1) items on top, then let the requested
        // sort act as the tiebreaker. Ordering is added before sort() because
        // Eloquent applies ORDER BY clauses in the order they are chained.
        $paginator = Product::searchAll($request->search)
            ->brand($request->brand)
            ->when($request->category, function ($q) use ($request) {
                // Filter only — avoid scopeCategory()'s latest() side effect,
                // which would otherwise override the availability/price ordering.
                $q->where('category_id', $request->category);
            })
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
            ->filterByPrice($price[0] ?? null, $price[1] ?? null, $request->search)
            ->orderByRaw("availability = 1 DESC")
            ->sort($request->sort)
            ->paginate($perPage, ['*'], 'page', $request->page);

        // Resolve the main image for every product on this page in a single query (no N+1).
        $imageMap = ProductImages::whereIn('product_id', collect($paginator->items())->pluck('id'))
            ->get(['product_id', 'url'])
            ->groupBy('product_id')
            ->map(function ($images) {
                return $images->first()->url;
            });

        $paginator->getCollection()->transform(function ($product) use ($imageMap) {
            return [
                'name' => $product->name,
                'price' => $product->price,
                'new_price' => $product->new_price,
                'slug' => $product->slug,
                'image' => $imageMap[$product->id] ?? $product->image,
                'processor' => $product->processor,
                'ram' => $product->ram,
                'storage' => $product->storage,
                'graphics_card' => $product->graphics_card,
                'availability' => $product->availability,
            ];
        });

        return response()->json([
            'products' => $paginator->items(),
        ]);
    }


}
