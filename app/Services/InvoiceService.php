<?php

namespace App\Services;

use App\Models\Invoice;
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
        $subtotal = $order->subtotal ?? 0;
        $shippingCost = $order->shipping_cost ?? 0;

        // Use VAT if available, otherwise fall back to tax
        $taxAmount = $order->vat_amount > 0 ? $order->vat_amount : ($order->tax ?? 0);
        $taxRate = $order->vat_rate ?? 0;

        // Calculate tax rate if not provided
        if ($store->is_small_business) {
            $taxRate = 0;
            $taxAmount = 0;
        } elseif ($taxRate == 0 && $taxAmount > 0 && ($subtotal + $shippingCost) > 0) {
            // Calculate tax rate from tax amount
            $taxRate = round(($taxAmount / ($subtotal + $shippingCost)) * 100, 2);
        }

        // Use final_total if available, otherwise grand_total
        $total = $order->final_total > 0 ? $order->final_total : ($order->grand_total ?? ($subtotal + $shippingCost + $taxAmount));

        return Invoice::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'customer_name' => $order->shipping_name ?? $order->buyer_name,
            'customer_email' => $order->buyer_email,
            'customer_address1' => $order->shipping_address1,
            'customer_address2' => $order->shipping_address2,
            'customer_city' => $order->shipping_city,
            'customer_state' => $order->shipping_state,
            'customer_postal_code' => $order->shipping_postal_code,
            'customer_country' => $order->shipping_country,
            'subtotal' => $subtotal,
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
            'items' => $order->items,
        ];

        $pdf = Pdf::loadView('invoices.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Save PDF to storage
     */
    public function savePDF(Invoice $invoice): string
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
}
