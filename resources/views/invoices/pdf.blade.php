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
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        .container {
            padding: 20px;
        }
        .company-info {
            text-align: right;
            font-size: 9pt;
            margin-bottom: 30px;
            line-height: 1.3;
        }
        .company-info .company-name {
            font-weight: bold;
            font-size: 11pt;
        }
        .sender-line {
            font-size: 7pt;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 10px;
        }
        .address-block {
            margin-bottom: 20px;
        }
        .invoice-details {
            text-align: right;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .invoice-details table {
            margin-left: auto;
            border-spacing: 0;
        }
        .invoice-details td {
            padding: 2px 5px;
            text-align: left;
        }
        .invoice-details td:first-child {
            text-align: right;
            padding-right: 10px;
        }
        h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
            margin-top: 10px;
        }
        .delivery-note {
            font-size: 9pt;
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        .items-table thead {
            border-bottom: 1px solid #000;
        }
        .items-table th {
            padding: 5px 3px;
            text-align: left;
            font-weight: normal;
        }
        .items-table td {
            padding: 5px 3px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .summary-section {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .summary-table {
            margin-left: auto;
            width: 650px;
            font-size: 9pt;
        }
        .summary-table td {
            padding: 3px 5px;
        }
        .summary-table .label {
            text-align: left;
        }
        .summary-table .value {
            text-align: right;
            width: 110px;
        }
        .summary-table .total-row {
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
        }
        .thank-you {
            margin-top: 30px;
            font-size: 9pt;
        }
        .footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 7pt;
            line-height: 1.5;
        }
        .footer-columns {
            display: table;
            width: 100%;
        }
        .footer-column {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            padding-right: 10px;
        }
        .footer-column strong {
            display: block;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Company Info (Absender) oben rechts -->
        <div class="company-info">
            <div class="company-name">{{ $store->company_name }}</div>
            {{ $store->owner_name }}<br>
            {{ $store->street }}<br>
            {{ $store->postal_code }} {{ $store->city }}
            @if($store->country !== 'Deutschland')
                <br>{{ $store->country }}
            @endif
            <br><br>
            Telefon: {{ $store->phone ?? '0176/26853673' }}<br>
            E-Mail: {{ $store->smtp_from_email ?? $store->user->email }}
        </div>

        <!-- Titel -->
        <h1>RECHNUNG</h1>

        <!-- Absender-Zeile klein -->
        <div class="sender-line">
            {{ $store->owner_name }} – {{ $store->street }} – D-{{ $store->postal_code }} {{ $store->city }}
        </div>

        <!-- Empfängeradresse -->
        <div class="address-block">
            <strong>{{ $invoice->customer_name }}</strong><br>
            @if($invoice->customer_address1)
                {{ $invoice->customer_address1 }}<br>
            @endif
            @if($invoice->customer_address2)
                {{ $invoice->customer_address2 }}<br>
            @endif
            {{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}

        </div>

        <!-- Rechnungsdetails rechts -->
        <div class="invoice-details">
            <table>
                <tr>
                    <td>Rechnungsnr.:</td>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td>Rechnungsdatum:</td>
                    <td>{{ $invoice->invoice_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td>Bestellnummer:</td>
                    <td>{{ $order->bricklink_order_id }}</td>
                </tr>
                <tr>
                    <td>Bestelldatum:</td>
                    <td>{{ $order->order_date ? $order->order_date->format('d.m.Y') : 'N/A' }}</td>
                </tr>
                @if(!$invoice->is_paid && $invoice->due_date)
                <tr>
                    <td>Fällig am:</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                @endif
                @if($invoice->is_paid && $invoice->paid_date)
                <tr>
                    <td>Bezahlt am:</td>
                    <td>{{ $invoice->paid_date->format('d.m.Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td>Zahlungsart:</td>
                    <td>{{ $invoice->payment_method ?? 'PayPal (Onsite)' }}</td>
                </tr>
            </table>
        </div>

        <!-- Lieferdatum-Hinweis -->
        <div class="delivery-note">
            Das Lieferdatum entspricht dem Rechnungsdatum.
        </div>

        <!-- Artikel-Tabelle -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Art.Nr.</th>
                    <th style="width: 38%;">Artikel</th>
                    <th style="width: 20%;">Farbe</th>
                    <th style="width: 10%;">Zustand</th>
                    <th style="width: 8%;" class="text-center">Anzahl</th>
                    <th style="width: 9%;" class="text-right">Einzelpreis</th>
                    <th style="width: 9%;" class="text-right">Gesamtpreis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->item_number }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->color_name ?? '-' }}</td>
                    <td>{{ $item->condition === 'N' ? 'Neu' : 'Gebraucht' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 3, ',', '.') }} €</td>
                    <td class="text-right">{{ number_format($item->total_price, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Zusammenfassung -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="label">Gemäß §19 UstG von der Umsatzsteuer befreit.</td>
                    <td class="value">Warenwert:</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td class="label"></td>
                    <td class="value">Lieferung / Versand:</td>
                    <td class="value" style="border-bottom: 1px solid black">{{ number_format($invoice->shipping_cost, 2, ',', '.') }} €</td>
                </tr>
                <tr class="total-row">
                    <td class="label"></td>
                    <td class="value">Gesamtbetrag</td>
                    <td class="value"  style="border-bottom: black double 4px">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>

        <!-- Dankeschön -->
        <div class="thank-you">
            Vielen Dank für Ihre Bestellung!
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-columns">
                <div class="footer-column">
                    <strong>Firma</strong>
                    {{ $store->owner_name }}<br>
                    {{ $store->company_name }}<br>
                    {{ $store->city }}<br>
                    @if($store->vat_id)
                        USt-IdNr.: {{ $store->vat_id }}
                    @endif
                </div>
                <div class="footer-column">
                    <strong>Anschrift</strong>
                    {{ $store->owner_name }}<br>
                    {{ $store->street }}<br>
                    {{ $store->postal_code }} {{ $store->city }}<br>
                    Deutschland
                </div>
                <div class="footer-column">
                    <strong>Kontakt</strong>
                    Telefon: {{ $store->phone ?? '0176/26853673' }}<br>
                    E-Mail: {{ $store->smtp_from_email ?? $store->user->email }}
                </div>
                <div class="footer-column">
                    <strong>Bankverbindung</strong>
                    {{ $store->bank_name ?? 'Vivid – Banking Circle S.A.' }}<br>
                    IBAN: {{ $store->bank_iban }}<br>
                    BIC: {{ $store->bank_bic }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>

