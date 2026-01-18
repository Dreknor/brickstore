<x-layouts.app title="Inventar-Artikel bearbeiten">
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('inventory.show', $inventory) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fa-solid fa-edit mr-2 text-yellow-500"></i>
                {{ $inventory->item_no }} bearbeiten
            </h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
            Änderungen werden mit BrickLink synchronisiert
        </p>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg">
            <i class="fa-solid fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('inventory.update', $inventory) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information (Read-only) -->
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fa-solid fa-info-circle mr-2 text-blue-500"></i>
                Artikelinformationen (nicht editierbar)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block text-gray-500 dark:text-gray-400 mb-1">Artikel-Nr.</label>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $inventory->item_no }}</p>
                </div>
                <div>
                    <label class="block text-gray-500 dark:text-gray-400 mb-1">Typ</label>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $inventory->item_type }}</p>
                </div>
                @if($inventory->color_name)
                    <div>
                        <label class="block text-gray-500 dark:text-gray-400 mb-1">Farbe</label>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $inventory->color_name }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Editable Fields -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fa-solid fa-edit mr-2 text-blue-500"></i>
                Bearbeitbare Felder
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Menge
                    </label>
                    <input type="number"
                           name="quantity"
                           id="quantity"
                           value="{{ old('quantity', $inventory->quantity) }}"
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('quantity') border-red-500 @enderror">
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit Price -->
                <div>
                    <label for="unit_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Stückpreis (€)
                    </label>
                    <input type="number"
                           name="unit_price"
                           id="unit_price"
                           value="{{ old('unit_price', $inventory->unit_price) }}"
                           min="0"
                           step="0.001"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('unit_price') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Preis bis zu 3 Dezimalstellen
                    </p>
                    @error('unit_price')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bulk -->
                <div>
                    <label for="bulk" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bulk (Verkaufsmenge)
                    </label>
                    <input type="number"
                           name="bulk"
                           id="bulk"
                           value="{{ old('bulk', $inventory->bulk) }}"
                           min="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('bulk') border-red-500 @enderror">
                    @error('bulk')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sale Rate -->
                <div>
                    <label for="sale_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Rabatt (%)
                    </label>
                    <input type="number"
                           name="sale_rate"
                           id="sale_rate"
                           value="{{ old('sale_rate', $inventory->sale_rate) }}"
                           min="0"
                           max="100"
                           step="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('sale_rate') border-red-500 @enderror">
                    @error('sale_rate')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- My Cost -->
                <div>
                    <label for="my_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Einkaufspreis (€)
                    </label>
                    <input type="number"
                           name="my_cost"
                           id="my_cost"
                           value="{{ old('my_cost', $inventory->my_cost) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('my_cost') border-red-500 @enderror">
                    @error('my_cost')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- My Weight -->
                <div>
                    <label for="my_weight" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Gewicht (g)
                    </label>
                    <input type="number"
                           name="my_weight"
                           id="my_weight"
                           value="{{ old('my_weight', $inventory->my_weight) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('my_weight') border-red-500 @enderror">
                    @error('my_weight')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Beschreibung
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror"
                    >{{ old('description', $inventory->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remarks -->
                <div class="md:col-span-2">
                    <label for="remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notizen
                    </label>
                    <textarea name="remarks"
                              id="remarks"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('remarks') border-red-500 @enderror"
                    >{{ old('remarks', $inventory->remarks) }}</textarea>
                    @error('remarks')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Storage Options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fa-solid fa-warehouse mr-2 text-blue-500"></i>
                Lageroptionen
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Is Retain -->
                <div class="flex items-start">
                    <input type="hidden" name="is_retain" value="0">
                    <input type="checkbox"
                           name="is_retain"
                           id="is_retain"
                           value="1"
                           {{ old('is_retain', $inventory->is_retain) ? 'checked' : '' }}
                           class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                    <label for="is_retain" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Behalten</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Im Inventar behalten, auch wenn die Menge 0 erreicht
                        </p>
                    </label>
                </div>

                <!-- Is Stock Room -->
                <div class="flex items-start">
                    <input type="hidden" name="is_stock_room" value="0">
                    <input type="checkbox"
                           name="is_stock_room"
                           id="is_stock_room"
                           value="1"
                           {{ old('is_stock_room', $inventory->is_stock_room) ? 'checked' : '' }}
                           class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                    <label for="is_stock_room" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Lagerraum</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Artikel im Lagerraum aufbewahren
                        </p>
                    </label>
                </div>

                <!-- Stock Room ID -->
                <div class="md:col-span-2">
                    <label for="stock_room_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Lagerraum-ID
                    </label>
                    <input type="text"
                           name="stock_room_id"
                           id="stock_room_id"
                           value="{{ old('stock_room_id', $inventory->stock_room_id) }}"
                           placeholder="z.B. Regal A1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('stock_room_id') border-red-500 @enderror">
                    @error('stock_room_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Tier Pricing -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fa-solid fa-tag mr-2 text-blue-500"></i>
                Staffelpreise
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @for($i = 1; $i <= 3; $i++)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Stufe {{ $i }}
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label for="tier_quantity{{ $i }}" class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    Ab Menge
                                </label>
                                <input type="number"
                                       name="tier_quantity{{ $i }}"
                                       id="tier_quantity{{ $i }}"
                                       value="{{ old('tier_quantity' . $i, $inventory->{'tier_quantity' . $i}) }}"
                                       min="1"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                            <div>
                                <label for="tier_price{{ $i }}" class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    Preis (€)
                                </label>
                                <input type="number"
                                       name="tier_price{{ $i }}"
                                       id="tier_price{{ $i }}"
                                       value="{{ old('tier_price' . $i, $inventory->{'tier_price' . $i}) }}"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('inventory.show', $inventory) }}" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i class="fa-solid fa-times mr-2"></i>
                Abbrechen
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-save mr-2"></i>
                Änderungen speichern
            </button>
        </div>
    </form>
</div>
</x-layouts.app>

