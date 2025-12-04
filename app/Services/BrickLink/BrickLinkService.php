<?php

namespace App\Services\BrickLink;

use App\Models\Store;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BrickLinkService
{
    protected Client $client;

    protected Store $store;

    protected string $baseUrl = 'https://api.bricklink.com/api/store/v1';

    protected int $rateLimit = 5000; // BrickLink: 5000 requests per day

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->client = $this->createClient();
    }

    /**
     * Create Guzzle HTTP client (without OAuth middleware)
     */
    protected function createClient(): Client
    {
        if (! $this->store->hasBrickLinkCredentials()) {
            throw new \Exception('BrickLink API credentials not configured for store: '.$this->store->name);
        }

        Log::debug('Creating BrickLink API client', ['store_id' => $this->store->id]);

        return new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'http_errors' => false, // Handle errors manually
        ]);
    }

    /**
     * Generate OAuth 1.0a authorization header manually
     * Exact implementation from working Nextcloud BrickLinkClient
     */
    protected function generateOAuthHeader(string $method, string $url, array $queryParams = []): string
    {
        // OAuth parameters
        $nonce = md5(microtime().mt_rand());
        $timestamp = time();

        $oauth = [
            'oauth_consumer_key' => $this->store->bl_consumer_key,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $this->store->bl_token,
            'oauth_version' => '1.0',
        ];

        // Merge all parameters for signature
        $signatureParams = array_merge($oauth, $queryParams);

        // Generate base string
        $baseString = $this->buildBaseString($method, $url, $signatureParams);

        // Generate signature
        $signingKey = $this->store->bl_consumer_secret.'&'.$this->store->bl_token_secret;
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        Log::debug('OAuth Signature Details', [
            'method' => $method,
            'url' => $url,
            'nonce' => $nonce,
            'timestamp' => $timestamp,
            'baseString' => $baseString,
            'signature' => $signature,
        ]);

        // Build OAuth header
        return sprintf(
            'OAuth oauth_consumer_key="%s", oauth_nonce="%s", oauth_signature="%s", oauth_signature_method="%s", oauth_timestamp="%s", oauth_token="%s", oauth_version="%s"',
            rawurlencode($oauth['oauth_consumer_key']),
            rawurlencode($nonce),
            rawurlencode($signature),
            rawurlencode('HMAC-SHA1'),
            rawurlencode((string) $timestamp),
            rawurlencode($oauth['oauth_token']),
            rawurlencode('1.0')
        );
    }

    /**
     * Build OAuth base string for signature
     * Exact implementation from Nextcloud
     */
    protected function buildBaseString(string $method, string $url, array $params): string
    {
        // Sort params alphabetically by key
        ksort($params);

        // Build parameter string
        $paramStrings = [];
        foreach ($params as $key => $value) {
            $paramStrings[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }
        $paramString = implode('&', $paramStrings);

        // Build base string
        $baseString = strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode($paramString);

        Log::debug('Base String Components', [
            'method' => strtoupper($method),
            'url' => $url,
            'params' => $paramString,
            'baseString' => $baseString,
        ]);

        return $baseString;
    }

    /**
     * Make GET request to BrickLink API
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $this->checkRateLimit();

        // Build full URL WITHOUT query parameters for OAuth signature
        $fullUrl = $this->baseUrl.$endpoint;

        // Generate OAuth header - include query params in signature but NOT in URL
        $oauthHeader = $this->generateOAuthHeader('GET', $fullUrl, $params);

        Log::debug('BrickLink API Request Details', [
            'endpoint' => $endpoint,
            'baseUrl' => $this->baseUrl,
            'fullUrl' => $fullUrl,
            'params' => $params,
            'oauthHeader' => $oauthHeader,
        ]);

        try {
            $response = $this->client->request('GET', $endpoint, [
                'query' => $params,
                'headers' => [
                    'Authorization' => $oauthHeader,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            Log::debug('BrickLink API raw response', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'body_length' => strlen($body),
                'body_preview' => substr($body, 0, 500),
            ]);

            $data = json_decode($body, true);

            if ($data === null) {
                Log::error('BrickLink API response is not valid JSON', [
                    'endpoint' => $endpoint,
                    'body' => $body,
                ]);
                throw new \Exception('Invalid JSON response from BrickLink API');
            }

            Log::debug('BrickLink API parsed response', [
                'meta' => $data['meta'] ?? null,
                'data_count' => is_array($data['data'] ?? null) ? count($data['data']) : null,
            ]);

            // Check for API errors
            if (isset($data['meta']['code'])) {
                $apiCode = (int) $data['meta']['code'];

                if ($apiCode === 401 || (isset($data['meta']['description']) && str_contains($data['meta']['description'], 'SIGNATURE_INVALID'))) {
                    Log::error('BrickLink API authentication failed', [
                        'endpoint' => $endpoint,
                        'apiCode' => $apiCode,
                        'description' => $data['meta']['description'] ?? '',
                        'message' => $data['meta']['message'] ?? '',
                    ]);
                    throw new \Exception('BrickLink API authentication failed: '.($data['meta']['description'] ?? 'Invalid signature'));
                }

                if ($apiCode !== 200) {
                    throw new \Exception('BrickLink API Error (code '.$apiCode.'): '.($data['meta']['description'] ?? $data['meta']['message'] ?? 'Unknown error'));
                }
            }

            // Check HTTP status code
            if ($statusCode === 403) {
                Log::error('BrickLink API returned 403 Forbidden', [
                    'endpoint' => $endpoint,
                    'statusCode' => $statusCode,
                    'response' => substr($body, 0, 500),
                ]);
                throw new \Exception("BrickLink API returned 403 Forbidden. Possible causes:\n".
                    "1. IP address is blocked or not whitelisted\n".
                    "2. API credentials are incorrect or expired\n".
                    '3. API access is not enabled for your account');
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \Exception("BrickLink API HTTP error: $statusCode - ".(isset($data['meta']['message']) ? $data['meta']['message'] : 'Unknown error'));
            }

            $this->incrementRateLimit();

            return $data['data'] ?? [];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::error('BrickLink API Client Error', [
                'endpoint' => $endpoint,
                'params' => $params,
                'status_code' => $e->getResponse()->getStatusCode(),
                'response_body' => $e->getResponse()->getBody()->getContents(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::error('BrickLink API GET Error', [
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Make PUT request to BrickLink API
     */
    protected function put(string $endpoint, array $data = []): array
    {
        $this->checkRateLimit();

        $fullUrl = $this->baseUrl.$endpoint;
        $oauthHeader = $this->generateOAuthHeader('PUT', $fullUrl, []);

        try {
            $response = $this->client->put($endpoint, [
                'json' => $data,
                'headers' => [
                    'Authorization' => $oauthHeader,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'BrickStore/1.0 (Laravel)',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);

            if (isset($responseData['meta']['code']) && $responseData['meta']['code'] !== 200) {
                throw new \Exception('BrickLink API Error: '.$responseData['meta']['message']);
            }

            $this->incrementRateLimit();

            return $responseData['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('BrickLink API PUT Error', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit(): void
    {
        $key = 'bricklink_rate_limit_'.$this->store->id;
        $count = Cache::get($key, 0);

        if ($count >= $this->rateLimit) {
            throw new \Exception('BrickLink API rate limit exceeded for today');
        }
    }

    /**
     * Increment rate limit counter
     */
    protected function incrementRateLimit(): void
    {
        $key = 'bricklink_rate_limit_'.$this->store->id;
        $count = Cache::get($key, 0);
        $expiresAt = now()->endOfDay();

        Cache::put($key, $count + 1, $expiresAt);
    }

    /**
     * Get all orders
     */
    public function getOrders(array $params = []): array
    {
        return $this->get('/orders', $params);
    }

    /**
     * Get single order
     */
    public function getOrder(string $orderId): array
    {
        Log::debug('Getting BrickLink API order', ['orderId' => $orderId]);

        return $this->get("/orders/{$orderId}");
    }

    /**
     * Get order items
     */
    public function getOrderItems(string $orderId): array
    {
        return $this->get("/orders/{$orderId}/items");
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(string $orderId, string $status): array
    {
        return $this->put("/orders/{$orderId}/status", [
            'field' => 'status',
            'value' => $status,
        ]);
    }

    /**
     * Get order messages
     */
    public function getOrderMessages(string $orderId): array
    {
        return $this->get("/orders/{$orderId}/messages");
    }

    /**
     * Send message to buyer
     */
    public function sendOrderMessage(string $orderId, string $subject, string $body): array
    {
        return $this->put("/orders/{$orderId}/messages", [
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Get current rate limit usage
     */
    public function getRateLimitUsage(): int
    {
        $key = 'bricklink_rate_limit_'.$this->store->id;

        return Cache::get($key, 0);
    }

    /**
     * Get remaining rate limit
     */
    public function getRateLimitRemaining(): int
    {
        return $this->rateLimit - $this->getRateLimitUsage();
    }
}
