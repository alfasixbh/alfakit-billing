<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes;

/**
 * Mirrors the public API of `Laravel\Paddle\Checkout` that the provider uses.
 */
class FakePaddleCheckout
{
    protected ?string $returnTo = null;

    /**
     * @param  array<int, string>  $prices
     */
    public function __construct(protected array $prices, protected string $type) {}

    public function returnTo(string $url): static
    {
        $this->returnTo = $url;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'items' => array_map(static fn (string $price): array => ['priceId' => $price, 'quantity' => 1], $this->prices),
            'customData' => ['subscription_type' => $this->type],
            'settings' => ['successUrl' => $this->returnTo],
        ];
    }
}
