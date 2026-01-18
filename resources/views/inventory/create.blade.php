<x-layouts.app title="Inventar-Artikel hinzufügen">
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('inventory.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fa-solid fa-plus mr-2 text-blue-500"></i>
                Inventar-Artikel hinzufügen
            </h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
            Fügen Sie einen neuen Artikel zu Ihrem BrickLink Inventar hinzu
        </p>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg">
            <i class="fa-solid fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('inventory.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fa-solid fa-download mr-2 text-green-500"></i>
                Von BrickLink laden
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Geben Sie Typ, Artikel-Nummer und Farbe ein, um automatisch Daten und Preisempfehlungen von BrickLink zu laden
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Item Type for Loader -->
                <div>
                    <label for="loader_item_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Typ <span class="text-red-500">*</span>
                    </label>
                    <select id="loader_item_type"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Bitte wählen...</option>
                        <option value="PART">Teil</option>
                        <option value="SET">Set</option>
                        <option value="MINIFIG">Minifigur</option>
                        <option value="BOOK">Buch</option>
                        <option value="GEAR">Merchandise</option>
                        <option value="CATALOG">Katalog</option>
                        <option value="INSTRUCTION">Anleitung</option>
                        <option value="UNSORTED_LOT">Unsortiertes Lot</option>
                        <option value="ORIGINAL_BOX">Original Box</option>
                    </select>
                </div>

                <!-- Item Number for Loader -->
                <div>
                    <label for="loader_item_no" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Artikel-Nummer <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="loader_item_no"
                           placeholder="z.B. 3001"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                </div>

                <!-- Color Selection for Loader -->
                <div>
                    <label for="loader_color_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Farbe <span class="text-red-500">*</span>
                    </label>
                    <select id="loader_color_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Bitte wählen...</option>

                        @if(isset($colors) && $colors->count() > 0)
                            @php
                                $colorsByType = $colors->groupBy('color_type');
                            @endphp

                            @foreach($colorsByType as $type => $typeColors)
                                <optgroup label="{{ $type ?: 'Standard' }}">
                                    @foreach($typeColors as $color)
                                        <option value="{{ $color->color_id }}"
                                                data-color-code="{{ $color->color_code }}"
                                                data-color-type="{{ $color->color_type }}">
                                            {{ $color->getDisplayName() }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @else
                            <option disabled>Keine Farben verfügbar</option>
                        @endif
                    </select>
                </div>

                <!-- Load Button -->
                <div class="flex items-end">
                    <button type="button"
                            id="loader_load_btn"
                            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i>
                        <span>Daten laden</span>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loader_loading" class="hidden mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                    <span>Lädt Daten von BrickLink...</span>
                </div>
            </div>

            <!-- Error State -->
            <div id="loader_error" class="hidden mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span id="loader_error_msg"></span>
                </div>
            </div>

            <!-- Success State -->
            <div id="loader_success" class="hidden mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>✓ Daten erfolgreich geladen!</span>
                </div>
            </div>

            <!-- Item Preview (wird dynamisch befüllt) -->
            <div id="item-preview-container" class="hidden mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"></div>

            <!-- Price Guide (wird dynamisch befüllt) -->
            <div id="price-guide-container" class="hidden mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800"></div>
        </div>

        <!-- Artikel-Informationen -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fa-solid fa-cube mr-2 text-blue-500"></i>
                Artikel-Informationen
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Item Type -->
                <div>
                    <label for="item_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Typ <span class="text-red-500">*</span>
                    </label>
                    <select name="item_type"
                            id="item_type"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('item_type') border-red-500 @enderror">
                        <option value="">Bitte wählen...</option>
                        <option value="PART" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'PART' ? 'selected' : '' }}>Teil</option>
                        <option value="SET" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'SET' ? 'selected' : '' }}>Set</option>
                        <option value="MINIFIG" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'MINIFIG' ? 'selected' : '' }}>Minifigur</option>
                        <option value="BOOK" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'BOOK' ? 'selected' : '' }}>Buch</option>
                        <option value="GEAR" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'GEAR' ? 'selected' : '' }}>Merchandise</option>
                        <option value="CATALOG" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'CATALOG' ? 'selected' : '' }}>Katalog</option>
                        <option value="INSTRUCTION" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'INSTRUCTION' ? 'selected' : '' }}>Anleitung</option>
                        <option value="UNSORTED_LOT" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'UNSORTED_LOT' ? 'selected' : '' }}>Unsortiertes Lot</option>
                        <option value="ORIGINAL_BOX" {{ (old('item_type') ?? $prefilledData['item_type'] ?? '') === 'ORIGINAL_BOX' ? 'selected' : '' }}>Original Box</option>
                    </select>
                    @error('item_type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Item Number -->
                <div>
                    <label for="item_no" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Artikel-Nummer <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="item_no"
                           id="item_no"
                           value="{{ old('item_no') ?? $prefilledData['item_no'] ?? '' }}"
                           required
                           placeholder="z.B. 3001 oder 10251"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('item_no') border-red-500 @enderror">
                    @error('item_no')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color Selection -->
                <div>
                    <label for="color_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Farbe
                    </label>
                    <select name="color_id"
                            id="color_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('color_id') border-red-500 @enderror">
                        <option value="">-- Keine Farbe / Unbekannt --</option>

                        @if(isset($colors) && $colors->count() > 0)
                            <!-- Gruppiere Farben nach Typ -->
                            @php
                                $colorsByType = $colors->groupBy('color_type');
                            @endphp

                            @foreach($colorsByType as $type => $typeColors)
                                <optgroup label="{{ $type ?: 'Standard' }}">
                                    @foreach($typeColors as $color)
                                        <option value="{{ $color->color_id }}" style="background-color: #{{$color->color_code}};"
                                                {{ (old('color_id') ?? $prefilledData['color_id'] ?? '') == $color->color_id ? 'selected' : '' }}
                                                data-color-code="{{ $color->color_code }}"
                                                data-color-type="{{ $color->color_type }}">
                                            {{ $color->getDisplayName() }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @else
                            <option disabled>Keine Farben verfügbar - bitte synchronisieren Sie die Farben</option>
                        @endif
                    </select>
                    @error('color_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Wählen Sie die Farbe des Teils. Farben werden von BrickLink synchronisiert.
                    </p>
                </div>

                <!-- Condition -->
                <div>
                    <label for="new_or_used" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Zustand <span class="text-red-500">*</span>
                    </label>
                    <select name="new_or_used"
                            id="new_or_used"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('new_or_used') border-red-500 @enderror">
                        <option value="N" {{ old('new_or_used', 'N') === 'N' ? 'selected' : '' }}>Neu</option>
                        <option value="U" {{ old('new_or_used') === 'U' ? 'selected' : '' }}>Gebraucht</option>
                    </select>
                    @error('new_or_used')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Menge <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="quantity"
                           id="quantity"
                           value="{{ old('quantity', 1) }}"
                           required
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('quantity') border-red-500 @enderror">
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit Price -->
                <div>
                    <label for="unit_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Stückpreis (€) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="unit_price"
                           id="unit_price"
                           value="{{ old('unit_price', '0.000') }}"
                           required
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

                <!-- Completeness -->
                <div>
                    <label for="completeness" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Vollständigkeit
                    </label>
                    <select name="completeness"
                            id="completeness"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('completeness') border-red-500 @enderror">
                        <option value="">Keine Angabe</option>
                        <option value="C" {{ old('completeness') === 'C' ? 'selected' : '' }}>Complete (Vollständig)</option>
                        <option value="B" {{ old('completeness') === 'B' ? 'selected' : '' }}>Incomplete (Unvollständig)</option>
                        <option value="S" {{ old('completeness') === 'S' ? 'selected' : '' }}>Sealed (Versiegelt)</option>
                    </select>
                    @error('completeness')
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
                           value="{{ old('bulk', 1) }}"
                           min="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('bulk') border-red-500 @enderror">
                    @error('bulk')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Verkauf in Vielfachen dieser Menge
                    </p>
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
                    >{{ old('description') ?? $prefilledData['item_name'] ?? '' }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remarks -->
                <div class="md:col-span-2">
                    <label for="remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notizen <span class="text-red-500">*</span>
                    </label>
                    <textarea name="remarks"
                              id="remarks"
                              rows="3"
                              required
                              placeholder="z.B. Regal A3, Box 12 - für bessere Wiederfindbarkeit"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('remarks') border-red-500 @enderror"
                    >{{ old('remarks') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Geben Sie den Lagerort oder andere Hinweise ein, damit das Teil leicht wiedergefunden werden kann.
                    </p>
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
                           {{ old('is_retain') ? 'checked' : '' }}
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
                           {{ old('is_stock_room') ? 'checked' : '' }}
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
                           value="{{ old('stock_room_id') }}"
                           placeholder="z.B. Regal A1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('stock_room_id') border-red-500 @enderror">
                    @error('stock_room_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Advanced Pricing -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fa-solid fa-tag mr-2 text-blue-500"></i>
                Erweiterte Preisoptionen
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- My Cost -->
                <div>
                    <label for="my_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Einkaufspreis (€)
                    </label>
                    <input type="number"
                           name="my_cost"
                           id="my_cost"
                           value="{{ old('my_cost', '0.00') }}"
                           min="0"
                           step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('my_cost') border-red-500 @enderror">
                    @error('my_cost')
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
                           value="{{ old('sale_rate', '0') }}"
                           min="0"
                           max="100"
                           step="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('sale_rate') border-red-500 @enderror">
                    @error('sale_rate')
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
                           value="{{ old('my_weight', '0.00') }}"
                           min="0"
                           step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('my_weight') border-red-500 @enderror">
                    @error('my_weight')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Tier Pricing -->
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Staffelpreise
                </h3>
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
                                           value="{{ old('tier_quantity' . $i) }}"
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
                                           value="{{ old('tier_price' . $i) }}"
                                           min="0"
                                           step="0.01"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i class="fa-solid fa-times mr-2"></i>
                Abbrechen
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-save mr-2"></i>
                Artikel erstellen
            </button>
        </div>
    </form>
</div>

<!-- Item Loader Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadBtn = document.getElementById('loader_load_btn');
    const itemTypeSelect = document.getElementById('loader_item_type');
    const itemNoInput = document.getElementById('loader_item_no');
    const colorSelect = document.getElementById('loader_color_id');
    const loadingDiv = document.getElementById('loader_loading');
    const errorDiv = document.getElementById('loader_error');
    const errorMsg = document.getElementById('loader_error_msg');
    const successDiv = document.getElementById('loader_success');

    // Form fields
    const formItemType = document.getElementById('item_type');
    const formItemNo = document.getElementById('item_no');
    const formColorId = document.getElementById('color_id');
    const formDescription = document.getElementById('description');

    function hideAllMessages() {
        loadingDiv.classList.add('hidden');
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');
    }

    function showError(message) {
        hideAllMessages();
        errorMsg.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    function showSuccess() {
        hideAllMessages();
        successDiv.classList.remove('hidden');
    }

    function showLoading() {
        hideAllMessages();
        loadingDiv.classList.remove('hidden');
    }

    loadBtn.addEventListener('click', async function() {
        const itemType = itemTypeSelect.value;
        const itemNo = itemNoInput.value.trim();
        const colorId = colorSelect.value;

        // Validation
        if (!itemType) {
            showError('Bitte wählen Sie einen Typ aus');
            return;
        }

        if (!itemNo) {
            showError('Bitte geben Sie eine Artikel-Nummer ein');
            return;
        }

        if (!colorId) {
            showError('Bitte wählen Sie eine Farbe aus');
            return;
        }

        showLoading();
        loadBtn.disabled = true;

        try {
            // Hole new_or_used Wert
            const newOrUsed = document.querySelector('input[name="new_or_used"]:checked')?.value || 'N';

            // Baue URL mit allen Parametern
            const url = new URL('{{ route("inventory.load-item") }}', window.location.origin);
            url.searchParams.append('item_type', itemType);
            url.searchParams.append('item_no', itemNo);
            url.searchParams.append('color_id', colorId);
            url.searchParams.append('new_or_used', newOrUsed);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            const result = await response.json();

            if (!result.success) {
                showError(result.message || 'Fehler beim Laden der Daten');
                loadBtn.disabled = false;
                return;
            }

            // Fülle die Formularfelder
            formItemType.value = itemType;
            formItemNo.value = result.data.item_no || itemNo;
            formColorId.value = colorId; // Übertrage gewählte Farbe ins Formular
            formDescription.value = result.data.description || '';

            // Zeige Duplikat-Warnung wenn vorhanden
            if (result.has_duplicates && result.existing_items.length > 0) {
                showDuplicateWarning(result.existing_items);
            }

            // Zeige Price Guide wenn vorhanden
            if (result.price_guide) {
                showPriceGuideInfo(result.price_guide);
            }

            // Scrolle zum Formular
            formItemType.scrollIntoView({ behavior: 'smooth', block: 'center' });

            showSuccess();

            // Verstecke Success-Message nach 3 Sekunden
            setTimeout(() => {
                successDiv.classList.add('hidden');
            }, 3000);

        } catch (error) {
            console.error('Fehler:', error);
            showError('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        } finally {
            loadBtn.disabled = false;
        }
    });

    // Zeige Duplikat-Warnung
    function showDuplicateWarning(existingItems) {
        const previewContainer = document.getElementById('item-preview-container');
        if (!previewContainer) return;

        let html = `
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-400 dark:border-yellow-700 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-2xl mt-1"></i>
                    <div class="flex-1">
                        <h4 class="font-bold text-yellow-900 dark:text-yellow-100 mb-2">
                            ⚠️ Artikel bereits im Inventar vorhanden!
                        </h4>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-3">
                            Dieser Artikel existiert bereits ${existingItems.length}× in Ihrem Inventar.
                            Beim Speichern wird die Menge automatisch erhöht.
                        </p>
                        <div class="space-y-2">`;

        existingItems.forEach((item, index) => {
            const condition = item.new_or_used === 'N' ? 'Neu' : 'Gebraucht';
            html += `
                <div class="bg-white dark:bg-gray-800 p-3 rounded border border-yellow-300 dark:border-yellow-800">
                    <div class="flex justify-between items-start text-sm">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">
                                ${item.color_name || 'Farbe ID ' + item.color_id} (${condition})
                            </span>
                            <br>
                            <span class="text-gray-600 dark:text-gray-400">
                                ${item.remarks || 'Keine Bemerkungen'}
                            </span>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-blue-600 dark:text-blue-400">
                                ${item.quantity}× à ${parseFloat(item.unit_price).toFixed(3)}€
                            </div>
                        </div>
                    </div>
                </div>`;
        });

        html += `
                        </div>
                    </div>
                </div>
            </div>`;

        // Füge nach Preview Container ein
        const container = document.createElement('div');
        container.id = 'duplicate-warning-container';
        container.innerHTML = html;

        // Entferne alte Warnung falls vorhanden
        const oldWarning = document.getElementById('duplicate-warning-container');
        if (oldWarning) {
            oldWarning.remove();
        }

        previewContainer.parentNode.insertBefore(container, previewContainer.nextSibling);
    }

    // Zeige Price Guide Info
    function showPriceGuideInfo(priceGuide) {
        const priceGuideContainer = document.getElementById('price-guide-container');
        if (!priceGuideContainer) return;

        const condition = priceGuide.new_or_used === 'N' ? 'Neu' : 'Gebraucht';

        priceGuideContainer.innerHTML = `
            <div>
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                    <i class="fa-solid fa-chart-line mr-2"></i>
                    Preisempfehlungen (${condition})
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    ${priceGuide.avg_price ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-blue-200 dark:border-blue-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Durchschnitt</div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                ${parseFloat(priceGuide.avg_price).toFixed(3)}€
                            </div>
                        </div>
                    ` : ''}

                    ${priceGuide.min_price ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-green-200 dark:border-green-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                ${parseFloat(priceGuide.min_price).toFixed(3)}€
                            </div>
                        </div>
                    ` : ''}

                    ${priceGuide.max_price ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-orange-200 dark:border-orange-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum</div>
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                ${parseFloat(priceGuide.max_price).toFixed(3)}€
                            </div>
                        </div>
                    ` : ''}

                    ${priceGuide.qty_sold ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-purple-200 dark:border-purple-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Verkauft</div>
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                ${priceGuide.qty_sold}×
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-clock mr-1"></i>
                    Basierend auf BrickLink Marktdaten
                </div>
            </div>
        `;
        priceGuideContainer.classList.remove('hidden');

        // Setze Preis-Vorschlag wenn unit_price leer ist
        const unitPriceInput = document.getElementById('unit_price');
        if (unitPriceInput && priceGuide.avg_price && (!unitPriceInput.value || unitPriceInput.value === '0.000')) {
            unitPriceInput.value = parseFloat(priceGuide.avg_price).toFixed(3);
        }
    }

    // Enter-Taste zum Laden
    itemNoInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadBtn.click();
        }
    });
});
</script>

<!-- Auto-Complete Script -->
@vite('resources/js/inventory-autocomplete.js')
</x-layouts.app>

