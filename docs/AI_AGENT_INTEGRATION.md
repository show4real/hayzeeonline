# AI Agent Integration (ChatGPT, Claude, and others)

This backend exposes a public, read-only slice of the product catalog that AI
agents can discover and query. Users asking ChatGPT/Claude for e.g. *"gaming
laptops under ₦1.5M on hayzeeonline.com"* get live, accurate results, each with
a direct link to the product page on the storefront.

There are two integration surfaces, both backed by the same service
(`App\Services\AiProductCatalog`):

| Surface | URL | Consumed by |
|---|---|---|
| REST + OpenAPI | `https://apiv2.hayzeeonline.com/api/ai/...` spec at `/api/openapi.json` | ChatGPT Custom GPT Actions, LangChain/agent frameworks, anything HTTP |
| MCP server | `https://apiv2.hayzeeonline.com/api/mcp` | ChatGPT connectors & Apps, Claude custom connectors, any MCP client |

## REST endpoints

All GET, no auth, flat query params:

- `GET /api/ai/products` — search/filter. Params: `q`, `category`, `brand`
  (names or slugs, comma-separated for multiples), `min_price`, `max_price`,
  `ram`, `storage`, `processor`, `graphics_card`, `condition`,
  `operating_system` (fuzzy match), `include_sold` (default false — sold items
  are hidden), `sort` (`relevance` | `price_asc` | `price_desc` | `newest`),
  `page`, `per_page` (max 50).
- `GET /api/ai/products/{slug}` — full detail: description, images, spec sheet.
- `GET /api/ai/categories` and `GET /api/ai/brands` — valid filter values with
  in-stock counts.

Example:

```
GET /api/ai/products?q=gaming laptop&min_price=500000&max_price=1500000&sort=price_asc
```

Every product in a response carries `url` — the storefront page
(`STORE_URL` + `/search/{slug}`) — plus price, currency, stock status, brand,
category and specs. Unmatchable filters are ignored and reported in `hints`
instead of silently returning nothing.

## MCP server

`POST /api/mcp` — stateless JSON-RPC 2.0 over Streamable HTTP (no SSE, no
sessions). Tools:

- `search_products`, `get_product`, `list_categories`, `list_brands` — rich,
  filterable catalog access.
- `search` and `fetch` — the exact pair ChatGPT connectors require
  (`search` → `{results: [{id, title, url}]}`, `fetch` → full document).

## Hooking up each platform

**ChatGPT (connector / app):** ChatGPT settings → Connectors → Add custom
connector (developer mode) → MCP server URL `https://apiv2.hayzeeonline.com/api/mcp`,
no authentication. The `search`/`fetch` tools make it usable in regular chat
and deep research; the richer tools are used in developer-mode conversations.

**ChatGPT (Custom GPT with Actions):** Create a GPT → Actions → Import from URL
→ `https://apiv2.hayzeeonline.com/api/openapi.json`. Auth: none.

**Claude (claude.ai / Claude Code):** Settings → Connectors → Add custom
connector → `https://apiv2.hayzeeonline.com/api/mcp`. Or in Claude Code:
`claude mcp add --transport http hayzeeonline https://apiv2.hayzeeonline.com/api/mcp`.

**Other agent frameworks:** point any MCP client at `/api/mcp`, or feed
`/api/openapi.json` to OpenAPI tool loaders.

## Configuration

`.env` (optional — defaults shown):

```
STORE_URL=https://hayzeeonline.com   # storefront that product links point to
STORE_CURRENCY=NGN                   # currency label included with prices
```

After changing `.env` on the server, run `php artisan config:cache` if config
caching is enabled.

## Notes

- Everything is read-only; no order, payment, user or admin data is exposed.
- Endpoints sit behind the standard `api` throttle (60 requests/min per IP).
- CORS already allows `api/*`, so browser-based agents work too.
- Sold-out products are excluded unless `include_sold=true`, so agents never
  recommend items that cannot be bought.
