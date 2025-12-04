<x-layouts.app title="Bestellung {{ $order->bricklink_order_id }}">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Bestellung {{ $order->bricklink_order_id }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bestellt am {{ $order->order_date->format('d.m.Y H:i') }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('orders.index') }}"
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <i class="fa-solid fa-arrow-left"></i> Zurück
                    </a>
                    <a href="{{ route('orders.pack', $order) }}"
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fa-solid fa-box"></i> Packen
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Käufer & Versand -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Käuferinformationen -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fa-solid fa-user mr-2 text-blue-600"></i> Käufer
                    </h2>
                    <dl class="grid grid-cols-1 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $order->buyer_name }}</dd>
                        </div>
                        @if($order->buyer_email)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-Mail</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <a href="mailto:{{ $order->buyer_email }}" class="text-blue-600 hover:text-blue-700">
                                        {{ $order->buyer_email }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if($order->buyer_username)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">BrickLink Username</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $order->buyer_username }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Versandadresse -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fa-solid fa-truck mr-2 text-blue-600"></i> Versandadresse
                    </h2>
                    <address class="not-italic text-sm text-gray-900 dark:text-white">
                        @if($order->shipping_name)
                            <div class="font-semibold">{{ $order->shipping_name }}</div>
                        @endif
                        @if($order->shipping_address1)
                            <div>{{ $order->shipping_address1 }}</div>
                        @endif
                        @if($order->shipping_address2)
                            <div>{{ $order->shipping_address2 }}</div>
                        @endif
                        <div>
                            @if($order->shipping_postal_code){{ $order->shipping_postal_code }} @endif
                            @if($order->shipping_city){{ $order->shipping_city }}@endif
                        </div>
                        @if($order->shipping_country)
                            <div>{{ $order->shipping_country }}</div>
                        @endif
                    </address>

                    @if($order->shipping_method)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Versandart</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $order->shipping_method }}</dd>
                        </div>
                    @endif

                    @if($order->tracking_number)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tracking</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $order->tracking_number }}</dd>
                        </div>
                    @endif
                </div>

                <!-- Bestellte Items -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <i class="fa-solid fa-list mr-2 text-blue-600"></i> Bestellte Items ({{ $order->items->count() }})
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($order->items as $item)
                            <div class="p-4 flex items-center gap-4">
                                <div class="w-12 h-12 bg-white dark:bg-gray-700 rounded flex items-center justify-center flex-shrink-0 p-1">
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}"
                                             alt="{{ $item->item_name }}"
                                             onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<i class=\'fa-solid fa-cube text-gray-400\'></i>';"
                                             class="w-full h-full object-contain">
                                    @else
                                        <i class="fa-solid fa-cube text-gray-400"></i>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ $item->item_type }}
                                        </span>
                                        <span class="text-sm font-mono text-gray-600 dark:text-gray-400">
                                            {{ $item->item_number }}
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item->item_name }}
                                    </h3>
                                    @if($item->color_name)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Farbe: {{ $item->color_name }}
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $item->quantity }}x
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($item->unit_price, 2, ',', '.') }} {{ $order->currency_code }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Status
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Bestellstatus</span>
                            <div class="mt-1">
                                <span class="px-3 py-1 text-sm font-semibold rounded
                                    @if($order->status === 'Shipped') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($order->status === 'Paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($order->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                    @endif">
                                    {{ $order->status }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Zahlung</span>
                            <div class="mt-1">
                                @if($order->is_paid)
                                    <span class="text-green-600 dark:text-green-400 font-semibold">
                                        <i class="fa-solid fa-check-circle"></i> Bezahlt
                                    </span>
                                @else
                                    <span class="text-red-600 dark:text-red-400 font-semibold">
                                        <i class="fa-solid fa-times-circle"></i> Unbezahlt
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kosten -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Kosten
                    </h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Zwischensumme</dt>
                            <dd class="text-gray-900 dark:text-white font-medium">
                                {{ number_format($order->subtotal, 2, ',', '.') }} {{ $order->currency_code }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Versand</dt>
                            <dd class="text-gray-900 dark:text-white font-medium">
                                {{ number_format($order->shipping_cost, 2, ',', '.') }} {{ $order->currency_code }}
                            </dd>
                        </div>
                        @if($order->tax > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">MwSt.</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">
                                    {{ number_format($order->tax, 2, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <dt class="font-semibold text-gray-900 dark:text-white">Gesamt</dt>
                            <dd class="font-bold text-lg text-gray-900 dark:text-white">
                                {{ number_format($order->grand_total, 2, ',', '.') }} {{ $order->currency_code }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Aktionen -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Aktionen
                    </h2>
                    <div class="space-y-2">
                        @if(!$order->invoice)
                            <form action="{{ route('orders.create-invoice', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                    <i class="fa-solid fa-file-invoice"></i> Rechnung erstellen
                                </button>
                            </form>
                        @else
                            <a href="{{ route('invoices.show', $order->invoice) }}"
                               class="block w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-center">
                                <i class="fa-solid fa-file-invoice"></i> Rechnung anzeigen
                            </a>
                        @endif

                        <form action="{{ route('orders.sync', $order) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fa-solid fa-sync"></i> Mit BrickLink synchronisieren
                            </button>
                        </form>

                        @if(!$order->shipped_date)
                            <form action="{{ route('orders.ship', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <i class="fa-solid fa-truck"></i> Als versendet markieren
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

