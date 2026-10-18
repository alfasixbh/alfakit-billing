<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests\Fixtures\Models;

use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use Laravel\Cashier\Billable;

/**
 * A tenant billed through Laravel Cashier (Stripe).
 */
class BillableTeam extends Team
{
    use Billable;

    protected $table = 'teams';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [...parent::casts(), 'trial_ends_at' => 'datetime'];
    }

    public function getForeignKey(): string
    {
        return 'team_id';
    }
}
