<x-layouts.app title="Inventar">
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fa-solid fa-boxes-stacked mr-2 text-blue-500"></i>
                Inventar
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Verwalten Sie Ihr BrickLink Inventar
            </p>
        </div>
        <div class="flex gap-3">
            <form action="{{ route('inventory.sync') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-sync mr-2"></i>
                    Synchronisieren
                </button>
            </form>
            <form action="{{ route('inventory.cache-images') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fa-solid fa-images mr-2"></i>
                    Bilder cachen
                </button>
            </form>
            <a href="{{ route('inventory.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-plus mr-2"></i>
                Artikel hinzufügen
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/20 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 rounded-lg">
            <i class="fa-solid fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg">
            <i class="fa-solid fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mb-6 p-4 bg-blue-100 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-400 rounded-lg">
            <i class="fa-solid fa-info-circle mr-2"></i>
            {{ session('info') }}
        </div>
    @endif

    @php
        $externalImagesCount = \App\Models\Inventory::where('store_id', auth()->user()->store?->id ?? 0)
            ->whereNotNull('image_url')
            ->where('image_url', 'NOT LIKE', '%/storage/%')
            ->where('image_url', '!=', '')
            ->count();
    @endphp

    @if($externalImagesCount > 0)
        <div class="mb-6 p-4 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 text-yellow-800 dark:text-yellow-400 rounded-lg">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-images text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-semibold mb-1">Bilder-Cache ausstehend</h3>
                    <p class="text-sm mb-3">
                        {{ $externalImagesCount }} {{ $externalImagesCount === 1 ? 'Bild muss' : 'Bilder müssen' }} noch lokal gecacht werden.
                        Das Cachen verbessert die Ladegeschwindigkeit und reduziert externe API-Anfragen.
                    </p>
                    <form action="{{ route('inventory.cache-images') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="fa-solid fa-download mr-2"></i>
                            Jetzt {{ $externalImagesCount }} {{ $externalImagesCount === 1 ? 'Bild' : 'Bilder' }} cachen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
        <form method="GET" action="{{ route('inventory.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Suche
                </label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Artikel-Nr., Beschreibung, Farbe..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Item Type -->
            <div>
                <label for="item_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Typ
                </label>
                <select name="item_type"
                        id="item_type"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Alle Typen</option>
                    <option value="PART" {{ request('item_type') === 'PART' ? 'selected' : '' }}>Teile</option>
                    <option value="SET" {{ request('item_type') === 'SET' ? 'selected' : '' }}>Sets</option>
                    <option value="MINIFIG" {{ request('item_type') === 'MINIFIG' ? 'selected' : '' }}>Minifiguren</option>
                    <option value="BOOK" {{ request('item_type') === 'BOOK' ? 'selected' : '' }}>Bücher</option>
                    <option value="GEAR" {{ request('item_type') === 'GEAR' ? 'selected' : '' }}>Merchandise</option>
                    <option value="INSTRUCTION" {{ request('item_type') === 'INSTRUCTION' ? 'selected' : '' }}>Anleitungen</option>
                </select>
            </div>

            <!-- Condition -->
            <div>
                <label for="condition" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Zustand
                </label>
                <select name="condition"
                        id="condition"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Alle</option>
                    <option value="new" {{ request('condition') === 'new' ? 'selected' : '' }}>Neu</option>
                    <option value="used" {{ request('condition') === 'used' ? 'selected' : '' }}>Gebraucht</option>
                </select>
            </div>

            <!-- Stock Room -->
            <div>
                <label for="stock_room" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Lagerraum
                </label>
                <select name="stock_room"
                        id="stock_room"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Alle</option>
                    <option value="yes" {{ request('stock_room') === 'yes' ? 'selected' : '' }}>Ja</option>
                    <option value="no" {{ request('stock_room') === 'no' ? 'selected' : '' }}>Nein</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-search mr-2"></i>
                    Filtern
                </button>
                <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i class="fa-solid fa-times mr-2"></i>
                    Zurücksetzen
                </a>
            </div>
        </form>
    </div>

    <!-- Inventory List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        @if($inventories->isEmpty())
            <div class="p-12 text-center">
                <i class="fa-solid fa-boxes-stacked text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Keine Inventar-Artikel gefunden
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Synchronisieren Sie Ihr BrickLink Inventar oder fügen Sie manuell Artikel hinzu.
                </p>
                <div class="flex justify-center gap-3">
                    <form action="{{ route('inventory.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fa-solid fa-sync mr-2"></i>
                            Jetzt synchronisieren
                        </button>
                    </form>
                    <a href="{{ route('inventory.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Artikel hinzufügen
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Artikel
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Typ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Farbe
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Menge
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Zustand
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Preis
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Gesamtwert
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($inventories as $inventory)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <!-- Artikel-Bild -->
                                        <div class="w-12 h-12 flex-shrink-0">
                                            @if($inventory->cached_image_url)
                                                <img src="{{ $inventory->cached_image_url }}"
                                                     alt="{{ $inventory->item_no }}"
                                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center\'><i class=\'fa-solid fa-cube text-gray-400\'></i></div>';"
                                                     class="w-full h-full object-contain rounded bg-white dark:bg-gray-700 p-1"
                                                     loading="lazy">
                                            @else
                                                <div class="w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                    <i class="fa-solid {{ $inventory->item_type === 'PART' ? 'fa-cube' : ($inventory->item_type === 'SET' ? 'fa-boxes-stacked' : ($inventory->item_type === 'MINIFIG' ? 'fa-user' : 'fa-box')) }} text-gray-400"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $inventory->item_no }}
                                            </div>
                                            @if($inventory->description)
                                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ Str::limit($inventory->description, 50) }}
                                                </div>
                                            @endif
                                            @if($inventory->remarks)
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    <i class="fa-solid fa-note-sticky mr-1"></i>
                                                    {{ Str::limit($inventory->remarks, 30) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($inventory->item_type === 'PART') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                        @elseif($inventory->item_type === 'SET') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                        @elseif($inventory->item_type === 'MINIFIG') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400
                                        @endif">
                                        {{ $inventory->item_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $inventory->color_name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($inventory->quantity) }}
                                    </span>
                                    @if($inventory->is_stock_room)
                                        <i class="fa-solid fa-warehouse text-xs text-gray-400 ml-1" title="Im Lagerraum"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $inventory->new_or_used === 'N'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' }}">
                                        {{ $inventory->new_or_used === 'N' ? 'Neu' : 'Gebraucht' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ number_format($inventory->unit_price, 3) }} €
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($inventory->quantity * $inventory->unit_price, 3) }} €
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('inventory.show', $inventory) }}"
                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                           title="Details anzeigen">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('inventory.edit', $inventory) }}"
                                           class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300"
                                           title="Bearbeiten">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                        <form action="{{ route('inventory.destroy', $inventory) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Möchten Sie diesen Artikel wirklich löschen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                                                    title="Löschen">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                {{ $inventories->links() }}
            </div>
        @endif
    </div>
</div>
</x-layouts.app>

