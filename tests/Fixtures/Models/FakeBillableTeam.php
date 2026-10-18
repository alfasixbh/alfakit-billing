<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests\Fixtures\Models;

use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes\FakePaddleCheckout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A tenant standing in for a Cashier Paddle / Spark billable: the same public methods
 * (`subscribed()`, `subscribe()`, the `customer` and `subscriptions` relations), backed by
 * static state instead of the packages.
 */
class FakeBillableTeam extends Team
{
    protected $table = 'teams';

    /** @var array<int, string> Slugs of subscribed teams. */
    public static array $subscribed = [];

    /** @var array<int, array{prices: array<int, string>|string, type: string}> */
    public static array $subscribeCalls = [];

    public static ?string $paddleCustomerId = 'ctm_123';

    public static function reset(): void
    {
        static::$subscribed = [];
        static::$subscribeCalls = [];
        static::$paddleCustomerId = 'ctm_123';
    }

    public function subscribed(string $type = 'default', ?string $price = null): bool
    {
        return in_array($this->getAttribute('slug'), static::$subscribed, true);
    }

    /**
     * @param  array<int, string>|string  $prices
     */
    public function subscribe(array|string $prices, string $type = 'default'): FakePaddleCheckout
    {
        static::$subscribeCalls[] = ['prices' => $prices, 'type' => $type];

        return new FakePaddleCheckout((array) $prices, $type);
    }

    public function getCustomerAttribute(): ?Model
    {
        if (static::$paddleCustomerId === null) {
            return null;
        }

        return (new FakeRecord)->forceFill(['paddle_id' => static::$paddleCustomerId]);
    }

    /**
     * @return Collection<int, FakeRecord>
     */
    public function getSubscriptionsAttribute(): Collection
    {
        return collect([(new FakeRecord)->forceFill(['paddle_id' => 'sub_'.$this->getAttribute('slug')])]);
    }
}
