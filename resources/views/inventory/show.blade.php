<x-layouts.app title="Inventar-Artikel Details">
    <div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $inventory->item_no }}
                </h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($inventory->item_type === 'PART') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                    @elseif($inventory->item_type === 'SET') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                    @elseif($inventory->item_type === 'MINIFIG') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                    @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400
                    @endif">
                    {{ $inventory->item_type }}
                </span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventory.edit', $inventory) }}" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                    <i class="fa-solid fa-edit mr-2"></i>
                    Bearbeiten
                </a>
                <form action="{{ route('inventory.destroy', $inventory) }}"
                      method="POST"
                      class="inline"
                      onsubmit="return confirm('Möchten Sie diesen Artikel wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Löschen
                    </button>
                </form>
            </div>
        </div>
        @if($inventory->description)
            <p class="text-gray-600 dark:text-gray-400">
                {{ $inventory->description }}
            </p>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/20 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 rounded-lg">
            <i class="fa-solid fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fa-solid fa-info-circle mr-2 text-blue-500"></i>
                    Grundinformationen
                </h2>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Artikel-Nummer
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $inventory->item_no }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Typ
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $inventory->item_type }}
                        </p>
                    </div>

                    @if($inventory->color_name)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Farbe
                            </label>
                            <div class="flex items-center gap-3">
                                @php
                                    $color = $inventory->color;
                                @endphp

                                @if($color)
                                    <!-- Farb-Swatch -->
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-lg border-2 border-gray-300 dark:border-gray-600"
                                             style="background-color: {{ $color->getCssColor() }};"
                                             title="{{ $color->getDisplayName() }}">
                                        </div>
                                    </div>

                                    <!-- Farb-Info -->
                                    <div>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $color->getDisplayName() }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Hex: {{ $color->color_code }} | ID: {{ $color->color_id }}
                                        </p>
                                    </div>
                                @else
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $inventory->color_name }}
                                        @if($inventory->color_id)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">(ID: {{ $inventory->color_id }})</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Zustand
                        </label>
                        <p class="text-lg">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $inventory->new_or_used === 'N'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' }}">
                                {{ $inventory->new_or_used === 'N' ? 'Neu' : 'Gebraucht' }}
                            </span>
                        </p>
                    </div>

                    @if($inventory->completeness)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Vollständigkeit
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($inventory->completeness === 'C') Vollständig
                                @elseif($inventory->completeness === 'B') Unvollständig
                                @elseif($inventory->completeness === 'S') Versiegelt
                                @else {{ $inventory->completeness }}
                                @endif
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            BrickLink ID
                        </label>
                        <p class="text-lg font-mono text-gray-900 dark:text-white">
                            {{ $inventory->inventory_id }}
                        </p>
                    </div>
                </div>

                @if($inventory->remarks)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                            <i class="fa-solid fa-note-sticky mr-1"></i>
                            Notizen
                        </label>
                        <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ $inventory->remarks }}</p>
                    </div>
                @endif
            </div>

            <!-- Storage Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fa-solid fa-warehouse mr-2 text-blue-500"></i>
                    Lagerinformationen
                </h2>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Im Lagerraum
                        </label>
                        <p class="text-lg">
                            @if($inventory->is_stock_room)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    <i class="fa-solid fa-check mr-1"></i> Ja
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                    <i class="fa-solid fa-times mr-1"></i> Nein
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($inventory->stock_room_id)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Lagerraum-ID
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $inventory->stock_room_id }}
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Behalten bei Menge 0
                        </label>
                        <p class="text-lg">
                            @if($inventory->is_retain)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i class="fa-solid fa-check mr-1"></i> Ja
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                    <i class="fa-solid fa-times mr-1"></i> Nein
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($inventory->bulk && $inventory->bulk > 1)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Bulk-Verkaufsmenge
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $inventory->bulk }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pricing Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fa-solid fa-tag mr-2 text-blue-500"></i>
                    Preisinformationen
                </h2>

                <div class="grid grid-cols-2 gap-6">
                    @if($inventory->my_cost)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Einkaufspreis
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($inventory->my_cost, 3) }} €
                            </p>
                        </div>
                    @endif

                    @if($inventory->sale_rate)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Rabatt
                            </label>
                            <p class="text-lg font-semibold text-orange-600 dark:text-orange-400">
                                {{ $inventory->sale_rate }}%
                            </p>
                        </div>
                    @endif

                    @if($inventory->my_weight)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Gewicht
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($inventory->my_weight, 2) }} g
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Tier Pricing -->
                @if($inventory->tier_quantity1 || $inventory->tier_quantity2 || $inventory->tier_quantity3)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Staffelpreise
                        </h3>
                        <div class="space-y-2">
                            @if($inventory->tier_quantity1)
                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        Ab {{ $inventory->tier_quantity1 }} Stück
                                    </span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($inventory->tier_price1, 3) }} € / Stück
                                    </span>
                                </div>
                            @endif
                            @if($inventory->tier_quantity2)
                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        Ab {{ $inventory->tier_quantity2 }} Stück
                                    </span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($inventory->tier_price2, 3) }} € / Stück
                                    </span>
                                </div>
                            @endif
                            @if($inventory->tier_quantity3)
                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        Ab {{ $inventory->tier_quantity3 }} Stück
                                    </span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($inventory->tier_price3, 3) }} € / Stück
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- BrickLink Price Guide -->
            @if($inventory->avg_price)
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg shadow-sm border border-green-200 dark:border-green-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <i class="fa-solid fa-chart-line mr-2 text-green-600 dark:text-green-400"></i>
                            BrickLink Price Guide
                        </h2>
                        <form action="{{ route('inventory.refresh-price-guide', $inventory) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center gap-1">
                                <i class="fa-solid fa-sync-alt"></i>
                                <span>Aktualisieren</span>
                            </button>
                        </form>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Average Price -->
                        <div class="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-green-100 dark:border-green-900/30">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                                Durchschnittspreis
                            </label>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($inventory->avg_price, 3) }} €
                            </p>
                        </div>

                        <!-- Min Price -->
                        <div class="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-blue-100 dark:border-blue-900/30">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                                Mindestpreis
                            </label>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($inventory->min_price, 3) }} €
                            </p>
                        </div>

                        <!-- Max Price -->
                        <div class="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-purple-100 dark:border-purple-900/30">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                                Höchstpreis
                            </label>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ number_format($inventory->max_price, 3) }} €
                            </p>
                        </div>

                        <!-- Quantity Sold -->
                        <div class="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-orange-100 dark:border-orange-900/30">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                                Verkaufte Menge
                            </label>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                {{ number_format($inventory->qty_sold) }}
                            </p>
                        </div>
                    </div>

                    @if($inventory->price_guide_fetched_at)
                        <div class="mt-4 pt-4 border-t border-green-200 dark:border-green-800">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-sync-alt mr-1"></i>
                                Abgerufen: {{ \Carbon\Carbon::parse($inventory->price_guide_fetched_at)->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <!-- No Price Guide Card -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-50 dark:from-gray-800/30 dark:to-gray-800/30 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-chart-line text-2xl text-gray-400 dark:text-gray-600"></i>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    BrickLink Price Guide
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Für diesen Artikel liegt noch kein Preisvorschlag vor
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('inventory.refresh-price-guide', $inventory) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-sync-alt"></i>
                                <span>Jetzt abrufen</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Timestamps -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fa-solid fa-clock mr-2 text-blue-500"></i>
                    Zeitstempel
                </h2>

                <div class="grid grid-cols-2 gap-6">
                    @if($inventory->date_created)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Erstellt (BrickLink)
                            </label>
                            <p class="text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($inventory->date_created)->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    @endif

                    @if($inventory->date_updated)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Zuletzt aktualisiert (BrickLink)
                            </label>
                            <p class="text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($inventory->date_updated)->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Erstellt (lokal)
                        </label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $inventory->created_at->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Aktualisiert (lokal)
                        </label>
                        <p class="text-gray-900 dark:text-white">
                            {{ $inventory->updated_at->format('d.m.Y H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stock & Value Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    Bestand & Wert
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Menge auf Lager
                        </label>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($inventory->quantity) }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Stückpreis
                        </label>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($inventory->unit_price, 3) }} €
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Gesamtwert
                        </label>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($inventory->quantity * $inventory->unit_price, 3) }} €
                        </p>
                    </div>
                    @if($inventory->my_cost > 0 and $inventory->my_cost != '0.00')

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Potenzieller Gewinn
                            </label>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ number_format($inventory->quantity * ($inventory->unit_price - $inventory->my_cost), 3) }} €
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ({{ number_format((($inventory->unit_price - $inventory->my_cost) / $inventory->my_cost) * 100, 1) }}% Marge)
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Schnellaktionen
                </h2>

                <div class="space-y-2">
                    <a href="{{ route('inventory.edit', $inventory) }}"
                       class="block w-full px-4 py-2 bg-yellow-600 text-white text-center rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fa-solid fa-edit mr-2"></i>
                        Bearbeiten
                    </a>

                    <a href="https://www.bricklink.com/v2/catalog/catalogitem.page?P={{ $inventory->item_no }}"
                       target="_blank"
                       class="block w-full px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fa-solid fa-external-link-alt mr-2"></i>
                        In BrickLink öffnen
                    </a>

                    <form action="{{ route('inventory.destroy', $inventory) }}"
                          method="POST"
                          onsubmit="return confirm('Möchten Sie diesen Artikel wirklich löschen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="block w-full px-4 py-2 bg-red-600 text-white text-center rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fa-solid fa-trash mr-2"></i>
                            Löschen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
</x-layouts.app>

