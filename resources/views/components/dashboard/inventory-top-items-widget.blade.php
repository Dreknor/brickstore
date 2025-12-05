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
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full {{
                                    $item->item_type === 'PART' ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300' :
                                    ($item->item_type === 'SET' ? 'bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300' :
                                    'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300')
                                }}">
                                    <i class="fa-solid {{
                                        $item->item_type === 'PART' ? 'fa-cube' :
                                        ($item->item_type === 'SET' ? 'fa-boxes-stacked' :
                                        ($item->item_type === 'MINIFIG' ? 'fa-user' : 'fa-box'))
                                    }}"></i>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $item->item_no }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->color_name ?? 'Keine Farbe' }} •
                                    {{ $item->quantity }} Stk. × {{ number_format($item->unit_price, 2) }}€
                                </p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ number_format($item->quantity * $item->unit_price, 2) }}€
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

