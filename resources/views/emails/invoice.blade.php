<x-mail::message>
# Ihre Rechnung {{ $invoice->invoice_number }}

Sehr geehrte(r) {{ $invoice->customer_name }},

anbei erhalten Sie Ihre Rechnung @if($invoice->service_date) für Ihre Bestellung vom {{ $invoice->service_date->format('d.m.Y') }} @endif.

## Rechnungsdetails

- **Rechnungsnummer:** {{ $invoice->invoice_number }}
@if($invoice->invoice_date)
- **Rechnungsdatum:** {{ $invoice->invoice_date->format('d.m.Y') }}
@endif
- **Gesamtbetrag:** {{ number_format($invoice->total, 2, ',', '.') }} {{ $invoice->currency }}
@if($invoice->due_date)
- **Zahlungsziel:** {{ $invoice->due_date->format('d.m.Y') }}
@endif

@if(!$invoice->is_paid)
## Zahlungsinformationen

Bitte überweisen Sie den Betrag auf folgendes Konto:

@if($store->bank_account_holder && $store->iban)
- **Kontoinhaber:** {{ $store->bank_account_holder }}
- **IBAN:** {{ $store->iban }}
@if($store->bic)
- **BIC:** {{ $store->bic }}
@endif
- **Verwendungszweck:** {{ $invoice->invoice_number }}
@endif
@endif

Vielen Dank für Ihre Bestellung!

Mit freundlichen Grüßen,<br>
{{ $store->company_name }}<br>
{{ $store->owner_name }}

---

<small style="color: #666;">
{{ $store->company_name }} • {{ $store->street }} • {{ $store->postal_code }} {{ $store->city }}<br>
@if($store->tax_number)
Steuernr.: {{ $store->tax_number }}
@endif
@if($store->vat_id)
@if($store->tax_number) • @endif
USt-IdNr.: {{ $store->vat_id }}
@endif
</small>
</x-mail::message>

