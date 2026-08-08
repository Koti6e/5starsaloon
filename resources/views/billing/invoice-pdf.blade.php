<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $bill->invoice_number }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #15120d; font-size: 12px; line-height: 1.45; }
        .muted { color: #6f6658; }
        .gold { color: #8a6616; }
        .top { border-bottom: 2px solid #c8a24a; padding-bottom: 16px; margin-bottom: 18px; position: relative; }
        .logo { width: 62px; height: 62px; object-fit: contain; float: left; margin-right: 14px; border-radius: 50%; }
        .brand { font-family: serif; font-size: 25px; font-weight: 700; color: #8a6616; margin: 0; }
        .tagline { margin-top: 2px; color: #6f6658; }
        .invoice-meta { position: absolute; top: 0; right: 0; text-align: right; }
        .invoice-number { font-size: 16px; font-weight: 700; }
        .seal { display: inline-block; margin-bottom: 8px; padding: 4px 13px; border: 1.6px solid #157347; border-radius: 999px; color: #157347; font-size: 11px; font-weight: 800; letter-spacing: 2px; }
        .seal.cancelled { border-color: #9f1239; color: #9f1239; }
        .clear { clear: both; }
        .panel-table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .panel-table td { width: 50%; border: 1px solid #eadfca; background: #fffaf0; padding: 12px; vertical-align: top; }
        .label { color: #7a6d58; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        .value { margin-top: 4px; font-weight: 700; }
        .items { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; page-break-after: auto; }
        .items th { background: #17130c; color: #fff3d0; padding: 9px 10px; font-size: 11px; text-align: left; border: 1px solid #17130c; }
        .items td { padding: 10px; border: 1px solid #eadfca; vertical-align: top; }
        .service-name { font-weight: 700; }
        .right { text-align: right; }
        .summary-wrap { width: 100%; margin-top: 18px; }
        .payment-box { width: 54%; float: left; border: 1px solid #eadfca; background: #fffaf0; padding: 12px; min-height: 100px; }
        .summary { width: 40%; float: right; border-collapse: collapse; }
        .summary td { padding: 7px 0; border-bottom: 1px solid #eadfca; }
        .summary .total td { border-bottom: 0; padding-top: 11px; font-size: 16px; font-weight: 800; color: #8a6616; }
        .footer { margin-top: 26px; padding-top: 15px; border-top: 1px solid #eadfca; }
        .thanks { font-family: serif; font-size: 17px; font-weight: 700; color: #8a6616; }
        .signature { float: right; width: 220px; text-align: right; }
        .signature-name { font-family: serif; font-size: 22px; color: #8a6616; margin-bottom: 2px; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
@php
    $statusLabel = strtoupper($bill->status === 'cancelled' ? 'cancelled' : $bill->payment_status);
    $note = $bill->payments->firstWhere('method_note')?->method_note;
    $phone = $settings['primary_phone'] ?? '';
    $whatsapp = $settings['whatsapp_number'] ?? '';
    $website = $settings['website'] ?? '';
@endphp

<div class="top">
    @if ($logoDataUri)
        <img class="logo" src="{{ $logoDataUri }}" alt="5 Star New Look Salon logo">
    @endif
    <h1 class="brand">5 Star New Look Salon</h1>
    <div class="tagline">Look Good. Feel Great. Be Confident.</div>
    <div class="muted">{{ $settings['address'] ?? '' }}</div>
    <div class="muted">{{ $phone }}</div>

    <div class="invoice-meta">
        <div class="seal {{ $statusLabel === 'PAID' ? '' : 'cancelled' }}">{{ $statusLabel }}</div>
        <div class="label">Invoice</div>
        <div class="invoice-number">{{ $bill->invoice_number }}</div>
        <div class="muted">{{ $bill->billed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
    </div>
    <div class="clear"></div>
</div>

<table class="panel-table">
    <tr>
        <td>
            <div class="label">Bill To</div>
            <div class="value">{{ $bill->customer->name }}</div>
            <div class="muted">+91 {{ $bill->customer->mobile }}</div>
        </td>
        <td class="right">
            <div class="label">Billed By</div>
            <div class="value">{{ $bill->billedBy->name }}</div>
            <div class="muted">System-generated salon invoice</div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width: 52%;">Service</th>
            <th class="right" style="width: 10%;">Qty</th>
            <th class="right" style="width: 18%;">Unit Price</th>
            <th class="right" style="width: 20%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($bill->items as $item)
            <tr>
                <td>
                    <div class="service-name">{{ $item->service_name_snapshot }}</div>
                    <div class="muted small">{{ $item->category_name_snapshot }}{{ $item->is_package_snapshot ? ' Package' : '' }}</div>
                </td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ \App\Support\Money::inr($item->unit_price) }}</td>
                <td class="right"><strong>{{ \App\Support\Money::inr($item->line_total) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="summary-wrap">
    <div class="payment-box">
        <div class="label">Payment Method</div>
        @foreach ($bill->payments as $payment)
            <div class="value">{{ ucfirst($payment->payment_method) }} - {{ \App\Support\Money::inr($payment->amount) }}</div>
        @endforeach
        @if ($note)
            <div style="margin-top: 10px;" class="muted">Notes: {{ $note }}</div>
        @endif
    </div>

    <table class="summary">
        <tr><td>Subtotal</td><td class="right">{{ \App\Support\Money::inr($bill->subtotal) }}</td></tr>
        @if ((float) $bill->discount_amount > 0)
            <tr><td>Discount</td><td class="right">-{{ \App\Support\Money::inr($bill->discount_amount) }}</td></tr>
        @endif
        @if ((float) $bill->home_visit_charge > 0)
            <tr><td>Home Service Charge</td><td class="right">{{ \App\Support\Money::inr($bill->home_visit_charge) }}</td></tr>
        @endif
        <tr class="total"><td>Grand Total</td><td class="right">{{ \App\Support\Money::inr($bill->grand_total) }}</td></tr>
    </table>
    <div class="clear"></div>
</div>

<div class="footer">
    <div class="signature">
        <div class="signature-name">5 Star New Look</div>
        <div class="label">Authorised Signature</div>
        <div class="muted small" style="margin-top: 8px;">This is a system-generated invoice. No handwritten signature required.</div>
    </div>
    <div style="margin-right: 245px;">
        <div class="thanks">Thank you for choosing 5 Star New Look Salon</div>
        <div class="muted">We appreciate your visit. Follow us for latest offers.</div>
        <div class="muted" style="margin-top: 8px;">
            @if ($phone) Phone: {{ $phone }} @endif
            @if ($whatsapp) {{ $phone ? ' | ' : '' }}WhatsApp: {{ $whatsapp }} @endif
            @if ($website) {{ ($phone || $whatsapp) ? ' | ' : '' }}{{ $website }} @endif
        </div>
        @if (! blank($settings['address'] ?? null))
            <div class="muted">{{ $settings['address'] }}</div>
        @endif
    </div>
    <div class="clear"></div>
</div>
</body>
</html>
