<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rechnung {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.5;
            color: #1a1a2e;
            background: #fff;
        }

        /* ─── Accent stripe (fixed → repeats top of every page) ─── */
        .accent-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 7px;
            background-color: #1e3a5f;
        }

        /* ─── Page container: padding creates the visual page margins ─── */
        /* top: clears accent bar · sides: 30px ≈ 11 mm · bottom: clears footer */
        .page {
            padding: 18px 30px 115px 30px;
        }

        /* ─── Two-column helpers ─── */
        .row {
            display: table;
            width: 100%;
        }
        .col-left {
            display: table-cell;
            vertical-align: top;
        }
        .col-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }

        /* ─── Header ─── */
        .header-section {
            margin-bottom: 28px;
        }
        .doc-title {
            font-size: 26pt;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 3px;
            line-height: 1;
        }
        .doc-subtitle {
            font-size: 9pt;
            color: #7a8ca0;
            margin-top: 3px;
            letter-spacing: 0.5px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 0.4px;
        }
        .company-address {
            font-size: 8pt;
            color: #555;
            line-height: 1.7;
            margin-top: 4px;
        }

        /* ─── Address + Details row ─── */
        .address-section {
            margin-bottom: 18px;
        }
        .address-col {
            display: table-cell;
            width: 52%;
            vertical-align: top;
            padding-right: 20px;
        }
        .details-col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }
        .sender-line {
            font-size: 6.5pt;
            color: #999;
            border-bottom: 1px solid #d0d8e4;
            padding-bottom: 3px;
            margin-bottom: 9px;
        }
        .recipient {
            font-size: 10pt;
            line-height: 1.7;
            color: #1a1a2e;
        }
        .recipient-name {
            font-size: 11pt;
            font-weight: bold;
        }
        /* Each address line stays on one line – prevents house-number wrap */
        .recipient-line {
            white-space: nowrap;
            overflow: hidden;
        }

        /* ─── Invoice details box ─── */
        .details-box {
            border: 1px solid #d0d8e4;
            font-size: 8.5pt;
            border-collapse: collapse;
            width: 100%;
        }
        .details-box tr:nth-child(even) {
            background-color: #f4f7fb;
        }
        .details-box td {
            padding: 5px 11px;
            line-height: 1.4;
        }
        .details-box td.lbl {
            color: #6b7c93;
            white-space: nowrap;
            width: 45%;
        }
        .details-box td.val {
            font-weight: bold;
            color: #1a1a2e;
            text-align: right;
        }

        /* ─── Delivery notice ─── */
        .delivery-note {
            font-size: 7.5pt;
            color: #888;
            font-style: italic;
            margin-bottom: 12px;
        }

        /* ─── Items table ─── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 6px;
        }
        .items-table thead tr {
            background-color: #1e3a5f;
        }
        .items-table thead th {
            padding: 6px 7px;
            text-align: left;
            color: #fff;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .items-table tbody tr.row-even {
            background-color: #f7f9fc;
        }
        .items-table tbody tr.row-odd {
            background-color: #ffffff;
        }
        .items-table tbody tr {
            page-break-inside: avoid;
        }
        .items-table tbody td {
            padding: 5px 7px;
            border-bottom: 1px solid #e8ecf2;
            vertical-align: top;
        }
        .item-nr {
            font-size: 7pt;
            color: #6b7c93;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .item-name {
            font-size: 7.5pt;
            font-weight: bold;
            color: #1a1a2e;
            line-height: 1.35;
        }
        .item-meta {
            font-size: 6.5pt;
            color: #7a8ca0;
            margin-top: 1px;
            line-height: 1.4;
        }
        .badge {
            font-size: 6pt;
            padding: 1px 4px;
        }
        .badge-new {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .badge-used {
            background-color: #fff3e0;
            color: #e65100;
        }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .font-bold   { font-weight: bold; }

        /* ─── Summary area (keep together on page) ─── */
        .summary-area {
            display: table;
            width: 100%;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        .summary-left {
            display: table-cell;
            width: 54%;
            vertical-align: bottom;
            padding-right: 20px;
        }
        .summary-right {
            display: table-cell;
            width: 46%;
            vertical-align: top;
        }
        .small-biz-note {
            font-size: 7.5pt;
            color: #4a5568;
            font-style: italic;
            line-height: 1.65;
            padding: 9px 12px;
            border-left: 3px solid #1e3a5f;
            background-color: #f4f7fb;
        }
        .thank-you {
            font-size: 9.5pt;
            font-weight: bold;
            color: #1e3a5f;
            margin-top: 14px;
        }

        /* ─── Totals table ─── */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .totals-table td {
            padding: 5px 10px;
        }
        .totals-table tr.divider td {
            border-top: 1px solid #d0d8e4;
        }
        .totals-table .t-label { color: #555; }
        .totals-table .t-value {
            text-align: right;
            white-space: nowrap;
        }
        .totals-table tr.total-row {
            background-color: #1e3a5f;
        }
        .totals-table tr.total-row td {
            color: #fff;
            font-weight: bold;
            font-size: 11pt;
            padding: 9px 12px;
        }
        .totals-table tr.total-row td.t-value {
            text-align: right;
        }

        /* ─── Footer (fixed → repeats on every page) ─── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 30px 7px 30px;   /* left/right = .page padding */
            border-top: 2px solid #1e3a5f;
            background-color: #fff;
        }
        .footer-row {
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            padding-right: 12px;
            font-size: 6.8pt;
            color: #555;
            line-height: 1.65;
        }
        .footer-col strong {
            display: block;
            font-size: 7pt;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>

    <!-- Accent stripe (position:fixed → repeats on every page) -->
    <div class="accent-bar"></div>

    <div class="page">

        <!-- ── Header: title left · company right ── -->
        <div class="header-section row">
            <div class="col-left">
                <div class="doc-title">RECHNUNG</div>
                <div class="doc-subtitle">Nr.&nbsp;{{ $invoice->invoice_number }}</div>
            </div>
            <div class="col-right">
                <div class="company-name">{{ $store->company_name }}</div>
                <div class="company-address">
                    {{ $store->owner_name }}<br>
                    {{ $store->street }}<br>
                    {{ $store->postal_code }} {{ $store->city }}@if($store->country && $store->country !== 'Deutschland') &middot; {{ $store->country }}@endif<br>
                    @if($store->phone)Tel.: {{ $store->phone }}<br>@endif
                    {{ $store->smtp_from_address ?? $store->user->email }}
                </div>
            </div>
        </div>

        <!-- ── Recipient address (left) + Invoice details (right) ── -->
        <div class="address-section row">
            <div class="address-col">
                <div class="sender-line">
                    {{ $store->owner_name }} &middot; {{ $store->street }} &middot; {{ $store->postal_code }} {{ $store->city }}
                </div>
                <div class="recipient">
                    <div class="recipient-name">{{ $invoice->customer_name }}</div>
                    @if($invoice->customer_address1)
                        <div class="recipient-line">{{ $invoice->customer_address1 }}</div>
                    @endif
                    @if($invoice->customer_address2)
                        <div class="recipient-line">{{ $invoice->customer_address2 }}</div>
                    @endif
                    <div class="recipient-line">{{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}@if($invoice->customer_state) &middot; {{ $invoice->customer_state }}@endif</div>
                    @if($invoice->customer_country && $invoice->customer_country !== 'DE' && $invoice->customer_country !== 'Deutschland')
                        <div class="recipient-line">{{ $invoice->customer_country }}</div>
                    @endif
                </div>
            </div>
            <div class="details-col">
                <table class="details-box">
                    <tr>
                        <td class="lbl">Rechnungsnummer</td>
                        <td class="val">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Rechnungsdatum</td>
                        <td class="val">{{ $invoice->invoice_date->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Bestellnummer</td>
                        <td class="val">{{ $order->bricklink_order_id }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Bestelldatum</td>
                        <td class="val">{{ $order->order_date ? $order->order_date->format('d.m.Y') : '–' }}</td>
                    </tr>
                    @if(!$invoice->is_paid && $invoice->due_date)
                    <tr>
                        <td class="lbl">Fällig am</td>
                        <td class="val">{{ $invoice->due_date->format('d.m.Y') }}</td>
                    </tr>
                    @endif
                    @if($invoice->is_paid && $invoice->paid_date)
                    <tr>
                        <td class="lbl">Bezahlt am</td>
                        <td class="val">{{ $invoice->paid_date->format('d.m.Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="lbl">Zahlungsart</td>
                        <td class="val">{{ $invoice->payment_method ?? 'PayPal (Onsite)' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ── Delivery notice ── -->
        <div class="delivery-note">
            @if($invoice->service_date)
                Leistungsdatum: {{ $invoice->service_date->format('d.m.Y') }}
            @else
                Liefer- und Leistungsdatum entspricht dem Rechnungsdatum.
            @endif
        </div>

        <!-- ── Items table ── -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 11%;">Art.-Nr.</th>
                    <th style="width: 53%;">Beschreibung</th>
                    <th style="width: 6%;" class="text-center">Menge</th>
                    <th style="width: 13%;" class="text-right">Einzelpreis</th>
                    <th style="width: 13%;" class="text-right">Gesamtpreis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr class="{{ $index % 2 === 0 ? 'row-odd' : 'row-even' }}">
                    <td class="item-nr">{{ $item->item_number }}</td>
                    <td>
                        <div class="item-name">{{ $item->item_name }}</div>
                        <div class="item-meta">
                            @if($item->color_name){{ $item->color_name }} &nbsp;&middot;&nbsp;@endif
                            <span class="badge {{ $item->condition === 'N' ? 'badge-new' : 'badge-used' }}">
                                {{ $item->condition === 'N' ? 'Neu' : 'Gebraucht' }}
                            </span>
                        </div>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 3, ',', '.') }}&nbsp;&euro;</td>
                    <td class="text-right font-bold">{{ number_format($item->total_price, 2, ',', '.') }}&nbsp;&euro;</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- ── Summary ── -->
        <div class="summary-area">
            <div class="summary-left">
                @if($invoice->is_small_business)
                    <div class="small-biz-note">
                        Gem&auml;&szlig; &sect;&nbsp;19 UStG wird keine Umsatzsteuer berechnet
                        (Kleinunternehmerregelung). Es erfolgt kein Ausweis der Umsatzsteuer.
                    </div>
                @endif
                <div class="thank-you">Vielen Dank f&uuml;r Ihre Bestellung!</div>
            </div>
            <div class="summary-right">
                <table class="totals-table">
                    <tr>
                        <td class="t-label">Warenwert</td>
                        <td class="t-value">{{ number_format($invoice->subtotal, 2, ',', '.') }}&nbsp;&euro;</td>
                    </tr>
                    <tr class="divider">
                        <td class="t-label">Versand &amp; Lieferung</td>
                        <td class="t-value">{{ number_format($invoice->shipping_cost, 2, ',', '.') }}&nbsp;&euro;</td>
                    </tr>
                    @if(!$invoice->is_small_business && $invoice->tax_rate > 0)
                        <tr>
                            <td class="t-label">Nettobetrag</td>
                            <td class="t-value">{{ number_format($invoice->subtotal + $invoice->shipping_cost, 2, ',', '.') }}&nbsp;&euro;</td>
                        </tr>
                        <tr>
                            <td class="t-label">MwSt. ({{ number_format($invoice->tax_rate, 0) }}%)</td>
                            <td class="t-value">{{ number_format($invoice->tax_amount, 2, ',', '.') }}&nbsp;&euro;</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Gesamtbetrag</td>
                        <td class="t-value">{{ number_format($invoice->total, 2, ',', '.') }}&nbsp;&euro;</td>
                    </tr>
                </table>
            </div>
        </div>

    </div><!-- /.page -->

    <!-- ── Footer (fixed = repeats on every page) ── -->
    <div class="footer">
        <div class="footer-row">
            <div class="footer-col">
                <strong>Anbieter</strong>
                {{ $store->company_name }}<br>
                {{ $store->owner_name }}
                @if($store->tax_number)<br>St.-Nr.: {{ $store->tax_number }}@endif
                @if($store->vat_id)<br>USt-IdNr.: {{ $store->vat_id }}@endif
            </div>
            <div class="footer-col">
                <strong>Anschrift</strong>
                {{ $store->street }}<br>
                {{ $store->postal_code }} {{ $store->city }}<br>
                Deutschland
            </div>
            <div class="footer-col">
                <strong>Kontakt</strong>
                @if($store->phone)Tel.: {{ $store->phone }}<br>@endif
                {{ $store->smtp_from_address ?? $store->user->email }}
            </div>
            <div class="footer-col">
                <strong>Bankverbindung</strong>
                {{ $store->bank_name }}<br>
                {{ $store->bank_account_holder }}<br>
                IBAN: {{ $store->iban }}<br>
                BIC: {{ $store->bic }}
            </div>
        </div>
    </div>

</body>
</html>

