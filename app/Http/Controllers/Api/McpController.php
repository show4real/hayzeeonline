<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiProductCatalog;
use Illuminate\Http\Request;

/**
 * Stateless MCP (Model Context Protocol) server over Streamable HTTP.
 *
 * POST /api/mcp with JSON-RPC 2.0 messages. Supports initialize, ping,
 * tools/list and tools/call. Exposes catalog tools plus the `search` and
 * `fetch` tools ChatGPT connectors require. No sessions and no SSE: every
 * request is answered with a single JSON response, which the spec permits
 * for servers that never push messages of their own.
 */
class McpController extends Controller
{
    private const PROTOCOL_VERSIONS = ['2024-11-05', '2025-03-26', '2025-06-18'];

    private $catalog;

    public function __construct(AiProductCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function handle(Request $request)
    {
        if ($request->isMethod('delete')) {
            // Session termination — nothing to clean up on a stateless server.
            return response()->noContent();
        }
        if (! $request->isMethod('post')) {
            return $this->error(null, -32000, 'This MCP endpoint only accepts POST requests.', 405);
        }

        $message = json_decode($request->getContent(), true);
        if (! is_array($message)) {
            return $this->error(null, -32700, 'Parse error: body must be a JSON-RPC 2.0 message.');
        }
        if (array_keys($message) === range(0, count($message) - 1)) {
            return $this->error(null, -32600, 'Batch requests are not supported.');
        }

        $method = (string) ($message['method'] ?? '');
        $params = isset($message['params']) && is_array($message['params']) ? $message['params'] : [];

        // Notifications (no id) get acknowledged without a body.
        if (! array_key_exists('id', $message) || $message['id'] === null) {
            return response()->noContent(202);
        }
        $id = $message['id'];

        switch ($method) {
            case 'initialize':
                return $this->initialize($id, $params);
            case 'ping':
                return $this->result($id, new \stdClass());
            case 'tools/list':
                return $this->result($id, ['tools' => $this->toolDefinitions()]);
            case 'tools/call':
                return $this->callTool($id, $params);
            default:
                return $this->error($id, -32601, "Method not found: {$method}");
        }
    }

    private function initialize($id, array $params)
    {
        $requested = (string) ($params['protocolVersion'] ?? '');
        $version = in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : '2025-06-18';

        return $this->result($id, [
            'protocolVersion' => $version,
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => [
                'name' => 'hayzeeonline-product-catalog',
                'version' => '1.0.0',
            ],
            'instructions' => 'Product catalog for hayzeeonline.com, a computer and gadget store in Nigeria. '
                . 'Prices are in ' . config('app.store_currency', 'NGN') . '. Use search_products for filtered '
                . 'queries (price range, brand, category, specs) and get_product for full details. Every product '
                . 'result includes a `url` to its storefront page — always share that link with the user.',
        ]);
    }

    private function callTool($id, array $params)
    {
        $name = (string) ($params['name'] ?? '');
        $args = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : [];

        try {
            switch ($name) {
                case 'search_products':
                    if (isset($args['query'])) {
                        $args['q'] = $args['query'];
                    }
                    return $this->toolResult($id, $this->catalog->search($args));

                case 'get_product':
                    $product = $this->catalog->findBySlug($args['slug'] ?? '');
                    if (! $product) {
                        return $this->toolFailure($id, 'No product found for slug "' . ($args['slug'] ?? '') . '". Slugs come from search_products results.');
                    }
                    return $this->toolResult($id, ['product' => $product]);

                case 'list_categories':
                    return $this->toolResult($id, ['categories' => $this->catalog->categories()]);

                case 'list_brands':
                    return $this->toolResult($id, ['brands' => $this->catalog->brands()]);

                // `search` and `fetch` follow the shape ChatGPT connectors expect.
                case 'search':
                    $found = $this->catalog->search(['q' => $args['query'] ?? '', 'per_page' => 10]);
                    $results = array_map(function ($product) {
                        return [
                            'id' => $product['slug'],
                            'title' => $product['name'] . ' — ' . $product['currency'] . ' ' . number_format((float) $product['price']),
                            'url' => $product['url'],
                        ];
                    }, $found['products']);
                    return $this->toolResult($id, ['results' => $results]);

                case 'fetch':
                    $product = $this->catalog->findBySlug($args['id'] ?? '');
                    if (! $product) {
                        return $this->toolFailure($id, 'No product found for id "' . ($args['id'] ?? '') . '". Ids come from search results.');
                    }
                    return $this->toolResult($id, [
                        'id' => $product['slug'],
                        'title' => $product['name'],
                        'text' => json_encode($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'url' => $product['url'],
                        'metadata' => [
                            'price' => $product['price'],
                            'currency' => $product['currency'],
                            'in_stock' => $product['in_stock'],
                        ],
                    ]);

                default:
                    return $this->error($id, -32602, "Unknown tool: {$name}");
            }
        } catch (\Throwable $e) {
            report($e);
            return $this->toolFailure($id, 'Tool execution failed unexpectedly.');
        }
    }

    private function toolDefinitions()
    {
        $currency = config('app.store_currency', 'NGN');

        return [
            [
                'name' => 'search_products',
                'description' => 'Search the hayzeeonline.com catalog with filters. Returns products with prices '
                    . "(in {$currency}), specs, stock status and a storefront url per product.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Free-text search, e.g. "gaming laptop".'],
                        'category' => ['type' => 'string', 'description' => 'Category name or slug; comma-separate multiples.'],
                        'brand' => ['type' => 'string', 'description' => 'Brand name or slug; comma-separate multiples, e.g. "hp,dell".'],
                        'min_price' => ['type' => 'number', 'description' => "Minimum price in {$currency}."],
                        'max_price' => ['type' => 'number', 'description' => "Maximum price in {$currency}."],
                        'ram' => ['type' => 'string', 'description' => 'e.g. "16GB" or "16GB,32GB".'],
                        'storage' => ['type' => 'string', 'description' => 'e.g. "512GB".'],
                        'processor' => ['type' => 'string', 'description' => 'e.g. "Core i7" or "Ryzen 7".'],
                        'graphics_card' => ['type' => 'string', 'description' => 'e.g. "RTX".'],
                        'condition' => ['type' => 'string', 'description' => 'e.g. "New" or "UK Used".'],
                        'include_sold' => ['type' => 'boolean', 'description' => 'Include out-of-stock items (default false).'],
                        'sort' => ['type' => 'string', 'enum' => AiProductCatalog::SORTS],
                        'page' => ['type' => 'integer'],
                        'per_page' => ['type' => 'integer', 'description' => '1-50, default 10.'],
                    ],
                ],
            ],
            [
                'name' => 'get_product',
                'description' => 'Full details for one product (description, images, spec sheet) by slug.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => ['type' => 'string', 'description' => 'Product slug from search_products results.'],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'list_categories',
                'description' => 'All product categories with in-stock counts. Use these values for category filters.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'list_brands',
                'description' => 'All brands with in-stock counts. Use these values for brand filters.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'search',
                'description' => 'Simple text search over the product catalog. Returns result ids, titles with prices, and urls.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Search query.'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'fetch',
                'description' => 'Fetch the full document for a search result by id.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'Result id from the search tool.'],
                    ],
                    'required' => ['id'],
                ],
            ],
        ];
    }

    private function toolResult($id, $data)
    {
        return $this->result($id, [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ],
            'isError' => false,
        ]);
    }

    private function toolFailure($id, $message)
    {
        return $this->result($id, [
            'content' => [['type' => 'text', 'text' => $message]],
            'isError' => true,
        ]);
    }

    private function result($id, $result)
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    }

    private function error($id, $code, $message, $status = 200)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }
}
