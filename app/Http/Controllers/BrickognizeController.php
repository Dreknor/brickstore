<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentifyItemRequest;
use App\Http\Requests\QuickAddInventoryRequest;
use App\Models\BrickognizeIdentification;
use App\Models\Inventory;
use App\Services\BrickognizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BrickognizeController extends Controller
{
    protected BrickognizeService $brickognizeService;

    public function __construct(BrickognizeService $brickognizeService)
    {
        $this->brickognizeService = $brickognizeService;
    }

    /**
     * Identifiziert ein LEGO-Teil anhand eines Bildes
     */
    public function identify(IdentifyItemRequest $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Kein Store gefunden. Bitte richten Sie zuerst Ihren Store ein.',
            ], 404);
        }

        // Bild temporär speichern
        $image = $request->file('image');
        $path = $image->store('brickognize/temp', 'local');

        // API-Anfrage
        $result = $this->brickognizeService->identify($image);

        if (!$result['success']) {
            // Temporäres Bild löschen
            Storage::disk('local')->delete($path);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Identifikation fehlgeschlagen',
            ], 500);
        }

        // Identifikation in DB speichern
        $topResult = $result['data'][0] ?? null;

        $identification = BrickognizeIdentification::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'image_path' => $path,
            'original_filename' => $image->getClientOriginalName(),
            'identified_item_no' => $topResult['item_no'] ?? null,
            'identified_item_name' => $topResult['item_name'] ?? null,
            'identified_color_id' => $topResult['color_id'] ?? null,
            'identified_color_name' => $topResult['color_name'] ?? null,
            'identified_item_type' => $topResult['item_type'] ?? 'PART',
            'confidence_score' => $topResult['confidence'] ?? 0,
            'api_response' => $result['data'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teil erfolgreich identifiziert',
            'data' => $result['data'],  // Array der Erkennungen
            'identification_id' => $identification->id,
            'top_result' => $topResult,
        ]);
    }

    /**
     * Sucht erkanntes Teil im eigenen Inventar
     */
    public function searchInventory(Request $request): JsonResponse
    {
        $request->validate([
            'identification_id' => 'required|exists:brickognize_identifications,id',
        ]);

        $user = Auth::user();
        $identification = BrickognizeIdentification::find($request->identification_id);

        // Prüfe ob Benutzer berechtigt ist
        if ($identification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nicht berechtigt',
            ], 403);
        }

        if (!$identification->identified_item_no) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Item-Nummer gefunden',
            ], 400);
        }

        // Suche im Inventar (alle Varianten)
        $inventoryItems = Inventory::where('store_id', $user->store->id)
            ->where('item_no', $identification->identified_item_no)
            ->when($identification->identified_color_id, function ($query) use ($identification) {
                return $query->where('color_id', $identification->identified_color_id);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'found' => $inventoryItems->count() > 0,
                'count' => $inventoryItems->count(),
                'items' => $inventoryItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_no' => $item->item_no,
                        'color_name' => $item->color_name,
                        'condition' => $item->new_or_used === 'N' ? 'N' : 'U',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'description' => $item->description,
                        'remarks' => $item->remarks ?? '',
                        'completeness' => $item->completeness ?? '—',
                    ];
                }),
            ],
        ]);
    }

    /**
     * Fügt X Teile zu bestehendem Inventar hinzu
     */
    public function quickAdd(QuickAddInventoryRequest $request): JsonResponse
    {
        $user = Auth::user();
        $inventory = Inventory::find($request->inventory_id);

        // Prüfe Berechtigung
        if ($inventory->store_id !== $user->store->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nicht berechtigt',
            ], 403);
        }

        // Menge hinzufügen
        $oldQuantity = $inventory->quantity;
        $inventory->quantity += $request->quantity;
        $inventory->save();

        // Optional: Identifikation aktualisieren
        if ($request->has('identification_id')) {
            $identification = BrickognizeIdentification::find($request->identification_id);
            if ($identification && $identification->user_id === $user->id) {
                $identification->update([
                    'action_taken' => 'quick_add',
                    'inventory_id' => $inventory->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$request->quantity} Teile hinzugefügt",
            'data' => [
                'old_quantity' => $oldQuantity,
                'new_quantity' => $inventory->quantity,
                'added' => $request->quantity,
            ],
        ]);
    }

    /**
     * Erstellt neuen Artikel aus Identifikationsdaten
     */
    public function createFromIdentification(Request $request): JsonResponse
    {
        $request->validate([
            'identification_id' => 'required|exists:brickognize_identifications,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'new_or_used' => 'required|in:N,U',
            'description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $identification = BrickognizeIdentification::find($request->identification_id);

        // Prüfe Berechtigung
        if ($identification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nicht berechtigt',
            ], 403);
        }

        // Erstelle Inventar-Eintrag
        $inventory = Inventory::create([
            'store_id' => $user->store->id,
            'item_no' => $identification->identified_item_no,
            'item_type' => $identification->identified_item_type,
            'color_id' => $identification->identified_color_id,
            'color_name' => $identification->identified_color_name,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'new_or_used' => $request->new_or_used,
            'description' => $request->description ?? $identification->identified_item_name,
        ]);

        // Identifikation aktualisieren
        $identification->update([
            'action_taken' => 'created_new',
            'inventory_id' => $inventory->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artikel erfolgreich erstellt',
            'data' => [
                'inventory_id' => $inventory->id,
                'item_no' => $inventory->item_no,
            ],
        ]);
    }

    /**
     * Zeigt Historie der Identifikationen
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();

        $identifications = BrickognizeIdentification::where('user_id', $user->id)
            ->with('inventory')
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $identifications,
        ]);
    }
}

