<x-layouts.app title="Rechnung {{ $invoice->invoice_number }}">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Rechnung {{ $invoice->invoice_number }}
                </h1>
                @if($invoice->invoice_date)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Erstellt am {{ $invoice->invoice_date->format('d.m.Y') }}
                    </p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('invoices.index') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fa-solid fa-arrow-left"></i> Zurück
                </a>
                <a href="{{ route('invoices.download-pdf', $invoice) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fa-solid fa-download"></i> PDF
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- PDF Preview -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                        <i class="fa-solid fa-file-pdf text-red-600"></i> Rechnungsvorschau
                    </h2>
                    <div class="border border-gray-300 dark:border-gray-600 rounded">
                        <iframe src="{{ route('invoices.stream-pdf', $invoice) }}"
                                class="w-full"
                                style="height: 800px;"></iframe>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status</h2>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Rechnungsstatus</span>
                            <div class="mt-1">
                                <span class="px-3 py-1 text-sm font-semibold rounded
                                    @if($invoice->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @endif">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Zahlung</span>
                            <div class="mt-1">
                                @if($invoice->is_paid)
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

                <!-- Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informationen</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Kunde:</dt>
                            <dd class="text-gray-900 dark:text-white font-medium">{{ $invoice->customer_name }}</dd>
                        </div>
                        @if($invoice->invoice_date)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Rechnungsdatum:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $invoice->invoice_date->format('d.m.Y') }}</dd>
                            </div>
                        @endif
                        @if(!$invoice->is_paid && $invoice->due_date)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Fällig am:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $invoice->due_date->format('d.m.Y') }}</dd>
                            </div>
                        @endif
                        @if($invoice->is_paid && $invoice->paid_date)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Bezahlt am:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $invoice->paid_date->format('d.m.Y') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t">
                            <dt class="font-semibold text-gray-900 dark:text-white">Gesamtbetrag:</dt>
                            <dd class="font-bold text-lg text-gray-900 dark:text-white">
                                {{ number_format($invoice->total, 2, ',', '.') }} {{ $invoice->currency }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Nextcloud Status -->
                <x-invoice.nextcloud-status :invoice="$invoice" />

                <!-- Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aktionen</h2>
                    <div class="space-y-2">
                        @if(!$invoice->is_paid)
                            <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <i class="fa-solid fa-check"></i> Als bezahlt markieren
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('invoices.send-email', $invoice) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fa-solid fa-envelope"></i> Per E-Mail senden
                            </button>
                        </form>

                        <a href="{{ route('orders.show', $invoice->order) }}"
                           class="block w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-center">
                            <i class="fa-solid fa-box"></i> Zur Bestellung
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

