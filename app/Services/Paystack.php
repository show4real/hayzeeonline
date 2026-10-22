<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Paystack REST client.
 *
 * Replaces unicodeveloper/laravel-paystack, which was abandoned at
 * illuminate/support ^11 and so cannot be installed alongside Laravel 12.
 * Only the two calls this application actually made are implemented:
 * initialising a transaction and verifying one.
 *
 * Credentials come from config('services.paystack'), never from env() — the
 * production containers run `config:cache`, after which env() returns null
 * outside config files.
 */
class Paystack
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl = rtrim(
            (string) config('services.paystack.payment_url', 'https://api.paystack.co'),
            '/'
        );
    }

    /**
     * Start a transaction and return the hosted checkout URL the customer is
     * sent to.
     *
     * @param  array  $payload  amount (kobo), email, reference, currency, …
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initializeTransaction(array $payload): array
    {
        $response = $this->request()
            ->post($this->baseUrl.'/transaction/initialize', $payload);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException(
                'Paystack could not start the transaction: '
                .($response->json('message') ?? $response->status())
            );
        }

        return $response->json('data');
    }

    /**
     * Look a transaction up by reference. Returns Paystack's `data` object,
     * whose `status` is 'success' only once the money has actually moved.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->request()
            ->get($this->baseUrl.'/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException(
                'Paystack could not verify '.$reference.': '
                .($response->json('message') ?? $response->status())
            );
        }

        return $response->json('data');
    }

    private function request()
    {
        if ($this->secretKey === '') {
            throw new RuntimeException(
                'PAYSTACK_SECRET_KEY is not set — payments cannot be processed.'
            );
        }

        return Http::withToken($this->secretKey)
            ->acceptJson()
            // Paystack occasionally takes several seconds to answer; without a
            // ceiling a stuck call holds a php-fpm worker until the pool's
            // request_terminate_timeout kills it.
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }
}
