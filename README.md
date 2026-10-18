# AlfaKit Billing

<p><picture><source media="(prefers-color-scheme: dark)" srcset="../../../art/alfasix/alfa-six-micro-white.svg"><img src="../../../art/alfasix/alfa-six-micro-navy.svg" alt="" width="24" height="18" align="top"></picture> <sub>by <a href="https://alfasixbh.com">Alfa Six</a></sub></p>

Subscription billing for [AlfaKit](https://alfakit.dev) panels: Stripe (Laravel Cashier), Paddle (Cashier Paddle) and Laravel Spark providers, a plans page and subscription middleware. Tenancy panels bill the tenant; other panels bill the signed-in user.

## Installation

```bash
composer require alfasix/alfakit-billing
```

Set up [Laravel Cashier](https://laravel.com/docs/billing) (migrations, keys, webhook) and add `Laravel\Cashier\Billable` to the model that pays.

## Registering

```php
use AlfaSix\AlfakitBilling\BillingPlugin;
use AlfaSix\AlfakitBilling\Plan;
use AlfaSix\AlfakitBilling\Providers\StripeBillingProvider;

$panel->plugin(
    BillingPlugin::make()
        ->provider(
            StripeBillingProvider::make()
                ->plans([
                    Plan::make('pro')->price('price_123')->label('Pro')
                        ->amount(29, 'usd')->monthly()
                        ->features(['Unlimited projects', 'Priority support']),
                ])
                ->trialDays(14),
        )
        ->requiresSubscription(),
);
```

- `/{tenant}/billing` (or `/billing`) opens the Stripe Billing Portal for subscribers and the plans page for everyone else; `?plan=pro` starts Stripe Checkout.
- A **Billing** entry is added to the tenant menu (or the user menu without tenancy).
- `requiresSubscription()` sends unsubscribed tenants or users to the plans page.

`PaddleBillingProvider` (needs `laravel/cashier-paddle`; it requires Guzzle 7, which Laravel 13 supports) and `SparkBillingProvider` (needs Laravel Spark) work the same way and throw a clear exception until their package is installed.

See the [documentation](https://alfakit.dev/docs/plugins/billing) for every option.

## Testing

```bash
vendor/bin/pest --test-directory=packages/plugins packages/plugins/billing
```

## License

MIT. See [LICENSE.md](LICENSE.md).
