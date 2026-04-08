@php
    $store = auth()->user()->store;
    if (!$store) return;

    $now = now();
    $startOfMonth = $now->copy()->startOfMonth();

    // Order statistics
    $totalOrders = $store->orders()->count();
    $pendingOrders = $store->orders()->whereIn('status', ['Pending', 'Updated'])->count();
    $paidUnshipped = $store->orders()->where('is_paid', true)->whereNull('shipped_date')->whereNotIn('status', ['Shipped', 'Completed', 'Cancelled'])->count();
    $shippedThisMonth = $store->orders()->where('shipped_date', '>=', $startOfMonth)->count();

    // Revenue
    $revenueThisMonth = $store->orders()
        ->where('is_paid', true)
        ->where('paid_date', '>=', $startOfMonth)
        ->sum('grand_total');
    $revenueLastMonth = $store->orders()
        ->where('is_paid', true)
        ->where('paid_date', '>=', $now->copy()->subMonth()->startOfMonth())
        ->where('paid_date', '<', $startOfMonth)
        ->sum('grand_total');

    // Invoice statistics
    $draftInvoices = $store->invoices()->where('status', 'draft')->count();
    $unpaidInvoices = $store->invoices()->where('is_paid', false)->where('status', '!=', 'cancelled')->count();
    $unsentInvoices = $store->invoices()->where('sent_via_email', false)->where('status', 'draft')->count();

    // Orders without invoices (paid orders)
    $ordersWithoutInvoice = $store->orders()
        ->where('is_paid', true)
        ->whereDoesntHave('invoice')
        ->count();
@endphp

@if($store)
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <i class="fa-solid fa-chart-line"></i>
            <span>Bestellübersicht</span>
        </h2>
    </div>
    <div class="p-6">
        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Umsatz diesen Monat -->
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide">
                    Umsatz {{ $now->translatedFormat('F') }}
                </div>
                <div class="text-2xl font-bold text-green-800 dark:text-green-200 mt-1">
                    {{ number_format($revenueThisMonth, 2, ',', '.') }} €
                </div>
                @if($revenueLastMonth > 0)
                    @php $change = (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100; @endphp
                    <div class="text-xs mt-1 {{ $change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        <i class="fa-solid fa-{{ $change >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                        {{ number_format(abs($change), 0) }}% gg. Vormonat
                    </div>
                @endif
            </div>

            <!-- Offene Bestellungen -->
            <a href="{{ route('orders.index', ['status' => 'Pending']) }}" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 hover:ring-2 hover:ring-yellow-400 transition-all">
                <div class="text-xs font-semibold text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">
                    Ausstehend
                </div>
                <div class="text-2xl font-bold text-yellow-800 dark:text-yellow-200 mt-1">
                    {{ $pendingOrders }}
                </div>
                <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Bestellungen</div>
            </a>

            <!-- Versandbereit -->
            <a href="{{ route('orders.index', ['is_paid' => '1']) }}" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 hover:ring-2 hover:ring-blue-400 transition-all">
                <div class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">
                    Versandbereit
                </div>
                <div class="text-2xl font-bold text-blue-800 dark:text-blue-200 mt-1">
                    {{ $paidUnshipped }}
                </div>
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Bezahlt & nicht versendet</div>
            </a>

            <!-- Versendet diesen Monat -->
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <div class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide">
                    Versendet ({{ $now->translatedFormat('M') }})
                </div>
                <div class="text-2xl font-bold text-purple-800 dark:text-purple-200 mt-1">
                    {{ $shippedThisMonth }}
                </div>
                <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">von {{ $totalOrders }} gesamt</div>
            </div>
        </div>

        <!-- Ausstehende Aktionen -->
        @if($ordersWithoutInvoice > 0 || $unsentInvoices > 0 || $unpaidInvoices > 0)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-500"></i>
                    Ausstehende Aktionen
                </h3>
                <div class="space-y-2">
                    @if($ordersWithoutInvoice > 0)
                        <a href="{{ route('orders.index', ['is_paid' => '1']) }}"
                           class="flex items-center justify-between px-3 py-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                            <span class="text-sm text-amber-800 dark:text-amber-300">
                                <i class="fa-solid fa-file-invoice mr-2"></i>
                                {{ $ordersWithoutInvoice }} Bestellung(en) ohne Rechnung
                            </span>
                            <i class="fa-solid fa-chevron-right text-amber-400"></i>
                        </a>
                    @endif
                    @if($unsentInvoices > 0)
                        <a href="{{ route('invoices.index', ['status' => 'draft']) }}"
                           class="flex items-center justify-between px-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <span class="text-sm text-blue-800 dark:text-blue-300">
                                <i class="fa-solid fa-envelope mr-2"></i>
                                {{ $unsentInvoices }} Rechnung(en) noch nicht versendet
                            </span>
                            <i class="fa-solid fa-chevron-right text-blue-400"></i>
                        </a>
                    @endif
                    @if($unpaidInvoices > 0)
                        <a href="{{ route('invoices.index', ['is_paid' => '0']) }}"
                           class="flex items-center justify-between px-3 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                            <span class="text-sm text-red-800 dark:text-red-300">
                                <i class="fa-solid fa-euro-sign mr-2"></i>
                                {{ $unpaidInvoices }} unbezahlte Rechnung(en)
                            </span>
                            <i class="fa-solid fa-chevron-right text-red-400"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endif

