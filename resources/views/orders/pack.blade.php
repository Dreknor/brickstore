<x-layouts.app title="Order packen - {{ $order->bricklink_order_id }}">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Order {{ $order->bricklink_order_id }} packen
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $order->buyer_name }} • {{ $order->items->count() }} Teile
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('orders.show', $order) }}"
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Zurück
                    </a>
                    @if($order->status !== 'Shipped' && !$order->shipped_date)
                        <a href="{{ route('orders.shipping-label', $order) }}"
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                           target="_blank"
                           title="Versandetikett drucken">
                            <i class="fa-solid fa-file-pdf"></i> Versandetikett
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Fortschritt -->
        <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Packfortschritt
                </span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $order->items->where('is_packed', true)->count() }} / {{ $order->items->count() }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-green-600 h-2.5 rounded-full transition-all duration-300"
                     style="width: {{ $order->items->count() > 0 ? ($order->items->where('is_packed', true)->count() / $order->items->count() * 100) : 0 }}%">
                </div>
            </div>
        </div>

        <!-- Items nach Location gruppiert -->
        <div class="space-y-6">
            @forelse($itemsByLocation as $location => $items)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <!-- Location Header -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <i class="fa-solid fa-location-dot mr-2 text-blue-600"></i>
                                {{ $location ?: 'Keine Location' }}
                            </h2>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $items->where('is_packed', true)->count() }} / {{ $items->count() }} gepackt
                            </span>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($items as $item)
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                 x-data="{ packed: {{ $item->is_packed ? 'true' : 'false' }} }">
                                <div class="flex items-start gap-6">
                                    <!-- Checkbox -->
                                    <div class="pt-2">
                                        <input type="checkbox"
                                               x-model="packed"
                                               @change="togglePacked({{ $item->id }}, $event.target.checked)"
                                               class="w-8 h-8 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    </div>
                                    <!-- GROSSE Anzahl-Anzeige -->
                                    <div class="flex-shrink-0 text-center min-w-[120px]">
                                        <div class="px-6 py-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border-2 border-blue-300 dark:border-blue-600">
                                            <div class="text-5xl font-black text-gray-900 dark:text-white leading-none">
                                                {{ $item->quantity }}
                                            </div>
                                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mt-2 uppercase tracking-wide">
                                                Stück
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Großes Vorschaubild -->
                                    <div class="w-32 h-32 flex-shrink-0 bg-white dark:bg-gray-700 rounded-lg border-2 border-gray-200 dark:border-gray-600 p-2">
                                        @if($item->cached_image_url)
                                            <img src="{{ $item->cached_image_url }}"
                                                 alt="{{ $item->item_name }}"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center\'><i class=\'fa-solid fa-cube text-gray-400 text-4xl\'></i></div>';"
                                                 class="w-full h-full object-contain"
                                                 loading="lazy">
                                        @else
                                            <div class="w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                <i class="fa-solid fa-cube text-gray-400 text-4xl"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Item Info mit prominentem Ablagefach -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Ablagefach - PROMINENT -->
                                        @if($item->remarks)
                                            <div class="mb-3">
                                                <div class="inline-flex items-center gap-2 px-4 py-3 text-lg font-bold rounded-lg bg-yellow-100 text-yellow-900 dark:bg-yellow-900/40 dark:text-yellow-200 border-2 border-yellow-400 dark:border-yellow-600">
                                                    <i class="fa-solid fa-location-dot text-xl"></i>
                                                    <span>{{ $item->remarks }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mb-3">
                                                <div class="inline-flex items-center gap-2 px-4 py-3 text-lg font-bold rounded-lg bg-red-100 text-red-900 dark:bg-red-900/40 dark:text-red-200 border-2 border-red-400 dark:border-red-600">
                                                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                                                    <span>KEIN ABLAGEFACH</span>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Item Details -->
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-3 py-1 text-sm font-semibold rounded-lg bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $item->item_type }}
                                            </span>
                                            <span class="text-lg font-mono font-bold text-gray-900 dark:text-white">
                                                {{ $item->item_number }}
                                            </span>
                                            @if($item->color_name)
                                                <span class="text-base text-gray-600 dark:text-gray-400">
                                                    • {{ $item->color_name }}
                                                </span>
                                            @endif
                                            <span class="px-2 py-1 text-xs font-medium rounded {{ $item->condition === 'N' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' }}">
                                                {{ $item->condition === 'N' ? 'NEU' : 'GEBRAUCHT' }}
                                            </span>
                                        </div>
                                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">
                                            {{ $item->item_name }}
                                        </h3>
                                        @if($item->description)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $item->description }}
                                            </p>
                                        @endif
                                    </div>


                                    <!-- Status mit größerem Icon -->
                                    <div class="flex-shrink-0 pt-2">
                                        <div x-show="packed"
                                             class="flex flex-col items-center gap-2 text-green-600 dark:text-green-400">
                                            <i class="fa-solid fa-check-circle text-4xl"></i>
                                            <span class="text-sm font-bold uppercase">Gepackt</span>
                                        </div>
                                        <div x-show="!packed"
                                             class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                            <i class="fa-regular fa-circle text-4xl"></i>
                                            <span class="text-sm font-medium uppercase">Offen</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <i class="fa-solid fa-box-open text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">Keine Items gefunden</p>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
    <script>
        function togglePacked(itemId, isPacked) {
            const url = isPacked
                ? '{{ route("orders.pack-item", $order) }}'
                : '{{ route("orders.unpack-item", $order) }}';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ item_id: itemId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to update progress bar
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Aktualisieren des Status');
            });
        }
    </script>
    @endpush
</x-layouts.app>

