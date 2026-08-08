<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bill->invoice_number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white p-6 text-black" onload="window.print()">
    <div class="mx-auto max-w-4xl">
        @include('billing.partials.invoice-card', ['printMode' => true])
    </div>
</body>
</html>
