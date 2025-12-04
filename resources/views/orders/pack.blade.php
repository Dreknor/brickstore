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
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                 x-data="{ packed: {{ $item->is_packed ? 'true' : 'false' }} }">
                                <div class="flex items-center gap-4">
                                    <!-- Checkbox -->
                                    <div>
                                        <input type="checkbox"
                                               x-model="packed"
                                               @change="togglePacked({{ $item->id }}, $event.target.checked)"
                                               class="w-6 h-6 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    </div>

                                    <!-- Vorschaubild -->
                                    <div class="w-16 h-16 flex-shrink-0">
                                        @if($item->image_url)
                                            <img src="{{ $item->image_url }}"
                                                 alt="{{ $item->item_name }}"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center\'><i class=\'fa-solid fa-cube text-gray-400 text-2xl\'></i></div>';"
                                                 class="w-full h-full object-contain rounded bg-white dark:bg-gray-700 p-1">
                                        @else
                                            <div class="w-full h-full bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                <i class="fa-solid fa-cube text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Item Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $item->item_type }}
                                            </span>
                                            <span class="text-sm font-mono text-gray-600 dark:text-gray-400">
                                                {{ $item->item_number }}
                                            </span>
                                            @if($item->color_name)
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    • {{ $item->color_name }}
                                                </span>
                                            @endif
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $item->item_name }}
                                        </h3>
                                        @if($item->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $item->description }}
                                            </p>
                                        @endif
                                    </div>

                                    <!-- Quantity -->
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $item->quantity }}x
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item->condition === 'N' ? 'Neu' : 'Gebraucht' }}
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="flex-shrink-0">
                                        <div x-show="packed"
                                             class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                            <i class="fa-solid fa-check-circle text-xl"></i>
                                            <span class="text-sm font-medium">Gepackt</span>
                                        </div>
                                        <div x-show="!packed"
                                             class="flex items-center gap-2 text-gray-400 dark:text-gray-500">
                                            <i class="fa-regular fa-circle text-xl"></i>
                                            <span class="text-sm font-medium">Offen</span>
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

