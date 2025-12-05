@props(['invoice'])

@if($invoice->store->nextcloud_url)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fa-brands fa-nextcloud text-blue-600"></i> Nextcloud
        </h2>
        <div class="space-y-3">
            @if($invoice->uploaded_to_nextcloud)
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-check-circle text-green-600"></i>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                            Zu Nextcloud hochgeladen
                        </span>
                    </div>
                    @if($invoice->uploaded_at)
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $invoice->uploaded_at->format('d.m.Y H:i') }}
                        </p>
                    @endif
                    @if($invoice->nextcloud_path)
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            <i class="fa-solid fa-folder"></i> {{ $invoice->nextcloud_path }}
                        </p>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-hourglass-end text-yellow-600"></i>
                    <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                        Upload ausstehend...
                    </span>
                </div>
            @endif

            <form action="{{ route('invoices.reupload-nextcloud', $invoice) }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Erneut hochladen
                </button>
            </form>
        </div>
    </div>
@endif

