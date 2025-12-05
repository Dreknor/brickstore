<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use App\Services\InvoiceService;

$invoice = Invoice::find(5);

if (!$invoice) {
    echo "Invoice ID 5 not found!\n";
    exit(1);
}

echo "Invoice found: {$invoice->invoice_number}\n";
echo "Store: {$invoice->store->name}\n";

$service = new InvoiceService();

try {
    $path = $service->savePDF($invoice, false);
    echo "✅ PDF saved to: {$path}\n";

    $fullPath = storage_path('app/private/' . $path);
    if (file_exists($fullPath)) {
        echo "✅ File exists at: {$fullPath}\n";
        echo "✅ File size: " . filesize($fullPath) . " bytes\n";
    } else {
        echo "❌ File NOT found at: {$fullPath}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}

