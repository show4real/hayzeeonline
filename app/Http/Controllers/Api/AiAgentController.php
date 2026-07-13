<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiProductCatalog;
use Illuminate\Http\Request;

/**
 * Public, read-only endpoints for AI agents (ChatGPT Actions, Claude,
 * LangChain agents, ...). GET-only with flat query parameters so any
 * agent can call them, and described by the OpenAPI document at
 * GET /api/openapi.json.
 */
class AiAgentController extends Controller
{
    private $catalog;

    public function __construct(AiProductCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function searchProducts(Request $request)
    {
        return response()->json($this->catalog->search($request->query()));
    }

    public function showProduct($slug)
    {
        $product = $this->catalog->findBySlug($slug);
        if (! $product) {
            return response()->json([
                'error' => 'Product not found',
                'hint' => 'Slugs come from the search endpoint (GET /api/ai/products).',
            ], 404);
        }

        return response()->json(['product' => $product]);
    }

    public function categories()
    {
        return response()->json(['categories' => $this->catalog->categories()]);
    }

    public function brands()
    {
        return response()->json(['brands' => $this->catalog->brands()]);
    }

    /** OpenAPI 3.1 description of the AI endpoints (used by GPT Actions). */
    public function openapi()
    {
        $currency = config('app.store_currency', 'NGN');
        $storeUrl = rtrim(config('app.store_url', 'https://hayzeeonline.com'), '/');

        $productSummary = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'slug' => ['type' => 'string', 'description' => 'Stable identifier; use it with GET /ai/products/{slug}.'],
                'name' => ['type' => 'string'],
                'price' => ['type' => ['number', 'null'], 'description' => "Price in {$currency}."],
                'currency' => ['type' => 'string'],
                'in_stock' => ['type' => 'boolean'],
                'condition' => ['type' => ['string', 'null'], 'description' => 'e.g. New, UK Used.'],
                'brand' => ['type' => ['string', 'null']],
                'category' => ['type' => ['string', 'null']],
                'specs' => [
                    'type' => 'object',
                    'description' => 'Hardware specs; only populated keys are present.',
                ],
                'image' => ['type' => ['string', 'null']],
                'url' => ['type' => 'string', 'description' => "Product page on {$storeUrl} — always share this link with users."],
            ],
        ];

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Hayzeeonline Product Catalog API',
                'description' => 'Read-only catalog search for hayzeeonline.com, a computer and gadget store. '
                    . "Prices are in {$currency}. Every product includes a `url` field linking to its page on the storefront; "
                    . 'always give users that link.',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => url('/api')],
            ],
            'paths' => [
                '/ai/products' => [
                    'get' => [
                        'operationId' => 'searchProducts',
                        'summary' => 'Search products with optional filters (text, category, brand, price range, specs).',
                        'parameters' => [
                            $this->param('q', 'Free-text search over product names, descriptions and category names, e.g. "gaming laptop".'),
                            $this->param('category', 'Category name or slug; comma-separate for multiple, e.g. "gaming laptops".'),
                            $this->param('brand', 'Brand name or slug; comma-separate for multiple, e.g. "hp,dell".'),
                            $this->param('min_price', "Minimum price in {$currency}.", 'number'),
                            $this->param('max_price', "Maximum price in {$currency}.", 'number'),
                            $this->param('ram', 'RAM filter, fuzzy match, e.g. "16GB" or "16GB,32GB".'),
                            $this->param('storage', 'Storage filter, fuzzy match, e.g. "512GB".'),
                            $this->param('processor', 'Processor filter, fuzzy match, e.g. "Core i7" or "Ryzen 7".'),
                            $this->param('graphics_card', 'GPU filter, fuzzy match, e.g. "RTX".'),
                            $this->param('condition', 'Condition filter, e.g. "New" or "UK Used".'),
                            $this->param('operating_system', 'OS filter, e.g. "Windows 11".'),
                            $this->param('include_sold', 'Set true to include out-of-stock products (excluded by default).', 'boolean'),
                            $this->param('sort', 'One of: relevance (default), price_asc, price_desc, newest.'),
                            $this->param('page', 'Page number, starting at 1.', 'integer'),
                            $this->param('per_page', 'Results per page (1-50, default 10).', 'integer'),
                        ],
                        'responses' => [
                            '200' => $this->jsonResponse('Matching products.', [
                                'type' => 'object',
                                'properties' => [
                                    'products' => ['type' => 'array', 'items' => $productSummary],
                                    'pagination' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'page' => ['type' => 'integer'],
                                            'per_page' => ['type' => 'integer'],
                                            'total' => ['type' => 'integer'],
                                            'total_pages' => ['type' => 'integer'],
                                        ],
                                    ],
                                    'hints' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'description' => 'Notes about filters that could not be applied.',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
                '/ai/products/{slug}' => [
                    'get' => [
                        'operationId' => 'getProduct',
                        'summary' => 'Full details for one product: description, images, and spec sheet.',
                        'parameters' => [
                            [
                                'name' => 'slug',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                                'description' => 'Product slug from the search results.',
                            ],
                        ],
                        'responses' => [
                            '200' => $this->jsonResponse('Product detail.', [
                                'type' => 'object',
                                'properties' => ['product' => $productSummary],
                            ]),
                            '404' => $this->jsonResponse('Unknown slug.', ['type' => 'object']),
                        ],
                    ],
                ],
                '/ai/categories' => [
                    'get' => [
                        'operationId' => 'listCategories',
                        'summary' => 'All product categories with in-stock counts; use these values for the category filter.',
                        'responses' => ['200' => $this->jsonResponse('Category list.', ['type' => 'object'])],
                    ],
                ],
                '/ai/brands' => [
                    'get' => [
                        'operationId' => 'listBrands',
                        'summary' => 'All brands with in-stock counts; use these values for the brand filter.',
                        'responses' => ['200' => $this->jsonResponse('Brand list.', ['type' => 'object'])],
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }

    private function param($name, $description, $type = 'string')
    {
        return [
            'name' => $name,
            'in' => 'query',
            'required' => false,
            'schema' => ['type' => $type],
            'description' => $description,
        ];
    }

    private function jsonResponse($description, array $schema)
    {
        return [
            'description' => $description,
            'content' => ['application/json' => ['schema' => $schema]],
        ];
    }
}
