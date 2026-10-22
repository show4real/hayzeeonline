<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * AI shopping assistant for the storefront chat widget.
 *
 * Runs a tool-calling loop against Claude (Anthropic) or ChatGPT (OpenAI)
 * with API keys kept server-side (config/services.php). Tools are answered
 * directly from AiProductCatalog — no internal HTTP round-trips — and every
 * product a tool surfaces is collected so the widget can render cards.
 *
 * Called from POST /api/ai/chat (AiAgentController@chat).
 */
class ChatAgent
{
    private const MAX_TOOL_ROUNDS = 6;
    private const REQUEST_TIMEOUT = 55;

    private $catalog;

    /** Products surfaced by tool calls this run, keyed by slug. */
    private $seenProducts = [];

    public function __construct(AiProductCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    /**
     * @param array  $messages [['role' => 'user'|'assistant', 'content' => string], ...]
     * @param string $provider 'claude' or 'chatgpt'
     * @return array ['reply' => string, 'products' => array]
     */
    public function chat(array $messages, $provider)
    {
        $this->seenProducts = [];

        $reply = $provider === 'chatgpt'
            ? $this->runOpenAi($messages)
            : $this->runAnthropic($messages);

        return [
            'reply' => $reply,
            'products' => array_slice(array_values($this->seenProducts), 0, 8),
        ];
    }

    public static function providerConfigured($provider)
    {
        return $provider === 'chatgpt'
            ? (bool) config('services.openai.key')
            : (bool) config('services.anthropic.key');
    }

    // ------------------------------------------------------------------
    // System prompt & tool definitions (shared by both providers)
    // ------------------------------------------------------------------

    private function systemPrompt()
    {
        $currency = config('app.store_currency', 'NGN');

        return "You are the shopping assistant for Hayzeeonline (hayzeeonline.com), a computer and gadget store in Ibadan, Nigeria selling US/UK used laptops, iPhones, Samsung/Android phones and accessories. All prices are in {$currency} (₦).\n\n"
            . "Help customers find the right product using the tools:\n"
            . "- Always search the catalog before recommending anything — never invent products or prices.\n"
            . "- Interpret budgets naturally: \"300k\" means ₦300,000. Use max_price and sort=price_asc for budget shoppers.\n"
            . "- Translate use cases into specs before searching (graphics design/video editing → 16GB+ RAM, Core i7/Ryzen 7 or better, dedicated graphics if possible; programming/school → 8GB+ RAM, SSD; gaming → 16GB+ RAM and a dedicated graphics card; office work → 8GB RAM is fine).\n"
            . "- Spec filters are fuzzy text matches, so pass simple values like ram=\"16GB\", processor=\"Core i7,Ryzen 7\", graphics_card=\"RTX\".\n"
            . "- If a filtered search returns nothing, relax the filters (drop processor, widen price) and try again before saying nothing is available.\n"
            . "- Customers can ask for anything the store sells — phones, monitors, accessories — not just laptops.\n"
            . "- Recommend 2-4 concrete options with name and price, and briefly say why each fits. The chat UI renders product cards with links automatically, so don't paste URLs or repeat full spec sheets.\n"
            . "- Keep replies short, warm and conversational. If the request is vague, ask one clarifying question (budget or use case) instead of guessing.\n"
            . "- Only discuss Hayzeeonline products and shopping; politely decline unrelated topics. For order/delivery issues, point customers to WhatsApp +234 916 140 1307.";
    }

    /**
     * Tool schemas. Neutral shape; converted per provider below.
     * Parameters mirror AiProductCatalog::search() (same filters as GET /api/ai/products).
     */
    private function toolDefinitions()
    {
        $currency = config('app.store_currency', 'NGN');

        return [
            [
                'name' => 'search_products',
                'description' => 'Search the store catalog (laptops, phones, accessories and other gadgets). '
                    . 'Supports free text, price bounds and fuzzy spec filters. Out-of-stock products are '
                    . 'excluded automatically. If a filtered search returns nothing, retry with fewer filters.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'q' => [
                            'type' => 'string',
                            'description' => 'Free-text search over names, descriptions and category names, e.g. "hp elitebook", "iphone". Keep it short — brand/model words only.',
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'Category name or slug; comma-separate for multiple, e.g. "gaming laptops".',
                        ],
                        'brand' => [
                            'type' => 'string',
                            'description' => 'Brand name or slug; comma-separate for multiple, e.g. "hp,dell".',
                        ],
                        'min_price' => ['type' => 'number', 'description' => "Minimum price in {$currency}."],
                        'max_price' => ['type' => 'number', 'description' => "Maximum price in {$currency} (e.g. 300000 for a 300k budget)."],
                        'ram' => ['type' => 'string', 'description' => 'RAM filter, fuzzy, e.g. "16GB" or "16GB,32GB".'],
                        'storage' => ['type' => 'string', 'description' => 'Storage filter, fuzzy, e.g. "512GB" or "SSD".'],
                        'processor' => ['type' => 'string', 'description' => 'Processor filter, fuzzy, e.g. "Core i7" or "Core i7,Ryzen 7".'],
                        'graphics_card' => ['type' => 'string', 'description' => 'GPU filter, fuzzy, e.g. "RTX" or "GTX,RTX".'],
                        'condition' => ['type' => 'string', 'description' => 'Condition filter, e.g. "New" or "UK Used".'],
                        'sort' => [
                            'type' => 'string',
                            'enum' => AiProductCatalog::SORTS,
                            'description' => 'Sort order. Use price_asc for budget shoppers.',
                        ],
                        'per_page' => ['type' => 'integer', 'description' => 'Results to return (default 6, max 12).'],
                    ],
                ],
            ],
            [
                'name' => 'get_product_details',
                'description' => 'Full details for one product by slug (from search results): description, images and the spec sheet.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => ['type' => 'string', 'description' => 'Product slug from search results.'],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'list_categories',
                'description' => 'All product categories with in-stock counts. Useful when the customer asks what the store sells or a category filter missed.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'list_brands',
                'description' => 'All brands with in-stock counts.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
            ],
        ];
    }

    private function executeTool($name, array $input)
    {
        switch ($name) {
            case 'search_products':
                $input['per_page'] = min((int) ($input['per_page'] ?? 6), 12);
                $result = $this->catalog->search($input);
                $this->collectProducts($result['products']);

                return $result;

            case 'get_product_details':
                $product = $this->catalog->findBySlug($input['slug'] ?? '');
                if (! $product) {
                    return ['error' => 'Product not found', 'hint' => 'Use a slug returned by search_products.'];
                }
                $this->collectProducts([$product]);

                return ['product' => $product];

            case 'list_categories':
                return ['categories' => $this->catalog->categories()];

            case 'list_brands':
                return ['brands' => $this->catalog->brands()];

            default:
                return ['error' => "Unknown tool: {$name}"];
        }
    }

    private function collectProducts(array $products)
    {
        foreach ($products as $product) {
            $slug = $product['slug'] ?? null;
            if ($slug && ! isset($this->seenProducts[$slug])) {
                // Card-sized subset; the widget links via slug.
                $this->seenProducts[$slug] = [
                    'slug' => $slug,
                    'name' => $product['name'] ?? '',
                    'price' => $product['price'] ?? null,
                    'currency' => $product['currency'] ?? config('app.store_currency', 'NGN'),
                    'in_stock' => $product['in_stock'] ?? null,
                    'condition' => $product['condition'] ?? null,
                    'image' => $product['image'] ?? null,
                    'url' => $product['url'] ?? null,
                ];
            }
        }
    }

    // ------------------------------------------------------------------
    // Claude (Anthropic Messages API)
    // ------------------------------------------------------------------

    private function runAnthropic(array $messages)
    {
        $tools = array_map(function ($tool) {
            return [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'input_schema' => $tool['input_schema'],
            ];
        }, $this->toolDefinitions());

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $response = Http::withHeaders([
                    'x-api-key' => config('services.anthropic.key'),
                    'anthropic-version' => '2023-06-01',
                ])
                ->timeout(self::REQUEST_TIMEOUT)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-opus-4-8'),
                    'max_tokens' => 4096,
                    'system' => [
                        [
                            'type' => 'text',
                            'text' => $this->systemPrompt(),
                            'cache_control' => ['type' => 'ephemeral'],
                        ],
                    ],
                    'tools' => $tools,
                    'messages' => $messages,
                ]);

            if ($response->failed()) {
                // Exception code carries the upstream HTTP status so the
                // controller can map quota/auth problems to useful responses.
                throw new RuntimeException(
                    'Anthropic API error ' . $response->status() . ': '
                    . ($response->json('error.message') ?: $response->body()),
                    $response->status()
                );
            }

            $data = $response->json();

            if (($data['stop_reason'] ?? null) === 'refusal') {
                return "Sorry, I can't help with that one — but I'm happy to help you find a laptop, phone or any other gadget!";
            }

            if (($data['stop_reason'] ?? null) !== 'tool_use') {
                $texts = [];
                foreach ($data['content'] ?? [] as $block) {
                    if (($block['type'] ?? null) === 'text') {
                        $texts[] = $block['text'];
                    }
                }

                return trim(implode("\n", $texts));
            }

            // Append the assistant turn, run every requested tool, and send
            // all results back in a single user message.
            $messages[] = ['role' => 'assistant', 'content' => $data['content']];

            $results = [];
            foreach ($data['content'] as $block) {
                if (($block['type'] ?? null) !== 'tool_use') {
                    continue;
                }
                try {
                    $result = $this->executeTool($block['name'], (array) ($block['input'] ?? []));
                    $results[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $block['id'],
                        'content' => json_encode($result),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $block['id'],
                        'content' => 'Tool failed: ' . $e->getMessage(),
                        'is_error' => true,
                    ];
                }
            }

            $messages[] = ['role' => 'user', 'content' => $results];
        }

        return "Sorry, I couldn't finish looking that up. Could you rephrase your request?";
    }

    // ------------------------------------------------------------------
    // ChatGPT (OpenAI Chat Completions API)
    // ------------------------------------------------------------------

    private function runOpenAi(array $messages)
    {
        $tools = array_map(function ($tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['input_schema'],
                ],
            ];
        }, $this->toolDefinitions());

        array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt()]);

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $response = Http::withToken(config('services.openai.key'))
                ->timeout(self::REQUEST_TIMEOUT)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o'),
                    'messages' => $messages,
                    'tools' => $tools,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'OpenAI API error ' . $response->status() . ': '
                    . ($response->json('error.message') ?: $response->body()),
                    $response->status()
                );
            }

            $message = $response->json('choices.0.message');

            if (empty($message['tool_calls'])) {
                return trim((string) ($message['content'] ?? ''));
            }

            $messages[] = $message;

            foreach ($message['tool_calls'] as $call) {
                try {
                    $input = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                    $content = json_encode($this->executeTool($call['function']['name'], $input));
                } catch (\Throwable $e) {
                    $content = 'Tool failed: ' . $e->getMessage();
                }
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'],
                    'content' => $content,
                ];
            }
        }

        return "Sorry, I couldn't finish looking that up. Could you rephrase your request?";
    }
}
