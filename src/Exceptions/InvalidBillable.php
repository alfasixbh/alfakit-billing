<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Exceptions;

use RuntimeException;

/**
 * Thrown when the billable model (the tenant or the user) lacks the billing package's trait.
 */
class InvalidBillable extends RuntimeException
{
    public static function missingTrait(object $billable, string $trait): self
    {
        return new self(__('alfakit-billing::billing.exceptions.invalid_billable', ['model' => $billable::class, 'trait' => $trait]));
    }
}
