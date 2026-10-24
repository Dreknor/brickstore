<?php

namespace App\Services;

use App\Jobs\UploadInvoiceToNextcloudJob;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate invoice number for a store
     */
    public function generateInvoiceNumber(Store $store): string
    {
        return DB::transaction(function () use ($store) {
            // Lock the store row to prevent race conditions
            $store = Store::lockForUpdate()->findOrFail($store->id);

            // Increment counter
            $counter = $store->invoice_number_counter + 1;
            $store->update(['invoice_number_counter' => $counter]);

            // Generate number based on format
            $format = $store->invoice_number_format ?? 'RE-{year}-{number}';
            $year = now()->year;
            $paddedNumber = str_pad($counter, 4, '0', STR_PAD_LEFT);

            $invoiceNumber = str_replace(
                ['{year}', '{number}', '{month}'],
                [$year, $paddedNumber, now()->format('m')],
                $format
            );

            return $invoiceNumber;
        });
    }

    /**
     * Create invoice from order
     */
    public function createInvoiceFromOrder(Order $order): Invoice
    {
        $store = $order->store;
        $invoiceNumber = $this->generateInvoiceNumber($store);

        // Use values from BrickLink order
        $additionalItemsTotal = $order->items()->where('is_manual', true)->sum('total_price');
        $baseSubtotal = $order->subtotal ?? 0;
        $subtotal = $baseSubtotal + $additionalItemsTotal;
        $discount = ($order->discount ?? 0) + ($order->credit_coupon ?? 0);
        $shippingCost = $order->shipping_cost ?? 0;

        // Use VAT if available, otherwise fall back to tax
        $taxAmount = $order->vat_amount > 0 ? $order->vat_amount : ($order->tax ?? 0);
        $taxRate = $order->vat_rate ?? 0;

        // Calculate tax rate if not provided
        if ($store->is_small_business) {
            $taxRate = 0;
            $taxAmount = 0;
        } elseif ($taxRate == 0 && $taxAmount > 0 && ($baseSubtotal + $shippingCost) > 0) {
            // Calculate tax rate from tax amount
            $taxRate = round(($taxAmount / ($baseSubtotal + $shippingCost)) * 100, 2);
        }

        $additionalTax = $store->is_small_business ? 0 : round($additionalItemsTotal * $taxRate / 100, 2);
        $taxAmount += $additionalTax;

        // Use final_total if available, otherwise grand_total
        $baseTaxAmount = $taxAmount - $additionalTax;
        $baseTotal = $order->final_total > 0
            ? $order->final_total
            : ($order->grand_total ?? ($baseSubtotal + $shippingCost + $baseTaxAmount));
        $total = $baseTotal + $additionalItemsTotal + $additionalTax;

        return Invoice::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'service_date' => $order->shipped_date ?? $order->order_date ?? now(),
            'due_date' => $order->is_paid ? null : now()->addDays(14),
            'customer_name' => $order->shipping_name ?? $order->buyer_name,
            'customer_email' => $order->buyer_email,
            'customer_address1' => $order->shipping_address1,
            'customer_address2' => $order->shipping_address2,
            'customer_city' => $order->shipping_city,
            'customer_state' => $order->shipping_state,
            'customer_postal_code' => $order->shipping_postal_code,
            'customer_country' => $order->shipping_country,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $order->currency_code,
            'status' => 'draft',
            'is_paid' => $order->is_paid,
            'paid_date' => $order->paid_date,
            'is_small_business' => $store->is_small_business,
        ]);
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePDF(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $store = $invoice->store;
        $order = $invoice->order;

        $data = [
            'invoice' => $invoice,
            'store' => $store,
            'order' => $order,
            'items' => $order?->items ?? collect(),
            'additionalItems' => $invoice->items,
        ];

        $pdf = Pdf::loadView('invoices.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function addInvoiceItem(Invoice $invoice, array $data): InvoiceItem
    {
        $item = DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $quantity = (int) $data['quantity'];
            $unitPrice = round((float) $data['unit_price'], 2);
            $totalPrice = round($quantity * $unitPrice, 2);
            $taxAmount = $invoice->is_small_business
                ? 0
                : round($totalPrice * (float) $invoice->tax_rate / 100, 2);

            $item = $invoice->items()->create([
                'description' => $data['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            $invoice->update([
                'subtotal' => round((float) $invoice->subtotal + $totalPrice, 2),
                'tax_amount' => round((float) $invoice->tax_amount + $taxAmount, 2),
                'total' => round((float) $invoice->total + $totalPrice + $taxAmount, 2),
            ]);

            return $item;
        });

        $this->updateInvoiceAndRegeneratePDF($invoice, []);

        return $item;
    }

    public function removeInvoiceItem(InvoiceItem $item): void
    {
        $invoice = DB::transaction(function () use ($item) {
            $invoice = Invoice::lockForUpdate()->findOrFail($item->invoice_id);
            $item = InvoiceItem::where('invoice_id', $invoice->id)->lockForUpdate()->findOrFail($item->id);
            $taxAmount = $invoice->is_small_business
                ? 0
                : round((float) $item->total_price * (float) $invoice->tax_rate / 100, 2);

            $invoice->update([
                'subtotal' => round((float) $invoice->subtotal - (float) $item->total_price, 2),
                'tax_amount' => round((float) $invoice->tax_amount - $taxAmount, 2),
                'total' => round((float) $invoice->total - (float) $item->total_price - $taxAmount, 2),
            ]);

            $item->delete();

            return $invoice;
        });

        $this->updateInvoiceAndRegeneratePDF($invoice, []);
    }

    /**
     * Save PDF to storage
     */
    public function savePDF(Invoice $invoice, bool $uploadToNextcloud = true): string
    {
        $pdf = $this->generatePDF($invoice);

        $filename = 'invoices/'.$invoice->invoice_number.'.pdf';
        $path = storage_path('app/private/'.$filename);

        // Ensure directory exists
        $directory = dirname($path);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->save($path);

        $invoice->update(['pdf_path' => $filename]);

        // Queue Nextcloud upload if enabled
        if ($uploadToNextcloud && $invoice->store->nextcloud_url) {
            UploadInvoiceToNextcloudJob::dispatch($invoice);
        }

        return $filename;
    }

    /**
     * Get PDF stream for download
     */
    public function streamPDF(Invoice $invoice): \Illuminate\Http\Response
    {
        $pdf = $this->generatePDF($invoice);

        return $pdf->stream($invoice->invoice_number.'.pdf');
    }

    /**
     * Get PDF download response
     */
    public function downloadPDF(Invoice $invoice): \Illuminate\Http\Response
    {
        $pdf = $this->generatePDF($invoice);

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(Invoice $invoice): void
    {
        $invoice->update([
            'status' => 'sent',
            'sent_via_email' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, ?\DateTime $paidDate = null): void
    {
        $invoice->update([
            'status' => 'paid',
            'is_paid' => true,
            'paid_date' => $paidDate ?? now(),
        ]);
    }

    /**
     * Update invoice and regenerate PDF with Nextcloud upload
     */
    public function updateInvoiceAndRegeneratePDF(Invoice $invoice, array $data, bool $uploadToNextcloud = true): Invoice
    {
        // Update invoice data
        $invoice->update($data);

        // Delete old Nextcloud file if exists
        if ($invoice->nextcloud_path && $invoice->store->nextcloud_url) {
            try {
                $nextcloud = new NextcloudService($invoice->store);
                $nextcloud->deleteFile($invoice->nextcloud_path);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Could not delete old Nextcloud invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Regenerate PDF
        $this->savePDF($invoice, $uploadToNextcloud);

        return $invoice;
    }

    /**
     * Re-upload invoice to Nextcloud
     */
    public function reuploadToNextcloud(Invoice $invoice): void
    {
        if (! $invoice->pdf_path) {
            throw new \Exception('Invoice PDF not generated yet');
        }

        // Delete old Nextcloud file if exists
        if ($invoice->nextcloud_path && $invoice->store->nextcloud_url) {
            try {
                $nextcloud = new NextcloudService($invoice->store);
                $nextcloud->deleteFile($invoice->nextcloud_path);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Could not delete old Nextcloud invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Queue new upload
        UploadInvoiceToNextcloudJob::dispatch($invoice);
    }
}
