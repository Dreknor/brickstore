@php
    $store = auth()->user()->store ?? null;

    $recentItems = [];
    $topItems = [];

    if ($store) {
        // 5 neueste Items
        $recentItems = $store->inventories()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Top 5 nach Wert (Menge * Preis)
        $topItems = $store->inventories()
            ->select('*')
            ->selectRaw('quantity * unit_price as total_value')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();
    }
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="fa-solid fa-star mr-2 text-yellow-500"></i>
            Top Inventar-Artikel
        </h3>
    </div>

    @if(!$store)
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                Kein Store konfiguriert
            </p>
        </div>
    @elseif($topItems->isEmpty())
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                <i class="fa-solid fa-box-open mr-2"></i>
                Noch keine Inventar-Artikel vorhanden
            </p>
            <div class="text-center mt-4">
                <a href="{{ route('inventory.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Ersten Artikel erstellen
                </a>
            </div>
        </div>
    @else
        <div class="p-6">
            <div class="space-y-3">
                @foreach($topItems as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <!-- Artikel-Bild -->
                            <div class="w-12 h-12 flex-shrink-0">
                                @if($item->cached_image_url)
                                    <img src="{{ $item->cached_image_url }}"
                                         alt="{{ $item->item_no }}"
                                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center\'><i class=\'fa-solid fa-cube text-gray-400 text-lg\'></i></div>';"
                                         class="w-full h-full object-contain rounded bg-white dark:bg-gray-700 p-1"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                        <i class="fa-solid {{ $item->item_type === 'PART' ? 'fa-cube' : ($item->item_type === 'SET' ? 'fa-boxes-stacked' : ($item->item_type === 'MINIFIG' ? 'fa-user' : 'fa-box')) }} text-gray-400 text-lg"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Artikel-Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $item->item_no }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->color_name ?? 'Keine Farbe' }} •
                                    {{ $item->quantity }} Stk. × {{ number_format($item->unit_price, 3) }}€
                                </p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ number_format($item->quantity * $item->unit_price, 3) }}€
                            </p>
                            <a href="{{ route('inventory.show', $item) }}"
                               class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                Details →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($store->inventories()->count() > 5)
                <div class="mt-4 text-center">
                    <a href="{{ route('inventory.index') }}"
                       class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        Alle {{ number_format($store->inventories()->count()) }} Artikel anzeigen →
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

