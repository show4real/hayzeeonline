<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — Hayzeeonline API</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            max-width: 720px;
            margin: 0 auto;
            padding: 2rem 1.25rem 4rem;
            line-height: 1.7;
            color: #1f2933;
        }
        h1 { font-size: 1.6rem; margin-bottom: 0.25rem; }
        h2 { font-size: 1.15rem; margin-top: 2rem; }
        .muted { color: #6b7280; font-size: 0.9rem; }
        a { color: #0f62fe; }
    </style>
</head>
<body>
    <h1>Privacy Policy</h1>
    <p class="muted">Hayzeeonline Product Catalog API (apiv2.hayzeeonline.com) — Last updated: {{ date('F j, Y') }}</p>

    <p>
        This policy covers the public, read-only product catalog API operated by
        Hayzeeonline and used by AI assistants (such as ChatGPT and Claude) and other
        applications to search our product listings on behalf of their users.
    </p>

    <h2>What this API does</h2>
    <p>
        The API returns publicly available product information from the
        <a href="{{ config('app.store_url', 'https://hayzeeonline.com') }}">hayzeeonline.com</a>
        store: product names, specifications, prices, stock status, images, and links to
        product pages. It is read-only and does not process orders or payments.
    </p>

    <h2>Information we collect</h2>
    <p>
        We do not ask for, and the API does not accept, any personal information — no
        names, email addresses, phone numbers, account details, or payment information.
        When a request is made, we receive only the search terms and filters (for example
        a product keyword or price range) together with standard technical data found in
        any web request: IP address, user agent, and request time. These appear in routine
        server logs kept for security, debugging, and abuse prevention, and are not used
        to identify or profile individuals.
    </p>

    <h2>How search queries are used</h2>
    <p>
        Search queries are used solely to look up matching products and return results.
        We do not build user profiles from queries, and we do not sell or share query
        data with third parties.
    </p>

    <h2>Cookies and tracking</h2>
    <p>The API does not set cookies and does not use any advertising or analytics trackers.</p>

    <h2>Third parties</h2>
    <p>
        When you interact with this API through an AI assistant (for example ChatGPT or
        Claude), your conversation with that assistant is governed by that provider's own
        privacy policy. We only receive the search request the assistant sends to us.
        Our infrastructure providers (such as our hosting and content delivery network)
        may process technical request data as part of operating the service.
    </p>

    <h2>Data retention</h2>
    <p>
        Routine server logs are retained for a limited period for operational and security
        purposes and are then deleted or rotated. No other data about API users is stored.
    </p>

    <h2>Changes to this policy</h2>
    <p>
        We may update this policy from time to time. The date at the top of this page
        reflects the latest revision.
    </p>

    <h2>Contact</h2>
    <p>
        Questions about this policy or the API can be sent to
        <a href="mailto:support@hayzeeonline.com">support@hayzeeonline.com</a>.
    </p>
</body>
</html>
