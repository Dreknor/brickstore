<x-layouts.app title="Bestellung {{ $order->bricklink_order_id }}">
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Bestellung #{{ $order->bricklink_order_id }}
                        </h1>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                            @if($order->status === 'Shipped') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @elseif($order->status === 'Paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                            @elseif($order->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                            @endif">
                            {{ $order->status }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-4 flex-wrap text-sm text-gray-600 dark:text-gray-400">
                        <span><i class="fa-solid fa-calendar"></i> Bestellt: {{ $order->order_date->format('d.m.Y H:i') }}</span>
                        @if($order->last_synced_at)
                            <span><i class="fa-solid fa-clock"></i> Synchronisiert: {{ $order->last_synced_at->format('d.m.Y H:i') }}</span>
                        @endif
                        <span><i class="fa-solid fa-coins"></i> {{ number_format($order->final_total > 0 ? $order->final_total : $order->grand_total, 3, ',', '.') }} {{ $order->currency_code }}</span>
                        <span><i class="fa-solid fa-box"></i> {{ $order->total_count }} Artikel</span>
                        @if($order->unique_count > 0)
                            <span><i class="fa-solid fa-cubes"></i> {{ $order->unique_count }} eindeutige Artikel</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('orders.index') }}"
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fa-solid fa-arrow-left"></i> Zurück
                    </a>
                    @if($order->status !== 'Shipped' && !$order->shipped_date)
                        <a href="{{ route('orders.shipping-label', $order) }}"
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                           target="_blank"
                           title="Versandetikett drucken">
                            <i class="fa-solid fa-file-pdf"></i> Versandetikett
                        </a>
                    @endif
                    <a href="{{ route('orders.pack', $order) }}"
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
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

        <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Linke Spalte -->
            <div class="col-span-2 space-y-6">
                <!-- Käuferinformationen -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-user"></i>
                            <span>Käuferinformationen</span>
                        </h2>
                    </div>
                    <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="group">
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Name</dt>
                            <dd class="text-base text-gray-900 dark:text-white font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-user-circle text-blue-500"></i>
                                {{ $order->buyer_name }}
                            </dd>
                        </div>
                        @if($order->buyer_username)
                            <div class="group">
                                <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">BrickLink Username</dt>
                                <dd class="text-base text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-brands fa-bricklink text-orange-500"></i>
                                    {{ $order->buyer_username }}
                                </dd>
                            </div>
                        @endif
                        @if($order->buyer_email)
                            <div class="group">
                                <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">E-Mail</dt>
                                <dd class="text-base">
                                    <a href="mailto:{{ $order->buyer_email }}"
                                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors flex items-center gap-2">
                                        <i class="fa-solid fa-envelope"></i>
                                        <span>{{ $order->buyer_email }}</span>
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if($order->buyer_order_count > 0)
                            <div class="group">
                                <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Bestellanzahl</dt>
                                <dd class="text-base text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-shopping-bag text-green-500"></i>
                                    <span>{{ $order->buyer_order_count }}. Bestellung</span>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-red-500"></i>
                            Versandadresse
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <address class="not-italic text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-1">
                                    @if($order->shipping_name)
                                        <div class="font-semibold text-lg">{{ $order->shipping_name }}</div>
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
                                    @if($order->shipping_state)
                                        <div>{{ $order->shipping_state }}</div>
                                    @endif
                                    @if($order->shipping_country)
                                        <div class="font-medium mt-2">{{ $order->shipping_country }}</div>
                                    @endif
                                </address>
                            </div>
                            @if($order->shipping_method)
                                <div class="group">
                                    <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Versandart</dt>
                                    <dd class="text-base text-gray-900 dark:text-white flex items-center gap-2">
                                        <i class="fa-solid fa-truck text-blue-500"></i>
                                        {{ $order->shipping_method }}
                                    </dd>
                                </div>
                            @endif
                            <div class="group">
                                <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Versandkosten</dt>
                                <dd class="text-base text-gray-900 dark:text-white font-semibold flex items-center gap-2">
                                    <i class="fa-solid fa-dollar-sign text-green-500"></i>
                                    {{ number_format($order->shipping_cost, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                            @if($order->tracking_number)
                                <div class="md:col-span-2 group">
                                    <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Sendungsverfolgung</dt>
                                    <dd class="mt-2">
                                        <div class="inline-flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border border-blue-200 dark:border-blue-700/50 rounded-lg shadow-sm">
                                            <i class="fa-solid fa-truck-fast text-blue-600 dark:text-blue-400 text-lg"></i>
                                            <span class="font-mono text-base text-blue-900 dark:text-blue-100 font-semibold tracking-wider">{{ $order->tracking_number }}</span>
                                            @if($order->tracking_link)
                                                <a href="{{ $order->tracking_link }}" target="_blank"
                                                   class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors">
                                                    <i class="fa-solid fa-external-link-alt"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </dd>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Bemerkungen -->
                @if($order->buyer_remarks)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                            <i class="fa-solid fa-comment mr-2 text-blue-600"></i> Käuferbemerkung
                        </h2>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-600 p-4 rounded">
                            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $order->buyer_remarks }}</p>
                        </div>
                    </div>
                @endif

                <!-- Bestellpositionen Tabelle -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <i class="fa-solid fa-list mr-2 text-blue-600"></i> Bestellpositionen ({{ $order->items->count() }})
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">Bild</th>
                                    <th class="px-4 py-3 text-left">Typ</th>
                                    <th class="px-4 py-3 text-left">Artikel-Nr.</th>
                                    <th class="px-4 py-3 text-left">Beschreibung</th>
                                    <th class="px-4 py-3 text-left">Farbe</th>
                                    <th class="px-4 py-3 text-center">Zustand</th>
                                    <th class="px-4 py-3 text-right">Menge</th>
                                    <th class="px-4 py-3 text-right">Einzelpreis</th>
                                    <th class="px-4 py-3 text-right">Gesamt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($order->items as $index => $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="w-12 h-12 bg-white dark:bg-gray-600 rounded flex items-center justify-center p-1">
                                                @if($item->cached_image_url)
                                                    <img src="{{ $item->cached_image_url }}"
                                                         alt="{{ $item->item_name }}"
                                                         onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<i class=\'fa-solid fa-cube text-gray-400\'></i>';"
                                                         class="w-full h-full object-contain">
                                                @else
                                                    <i class="fa-solid fa-cube text-gray-400"></i>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $item->item_type }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm font-mono text-blue-600 dark:text-blue-400 font-semibold">{{ $item->item_number }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                                            {{ $item->item_name }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $item->color_name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            <span class="px-2 py-1 text-xs rounded {{ $item->condition === 'N' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }}">
                                                {{ $item->condition === 'N' ? 'Neu' : 'Gebraucht' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-mono text-gray-900 dark:text-white">
                                            {{ number_format($item->unit_price, 3, ',', '.') }} {{ $order->currency_code }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-mono font-semibold text-gray-900 dark:text-white">
                                            {{ number_format($item->total_price, 3, ',', '.') }} {{ $order->currency_code }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Zeitstempel & Status-Historie -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <i class="fa-solid fa-clock mr-2 text-blue-600"></i> Status & Zeitstempel
                    </h2>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bestelldatum</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">
                                <i class="fa-solid fa-calendar-plus mr-1 text-green-600"></i>
                                {{ $order->order_date->format('d.m.Y H:i') }}
                            </dd>
                        </div>
                        @if($order->last_synced_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zuletzt synchronisiert</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <i class="fa-solid fa-sync mr-1 text-blue-600"></i>
                                    {{ $order->last_synced_at->format('d.m.Y H:i') }}
                                </dd>
                            </div>
                        @endif
                        @if($order->paid_date)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bezahlt am</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <i class="fa-solid fa-check-circle mr-1 text-green-600"></i>
                                    {{ $order->paid_date->format('d.m.Y H:i') }}
                                </dd>
                            </div>
                        @endif
                        @if($order->shipped_date)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Versendet am</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <i class="fa-solid fa-truck mr-1 text-blue-600"></i>
                                    {{ $order->shipped_date->format('d.m.Y H:i') }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktueller Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full
                                    @if($order->status === 'Shipped') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($order->status === 'Paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($order->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                    @endif">
                                    {{ $order->status }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            </div>
            <!-- Rechte Spalte -->
            <div class="col-span-1 space-y-6">
                <!-- Status-Übersicht -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <i class="fa-solid fa-info-circle mr-2 text-blue-600"></i> Status
                    </h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Bestellstatus</dt>
                            <dd>
                                <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full
                                    @if($order->status === 'Shipped') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($order->status === 'Paid') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($order->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @elseif($order->status === 'Processing') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                    @elseif($order->status === 'Packed') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                    @endif">
                                    {{ $order->status }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Zahlungsstatus</dt>
                            <dd>
                                @if($order->is_paid)
                                    <span class="inline-flex items-center text-green-600 dark:text-green-400 font-semibold">
                                        <i class="fa-solid fa-check-circle mr-1"></i> Bezahlt
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-red-600 dark:text-red-400 font-semibold">
                                        <i class="fa-solid fa-times-circle mr-1"></i> Unbezahlt
                                    </span>
                                @endif
                            </dd>
                        </div>
                        @if($order->payment_method)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Zahlungsart</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $order->payment_method }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Kosten -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <i class="fa-solid fa-coins mr-2 text-blue-600"></i> Kostenaufschlüsselung
                    </h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Zwischensumme</dt>
                            <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                {{ number_format($order->subtotal, 3, ',', '.') }} {{ $order->currency_code }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Versandkosten</dt>
                            <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                {{ number_format($order->shipping_cost, 3, ',', '.') }} {{ $order->currency_code }}
                            </dd>
                        </div>
                        @if($order->insurance > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Versicherung</dt>
                                <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                    {{ number_format($order->insurance, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        @if($order->discount > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Rabatt</dt>
                                <dd class="text-red-600 dark:text-red-400 font-medium font-mono">
                                    -{{ number_format($order->discount, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        @if($order->etc1 > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Zusatzkosten 1</dt>
                                <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                    {{ number_format($order->etc1, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        @if($order->etc2 > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Zusatzkosten 2</dt>
                                <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                    {{ number_format($order->etc2, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        @if($order->vat_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">
                                    MwSt. @if($order->vat_rate > 0)({{ number_format($order->vat_rate, 2) }}%)@endif
                                    @if($order->vat_collected_by_bl)
                                        <span class="text-xs text-blue-600 dark:text-blue-400">(von BrickLink)</span>
                                    @endif
                                </dt>
                                <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                    {{ number_format($order->vat_amount, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @elseif($order->tax > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Steuern</dt>
                                <dd class="text-gray-900 dark:text-white font-medium font-mono">
                                    {{ number_format($order->tax, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <dt class="font-semibold text-gray-900 dark:text-white">Zwischensumme</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white font-mono">
                                {{ number_format($order->grand_total, 3, ',', '.') }} {{ $order->currency_code }}
                            </dd>
                        </div>
                        @if($order->credit > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Store Credit</dt>
                                <dd class="text-red-600 dark:text-red-400 font-medium font-mono">
                                    -{{ number_format($order->credit, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        @if($order->credit_coupon > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Gutschein</dt>
                                <dd class="text-red-600 dark:text-red-400 font-medium font-mono">
                                    -{{ number_format($order->credit_coupon, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                        @if($order->final_total > 0 && $order->final_total != $order->grand_total)
                            <div class="flex justify-between pt-3 mt-2 border-t-2 border-gray-300 dark:border-gray-600">
                                <dt class="font-bold text-gray-900 dark:text-white text-base">Endbetrag</dt>
                                <dd class="font-bold text-lg text-gray-900 dark:text-white font-mono">
                                    {{ number_format($order->final_total, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @else
                            <div class="flex justify-between pt-3 mt-2 border-t-2 border-gray-300 dark:border-gray-600">
                                <dt class="font-bold text-gray-900 dark:text-white text-base">Gesamtbetrag</dt>
                                <dd class="font-bold text-lg text-gray-900 dark:text-white font-mono">
                                    {{ number_format($order->grand_total, 3, ',', '.') }} {{ $order->currency_code }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Aktionen</span>
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Status ändern -->
                        <div>
                            <form action="{{ route('orders.update-status', $order) }}" method="POST">
                                @csrf
                                <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-exchange-alt text-blue-500"></i>
                                    <span>Status ändern</span>
                                </label>
                                <select name="status" id="status"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 mb-3 transition-all">
                                    <option value="Processing" {{ $order->status === 'Processing' ? 'selected' : '' }}>⚙️ Processing</option>
                                    <option value="Ready" {{ $order->status === 'Ready' ? 'selected' : '' }}>✅ Ready</option>
                                    <option value="Paid" {{ $order->status === 'Paid' ? 'selected' : '' }}>💰 Paid</option>
                                    <option value="Packed" {{ $order->status === 'Packed' ? 'selected' : '' }}>📦 Packed</option>
                                    <option value="Shipped" {{ $order->status === 'Shipped' ? 'selected' : '' }}>🚚 Shipped</option>
                                </select>
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Status aktualisieren</span>
                                </button>
                                @error('status')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="fa-solid fa-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                                @enderror
                            </form>
                        </div>

                        <!-- Sendungsverfolgung -->
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <form action="{{ route('orders.update-shipping', $order) }}" method="POST">
                                @csrf
                                <label for="tracking_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-truck-fast text-indigo-500"></i>
                                    <span>Sendungsverfolgung</span>
                                </label>
                                <div class="space-y-3">
                                    <div>
                                        <input type="text"
                                               name="tracking_number"
                                               id="tracking_number"
                                               value="{{ old('tracking_number', $order->tracking_number) }}"
                                               placeholder="Tracking-Nummer"
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                        @error('tracking_number')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="fa-solid fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <input type="url"
                                               name="tracking_link"
                                               id="tracking_link"
                                               value="{{ old('tracking_link', $order->tracking_link) }}"
                                               placeholder="Tracking-Link (optional)"
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                        @error('tracking_link')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="fa-solid fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>

                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all">
                                        <i class="fa-solid fa-save"></i>
                                        <span>Speichern</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Als versendet markieren -->
                        @if($order->status !== 'Shipped')
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                <form action="{{ route('orders.ship', $order) }}" method="POST" onsubmit="return confirm('Bestellung wirklich als versendet markieren?')">
                                    @csrf
                                    <input type="hidden" name="tracking_number" value="{{ $order->tracking_number ?? '' }}">
                                    <input type="hidden" name="tracking_link" value="{{ $order->tracking_link ?? '' }}">
                                    <button type="submit"
                                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                        {{ !$order->tracking_number ? 'disabled title="Bitte zuerst Sendungsnummer eingeben"' : '' }}>
                                        <i class="fa-solid fa-truck"></i> Als versendet markieren
                                    </button>
                                    @if(!$order->tracking_number)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bitte geben Sie zuerst eine Sendungsnummer ein</p>
                                    @endif
                                </form>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            @if(!$order->invoice)
                                <form action="{{ route('orders.create-invoice', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                        <i class="fa-solid fa-file-invoice"></i> Rechnung erstellen
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('invoices.show', $order->invoice) }}"
                                   class="block w-full px-4 py-2 bg-purple-600 text-white text-center rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fa-solid fa-file-invoice"></i> Rechnung anzeigen
                                </a>
                            @endif
                        </div>

                        <div>
                            <form action="{{ route('orders.sync-all') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fa-solid fa-sync"></i> Mit BrickLink synchronisieren
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Feedback -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-star"></i>
                            <span>Bewertungen</span>
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        @foreach($order->feedback as $feedback)

                            <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-600 p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-user-circle text-amber-600"></i>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $feedback->from }}</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $feedback->created_at->format('d.m.Y') }}
                                    </span>
                                </div>
                                <div class="flex items-center mb-3">
                                    @for($i = 0; $i < 5; $i++)
                                        @if($i < (5 - $feedback->rating))
                                            <i class="fa-solid fa-star text-amber-300"></i>
                                        @else
                                            <i class="fa-solid fa-star text-amber-600"></i>
                                        @endif
                                    @endfor
                                </div>
                                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $feedback->comment }}</p>
                            </div>


                        @endforeach
                        <!-- Feedback senden (wenn noch nicht vorhanden) -->
                        @if(! $order->status === 'Shipped')
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-paper-plane text-indigo-500"></i>
                                    Bewertung abgeben
                                </h3>
                                <form action="{{ route('orders.feedback.store', $order) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Bewertung
                                        </label>
                                        <select name="rating" id="rating" required
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                            <option value="0">👍 Praise (Positiv)</option>
                                            <option value="1">➖ Neutral</option>
                                            <option value="2">👎 Complaint (Negativ)</option>
                                        </select>
                                        @error('rating')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Kommentar
                                        </label>
                                        <textarea name="comment" id="comment" rows="3" required maxlength="500"
                                                  placeholder="Ihre Bewertung für den Käufer..."
                                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"></textarea>
                                        @error('comment')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all">
                                        <i class="fa-solid fa-star"></i>
                                        <span>Bewertung absenden</span>
                                    </button>
                                </form>
                            </div>
                        @endif

                        <!-- Feedback synchronisieren -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <form action="{{ route('orders.feedback.sync', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                                    <i class="fa-solid fa-sync"></i>
                                    <span>Bewertungen aktualisieren</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-layouts.app>
