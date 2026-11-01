<x-mail::message>
# Invoice {{ $invoice->number }}

Hello {{ $invoice->client->name }},

@if (!empty($customMessage))
{{ $customMessage }}
@else
Please find attached invoice **{{ $invoice->number }}** for **{{ $invoice->currency }} {{ number_format($invoice->total / 100, 2) }}** from **{{ $invoice->company->name }}**.
@endif

**Invoice Details:**
- **Invoice Number:** {{ $invoice->number }}
- **Issue Date:** {{ $invoice->issue_date->format('d M Y') }}
- **Due Date:** {{ $invoice->due_date->format('d M Y') }}
- **Amount Due:** {{ $invoice->currency }} {{ number_format($invoice->balance() / 100, 2) }}

<x-mail::button :url="$publicUrl">
View & Pay Invoice
</x-mail::button>

Thank you for your business!<br>
{{ $invoice->company->name }}
</x-mail::message>
