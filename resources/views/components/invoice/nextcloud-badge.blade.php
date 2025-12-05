@props(['invoice', 'size' => 'md'])

@php
    $sizeClasses = match($size) {
        'sm' => 'text-xs px-2 py-1',
        'lg' => 'text-base px-4 py-2',
        'md' => 'text-sm px-3 py-1',
    };
@endphp

@if($invoice->store->nextcloud_url)
    @if($invoice->uploaded_to_nextcloud)
        <span class="inline-flex items-center gap-1 {{ $sizeClasses }} rounded font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300"
              title="{{ $invoice->uploaded_at?->format('d.m.Y H:i') }}">
            <i class="fa-solid fa-cloud-check"></i>
            <span>Nextcloud</span>
        </span>
    @else
        <span class="inline-flex items-center gap-1 {{ $sizeClasses }} rounded font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">
            <i class="fa-solid fa-hourglass-end"></i>
            <span>Ausstehend</span>
        </span>
    @endif
@else
    <span class="inline-flex items-center gap-1 {{ $sizeClasses }} rounded font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
        <i class="fa-solid fa-ban"></i>
        <span>N/A</span>
    </span>
@endif

