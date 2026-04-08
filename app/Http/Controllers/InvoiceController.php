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
            return redirect()->back()->with('error', 'Für diese Bestellung existiert bereits eine Rechnung');
        }

        try {
            $invoice = $this->invoiceService->createInvoiceFromOrder($order);
            $this->invoiceService->savePDF($invoice);

            ActivityLogger::info('invoice.created', "Rechnung {$invoice->invoice_number} erstellt für Bestellung {$order->bricklink_order_id}", $invoice);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Rechnung erfolgreich erstellt');
        } catch (\Exception $e) {
            ActivityLogger::error('invoice.create_failed', "Rechnung für Bestellung {$order->bricklink_order_id} konnte nicht erstellt werden: {$e->getMessage()}", $order);

            return redirect()->back()->with('error', 'Rechnung konnte nicht erstellt werden: '.$e->getMessage());
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
     * Show email preview before sending
     */
    public function emailPreview(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $invoice->load('store', 'order.items');

        // Render the email as HTML for preview
        $mailable = new \App\Mail\InvoiceMail($invoice);
        $emailHtml = $mailable->render();

        $store = $invoice->store;
        $fromAddress = $store->smtp_from_address ?? $store->user->email;
        $fromName = $store->smtp_from_name ?? $store->company_name;
        $subject = 'Ihre Rechnung ' . $invoice->invoice_number;

        return view('invoices.email-preview', compact(
            'invoice',
            'emailHtml',
            'fromAddress',
            'fromName',
            'subject'
        ));
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        try {
            $mailerName = 'dynamic_smtp';

            $smtpSettings = [
                'host' => $invoice->store->smtp_host,
                'port' => $invoice->store->smtp_port,
                'encryption' => $invoice->store->smtp_encryption ?? 'tls',
                'username' => $invoice->store->smtp_username,
                'password' => $invoice->store->smtp_password,
                'from_address' => $invoice->store->smtp_from_address,
                'from_name' => $invoice->store->smtp_from_name,
            ];

            Config::set("mail.mailers.{$mailerName}", [
                'transport' => 'smtp',
                'host' => $smtpSettings['host'],
                'port' => $smtpSettings['port'],
                'encryption' => $smtpSettings['encryption'],
                'username' => $smtpSettings['username'],
                'password' => $smtpSettings['password'],
                'timeout' => null,
                'local_domain' => parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST),
            ]);

            $fromAddress = $smtpSettings['from_address'] ?? config('mail.from.address');
            $fromName = $smtpSettings['from_name'] ?? config('mail.from.name');

            Log::debug("Sende Rechnungs-E-Mail {$invoice->invoice_number} an {$invoice->customer_email}");

            Mail::mailer($mailerName)
                ->to($invoice->customer_email)
                ->send((new \App\Mail\InvoiceMail($invoice))->from($fromAddress, $fromName));

            // Rechnung als versendet markieren
            $this->invoiceService->markAsSent($invoice);

            ActivityLogger::info('invoice.email_sent', "Rechnung {$invoice->invoice_number} per E-Mail versendet an {$invoice->customer_email}", $invoice);

            return redirect()->back()->with('success', 'Rechnung wurde erfolgreich per E-Mail versendet');
        } catch (\Exception $e) {
            Log::error("E-Mail-Versand fehlgeschlagen für Rechnung {$invoice->invoice_number}", [
                'error' => $e->getMessage(),
                'customer_email' => $invoice->customer_email,
            ]);

            ActivityLogger::error('invoice.email_failed', "E-Mail-Versand fehlgeschlagen für Rechnung {$invoice->invoice_number}: {$e->getMessage()}", $invoice);

            return redirect()->back()->with('error', 'E-Mail-Versand fehlgeschlagen: '.$e->getMessage());
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        $this->invoiceService->markAsPaid($invoice);

        return redirect()->back()->with('success', 'Rechnung als bezahlt markiert');
    }

    /**
     * Manually mark invoice as sent (e.g. when sent by hand, post, or external email)
     */
    public function markAsSent(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        $this->invoiceService->markAsSent($invoice);

        ActivityLogger::info('invoice.marked_sent', "Rechnung {$invoice->invoice_number} manuell als versendet markiert", $invoice);

        return redirect()->back()->with('success', 'Rechnung als versendet markiert');
    }

    /**
     * Reupload invoice to Nextcloud
     */
    public function reuploadToNextcloud(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        if (! $invoice->store->nextcloud_url) {
            return redirect()->back()->with('error', 'Nextcloud ist für diesen Store nicht konfiguriert');
        }

        try {
            $this->invoiceService->reuploadToNextcloud($invoice);

            ActivityLogger::info('invoice.nextcloud_reupload', "Rechnung {$invoice->invoice_number} für Nextcloud-Upload eingereiht", $invoice);

            return redirect()->back()->with('success', 'Rechnung wird erneut zu Nextcloud hochgeladen');
        } catch (\Exception $e) {
            ActivityLogger::error('invoice.nextcloud_reupload_failed', "Nextcloud-Upload fehlgeschlagen für Rechnung {$invoice->invoice_number}: {$e->getMessage()}", $invoice);

            return redirect()->back()->with('error', 'Nextcloud-Upload fehlgeschlagen: '.$e->getMessage());
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

            ActivityLogger::info('invoice.updated', "Rechnung {$invoice->invoice_number} aktualisiert und neu generiert", $invoice);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Rechnung aktualisiert und PDF neu generiert. Nextcloud-Upload wurde eingereiht.');
        } catch (\Exception $e) {
            ActivityLogger::error('invoice.update_failed', "Aktualisierung der Rechnung {$invoice->invoice_number} fehlgeschlagen: {$e->getMessage()}", $invoice);

            return redirect()->back()->with('error', 'Rechnungsaktualisierung fehlgeschlagen: '.$e->getMessage());
        }
    }
}
