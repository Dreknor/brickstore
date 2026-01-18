<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class BrickognizeService
{
    protected Client $client;
    protected string $apiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.brickognize.api_url');
        $this->timeout = config('services.brickognize.timeout');

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Identifiziert ein LEGO-Teil anhand eines hochgeladenen Bildes
     *
     * @param UploadedFile|string $image UploadedFile oder Pfad zum Bild
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function identify($image): array
    {
        try {
            // Bild vorbereiten
            if ($image instanceof UploadedFile) {
                $imageContent = file_get_contents($image->getRealPath());
                $filename = $image->getClientOriginalName();
            } else {
                $imageContent = file_get_contents($image);
                $filename = basename($image);
            }

            // Mögliche API-Endpoints (versuche mehrere Varianten)
            $endpoints = [
                '/predict',
            ];

            $response = null;
            $lastError = null;

            foreach ($endpoints as $endpoint) {
                try {
                    Log::info('Brickognize: Versuche Endpoint', ['endpoint' => $endpoint]);

                    $response = $this->client->post($endpoint, [
                        'multipart' => [
                            [
                                'name' => 'query_image',
                                'contents' => $imageContent,
                                'filename' => $filename,
                            ],
                        ],
                    ]);

                    // Erfolg - unterbreche Schleife
                    Log::info('Brickognize: Erfolgreich mit Endpoint', ['endpoint' => $endpoint]);
                    break;
                } catch (GuzzleException $e) {
                    $lastError = $e;
                    Log::warning('Brickognize: Endpoint fehlgeschlagen', [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    // Versuche nächsten Endpoint
                    continue;
                }
            }

            if (!$response) {
                throw $lastError ?? new \Exception('Kein funktionierender Endpoint gefunden');
            }

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 && isset($body['items'])) {
                Log::info('Brickognize identification successful', [
                    'items_count' => count($body['items']),
                ]);

                return [
                    'success' => true,
                    'data' => $this->parseResponse($body),
                    'error' => null,
                ];
            }

            Log::warning('Brickognize API unexpected response', [
                'status' => $statusCode,
                'body' => $body,
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Unerwartete API-Antwort: HTTP ' . $statusCode,
            ];

        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            Log::error('Brickognize API error', [
                'message' => $message,
                'code' => $e->getCode(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);

            // Bessere Fehlermeldung für 404
            if (strpos($message, '404') !== false) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'API-Endpoint nicht gefunden (404). Bitte überprüfe die BRICKOGNIZE_API_URL Konfiguration.',
                ];
            }

            return [
                'success' => false,
                'data' => null,
                'error' => 'API-Fehler: ' . $message,
            ];
        } catch (\Exception $e) {
            Log::error('Brickognize identification error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Fehler bei der Identifikation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parst die API-Antwort und standardisiert das Format
     *
     * @param array $response
     * @return array
     */
    protected function parseResponse(array $response): array
    {
        $items = $response['items'] ?? [];

        // Wenn leeres Array oder nicht vorhanden, versuche andere Strukturen
        if (empty($items) && is_array($response)) {
            // Manche APIs können Ergebnisse direkt im Root haben
            Log::warning('Brickognize: Unerwartete Response-Struktur', ['response' => $response]);

            // Versuche, Ergebnisse direkt zu nehmen
            if (isset($response[0]) && is_array($response[0])) {
                $items = $response;
            }
        }

        $results = [];
        foreach ($items as $item) {
            // Handle verschiedene mögliche Feldnamen
            $confidence = $item['score'] ?? $item['confidence'] ?? 0;
            if (is_string($confidence)) {
                $confidence = (int)$confidence;
            }

            $result = [
                'item_no' => $item['id'] ?? $item['item_no'] ?? null,
                'item_name' => $item['name'] ?? $item['item_name'] ?? null,
                'confidence' => min(100, max(0, $confidence)),  // Normalisiere auf 0-100
                'color_id' => $item['color_id'] ?? $item['colorId'] ?? null,
                'color_name' => $item['color_name'] ?? $item['colorName'] ?? null,
                'item_type' => $this->mapItemType($item['type'] ?? $item['item_type'] ?? null),
                'thumbnail_url' => $item['thumbnail_url'] ?? $item['thumbnailUrl'] ?? null,
                'image_url' => $item['img_url'] ?? $item['image_url'] ?? $item['imageUrl'] ?? null,
                'raw_data' => $item, // Originaldaten für Debugging
            ];

            // Nur hinzufügen wenn mindestens item_no oder item_name vorhanden
            if ($result['item_no'] || $result['item_name']) {
                $results[] = $result;
            }
        }

        // Sortiere nach Confidence Score (höchster zuerst)
        usort($results, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        Log::info('Brickognize parseResponse', [
            'input_items_count' => count($items),
            'output_results_count' => count($results),
            'first_result' => $results[0] ?? null,
        ]);

        return $results;
    }

    /**
     * Mappt Brickognize Item-Type zu BrickLink Item-Type
     *
     * @param string|null $type
     * @return string
     */
    protected function mapItemType(?string $type): string
    {
        $mapping = [
            'part' => 'PART',
            'minifig' => 'MINIFIG',
            'set' => 'SET',
            'book' => 'BOOK',
            'gear' => 'GEAR',
            'catalog' => 'CATALOG',
            'instruction' => 'INSTRUCTION',
        ];

        return $mapping[strtolower($type ?? '')] ?? 'PART';
    }

    /**
     * Testet die API-Verbindung
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client->get('/');

            return [
                'success' => true,
                'message' => 'Verbindung zur Brickognize API erfolgreich',
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => 'Verbindungsfehler: ' . $e->getMessage(),
            ];
        }
    }
}

