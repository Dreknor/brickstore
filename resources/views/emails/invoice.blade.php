<x-mail::message>
# Ihre Rechnung {{ $invoice->invoice_number }}

Sehr geehrte(r) {{ $invoice->customer_name }},

anbei erhalten Sie Ihre Rechnung für Ihre Bestellung vom {{ $invoice->service_date->format('d.m.Y') }}.

## Rechnungsdetails

- **Rechnungsnummer:** {{ $invoice->invoice_number }}
- **Rechnungsdatum:** {{ $invoice->invoice_date->format('d.m.Y') }}
- **Gesamtbetrag:** {{ number_format($invoice->total, 2, ',', '.') }} {{ $invoice->currency }}
- **Zahlungsziel:** {{ $invoice->due_date->format('d.m.Y') }}

@if(!$invoice->is_paid)
## Zahlungsinformationen

Bitte überweisen Sie den Betrag auf folgendes Konto:

- **Kontoinhaber:** {{ $store->bank_account_holder }}
- **IBAN:** {{ $store->bank_iban }}
- **BIC:** {{ $store->bank_bic }}
- **Verwendungszweck:** {{ $invoice->invoice_number }}
@endif

Vielen Dank für Ihre Bestellung!

Mit freundlichen Grüßen,<br>
{{ $store->company_name }}<br>
{{ $store->owner_name }}

---

<small style="color: #666;">
{{ $store->company_name }} • {{ $store->street }} • {{ $store->postal_code }} {{ $store->city }}<br>
@if($store->tax_number)Steuernr.: {{ $store->tax_number }}@endif
@if($store->vat_id) • USt-IdNr.: {{ $store->vat_id }}@endif
</small>
</x-mail::message>

