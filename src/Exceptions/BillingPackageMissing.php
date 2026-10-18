<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Exceptions;

use RuntimeException;

/**
 * Thrown when a billing provider is used without the package it is built on.
 */
class BillingPackageMissing extends RuntimeException
{
    public static function make(string $provider, string $package, ?string $note = null): self
    {
        $message = __('alfakit-billing::billing.exceptions.missing_package', ['provider' => $provider, 'package' => $package]);

        return new self($note === null ? $message : $message.' '.$note);
    }

    public static function paddle(): self
    {
        return self::make('Paddle', 'laravel/cashier-paddle', __('alfakit-billing::billing.exceptions.paddle_compatibility'));
    }

    public static function spark(): self
    {
        return self::make('Spark', 'laravel/spark-stripe', __('alfakit-billing::billing.exceptions.spark_licence'));
    }

    public static function stripe(): self
    {
        return self::make('Stripe', 'laravel/cashier');
    }
}
