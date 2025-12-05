<x-layouts.app title="Bestellungen">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
        <!-- Header mit Gradient -->
        <div class="bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 dark:from-blue-800 dark:via-blue-700 dark:to-indigo-700 px-6 py-4">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-shopping-bag"></i>
                            Bestellungen
                        </h1>
                    </div>
                    <div class="flex gap-2">
                        <form action="{{ route('orders.index') }}" method="GET" class="flex gap-2">
                            <div class="relative">
                                <input type="text"
                                       name="search"
                                       value="{{ request('search') }}"
                                       placeholder="Order-ID, Käufer..."
                                       class="pl-9 pr-3 py-2 bg-white/20 border border-white/30 backdrop-blur rounded-lg text-sm text-white placeholder-blue-100 focus:outline-none focus:bg-white/30 focus:ring-2 focus:ring-white/50 transition-all">
                                <i class="fa-solid fa-search absolute left-3 top-2.5 text-blue-100 text-sm"></i>
                            </div>
                            <button type="submit"
                                    class="px-3 py-2 bg-white/20 border border-white/30 text-white text-sm rounded-lg hover:bg-white/30 backdrop-blur transition-all font-medium">
                                Suchen
                            </button>
                        </form>
                        <form action="{{ route('orders.sync-all') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-2 bg-white text-blue-600 text-sm rounded-lg hover:bg-blue-50 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i class="fa-solid fa-sync mr-1"></i> Sync
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/30 dark:to-green-900/30 border-l-4 border-emerald-500 rounded-lg shadow-lg backdrop-blur flex items-center justify-between animate-in fade-in slide-in-from-top">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-800 rounded-full">
                        <i class="fa-solid fa-check-circle text-emerald-600 dark:text-emerald-300"></i>
                    </div>
                    <span class="text-emerald-800 dark:text-emerald-200 font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 transition-colors">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/30 border-l-4 border-red-500 rounded-lg shadow-lg backdrop-blur flex items-center justify-between animate-in fade-in slide-in-from-top">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-800 rounded-full">
                        <i class="fa-solid fa-exclamation-circle text-red-600 dark:text-red-300"></i>
                    </div>
                    <span class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 transition-colors">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        @endif


        <!-- Filters -->
        <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <form action="{{ route('orders.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <select name="status"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">Alle Status</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Updated" {{ request('status') === 'Updated' ? 'selected' : '' }}>Updated</option>
                    <option value="Processing" {{ request('status') === 'Processing' ? 'selected' : '' }}>Processing</option>
                    <option value="Ready" {{ request('status') === 'Ready' ? 'selected' : '' }}>Ready</option>
                    <option value="Paid" {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
                    <option value="Packed" {{ request('status') === 'Packed' ? 'selected' : '' }}>Packed</option>
                    <option value="Shipped" {{ request('status') === 'Shipped' ? 'selected' : '' }}>Shipped</option>
                    <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                </select>

                <select name="is_paid"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">Alle Zahlungsstatus</option>
                    <option value="1" {{ request('is_paid') === '1' ? 'selected' : '' }}>Bezahlt</option>
                    <option value="0" {{ request('is_paid') === '0' ? 'selected' : '' }}>Unbezahlt</option>
                </select>

                <button type="submit"
                        class="px-3 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-medium shadow-sm hover:shadow-md">
                    <i class="fa-solid fa-check mr-1"></i> Anwenden
                </button>

                @if(request()->hasAny(['status', 'is_paid', 'search']))
                    <a href="{{ route('orders.index') }}"
                       class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all font-medium text-center">
                        <i class="fa-solid fa-redo mr-1"></i> Zurücksetzen
                    </a>
                @endif
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Order-ID
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Käufer
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Datum
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Betrag
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Zahlung
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($orders as $order)
                            <tr class="hover:bg-blue-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <i class="fa-solid fa-bag-shopping text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">#{{ $order->bricklink_order_id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                            {{ substr($order->buyer_name, 0, 1) }}
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $order->buyer_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fa-solid fa-calendar text-blue-500 mr-2"></i>
                                    {{ $order->order_date->format('d.m.Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg">
                                        {{ number_format($order->grand_total, 2, ',', '.') }} {{ $order->currency_code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 text-xs font-bold rounded-lg inline-flex items-center gap-2
                                        @if($order->status === 'Shipped') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($order->status === 'Paid') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($order->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                        @elseif($order->status === 'Processing') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                        @elseif($order->status === 'Packed') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300
                                        @endif">
                                        <span class="text-lg">
                                            @if($order->status === 'Shipped') 🚚
                                            @elseif($order->status === 'Paid') 💰
                                            @elseif($order->status === 'Pending') ⏳
                                            @elseif($order->status === 'Processing') ⚙️
                                            @elseif($order->status === 'Packed') 📦
                                            @else 📋
                                            @endif
                                        </span>
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($order->is_paid)
                                        <span class="inline-flex items-center gap-2 text-green-600 dark:text-green-400 font-semibold">
                                            <i class="fa-solid fa-circle-check text-lg"></i> Bezahlt
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 text-red-600 dark:text-red-400 font-semibold">
                                            <i class="fa-solid fa-circle-xmark text-lg"></i> Offen
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('orders.show', $order) }}"
                                           class="inline-flex items-center gap-2 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-all hover:shadow-lg transform hover:scale-105"
                                           title="Details anzeigen">
                                            <i class="fa-solid fa-eye text-sm"></i>
                                            <span class="text-xs font-medium">Details</span>
                                        </a>
                                        <a href="{{ route('orders.pack', $order) }}"
                                           class="inline-flex items-center gap-2 px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all hover:shadow-lg transform hover:scale-105"
                                           title="Packen">
                                            <i class="fa-solid fa-box text-sm"></i>
                                            <span class="text-xs font-medium">Packen</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fa-solid fa-inbox text-6xl mb-4 text-gray-300 dark:text-gray-600"></i>
                                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Keine Bestellungen gefunden</p>
                                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Versuche deine Filter anzupassen oder synchronisiere neue Bestellungen</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

