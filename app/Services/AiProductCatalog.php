<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\ProductImages;

/**
 * Read-only product catalog tailored for AI agents (ChatGPT, Claude, etc.).
 *
 * Agents don't know internal ids, so every filter accepts human terms
 * (brand/category names or slugs, "16GB", "RTX 3060", ...) and price
 * bounds are plain min/max numbers. Results always carry the public
 * storefront URL so the agent can link users straight to the product page.
 */
class AiProductCatalog
{
    public const SORTS = ['relevance', 'price_asc', 'price_desc', 'newest'];

    /** Spec columns that accept comma-separated, fuzzy (LIKE) terms. */
    private const SPEC_FILTERS = [
        'ram',
        'storage',
        'processor',
        'graphics_card',
        'condition',
        'operating_system',
    ];

    /**
     * @param array $params q, category, brand, min_price, max_price,
     *                      ram, storage, processor, graphics_card, condition,
     *                      operating_system, include_sold, sort, page, per_page
     */
    public function search(array $params)
    {
        $perPage = min(max((int) ($params['per_page'] ?? 10), 1), 50);
        $page = max((int) ($params['page'] ?? 1), 1);
        $hints = [];

        $query = Product::query();

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            // Also match category names ("gaming laptops") so a plain-text
            // query still finds products whose own text lacks the phrase.
            $categoryIds = Category::where('name', 'like', "%{$q}%")->pluck('id');
            $query->where(function ($sub) use ($q, $categoryIds) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
                if ($categoryIds->isNotEmpty()) {
                    $sub->orWhereIn('category_id', $categoryIds);
                }
            });
        }

        foreach (['category' => Category::class, 'brand' => Brand::class] as $param => $model) {
            $value = trim((string) ($params[$param] ?? ''));
            if ($value === '') {
                continue;
            }
            $ids = $this->resolveIds($model, $value);
            if (empty($ids)) {
                // Ignoring a bad filter (with a hint) beats returning nothing.
                $hints[] = "No {$param} matched \"{$value}\"; that filter was ignored. "
                    . "List the available {$param} values to find the right name.";
                continue;
            }
            $query->whereIn($param . '_id', $ids);
        }

        foreach (self::SPEC_FILTERS as $column) {
            $this->applyLikeFilter($query, $column, $params[$column] ?? null);
        }

        if (isset($params['min_price']) && is_numeric($params['min_price'])) {
            $query->where('price', '>=', (float) $params['min_price']);
        }
        if (isset($params['max_price']) && is_numeric($params['max_price'])) {
            $query->where('price', '<=', (float) $params['max_price']);
        }

        // Sold items are excluded by default: an assistant recommending an
        // unavailable product is worse than a smaller result set.
        if (! filter_var($params['include_sold'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('availability', 1);
        }

        switch ($params['sort'] ?? 'relevance') {
            case 'price_asc':
                $query->orderByRaw('(price IS NULL OR price = 0) ASC')->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderByRaw('(price IS NULL OR price = 0) ASC')->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderByRaw('availability = 1 DESC')->orderBy('updated_at', 'desc');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = collect($paginator->items());

        // Resolve names for the whole page in two queries (no N+1).
        $brandMap = Brand::whereIn('id', $items->pluck('brand_id')->filter()->unique())
            ->pluck('name', 'id');
        $categoryMap = Category::whereIn('id', $items->pluck('category_id')->filter()->unique())
            ->pluck('name', 'id');

        $products = $items
            ->map(function ($product) use ($brandMap, $categoryMap) {
                return $this->productSummary($product, $brandMap, $categoryMap);
            })
            ->values()
            ->all();

        return [
            'products' => $products,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
            'hints' => $hints,
        ];
    }

    /** Full detail for one product, or null when the slug is unknown. */
    public function findBySlug($slug)
    {
        $product = Product::where('slug', trim((string) $slug))->first();
        if (! $product) {
            return null;
        }

        $brandMap = Brand::where('id', $product->brand_id)->pluck('name', 'id');
        $categoryMap = Category::where('id', $product->category_id)->pluck('name', 'id');

        $data = $this->productSummary($product, $brandMap, $categoryMap);
        $data['description'] = $product->description;
        $data['images'] = ProductImages::where('product_id', $product->id)->pluck('url')->values()->all();
        $data['details'] = ProductDescription::where('product_id', $product->id)
            ->get(['label', 'values'])
            ->map(function ($row) {
                return ['label' => $row->label, 'value' => $row->values];
            })
            ->values()
            ->all();

        return $data;
    }

    public function categories()
    {
        $inStock = Product::where('availability', 1)
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return Category::orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(function ($category) use ($inStock) {
                return [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'in_stock_products' => (int) ($inStock[$category->id] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    public function brands()
    {
        $inStock = Product::where('availability', 1)
            ->selectRaw('brand_id, COUNT(*) as total')
            ->groupBy('brand_id')
            ->pluck('total', 'brand_id');

        return Brand::orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(function ($brand) use ($inStock) {
                return [
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'in_stock_products' => (int) ($inStock[$brand->id] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function productSummary(Product $product, $brandMap, $categoryMap)
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price' => $product->price !== null ? (float) $product->price : null,
            'currency' => config('app.store_currency', 'NGN'),
            'in_stock' => (int) $product->availability === 1,
            'condition' => $product->condition,
            'brand' => $brandMap[$product->brand_id] ?? null,
            'category' => $categoryMap[$product->category_id] ?? null,
            'specs' => array_filter([
                'processor' => $product->processor,
                'number_of_cores' => $product->number_of_cores,
                'ram' => $product->ram,
                'storage' => $product->storage,
                'storage_type' => $product->storage_type,
                'display_size' => $product->display_size,
                'graphics_card' => $product->graphics_card,
                'graphics_card_memory' => $product->graphics_card_memory,
                'operating_system' => $product->operating_system,
                'color' => $product->color,
            ]),
            'image' => $product->image,
            'url' => $this->productUrl($product),
        ];
    }

    /** Public storefront page for a product (the "UI link"). */
    public function productUrl(Product $product)
    {
        $storeUrl = rtrim(config('app.store_url', 'https://hayzeeonline.com'), '/');

        return $storeUrl . '/search/' . $product->slug;
    }

    /**
     * Turn "hp, dell" (names or slugs) into ids. Empty array = nothing matched;
     * callers decide whether to ignore the filter or surface the miss.
     */
    private function resolveIds($model, $value)
    {
        $ids = [];
        foreach (array_filter(array_map('trim', explode(',', $value))) as $term) {
            $matches = $model::where('slug', $term)
                ->orWhere('name', 'like', "%{$term}%")
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $matches);
        }

        return array_values(array_unique($ids));
    }

    private function applyLikeFilter($query, $column, $value)
    {
        $terms = array_filter(array_map('trim', explode(',', (string) $value)));
        if (empty($terms)) {
            return;
        }
        $query->where(function ($sub) use ($column, $terms) {
            foreach ($terms as $term) {
                $sub->orWhere($column, 'like', "%{$term}%");
            }
        });
    }
}
