<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('alfakit-billing::billing.paddle.title') }} · {{ $plan->getLabel() }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: ui-sans-serif, system-ui, sans-serif; background: #fafafa; color: #18181b; text-align: center; }
        @media (prefers-color-scheme: dark) { body { background: #09090b; color: #fafafa; } }
        main { padding-inline: 1rem; }
        a { color: inherit; }
    </style>
    @paddleJS
</head>
<body>
    <main>
        <p role="status">{{ __('alfakit-billing::billing.paddle.loading') }}</p>
        <p><a href="{{ $cancelUrl }}">{{ __('alfakit-billing::billing.paddle.cancel') }}</a></p>
    </main>
    <script>
        window.addEventListener('load', function () {
            if (window.Paddle) {
                window.Paddle.Checkout.open(@json($options));
            }
        });
    </script>
</body>
</html>
