<x-layouts.app title="Rechnungen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Rechnungen
                </h1>
                <div class="flex gap-2">
                    <form action="{{ route('invoices.index') }}" method="GET" class="flex gap-2">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Rechnungsnummer, Kunde..."
                               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fa-solid fa-search"></i> Suchen
                        </button>
                    </form>
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
                <button onclick="this.parentElement.remove()" class="text-green-700 dark:text-green-300">
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
                <button onclick="this.parentElement.remove()" class="text-red-700 dark:text-red-300">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Filters -->
        <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <form action="{{ route('invoices.index') }}" method="GET" class="flex flex-wrap gap-4">
                <select name="status"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <option value="">Alle Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Entwurf</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Versendet</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Bezahlt</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Storniert</option>
                </select>

                <select name="is_paid"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <option value="">Alle Zahlungsstatus</option>
                    <option value="1" {{ request('is_paid') === '1' ? 'selected' : '' }}>Bezahlt</option>
                    <option value="0" {{ request('is_paid') === '0' ? 'selected' : '' }}>Unbezahlt</option>
                </select>

                <button type="submit"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Filter anwenden
                </button>

                @if(request()->hasAny(['status', 'is_paid', 'search']))
                    <a href="{{ route('invoices.index') }}"
                       class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Filter zurücksetzen
                    </a>
                @endif
            </form>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Rechnungsnr.
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Kunde
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Datum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Betrag
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Bezahlt
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Nextcloud
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Aktionen
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $invoice->invoice_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $invoice->customer_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $invoice->invoice_date->format('d.m.Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($invoice->total, 2, ',', '.') }} {{ $invoice->currency }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($invoice->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($invoice->status === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($invoice->is_paid)
                                    <span class="text-green-600 dark:text-green-400">
                                        <i class="fa-solid fa-check-circle"></i> Ja
                                    </span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">
                                        <i class="fa-solid fa-times-circle"></i> Nein
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-invoice.nextcloud-badge :invoice="$invoice" size="sm" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('invoices.show', $invoice) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                       title="Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('invoices.download-pdf', $invoice) }}"
                                       class="text-green-600 hover:text-green-900 dark:text-green-400"
                                       title="PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-file-invoice text-6xl mb-4 text-gray-300 dark:text-gray-600"></i>
                                <p>Keine Rechnungen gefunden</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($invoices->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

