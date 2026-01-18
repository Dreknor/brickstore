<?php

declare(strict_types=1);

namespace App\Services\BrickLink;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrickLinkService
{
    private const API_BASE_URL = 'https://api.bricklink.com/api/store/v1';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = 1; // seconds

    /**
     * Fetch orders from BrickLink API
     *
     * @param  string  $status  Order status filter (placed, paid, etc.)
     *
     * @throws \Exception
     */
    public function fetchOrders(Store $store, string $status = ''): array
    {
        $url = self::API_BASE_URL.'/orders';

        $params = [];
        if (! empty($status)) {
            $params['status'] = $status;
        }

        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        $response = $this->makeRequest('GET', $url, $store);

        return $response['data'] ?? [];
    }

    /**
     * Fetch a specific order by ID
     *
     * @param  string  $orderId  BrickLink Order ID
     *
     * @throws \Exception
     */
    public function fetchOrder(Store $store, string $orderId): array
    {
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId);

        $response = $this->makeRequest('GET', $url, $store);

        Log::debug('Fetched BrickLink order data', [
            'orderId' => $orderId,
            'response' => $response,
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Fetch items for a specific order by ID
     *
     * @param  string  $orderId  BrickLink Order ID
     * @return array List of order items
     *
     * @throws \Exception
     */
    public function fetchOrderItems(Store $store, string $orderId): array
    {
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId).'/items';

        Log::info('Fetching BrickLink order items', [
            'orderId' => $orderId,
        ]);

        $response = $this->makeRequest('GET', $url, $store);

        $items = $response['data'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        // BrickLink API returns items in a nested array: [[item1, item2, ...]]
        // We need to unwrap the first level
        if (count($items) === 1 && is_array($items[0])) {
            $items = $items[0];
        }

        Log::info('Fetched BrickLink order items', [
            'orderId' => $orderId,
            'itemCount' => count($items),
            'itemsPreview' => array_slice($items, 0, 2),
        ]);

        return $items;
    }

    /**
     * Fetch messages/remarks for a specific order
     * Note: BrickLink may not have a dedicated /messages endpoint.
     * This method attempts to fetch messages, but may fall back to remarks field.
     * If remarks contain timestamped messages, they are parsed into separate entries.
     * API: GET /orders/{order_id}/messages (if available) or GET /orders/{order_id}
     *
     * @param  string  $orderId  BrickLink Order ID
     * @return array List of messages or order remarks
     *
     * @throws \Exception
     */
    public function fetchOrderMessages(Store $store, string $orderId): array
    {
        // First, try the /messages endpoint
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId).'/messages';

        Log::info('Fetching BrickLink order messages', [
            'orderId' => $orderId,
            'url' => $url,
        ]);

        try {
            $response = $this->makeRequest('GET', $url, $store);

            return $response['data'] ?? [];
        } catch (\Exception $e) {
            // If /messages endpoint doesn't exist, fall back to getting order remarks
            if (str_contains($e->getMessage(), 'INVALID_URI') || str_contains($e->getMessage(), '404')) {
                Log::info('Messages endpoint not available, fetching order remarks instead', [
                    'orderId' => $orderId,
                ]);

                // Fetch the full order to get remarks
                $order = $this->fetchOrder($store, $orderId);
                $remarks = $order['remarks'] ?? '';

                // Parse remarks into message entries
                return $this->parseRemarksToMessages($remarks, $order);
            }

            // Re-throw other exceptions
            throw $e;
        }
    }

    /**
     * Send a message for a specific order
     * Note: BrickLink API doesn't have a direct POST /messages endpoint.
     * Instead, we use PUT /orders/{order_id} with the 'remarks' field.
     * Messages are appended with timestamp to preserve history.
     * API: PUT /orders/{order_id}
     *
     * @param  string  $orderId  BrickLink Order ID
     * @param  string  $message  Message text
     * @return array Updated order data
     *
     * @throws \Exception
     */
    public function sendOrderMessage(Store $store, string $orderId, string $message): array
    {
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId);

        Log::info('Sending BrickLink order message via remarks field', [
            'orderId' => $orderId,
            'messageLength' => strlen($message),
        ]);

        // Fetch current order to get existing remarks
        try {
            $currentOrder = $this->fetchOrder($store, $orderId);
            $existingRemarks = $currentOrder['remarks'] ?? '';
        } catch (\Exception $e) {
            Log::warning('Could not fetch existing remarks, proceeding with new message only', [
                'orderId' => $orderId,
                'error' => $e->getMessage(),
            ]);
            $existingRemarks = '';
        }

        // Append new message with timestamp
        $timestamp = now()->format('Y-m-d H:i:s');
        $separator = $existingRemarks ? "\n\n" : '';
        $newRemarks = $existingRemarks.$separator.'--- '.$timestamp." ---\n".$message;

        // BrickLink expects the message in the 'remarks' field
        $data = [
            'remarks' => $newRemarks,
        ];

        Log::debug('Appending message to existing remarks', [
            'orderId' => $orderId,
            'existingLength' => strlen($existingRemarks),
            'newLength' => strlen($newRemarks),
        ]);

        $response = $this->makeRequest('PUT', $url, $store, $data);

        return $response['data'] ?? [];
    }

    /**
     * Fetch feedback for a specific order
     * API: GET /orders/{order_id}/feedback
     *
     * @param  string  $orderId  BrickLink Order ID
     * @return array Feedback data (from buyer and to buyer)
     *
     * @throws \Exception
     */
    public function fetchOrderFeedback(Store $store, string $orderId): array
    {
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId).'/feedback';

        Log::info('Fetching BrickLink order feedback', [
            'orderId' => $orderId,
            'url' => $url,
        ]);

        $response = $this->makeRequest('GET', $url, $store);

        // Log the complete response for debugging
        Log::debug('BrickLink Feedback Response', [
            'orderId' => $orderId,
            'response' => $response,
            'data' => $response['data'] ?? null,
            'dataType' => gettype($response['data'] ?? null),
        ]);

        $data = $response['data'] ?? [];

        // Log processed data
        Log::info('Processed feedback data', [
            'orderId' => $orderId,
            'hasFrom' => isset($data['from']),
            'hasTo' => isset($data['to']),
            'isArray' => is_array($data),
            'dataKeys' => is_array($data) ? array_keys($data) : null,
        ]);

        return $data;
    }

    /**
     * Post feedback for a specific order
     * API: POST /orders/{order_id}/feedback
     *
     * @param  string  $orderId  BrickLink Order ID
     * @param  int  $rating  Rating (0-2: 0=Praise, 1=Neutral, 2=Complaint)
     * @param  string  $comment  Feedback comment
     * @return array Posted feedback
     *
     * @throws \Exception
     */
    public function postOrderFeedback(Store $store, string $orderId, int $rating, string $comment): array
    {
        $url = self::API_BASE_URL.'/feedback';

        Log::info('Posting BrickLink order feedback', [
            'orderId' => $orderId,
            'rating' => $rating,
        ]);

        $data = [
            'rating' => $rating,
            'comment' => $comment,
            'order_id' => $orderId,
        ];

        $response = $this->makeRequest('POST', $url, $store, $data);

        return $response['data'] ?? [];
    }

    /**
     * Update order shipping information (including tracking number)
     * API: PUT /orders/{order_id}
     *
     * @param  string  $orderId  BrickLink Order ID
     * @param  array  $shippingData  Shipping data (tracking_no, tracking_link, etc.)
     * @return array Updated order data
     *
     * @throws \Exception
     */
    public function updateOrderShipping(Store $store, string $orderId, array $shippingData): array
    {
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId);

        Log::info('Updating BrickLink order shipping', [
            'orderId' => $orderId,
            'hasTrackingNo' => isset($shippingData['tracking_no']),
            'hasTrackingLink' => isset($shippingData['tracking_link']),
        ]);

        $data = [
            'shipping' => $shippingData,
        ];

        $response = $this->makeRequest('PUT', $url, $store, $data);

        return $response['data'] ?? [];
    }

    /**
     * Update order status
     * API: PUT /orders/{order_id}
     *
     * @param  string  $orderId  BrickLink Order ID
     * @param  string  $status  New status (e.g., PAID, PACKED, SHIPPED, etc.)
     * @return array Updated order data
     *
     * @throws \Exception
     */
    public function updateOrderStatus(Store $store, string $orderId, string $status): array
    {
        $url = self::API_BASE_URL.'/orders/'.urlencode($orderId).'/status';

        Log::info('Updating BrickLink order status', [
            'orderId' => $orderId,
            'status' => $status,
            'url' => $url,
        ]);

        $data = [
            'field' => 'status',
            'value' => $status,
        ];

        Log::debug('BrickLink status update payload', [
            'payload' => $data,
        ]);

        $response = $this->makeRequest('PUT', $url, $store, $data);

        Log::info('BrickLink status update response', [
            'orderId' => $orderId,
            'requestedStatus' => $status,
            'response' => $response,
            'returnedStatus' => $response['status'] ?? 'not found',
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Fetch catalog item image URL
     * API: GET /items/{type}/{no}/images/{color_id}
     *
     * @param  string  $type  Item type (PART, MINIFIG, SET, etc.)
     * @param  string  $no  Item number
     * @param  int  $colorId  Color ID (0 for no color)
     * @return array Image data with thumbnail_url
     *
     * @throws \Exception
     */
    public function fetchCatalogItemImage(Store $store, string $type, string $no, int $colorId = 0): array
    {
        $url = self::API_BASE_URL.'/items/'.urlencode($type).'/'.urlencode($no).'/images/'.$colorId;

        Log::debug('Fetching BrickLink catalog item image', [
            'type' => $type,
            'no' => $no,
            'colorId' => $colorId,
            'url' => $url,
        ]);

        $response = $this->makeRequest('GET', $url, $store);

        Log::debug('BrickLink catalog image response', [
            'type' => $type,
            'no' => $no,
            'colorId' => $colorId,
            'response' => $response,
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Fetch all available colors from BrickLink API
     * API: GET /colors
     *
     * @return array List of colors
     *
     * @throws \Exception
     */
    public function fetchColors(Store $store): array
    {
        $url = self::API_BASE_URL.'/colors';

        Log::info('Fetching BrickLink colors from API');

        $response = $this->makeRequest('GET', $url, $store);

        $colors = $response['data'] ?? [];

        Log::info('Fetched BrickLink colors', [
            'colorCount' => count($colors),
        ]);

        return $colors;
    }

    /**
     * Fetch all inventory items from BrickLink
     * API: GET /inventories
     *
     * @param array $params Optional query parameters (item_type, status, category_id)
     * @return array List of inventory items
     * @throws \Exception
     */
    public function fetchInventories(Store $store, array $params = []): array
    {
        $url = self::API_BASE_URL . '/inventories';

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        Log::info('Fetching BrickLink inventories', [
            'params' => $params,
        ]);

        $response = $this->makeRequest('GET', $url, $store);

        $items = $response['data'] ?? [];

        Log::info('Fetched BrickLink inventories', [
            'itemCount' => count($items),
        ]);

        return $items;
    }

    /**
     * Fetch a single inventory item by ID
     * API: GET /inventories/{inventory_id}
     *
     * @param int $inventoryId BrickLink Inventory ID
     * @return array Inventory item data
     * @throws \Exception
     */
    public function fetchInventory(Store $store, int $inventoryId): array
    {
        $url = self::API_BASE_URL . '/inventories/' . $inventoryId;

        Log::info('Fetching BrickLink inventory item', [
            'inventoryId' => $inventoryId,
        ]);

        $response = $this->makeRequest('GET', $url, $store);

        return $response['data'] ?? [];
    }

    /**
     * Create a new inventory item
     * API: POST /inventories
     *
     * @param array $inventoryData Inventory item data
     * @return array Created inventory item
     * @throws \Exception
     */
    public function createInventory(Store $store, array $inventoryData): array
    {
        $url = self::API_BASE_URL . '/inventories';

        Log::info('Creating BrickLink inventory item', [
            'item_no' => $inventoryData['item']['no'] ?? null,
            'item_type' => $inventoryData['item']['type'] ?? null,
        ]);

        $response = $this->makeRequest('POST', $url, $store, $inventoryData);

        Log::info('Created BrickLink inventory item', [
            'inventoryId' => $response['data']['inventory_id'] ?? null,
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Update an existing inventory item
     * API: PUT /inventories/{inventory_id}
     *
     * @param int $inventoryId BrickLink Inventory ID
     * @param array $inventoryData Updated inventory data
     * @return array Updated inventory item
     * @throws \Exception
     */
    public function updateInventory(Store $store, int $inventoryId, array $inventoryData): array
    {
        $url = self::API_BASE_URL . '/inventories/' . $inventoryId;

        Log::info('Updating BrickLink inventory item', [
            'inventoryId' => $inventoryId,
        ]);

        $response = $this->makeRequest('PUT', $url, $store, $inventoryData);

        return $response['data'] ?? [];
    }

    /**
     * Delete an inventory item
     * API: DELETE /inventories/{inventory_id}
     *
     * @param int $inventoryId BrickLink Inventory ID
     * @return bool Success status
     * @throws \Exception
     */
    public function deleteInventory(Store $store, int $inventoryId): bool
    {
        $url = self::API_BASE_URL . '/inventories/' . $inventoryId;

        Log::info('Deleting BrickLink inventory item', [
            'inventoryId' => $inventoryId,
        ]);

        $response = $this->makeRequest('DELETE', $url, $store);

        return isset($response['meta']['code']) && $response['meta']['code'] === 200;
    }

    /**
     * Fetch catalog item details
     * API: GET /items/{type}/{no}
     *
     * @param string $type Item type (PART, SET, MINIFIG, etc.)
     * @param string $no Item number
     * @return array Catalog item data
     * @throws \Exception
     */
    public function fetchCatalogItem(Store $store, string $type, string $no): array
    {
        $url = self::API_BASE_URL . '/items/' . urlencode(strtoupper($type)) . '/' . urlencode($no);

        Log::debug('Fetching BrickLink catalog item', [
            'type' => $type,
            'no' => $no,
        ]);

        $response = $this->makeRequest('GET', $url, $store);

        return $response['data'] ?? [];
    }

    /**
     * Fetch price guide for a catalog item
     * API: GET /items/{type}/{no}/price_guide
     *
     * @param string $type Item type (PART, SET, MINIFIG, etc.)
     * @param string $no Item number
     * @param string $condition Item condition (N for new, U for used)
     * @return array Price guide data (avg_price, min_price, max_price, qty_sold)
     * @throws \Exception
     */
    public function fetchPriceGuide(Store $store, string $type, string $no, string $condition = 'N'): array
    {
        $url = self::API_BASE_URL . '/items/' . urlencode(strtoupper($type)) . '/' . urlencode($no) . '/price';

        // Add condition parameter
        $params = [
            'condition' => strtoupper($condition) === 'NEW' ? 'N' : 'U',
            'guide_type' => 'sold' // Use sold prices, not asking prices
        ];

        $url .= '?' . http_build_query($params);

        Log::info('Fetching BrickLink price guide', [
            'type' => $type,
            'no' => $no,
            'condition' => $condition,
            'url' => $url,
        ]);

        try {
            $response = $this->makeRequest('GET', $url, $store);

            $data = $response['data'] ?? [];

            Log::debug('Fetched price guide data', [
                'type' => $type,
                'no' => $no,
                'hasAvgPrice' => isset($data['avg_price']),
                'priceData' => $data,
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch price guide from BrickLink', [
                'type' => $type,
                'no' => $no,
                'condition' => $condition,
                'error' => $e->getMessage(),
            ]);

            // Return empty array instead of throwing, as price guide is optional
            return [];
        }
    }

    /**
     * Parse remarks field into message entries
     * Looks for timestamp separators (--- YYYY-MM-DD HH:MM:SS ---) to split messages
     *
     * @param  string  $remarks  Full remarks text
     * @param  array  $order  Order data for fallback date
     * @return array Array of message objects
     */
    private function parseRemarksToMessages(string $remarks, array $order): array
    {
        if (empty($remarks)) {
            return [];
        }

        $messages = [];

        // Pattern to match timestamp separators: --- 2025-12-04 14:30:00 ---
        $pattern = '/--- (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) ---\n/';

        // Split by timestamp pattern
        $parts = preg_split($pattern, $remarks, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (count($parts) === 1) {
            // No timestamps found, return entire remarks as one message
            $messages[] = [
                'subject' => 'Order Remarks',
                'body' => trim($remarks),
                'date_sent' => $order['date_ordered'] ?? null,
                'from' => 'seller',
            ];
        } else {
            // Parse timestamped messages
            // $parts[0] = text before first timestamp (if any)
            // $parts[1] = first timestamp
            // $parts[2] = first message
            // $parts[3] = second timestamp
            // $parts[4] = second message
            // etc.

            // Add initial text if exists (before any timestamp)
            if (! empty(trim($parts[0]))) {
                $messages[] = [
                    'subject' => 'Order Remarks',
                    'body' => trim($parts[0]),
                    'date_sent' => $order['date_ordered'] ?? null,
                    'from' => 'seller',
                ];
            }

            // Process timestamped messages
            for ($i = 1; $i < count($parts); $i += 2) {
                if (isset($parts[$i]) && isset($parts[$i + 1])) {
                    $timestamp = $parts[$i];
                    $messageText = trim($parts[$i + 1]);

                    if (! empty($messageText)) {
                        $messages[] = [
                            'subject' => 'Message',
                            'body' => $messageText,
                            'date_sent' => $timestamp,
                            'from' => 'seller',
                        ];
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Make an authenticated request to BrickLink API
     *
     * @param  string  $method  HTTP method
     * @param  string  $url  Full URL
     * @param  array  $data  Request body data (for POST/PUT)
     *
     * @throws \Exception
     */
    private function makeRequest(string $method, string $url, Store $store, array $data = []): array
    {
        $attempt = 0;
        $lastException = null;

        $credentials = [
            'consumer_key' => $store->bl_consumer_key,
            'consumer_secret' => $store->bl_consumer_secret,
            'token_value' => $store->bl_token,
            'token_secret' => $store->bl_token_secret,
        ];

        // Validate credentials
        if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret']) ||
            empty($credentials['token_value']) || empty($credentials['token_secret'])) {
            throw new \Exception('Incomplete BrickLink OAuth credentials for store: '.$store->name);
        }

        while ($attempt < self::MAX_RETRIES) {
            try {
                $attempt++;

                // Generate OAuth1 signature
                $oauth = $this->generateOAuthHeader($method, $url, $credentials);

                $options = [
                    'headers' => [
                        'Authorization' => $oauth,
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'BrickStore/1.0 (Laravel App)',
                    ],
                    'timeout' => 60, // Increased timeout to 60 seconds for large inventory requests
                ];

                // Detailed request logging for debugging
                Log::debug('BrickLink API Request Details', [
                    'method' => $method,
                    'url' => $url,
                    'headers' => [
                        'Authorization' => $oauth,
                        'Content-Type' => $options['headers']['Content-Type'],
                        'User-Agent' => $options['headers']['User-Agent'],
                    ],
                    'timeout' => $options['timeout'],
                ]);

                if (! empty($data) && in_array($method, ['POST', 'PUT'])) {
                    $options['body'] = json_encode($data);
                    Log::debug('BrickLink API Request Body', [
                        'body' => $options['body'],
                    ]);
                }

                // Execute request
                $response = Http::withHeaders($options['headers'])
                    ->timeout($options['timeout'])
                    ->send($method, $url, ! empty($data) ? ['json' => $data] : []);

                $statusCode = $response->status();
                $body = $response->body();

                // Log response details
                Log::debug('BrickLink API Response Details', [
                    'statusCode' => $statusCode,
                    'bodyLength' => strlen($body),
                    'bodyPreview' => substr($body, 0, 500),
                ]);

                // HTTP 204 No Content is a success response with empty body
                if ($statusCode === 204) {
                    Log::debug('BrickLink API returned 204 No Content (success)');

                    return ['meta' => ['code' => 204, 'message' => 'OK_NO_CONTENT'], 'data' => null];
                }

                $result = $response->json();

                // Log complete response structure
                if ($result !== null) {
                    Log::debug('BrickLink API Response Parsed', [
                        'meta' => $result['meta'] ?? null,
                        'dataKeys' => isset($result['data']) ? array_keys((array) $result['data']) : null,
                        'dataCount' => is_array($result['data'] ?? null) ? count($result['data']) : null,
                    ]);
                } else {
                    Log::error('BrickLink API Response JSON parsing failed', [
                        'body' => $body,
                    ]);
                }

                // BrickLink returns HTTP 200 but puts the actual status in meta.code
                if (isset($result['meta']['code'])) {
                    $apiCode = (int) $result['meta']['code'];

                    // Check for authentication error
                    if ($apiCode === 401) {
                        $description = $result['meta']['description'] ?? '';
                        $message = $result['meta']['message'] ?? '';

                        Log::error('BrickLink API authentication failed', [
                            'url' => $url,
                            'apiCode' => $apiCode,
                            'description' => $description,
                            'message' => $message,
                        ]);

                        // Check for IP mismatch
                        if (str_contains($description, 'TOKEN_IP_MISMATCHED')) {
                            // Extract IP from description
                            preg_match('/IP: ([\d.]+)/', $description, $matches);
                            $currentIp = $matches[1] ?? 'unknown';

                            throw new \Exception(
                                "BrickLink API IP-Mismatch: Ihre aktuelle IP-Adresse ($currentIp) stimmt nicht mit der bei BrickLink registrierten IP überein. ".
                                'Bitte aktualisieren Sie die IP-Adresse in Ihren BrickLink API-Einstellungen unter https://www.bricklink.com/v2/api/register_consumer.page '.
                                'Weitere Informationen finden Sie in docs/BRICKLINK_IP_MISMATCH.md'
                            );
                        }

                        // Check for signature errors
                        if (str_contains($description, 'SIGNATURE_INVALID')) {
                            throw new \Exception('BrickLink API authentication failed: Ungültige Signatur. Bitte überprüfen Sie Ihre API-Credentials.');
                        }

                        // Check for unknown consumer key
                        if (str_contains($description, 'CONSUMER_KEY_UNKNOWN')) {
                            throw new \Exception(
                                "BrickLink API Fehler: Der Consumer Key ist bei BrickLink unbekannt.\n\n".
                                "Mögliche Ursachen:\n".
                                "1. Die API-Credentials sind bei BrickLink nicht registriert\n".
                                "2. Die Credentials wurden falsch eingegeben\n".
                                "3. Die Credentials sind abgelaufen oder wurden gelöscht\n\n".
                                "Lösungsschritte:\n".
                                "- Überprüfen Sie Ihre BrickLink API-Registrierung unter:\n".
                                "  https://www.bricklink.com/v2/api/register_consumer.page\n".
                                "- Stellen Sie sicher, dass die Credentials korrekt in den Store-Einstellungen eingegeben wurden\n".
                                "- Bei Problemen: siehe docs/BRICKLINK_CREDENTIALS_ERROR.md\n\n".
                                'Consumer Key: '.substr($credentials['consumer_key'] ?? '', 0, 10).'...'
                            );
                        }

                        // Check for unknown token
                        if (str_contains($description, 'TOKEN_VALUE_UNKNOWN')) {
                            throw new \Exception(
                                "BrickLink API Fehler: Der Token ist bei BrickLink unbekannt.\n\n".
                                "Bitte überprüfen Sie Ihre BrickLink API-Credentials unter:\n".
                                "https://www.bricklink.com/v2/api/register_consumer.page\n\n".
                                'Stellen Sie sicher, dass der Token Value korrekt eingegeben wurde.'
                            );
                        }

                        throw new \Exception('BrickLink API authentication failed: '.$description);
                    }

                    // Check for other errors
                    if ($apiCode !== 200 && $apiCode !== 204) {
                        throw new \Exception("BrickLink API error (code $apiCode): ".($result['meta']['description'] ?? $result['meta']['message'] ?? 'Unknown error'));
                    }
                }

                // Accept all 2xx status codes as success
                if ($statusCode >= 200 && $statusCode < 300) {
                    return $result;
                }

                // Handle rate limiting (429)
                if ($statusCode === 429) {
                    Log::warning('BrickLink API rate limit hit, retrying...', [
                        'attempt' => $attempt,
                        'url' => $url,
                    ]);

                    sleep(self::RETRY_DELAY * $attempt);

                    continue;
                }

                // Handle authentication errors (403)
                if ($statusCode === 403) {
                    $headers = $response->headers();

                    Log::error('BrickLink API returned 403 Forbidden', [
                        'url' => $url,
                        'statusCode' => $statusCode,
                        'responseBody' => substr($body, 0, 500),
                        'responseHeaders' => $headers,
                    ]);
                    throw new \Exception('BrickLink API returned 403 Forbidden. This may indicate IP blocking, invalid credentials, or API access restrictions.');
                }

                throw new \Exception("BrickLink API error: HTTP $statusCode - ".($result['message'] ?? 'Unknown error'));
            } catch (\Exception $e) {
                $lastException = $e;

                // Check if it's a 403 error
                if (str_contains($e->getMessage(), '403 Forbidden') || str_contains($e->getMessage(), 'Forbidden')) {
                    Log::error('BrickLink API returned 403 (caught in exception)', [
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]);

                    throw new \Exception("BrickLink API returned 403 Forbidden. Possible causes:\n".
                        "1. IP address is blocked or not whitelisted\n".
                        "2. API credentials are incorrect or expired\n".
                        "3. API access is not enabled for your account\n".
                        "4. Geographic restrictions apply\n".
                        'Please verify your BrickLink API settings at: https://www.bricklink.com/v2/api/register_consumer.page');
                }

                // Don't retry on authentication errors
                if (str_contains($e->getMessage(), 'authentication failed')) {
                    throw $e;
                }

                if ($attempt < self::MAX_RETRIES) {
                    Log::warning('BrickLink API request failed, retrying...', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'url' => $url,
                    ]);

                    sleep(self::RETRY_DELAY * $attempt);
                }
            }
        }

        Log::error('BrickLink API request failed after all retries', [
            'url' => $url,
            'error' => $lastException?->getMessage(),
        ]);

        throw $lastException ?? new \Exception('BrickLink API request failed');
    }

    /**
     * Generate OAuth 1.0a authorization header
     *
     * @param  string  $method  HTTP method
     * @param  string  $url  Full URL
     * @param  array  $credentials  Must contain: consumer_key, consumer_secret, token_value, token_secret
     */
    private function generateOAuthHeader(string $method, string $url, array $credentials): string
    {
        // Validate credentials
        if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret']) ||
            empty($credentials['token_value']) || empty($credentials['token_secret'])) {
            Log::error('Invalid credentials passed to generateOAuthHeader', [
                'hasConsumerKey' => ! empty($credentials['consumer_key']),
                'hasConsumerSecret' => ! empty($credentials['consumer_secret']),
                'hasTokenValue' => ! empty($credentials['token_value']),
                'hasTokenSecret' => ! empty($credentials['token_secret']),
            ]);
            throw new \Exception('Incomplete OAuth credentials');
        }

        $oauth = [
            'oauth_consumer_key' => $credentials['consumer_key'],
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $credentials['token_value'],
            'oauth_version' => '1.0',
        ];

        // Generate signature base string BEFORE adding signature
        $baseString = $this->generateSignatureBaseString($method, $url, $oauth);

        // BrickLink uses standard OAuth 1.0 signing: signing_key = consumer_secret&token_secret
        $signingKey = rawurlencode($credentials['consumer_secret']).'&'.rawurlencode($credentials['token_secret']);
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $oauth['oauth_signature'] = $signature;

        // Debug logging
        Log::debug('OAuth Signature Generation', [
            'method' => $method,
            'url' => $url,
            'baseString' => $baseString,
            'signingKey' => substr($signingKey, 0, 10).'...',
            'signature' => $signature,
            'timestamp' => $oauth['oauth_timestamp'],
            'nonce' => $oauth['oauth_nonce'],
        ]);

        // Build header according to BrickLink documentation
        // Format: OAuth realm="", oauth_consumer_key="...", oauth_token="...", ...
        // Note: Only oauth_signature value should be URL-encoded in the header
        $headerParts = ['realm=""'];

        // OAuth parameters must be in a specific order as shown in BrickLink docs
        $headerParts[] = 'oauth_consumer_key="'.$oauth['oauth_consumer_key'].'"';
        $headerParts[] = 'oauth_token="'.$oauth['oauth_token'].'"';
        $headerParts[] = 'oauth_signature_method="'.$oauth['oauth_signature_method'].'"';
        $headerParts[] = 'oauth_signature="'.rawurlencode($oauth['oauth_signature']).'"';
        $headerParts[] = 'oauth_timestamp="'.$oauth['oauth_timestamp'].'"';
        $headerParts[] = 'oauth_nonce="'.$oauth['oauth_nonce'].'"';
        $headerParts[] = 'oauth_version="'.$oauth['oauth_version'].'"';

        $authHeader = 'OAuth '.implode(', ', $headerParts);

        Log::debug('OAuth Authorization Header', [
            'header' => $authHeader,
        ]);

        return $authHeader;
    }

    /**
     * Generate OAuth signature base string
     *
     * According to OAuth 1.0 spec (RFC 5849 Section 3.4.1):
     * Base String = HTTP-Method & "&" & base-url & "&" & normalized-parameters
     */
    private function generateSignatureBaseString(string $method, string $url, array $params): string
    {
        // Parse URL and query parameters
        $urlParts = parse_url($url);
        $baseUrl = $urlParts['scheme'].'://'.$urlParts['host'];

        // Add port if non-standard
        if (isset($urlParts['port']) &&
            ! (($urlParts['scheme'] === 'http' && $urlParts['port'] == 80) ||
              ($urlParts['scheme'] === 'https' && $urlParts['port'] == 443))) {
            $baseUrl .= ':'.$urlParts['port'];
        }

        $baseUrl .= $urlParts['path'];

        // Merge query parameters with OAuth parameters
        $allParams = $params;
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
            $allParams = array_merge($allParams, $queryParams);
        }

        // Sort parameters alphabetically by key (byte order)
        ksort($allParams);

        // Build normalized parameter string
        // Each key and value must be percent-encoded separately, then joined with =
        $paramParts = [];
        foreach ($allParams as $key => $value) {
            $paramParts[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }
        $paramString = implode('&', $paramParts);

        // Build the signature base string
        // Format: METHOD&url&params (each component is percent-encoded)
        $baseString = strtoupper($method).'&'.rawurlencode($baseUrl).'&'.rawurlencode($paramString);

        // Log the components for debugging
        Log::debug('Signature Base String Components', [
            'method' => strtoupper($method),
            'baseUrl' => $baseUrl,
            'paramString' => $paramString,
            'baseString' => $baseString,
        ]);

        return $baseString;
    }

    /**
     * Generate a random nonce
     */
    private function generateNonce(): string
    {
        return md5(microtime().mt_rand());
    }
}
