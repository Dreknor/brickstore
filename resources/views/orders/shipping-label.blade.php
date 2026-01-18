<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Versandetikett</title>
    <style>
        @page {
            size: 150mm 100mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #000;
            width: 150mm;
            height: 100mm;
            margin: 0;
            padding: 8mm;
        }

        .wrapper {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .from-col {
            display: table-cell;
            width: 35mm;
            border-right: 2px solid #000;
            padding-right: 4mm;
            vertical-align: top;
        }

        .to-col {
            display: table-cell;
            width: calc(100% - 35mm);
            padding-left: 8mm;
            vertical-align: middle;
        }

        .from-header {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3mm;
            letter-spacing: 0.5px;
        }

        .to-header {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5mm;
            letter-spacing: 1px;
        }

        .from-owner {
            font-size: 7pt;

            margin-bottom: 1mm;
        }

        .from-name {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .from-address {
            font-size: 7pt;
            line-height: 1.3;
        }

        .to-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3mm;
            line-height: 1.2;
        }

        .to-address {
            font-size: 12pt;
            line-height: 1.4;
            margin-bottom: 2mm;
        }

        .to-country {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 3mm;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="from-col">
            <div class="from-header">Absender</div>
            <div class="from-name">{{ $store->name }}</div>
            @if($store->owner_name)
                <div class="from-owner">{{ $store->owner_name }}</div>
            @endif
            @if($store->street)
                <div class="from-address">{{ $store->street }}</div>
            @endif
            @if($store->postal_code || $store->city)
                <div class="from-address">{{ $store->postal_code }} {{ $store->city }}</div>
            @endif
            @if($store->country)
                <div class="from-address">{{ $store->country }}</div>
            @endif
        </div>

        <div class="to-col">
            <div class="to-header">Empfänger</div>
            <div class="to-name">{{ $order->shipping_name ?? $order->buyer_name }}</div>
            @if($order->shipping_address1)
                <div class="to-address">{{ $order->shipping_address1 }}</div>
            @endif
            @if($order->shipping_address2)
                <div class="to-address">{{ $order->shipping_address2 }}</div>
            @endif
            @if($order->shipping_postal_code || $order->shipping_city)
                <div class="to-address">{{ $order->shipping_postal_code }} {{ $order->shipping_city }}</div>
            @endif
            @if($order->shipping_state)
                <div class="to-address">{{ $order->shipping_state }}</div>
            @endif
            @if($order->shipping_country)
                <div class="to-country">{{ $order->shipping_country }}</div>
            @endif
        </div>
    </div>
</body>
</html>
