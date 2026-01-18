<x-mail::message>
# Ihre Rechnung {{ $invoice->invoice_number }}

Sehr geehrte(r) {{ $invoice->customer_name }},

Vielen Dank für @if($invoice->service_date) für Ihre Bestellung vom {{ $invoice->service_date->format('d.m.Y') }} @endif bei {{ $store->company_name }}.<br>
Anbei erhalten Sie Ihre Rechnung. Die Artikel Ihrer Bestellung sind in der Rechnung aufgeführt.

Die Bestellung befindet sich bereits auf dem Weg zu Ihnen und sollte in Kürze eintreffen. Viel Freude mit Ihren neuen Artikeln!

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

