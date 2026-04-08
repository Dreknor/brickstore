@props(['status'])

@php
    $labels = [
        'Pending' => 'Ausstehend',
        'Updated' => 'Aktualisiert',
        'Processing' => 'In Bearbeitung',
        'Ready' => 'Bereit',
        'Paid' => 'Bezahlt',
        'Packed' => 'Verpackt',
        'Shipped' => 'Versendet',
        'Received' => 'Empfangen',
        'Completed' => 'Abgeschlossen',
        'OCR' => 'OCR',
        'NPB' => 'Nicht bezahlt',
        'NPX' => 'Nicht bezahlt (storniert)',
        'NRS' => 'Nicht versendet',
        'NSS' => 'Nicht versendet (storniert)',
        'Cancelled' => 'Storniert',
        'Purged' => 'Gelöscht',
    ];

    $colors = [
        'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'Updated' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'Processing' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        'Ready' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'Paid' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'Packed' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
        'Shipped' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'Received' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'Completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'Cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'NPB' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'NPX' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'NRS' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'NSS' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    ];

    $label = $labels[$status] ?? $status;
    $colorClass = $colors[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
@endphp

<span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full {{ $colorClass }}">
    {{ $label }}
</span>

