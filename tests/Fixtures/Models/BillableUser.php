<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests\Fixtures\Models;

use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use Laravel\Cashier\Billable;

/**
 * A user billed through Laravel Cashier (Stripe), for panels without tenancy.
 */
class BillableUser extends User
{
    use Billable;

    protected $table = 'users';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [...parent::casts(), 'trial_ends_at' => 'datetime'];
    }

    public function getForeignKey(): string
    {
        return 'user_id';
    }
}
