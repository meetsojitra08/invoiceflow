<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        @page {
            margin: 35px 40px;
            size: a4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 20%;
            width: 60%;
            text-align: center;
            font-size: 80px;
            font-weight: bold;
            color: rgba(220, 38, 38, 0.12);
            border: 6px dashed rgba(220, 38, 38, 0.15);
            padding: 20px;
            transform: rotate(-25deg);
            z-index: -1000;
            text-transform: uppercase;
        }
        .watermark.paid {
            color: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.18);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }
        .company-details {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.3;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            text-align: right;
            margin-bottom: 4px;
        }
        .invoice-meta {
            text-align: right;
            font-size: 11px;
            color: #4b5563;
        }
        .invoice-meta strong {
            color: #111827;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
        .bill-to-title {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6b7280;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .client-name {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }
        .client-details {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.3;
        }
        .items-table {
            margin-top: 25px;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            margin-top: 10px;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 11px;
        }
        .totals-table tr.total-row td {
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            color: #111827;
            padding: 8px;
        }
        .totals-table tr.due-row td {
            font-size: 12px;
            font-weight: bold;
            color: #4f46e5;
            padding-top: 6px;
        }
        .notes-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
        .notes-section strong {
            color: #374151;
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    @if ($invoice->status->value === 'paid')
        <div class="watermark paid">PAID</div>
    @elseif ($invoice->status->value === 'void')
        <div class="watermark">VOID</div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $invoice->company->name ?? config('app.name') }}</div>
                <div class="company-details">
                    @if (!empty($invoice->company->email))
                        <div>{{ $invoice->company->email }}</div>
                    @endif
                    @if (!empty($invoice->company->tax_number))
                        <div>Tax ID: {{ $invoice->company->tax_number }}</div>
                    @endif
                    @if (!empty($invoice->company->address))
                        <div>{!! nl2br(e($invoice->company->address)) !!}</div>
                    @endif
                </div>
            </td>
            <td style="width: 45%;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <div><strong>Invoice No:</strong> {{ $invoice->number }}</div>
                    <div><strong>Issue Date:</strong> {{ $invoice->issue_date->format('d M Y') }}</div>
                    <div><strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}</div>
                    <div><strong>Status:</strong> {{ strtoupper($invoice->status->value) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table style="width: 100%;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="bill-to-title">Billed To</div>
                <div class="client-name">{{ $invoice->client->name }}</div>
                <div class="client-details">
                    @if (!empty($invoice->client->email))
                        <div>{{ $invoice->client->email }}</div>
                    @endif
                    @if (!empty($invoice->client->tax_number))
                        <div>Tax ID: {{ $invoice->client->tax_number }}</div>
                    @endif
                    @if (!empty($invoice->client->billing_address))
                        <div>{!! nl2br(e($invoice->client->billing_address)) !!}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Description</th>
                <th class="text-right" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 15%;">Unit Price</th>
                <th class="text-right" style="width: 10%;">Tax</th>
                <th class="text-right" style="width: 10%;">Discount</th>
                <th class="text-right" style="width: 10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td><strong>{{ $item->description }}</strong></td>
                    <td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($item->unit_price / 100, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item->tax_rate, 2) }}%</td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($item->discount / 100, 2) }}</td>
                    <td class="text-right"><strong>{{ $invoice->currency }} {{ number_format($item->line_total / 100, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td style="color: #6b7280;">Subtotal:</td>
            <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->subtotal / 100, 2) }}</td>
        </tr>
        @if ($invoice->discount_total > 0)
            <tr>
                <td style="color: #6b7280;">Discount:</td>
                <td class="text-right">-{{ $invoice->currency }} {{ number_format($invoice->discount_total / 100, 2) }}</td>
            </tr>
        @endif
        @if ($invoice->tax_total > 0)
            <tr>
                <td style="color: #6b7280;">Tax:</td>
                <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->tax_total / 100, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>Total:</td>
            <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->total / 100, 2) }}</td>
        </tr>
        @if ($invoice->amount_paid > 0)
            <tr>
                <td style="color: #6b7280;">Amount Paid:</td>
                <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->amount_paid / 100, 2) }}</td>
            </tr>
        @endif
        <tr class="due-row">
            <td>Amount Due:</td>
            <td class="text-right">{{ $invoice->currency }} {{ number_format($invoice->balance() / 100, 2) }}</td>
        </tr>
    </table>

    @if (!empty($invoice->notes))
        <div class="notes-section">
            <strong>Notes</strong>
            <div>{!! nl2br(e($invoice->notes)) !!}</div>
        </div>
    @endif
</body>
</html>
