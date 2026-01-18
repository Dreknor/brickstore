<div
    x-data="identificationResultModal"
    x-cloak
    @open-identification-result.window="handleOpen($event.detail)"
>
    <!-- Modal Container (nur wenn open=true) - OHNE Backdrop -->
    <template x-if="open">
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full max-h-[90vh] overflow-y-auto"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                @keydown.escape="close()"
                style="max-width: 900px;"
            >
                    <!-- Header -->
                    <div class="bg-green-50 dark:bg-green-900/20 px-6 py-4 border-b border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <x-fas-check-circle class="w-8 h-8 text-green-600" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Teil identifiziert!
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Confidence: <span x-text="Math.round((topResult?.confidence ?? 0) * 100) / 100 + '%'" class="font-medium"></span>
                                    </p>
                                </div>
                            </div>
                            <button @click="close()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <x-fas-times class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-6">
                        <!-- Bild -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">BrickLink Bild</h4>
                            <template x-if="topResult?.image_url || topResult?.thumbnail_url">
                                <img
                                    :src="topResult?.image_url || topResult?.thumbnail_url"
                                    class="w-full max-h-48 object-contain bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md"
                                    alt="BrickLink Bild"
                                >
                            </template>
                            <template x-if="!topResult?.image_url && !topResult?.thumbnail_url">
                                <div class="w-full h-32 bg-gray-100 dark:bg-gray-900 rounded-lg flex items-center justify-center">
                                    <p class="text-gray-400">Kein Bild verfügbar</p>
                                </div>
                            </template>
                        </div>

                        <!-- Teil-Informationen -->
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Teil-Nr</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono font-bold" x-text="topResult?.item_no || '—'"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Beschreibung</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100" x-text="topResult?.item_name || '—'"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Farbe</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100" x-text="topResult?.color_name || 'Keine'"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Kategorie</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100" x-text="topResult?.item_type || 'PART'"></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Inventar Status -->
                        <div class="mb-6">
                            <h4 class="text-xs font-medium text-gray-900 dark:text-gray-100 mb-3">In deinem Inventar:</h4>

                            <template x-if="loadingInventory">
                                <div class="flex justify-center py-4">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                </div>
                            </template>

                            <template x-if="!loadingInventory && inventoryItems.length === 0">
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <p class="text-yellow-700 dark:text-yellow-300 text-sm">Dieses Teil ist noch nicht in deinem Inventar vorhanden.</p>
                                </div>
                            </template>

                            <template x-if="!loadingInventory && inventoryItems.length > 0">
                                <div class="overflow-x-auto mb-4">
                                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600 text-xs">
                                        <thead class="bg-gray-100 dark:bg-gray-900">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Zustand</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Menge</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Preis</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Vollst.</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Bemerkung</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <template x-for="item in inventoryItems" :key="item.id">
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100 font-medium" x-text="item.condition === 'N' ? '🆕 Neu' : '♻️ Gebraucht'"></td>
                                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100" x-text="item.quantity"></td>
                                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100" x-text="(item.unit_price || 0) + ' €'"></td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400" x-text="item.completeness || '-'"></td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400 truncate max-w-xs" :title="item.remarks || ''" x-text="item.remarks || '-'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Quick-Add Mengen-Input -->
                                <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <label for="quick-add-qty" class="text-sm font-medium text-gray-700 dark:text-gray-300">Menge hinzufügen:</label>
                                    <input
                                        type="number"
                                        id="quick-add-qty"
                                        x-model.number="quickAddQuantity"
                                        min="1"
                                        max="9999"
                                        class="flex-1 px-3 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                        placeholder="z.B. 5"
                                    />
                                </div>
                            </template>
                        </div>

                        <!-- Erfolgs-Meldung -->
                        <template x-if="actionSuccess">
                            <div class="px-6 py-4 bg-green-50 dark:bg-green-900/20 border-t border-green-200 dark:border-green-800">
                                <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                                    <x-fas-check-circle class="w-5 h-5" />
                                    <p x-text="actionSuccess"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Fehler-Meldung -->
                        <template x-if="actionError">
                            <div class="px-6 py-4 bg-red-50 dark:bg-red-900/20 border-t border-red-200 dark:border-red-800">
                                <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                    <x-fas-exclamation-circle class="w-5 h-5" />
                                    <p x-text="actionError"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Footer -->
                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between gap-3">
                        <div class="flex gap-2">
                            <!-- Quick-Add Button (wenn Teile im Inventar) -->
                            <template x-if="!loadingInventory && inventoryItems.length > 0 && !actionSuccess">
                                <button
                                    @click="quickAddItem()"
                                    :disabled="actionLoading || !quickAddQuantity || quickAddQuantity < 1"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
                                >
                                    <template x-if="!actionLoading">
                                        <x-fas-plus class="w-4 h-4" />
                                        <span>Hinzufügen</span>
                                    </template>
                                    <template x-if="actionLoading">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                        <span>Wird hinzugefügt...</span>
                                    </template>
                                </button>
                            </template>

                            <!-- Create-Item Button (wenn KEINE Teile im Inventar) -->
                            <template x-if="!loadingInventory && inventoryItems.length === 0 && !actionSuccess">
                                <button
                                    @click="createNewItem()"
                                    :disabled="actionLoading"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
                                >
                                    <template x-if="!actionLoading">
                                        <x-fas-plus class="w-4 h-4" />
                                        <span>Teil inventarisieren</span>
                                    </template>
                                    <template x-if="actionLoading">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                        <span>Wird vorbereitet...</span>
                                    </template>
                                </button>
                            </template>
                        </div>

                        <button
                            @click="close()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium"
                        >
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('identificationResultModal', () => ({
        open: false,
        identificationId: null,
        topResult: null,
        allResults: [],
        loadingInventory: false,
        inventoryItems: [],
        selectedInventoryId: null,
        quickAddQuantity: 1,
        actionLoading: false,
        actionSuccess: null,
        actionError: null,

        handleOpen(data) {
            console.log('🎯 Modal opened with data:', data);
            this.identificationId = data.identification_id;
            this.topResult = data.top_result;
            this.allResults = data.results;
            this.open = true;

            console.log('✅ Modal is now open:', {
                identification_id: this.identificationId,
                top_result: this.topResult
            });

            // Suche Inventar
            this.searchInventory();
        },

        close() {
            console.log('🔴 Modal closed');
            this.open = false;
            this.reset();
        },

        reset() {
            this.identificationId = null;
            this.topResult = null;
            this.allResults = [];
            this.inventoryItems = [];
            this.selectedInventoryId = null;
            this.quickAddQuantity = 1;
            this.actionLoading = false;
            this.actionSuccess = null;
            this.actionError = null;
        },

        async searchInventory() {
            if (!this.identificationId) {
                console.warn('⚠️ No identification_id');
                this.loadingInventory = false;
                return;
            }

            this.loadingInventory = true;

            try {
                const response = await fetch('{{ route("brickognize.search-inventory") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        identification_id: this.identificationId
                    })
                });

                const result = await response.json();
                console.log('📦 Inventory search result:', result);

                if (result.success && result.data?.found) {
                    this.inventoryItems = result.data.items || [];
                    console.log('✅ Found', this.inventoryItems.length, 'inventory items');
                } else {
                    this.inventoryItems = [];
                    console.log('ℹ️ No inventory items found');
                }
            } catch (err) {
                console.error('❌ Error searching inventory:', err);
                this.inventoryItems = [];
            } finally {
                this.loadingInventory = false;
            }
        },

        async quickAddItem() {
            if (!this.quickAddQuantity || this.quickAddQuantity < 1) {
                this.actionError = 'Bitte geben Sie eine gültige Menge ein.';
                return;
            }

            if (this.inventoryItems.length === 0) {
                this.actionError = 'Keine Inventar-Einträge gefunden.';
                return;
            }

            this.actionLoading = true;
            this.actionError = null;
            this.actionSuccess = null;

            try {
                // Nutze den ersten Inventar-Eintrag
                const firstItem = this.inventoryItems[0];

                const response = await fetch('{{ route("brickognize.quick-add") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        inventory_id: firstItem.id,
                        quantity: this.quickAddQuantity,
                        identification_id: this.identificationId
                    })
                });

                const result = await response.json();
                console.log('✅ Quick-Add Response:', result);

                if (result.success) {
                    this.actionSuccess = `${this.quickAddQuantity} Teile erfolgreich hinzugefügt!`;

                    // Inventory aktualisieren
                    this.searchInventory();

                    // Menge zurücksetzen
                    this.quickAddQuantity = 1;

                    // Nach 3 Sekunden schließen
                    setTimeout(() => {
                        this.close();
                    }, 3000);
                } else {
                    this.actionError = result.message || 'Fehler beim Hinzufügen der Teile.';
                }
            } catch (err) {
                console.error('❌ Quick-Add Error:', err);
                this.actionError = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
            } finally {
                this.actionLoading = false;
            }
        },

        createNewItem() {
            if (!this.topResult) {
                this.actionError = 'Keine Identifikationsdaten verfügbar.';
                return;
            }

            this.actionLoading = true;

            // Baue Query-Parameter
            const params = new URLSearchParams({
                item_no: this.topResult.item_no || '',
                item_type: this.topResult.item_type || 'PART',
                color_id: this.topResult.color_id || '',
                color_name: this.topResult.color_name || '',
                item_name: this.topResult.item_name || '',
                identification_id: this.identificationId || ''
            });

            // Navigiere zum Create-Formular
            window.location.href = `{{ route('inventory.create') }}?${params.toString()}`;
        }
    }));
});
</script>
@endPushOnce

