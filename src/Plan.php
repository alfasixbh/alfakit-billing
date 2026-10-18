<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling;

use Closure;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * A subscription plan listed on the checkout page.
 *
 * ```php
 * Plan::make('pro')
 *     ->price('price_123')            // Stripe price, Paddle price (pri_…)
 *     ->label('Pro')
 *     ->amount(29, 'usd')->interval('month')
 *     ->features(['Unlimited projects', 'Priority support'])
 *     ->trialDays(14)
 *     ->highlighted();
 * ```
 */
class Plan
{
    protected string|Closure|null $label = null;

    protected string|Closure|null $description = null;

    /** @var array<int, string> */
    protected array $prices = [];

    protected int|float|null $amount = null;

    protected ?string $currency = null;

    protected ?string $interval = null;

    protected string|Closure|null $priceLabel = null;

    /** @var array<int, string>|Closure */
    protected array|Closure $features = [];

    protected ?int $trialDays = null;

    protected bool $hasTrialDays = false;

    protected bool $isHighlighted = false;

    protected bool|Closure $isVisible = true;

    final public function __construct(protected string $id) {}

    public static function make(string $id): static
    {
        return new static($id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return (string) (value($this->label) ?? Str::headline($this->id));
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        $description = value($this->description);

        return is_string($description) && $description !== '' ? $description : null;
    }

    /**
     * The provider's price identifier(s): Stripe `price_…`, Paddle `pri_…`. Several prices
     * become several subscription items.
     *
     * @param  string|array<int, string>  $prices
     */
    public function price(string|array $prices): static
    {
        $this->prices = array_values((array) $prices);

        return $this;
    }

    /**
     * @param  string|array<int, string>  $prices
     */
    public function prices(string|array $prices): static
    {
        return $this->price($prices);
    }

    /**
     * @return array<int, string>
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    /**
     * The displayed amount, formatted in the current locale (`29` + `usd` = "$29.00").
     */
    public function amount(int|float|null $amount, ?string $currency = null): static
    {
        $this->amount = $amount;
        $this->currency = $currency === null ? null : strtoupper($currency);

        return $this;
    }

    public function getAmount(): int|float|null
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * The billing interval shown next to the amount: `day`, `week`, `month` or `year`.
     */
    public function interval(?string $interval): static
    {
        $this->interval = $interval;

        return $this;
    }

    public function monthly(): static
    {
        return $this->interval('month');
    }

    public function yearly(): static
    {
        return $this->interval('year');
    }

    public function getInterval(): ?string
    {
        return $this->interval;
    }

    /**
     * Replaces the formatted amount and interval ("Contact us", "€29 / seat").
     */
    public function priceLabel(string|Closure|null $label): static
    {
        $this->priceLabel = $label;

        return $this;
    }

    public function getPriceLabel(): ?string
    {
        $label = value($this->priceLabel);

        if (is_string($label) && $label !== '') {
            return $label;
        }

        if ($this->amount === null) {
            return null;
        }

        $amount = $this->currency === null
            ? (string) Number::format($this->amount, locale: app()->getLocale())
            : (string) Number::currency($this->amount, $this->currency, app()->getLocale());

        if ($this->interval === null) {
            return $amount;
        }

        return __('alfakit-billing::billing.checkout.price_per_interval', [
            'amount' => $amount,
            'interval' => __('alfakit-billing::billing.checkout.intervals.'.$this->interval),
        ]);
    }

    /**
     * @param  array<int, string>|Closure  $features
     */
    public function features(array|Closure $features): static
    {
        $this->features = $features;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getFeatures(): array
    {
        return array_values(array_map(strval(...), (array) value($this->features)));
    }

    /**
     * Free trial days for this plan, overriding the provider's `trialDays()` (`null` or `0`
     * = no trial).
     */
    public function trialDays(?int $days): static
    {
        $this->trialDays = $days;
        $this->hasTrialDays = true;

        return $this;
    }

    public function hasTrialDays(): bool
    {
        return $this->hasTrialDays;
    }

    public function getTrialDays(): ?int
    {
        return $this->trialDays;
    }

    public function highlighted(bool $condition = true): static
    {
        $this->isHighlighted = $condition;

        return $this;
    }

    public function isHighlighted(): bool
    {
        return $this->isHighlighted;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function hidden(bool|Closure $condition = true): static
    {
        $this->isVisible = $condition instanceof Closure ? static fn (): bool => ! value($condition) : ! $condition;

        return $this;
    }

    public function isVisible(): bool
    {
        return (bool) value($this->isVisible);
    }
}
