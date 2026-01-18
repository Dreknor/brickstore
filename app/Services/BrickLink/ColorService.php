<?php

namespace App\Services\BrickLink;

use App\Models\Color;
use Illuminate\Support\Facades\Log;

class ColorService
{
    public function __construct(
        protected BrickLinkService $brickLinkService
    ) {}

    /**
     * Sync all colors from BrickLink API to local database
     *
     * @param  \App\Models\Store|null  $store  Optional store (not required for color API)
     * @return array Statistics about the sync
     */
    public function syncColorsFromBrickLink($store = null): array
    {
        try {

            // If no store provided, get any store to use for API authentication
            if (!$store) {
                $store = \App\Models\Store::first();
                if (!$store) {
                    throw new \Exception('No store available for API authentication');
                }
            }

            // Fetch all colors from BrickLink API
            $colors = $this->brickLinkService->fetchColors($store);

            if (empty($colors)) {
                Log::warning('No colors returned from BrickLink API');
                return [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => [],
                ];
            }

            $created = 0;
            $updated = 0;
            $errors = [];

            foreach ($colors as $blColor) {
                try {
                    $data = $this->mapBrickLinkToColor($blColor);


                    // Prüfe ob Daten valide sind
                    if (!isset($data['color_id']) || $data['color_id'] === null) {
                        Log::warning('Skipping color with no ID', ['bl_color' => $blColor]);
                        $errors[] = [
                            'color_id' => $blColor['id'] ?? 'unknown',
                            'error' => 'No color_id in mapped data',
                        ];
                        continue;
                    }

                    $corlor = Color::updateOrCreate(
                        ['color_id' => $data['color_id']],
                        $data
                    );


                    if ($color->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();

                    $errors[] = [
                        'color_id' => $blColor['id'] ?? null,
                        'error' => $errorMsg,
                    ];
                }
            }

            return [
                'total' => count($colors),
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('Color sync failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Map BrickLink color data to Color model attributes
     *
     * BrickLink API returns:
     * {
     *   "id": 11,
     *   "name": "Black",
     *   "color": "1E1E1E",
     *   "type": "Solid"
     * }
     */
    protected function mapBrickLinkToColor(array $blColor): array
    {


        $mapped = [
            'color_id' => $blColor['color_id'] ?? null,
            'color_name' => $blColor['color_name'] ?? null,
            'color_code' => $blColor['color_code'] ?? null,
            'color_type' => $blColor['color_type'] ?? null,
        ];

        // Log mapping für debugging
        \Log::debug('Mapping BrickLink color to model', [
            'input' => $blColor,
            'output' => $mapped,
        ]);

        return $mapped;
    }

    /**
     * Get all colors sorted by name
     */
    public function getAllColors()
    {
        return Color::orderBy('color_name')->get();
    }

    /**
     * Get color by BrickLink color ID
     */
    public function getColorById(int $colorId): ?Color
    {
        return Color::byColorId($colorId)->first();
    }

    /**
     * Get colors by type (e.g., "Transparent", "Metallic")
     */
    public function getColorsByType(string $type)
    {
        return Color::where('color_type', $type)->orderBy('color_name')->get();
    }

    /**
     * Search colors by name
     */
    public function searchColors(string $query)
    {
        return Color::byName($query)->orderBy('color_name')->get();
    }

    /**
     * Get solid colors only
     */
    public function getSolidColors()
    {
        return Color::where('color_type', 'Solid')->orderBy('color_name')->get();
    }

    /**
     * Get transparent colors only
     */
    public function getTransparentColors()
    {
        return Color::where('color_type', 'like', '%Transparent%')
            ->orderBy('color_name')
            ->get();
    }

    /**
     * Get metallic colors only
     */
    public function getMetallicColors()
    {
        return Color::where('color_type', 'like', '%Metallic%')
            ->orderBy('color_name')
            ->get();
    }
}

