@php
    $store = auth()->user()->store ?? null;

    $totalItems = 0;
    $totalValue = 0;
    $stockroomItems = 0;
    $lowStockItems = 0;

    if ($store) {
        $totalItems = $store->inventories()->sum('quantity');
        $totalValue = $store->inventories()->sum(\DB::raw('quantity * unit_price'));
        $stockroomItems = $store->inventories()->where('is_stock_room', true)->count();
        $lowStockItems = $store->inventories()->where('quantity', '<', 10)->count();
    }
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fa-solid fa-boxes-stacked mr-2 text-blue-500"></i>
                Inventar-Übersicht
            </h3>
            @if($store)
                <a href="{{ route('inventory.index') }}"
                   class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    Alle anzeigen →
                </a>
            @endif
        </div>
    </div>

    @if(!$store)
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                <i class="fa-solid fa-info-circle mr-2"></i>
                Kein Store konfiguriert
            </p>
        </div>
    @else
        <div class="p-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <!-- Total Items -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">
                                Gesamt Artikel
                            </p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-300 mt-1">
                                {{ number_format($totalItems) }}
                            </p>
                        </div>
                        <i class="fa-solid fa-cubes text-3xl text-blue-500/30"></i>
                    </div>
                </div>

                <!-- Total Value -->
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-green-600 dark:text-green-400 uppercase">
                                Gesamtwert
                            </p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-300 mt-1">
                                {{ number_format($totalValue, 2) }} €
                            </p>
                        </div>
                        <i class="fa-solid fa-euro-sign text-3xl text-green-500/30"></i>
                    </div>
                </div>

                <!-- Stockroom Items -->
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase">
                                Im Lager
                            </p>
                            <p class="text-2xl font-bold text-purple-900 dark:text-purple-300 mt-1">
                                {{ number_format($stockroomItems) }}
                            </p>
                        </div>
                        <i class="fa-solid fa-warehouse text-3xl text-purple-500/30"></i>
                    </div>
                </div>

                <!-- Low Stock -->
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-orange-600 dark:text-orange-400 uppercase">
                                Niedriger Bestand
                            </p>
                            <p class="text-2xl font-bold text-orange-900 dark:text-orange-300 mt-1">
                                {{ number_format($lowStockItems) }}
                            </p>
                        </div>
                        <i class="fa-solid fa-triangle-exclamation text-3xl text-orange-500/30"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex gap-2 mt-4">
                <a href="{{ route('inventory.create') }}"
                   class="flex-1 px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Artikel hinzufügen
                </a>
                <form action="{{ route('inventory.sync') }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit"
                            class="w-full px-4 py-2 bg-gray-600 text-white text-center rounded-lg hover:bg-gray-700 text-sm font-medium transition-colors">
                        <i class="fa-solid fa-sync mr-2"></i>
                        Synchronisieren
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

