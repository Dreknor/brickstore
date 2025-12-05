<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService) {}

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = auth()->user()->store->invoices()->with('order');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by paid status
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'invoice_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $invoices = $query->paginate(25);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $invoice->load('order.items');

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Create invoice from order
     */
    public function createFromOrder(Order $order)
    {
        Gate::authorize('update', $order);

        // Check if invoice already exists
        if ($order->invoice) {
            return redirect()->back()->with('error', 'Invoice already exists for this order');
        }

        try {
            $invoice = $this->invoiceService->createInvoiceFromOrder($order);
            $this->invoiceService->savePDF($invoice);

            ActivityLogger::info('invoice.created', "Invoice {$invoice->invoice_number} created for order {$order->bricklink_order_id}", $invoice);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully');
        } catch (\Exception $e) {
            ActivityLogger::error('invoice.create_failed', "Failed to create invoice for order {$order->bricklink_order_id}: {$e->getMessage()}", $order);

            return redirect()->back()->with('error', 'Failed to create invoice: '.$e->getMessage());
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadPDF(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        return $this->invoiceService->downloadPDF($invoice);
    }

    /**
     * Stream invoice PDF
     */
    public function streamPDF(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        return $this->invoiceService->streamPDF($invoice);
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);
        // 1. Definiere einen eindeutigen Schlüssel für diesen dynamischen Mailer
        // (z.B. basierend auf der User-ID oder einfach 'dynamic_smtp')
        $mailerName = 'dynamic_smtp';

        $smtpSettings = [
            'host' => $invoice->store->smtp_host,
            'port' => $invoice->store->smtp_port,
            'encryption' => $invoice->store->smtp_encryption ?? 'tls', // 'tls' oder 'ssl'
            'username' => $invoice->store->smtp_username,
            'password' => $invoice->store->smtp_password,
            'from_address' => $invoice->store->smtp_from_address,
            'from_name' => $invoice->store->smtp_from_name,
        ];

        // 2. Setze die Konfiguration zur Laufzeit
        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'host' => $smtpSettings['host'],
            'port' => $smtpSettings['port'],
            'encryption' => $smtpSettings['encryption'], // 'tls' oder 'ssl'
            'username' => $smtpSettings['username'],
            'password' => $smtpSettings['password'],
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        // Optional: Auch die "From"-Adresse dynamisch setzen (falls nötig)
        $fromAddress = $smtpSettings['from_address'] ?? config('mail.from.address');
        $fromName = $smtpSettings['from_name'] ?? config('mail.from.name');

        // 3. Wähle explizit diesen Mailer aus und versende
        Mail::mailer($mailerName)
            ->to($invoice->customer_email)
            ->send((new \App\Mail\InvoiceMail($invoice))->from($fromAddress, $fromName));
        try {

            Log::debug("Queuing email for invoice {$invoice->invoice_number} to {$invoice->customer_email}");

            return redirect()->back()->with('success', 'Invoice email is being sent');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send invoice: '.$e->getMessage());
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        $this->invoiceService->markAsPaid($invoice);

        return redirect()->back()->with('success', 'Invoice marked as paid');
    }

    /**
     * Reupload invoice to Nextcloud
     */
    public function reuploadToNextcloud(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        if (! $invoice->store->nextcloud_url) {
            return redirect()->back()->with('error', 'Nextcloud is not configured for this store');
        }

        try {
            $this->invoiceService->reuploadToNextcloud($invoice);

            ActivityLogger::info('invoice.nextcloud_reupload', "Invoice {$invoice->invoice_number} queued for Nextcloud reupload", $invoice);

            return redirect()->back()->with('success', 'Invoice reupload to Nextcloud has been queued');
        } catch (\Exception $e) {
            ActivityLogger::error('invoice.nextcloud_reupload_failed', "Failed to reupload invoice {$invoice->invoice_number}: {$e->getMessage()}", $invoice);

            return redirect()->back()->with('error', 'Failed to reupload invoice: '.$e->getMessage());
        }
    }

    /**
     * Update invoice data and regenerate PDF
     */
    public function update(Invoice $invoice, Request $request)
    {
        Gate::authorize('update', $invoice);

        $validated = $request->validate([
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_address1' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_postal_code' => 'nullable|string',
            'customer_country' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->invoiceService->updateInvoiceAndRegeneratePDF($invoice, $validated);

            ActivityLogger::info('invoice.updated', "Invoice {$invoice->invoice_number} updated and regenerated", $invoice);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice updated and PDF regenerated. Nextcloud upload has been queued.');
        } catch (\Exception $e) {
            ActivityLogger::error('invoice.update_failed', "Failed to update invoice {$invoice->invoice_number}: {$e->getMessage()}", $invoice);

            return redirect()->back()->with('error', 'Failed to update invoice: '.$e->getMessage());
        }
    }
}
