<?php

declare(strict_types=1);

use AlfaSix\AlfakitBilling\Plan;
use AlfaSix\AlfakitBilling\Providers\StripeBillingProvider;
use AlfaSix\AlfakitBilling\Tests\TestCase;

uses(TestCase::class);

it('describes a plan', function (): void {
    $plan = Plan::make('pro_plus')->price('price_1')->features(fn (): array => ['A', 'B'])->highlighted();

    expect($plan->getLabel())->toBe('Pro Plus')
        ->and($plan->getPrices())->toBe(['price_1'])
        ->and($plan->getFeatures())->toBe(['A', 'B'])
        ->and($plan->isHighlighted())->toBeTrue()
        ->and($plan->getPriceLabel())->toBeNull()
        ->and($plan->amount(12.5)->getPriceLabel())->toBe('12.5')
        ->and($plan->amount(1200, 'usd')->interval('week')->getPriceLabel())->toBe('$1,200.00 per week')
        ->and($plan->priceLabel('Contact us')->getPriceLabel())->toBe('Contact us');
});

it('formats amounts in the current locale', function (): void {
    app()->setLocale('de');

    expect(Plan::make('pro')->amount(29, 'eur')->getPriceLabel())->toBe("29,00\u{a0}€");
});

it('resolves trial days from the plan, then the provider', function (): void {
    $provider = StripeBillingProvider::make()->trialDays(fn (?Plan $plan): int => $plan?->getId() === 'team' ? 30 : 7);

    expect($provider->getTrialDays(Plan::make('pro')))->toBe(7)
        ->and($provider->getTrialDays(Plan::make('team')))->toBe(30)
        ->and($provider->getTrialDays(Plan::make('pro')->trialDays(3)))->toBe(3)
        ->and($provider->getTrialDays(Plan::make('pro')->trialDays(null)))->toBeNull()
        ->and($provider->getTrialDays(Plan::make('pro')->trialDays(0)))->toBeNull();
});

it('hides plans', function (): void {
    $provider = StripeBillingProvider::make()->plans([
        Plan::make('a'),
        Plan::make('b')->hidden(),
        Plan::make('c')->visible(fn (): bool => false),
    ]);

    expect(array_keys($provider->getPlans()))->toBe(['a'])
        ->and($provider->getPlan('b'))->toBeNull();
});
