<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling;

use Illuminate\Support\ServiceProvider;

/**
 * Loads the billing plugin's translations (`alfakit-billing::billing.*`) and views.
 */
class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'alfakit-billing');
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'alfakit-billing');

        $this->publishes([
            dirname(__DIR__).'/resources/lang' => $this->app->langPath('vendor/alfakit-billing'),
        ], 'alfakit-billing-translations');
    }
}
