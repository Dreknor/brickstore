@props(['store' => null])

@php
    $store = $store ?? auth()->user()->store;
    if (!$store || !$store->nextcloud_url) {
        return;
    }

    $totalInvoices = $store->invoices()->count();
    $uploadedInvoices = $store->invoices()->where('uploaded_to_nextcloud', true)->count();
    $pendingInvoices = $totalInvoices - $uploadedInvoices;
    $uploadRate = $totalInvoices > 0 ? round(($uploadedInvoices / $totalInvoices) * 100) : 0;
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="fa-brands fa-nextcloud text-blue-600 mr-2"></i> Nextcloud Upload-Status
        </h2>
        <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm">
            Alle anzeigen →
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <!-- Total -->
        <div class="text-center">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalInvoices }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Rechnungen gesamt</div>
        </div>

        <!-- Uploaded -->
        <div class="text-center">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $uploadedInvoices }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Hochgeladen</div>
        </div>

        <!-- Pending -->
        <div class="text-center">
            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingInvoices }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Ausstehend</div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Upload-Erfolgsquote</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $uploadRate }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                 style="width: {{ $uploadRate }}%"></div>
        </div>
    </div>

    <!-- Status Info -->
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <p class="text-sm text-blue-900 dark:text-blue-300">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>{{ $store->name }}</strong> ist mit Nextcloud verbunden.
            @if($pendingInvoices > 0)
                {{ $pendingInvoices }} Rechnung(en) stehen zum Upload aus.
            @else
                Alle Rechnungen wurden erfolgreich hochgeladen!
            @endif
        </p>
    </div>

    <!-- Recent Uploads -->
    @php
        $recentUploads = $store->invoices()
            ->where('uploaded_to_nextcloud', true)
            ->orderBy('uploaded_at', 'desc')
            ->take(5)
            ->get();
    @endphp

    @if($recentUploads->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Letzte Uploads</h3>
            <div class="space-y-2">
                @foreach($recentUploads as $upload)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-400">
                            <i class="fa-solid fa-file-pdf text-red-600 mr-1"></i>
                            {{ $upload->invoice_number }}
                        </span>
                        <span class="text-gray-500 dark:text-gray-500">
                            {{ $upload->uploaded_at?->diffForHumans() ?? 'unbekannt' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

