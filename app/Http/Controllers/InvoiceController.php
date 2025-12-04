<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        try {
            // Queue the email job
            \App\Jobs\SendInvoiceEmailJob::dispatch($invoice);

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
}
