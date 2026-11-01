<x-mail::message>
# Payment Reminder: Invoice {{ $invoice->number }}

Hello {{ $invoice->client->name }},

This is a friendly reminder that invoice **{{ $invoice->number }}** was due on **{{ $invoice->due_date->format('d M Y') }}** ({{ $daysOverdue }} days ago).

**Outstanding Balance:** **{{ $invoice->currency }} {{ number_format($invoice->balance() / 100, 2) }}**

Please arrange payment at your earliest convenience. If you have already made this payment, please disregard this notice.

<x-mail::button :url="$publicUrl">
View & Pay Invoice Online
</x-mail::button>

Thank you for your business!<br>
{{ $invoice->company->name }}
</x-mail::message>

