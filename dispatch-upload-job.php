<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use App\Jobs\UploadInvoiceToNextcloudJob;

$invoice = Invoice::find(5);

if (!$invoice) {
    echo "Invoice ID 5 not found!\n";
    exit(1);
}

echo "Invoice found: {$invoice->invoice_number}\n";
echo "Dispatching upload job...\n";

UploadInvoiceToNextcloudJob::dispatch($invoice);

echo "✅ Job dispatched successfully!\n";
echo "Check logs: tail -f storage/logs/laravel.log\n";

