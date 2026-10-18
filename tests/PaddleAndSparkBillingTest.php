<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Tests\Fixtures\AuthTenancy\Panels;
use AlfaSix\Alfakit\Tests\Fixtures\Models\TenantUser;
use AlfaSix\Alfakit\Tests\Fixtures\Tenancy\ProjectResource;
use AlfaSix\AlfakitBilling\BillingPlugin;
use AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing;
use AlfaSix\AlfakitBilling\Plan;
use AlfaSix\AlfakitBilling\Providers\AbstractBillingProvider;
use AlfaSix\AlfakitBilling\Providers\PaddleBillingProvider;
use AlfaSix\AlfakitBilling\Providers\SparkBillingProvider;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes\FakePaddleCashier;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes\FakeSpark;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Models\FakeBillableTeam;
use AlfaSix\AlfakitBilling\Tests\TestCase;

uses(TestCase::class);

/**
 * A tenancy panel billed by `$provider`, with tenants `alpha` (subscribed) and `beta`.
 *
 * @return array{0: FakeBillableTeam, 1: FakeBillableTeam}
 */
function fakeBillingPanel(string $id, AbstractBillingProvider $provider): array
{
    Panels::register($id, fn (Panel $panel): Panel => $panel
        ->tenant(FakeBillableTeam::class, slugAttribute: 'slug', ownershipRelationship: 'team')
        ->resources([ProjectResource::class])
        ->pages([Dashboard::class])
        ->plugin(BillingPlugin::make()->provider($provider)->requiresSubscription()));

    $user = TenantUser::query()->create(['name' => 'Ada', 'email' => 'ada'.uniqid().'@example.com', 'password' => 'secret']);
    $alpha = FakeBillableTeam::query()->create(['name' => 'Alpha', 'slug' => 'alpha']);
    $beta = FakeBillableTeam::query()->create(['name' => 'Beta', 'slug' => 'beta']);
    $user->teams()->attach([$alpha->id, $beta->id]);
    FakeBillableTeam::$subscribed = ['alpha'];

    TestCase::current()->actingAs($user);

    return [$alpha, $beta];
}

// ---------------------------------------------------------------- Paddle

it('explains how to install Cashier Paddle when it is missing', function (): void {
    PaddleBillingProvider::useCashierClass('Missing\\Paddle\\Cashier');
    $provider = PaddleBillingProvider::make();

    expect($provider->isInstalled())->toBeFalse()
        ->and(fn () => $provider->ensureInstalled())->toThrow(BillingPackageMissing::class, 'composer require laravel/cashier-paddle')
        ->and(fn () => $provider->ensureInstalled())->toThrow(BillingPackageMissing::class, 'Guzzle 7');
});

it('refuses to bill through Paddle without Cashier Paddle', function (): void {
    PaddleBillingProvider::useCashierClass('Missing\\Paddle\\Cashier');
    fakeBillingPanel('paddle', PaddleBillingProvider::make()->plans([Plan::make('pro')->price('pri_pro')]));
    $test = TestCase::current();

    $response = $test->get('/paddle/alpha/projects')->assertServerError();

    expect($response->exception)->toBeInstanceOf(BillingPackageMissing::class);
    expect($test->get('/paddle/alpha/billing')->assertServerError()->exception)->toBeInstanceOf(BillingPackageMissing::class);
});

it('detects an installed Cashier Paddle', function (): void {
    expect(PaddleBillingProvider::make()->isInstalled())->toBeTrue();
});

it('checks Paddle subscriptions and opens the Paddle checkout', function (): void {
    PaddleBillingProvider::useCashierClass(FakePaddleCashier::class);
    fakeBillingPanel('paddle', PaddleBillingProvider::make()->plans([Plan::make('pro')->price('pri_pro')->label('Pro')])->type('main'));
    $test = TestCase::current();

    $test->inertiaGet('/paddle/alpha/projects')->assertOk();
    $test->get('/paddle/beta/projects')->assertRedirect('http://localhost/paddle/beta/billing/plans');
    $test->inertiaGet('/paddle/beta/billing/plans')->assertOk();

    // Inertia visits reload the page so Paddle.js can run.
    $test->inertiaGet('/paddle/beta/billing?plan=pro')->assertStatus(409)->assertHeader('X-Inertia-Location', 'http://localhost/paddle/beta/billing?plan=pro');

    $response = $test->get('/paddle/beta/billing?plan=pro')->assertOk();

    expect(FakeBillableTeam::$subscribeCalls)->toBe([['prices' => ['pri_pro'], 'type' => 'main']])
        ->and($response->getContent())
        ->toContain('Paddle.Checkout.open(')
        ->toContain('"priceId":"pri_pro"')
        ->toContain('"successUrl":"http:\/\/localhost\/paddle\/beta"')
        ->toContain('href="http://localhost/paddle/beta/billing/plans"')
        ->toContain('dir="ltr"');
});

it('opens the Paddle customer portal for subscribed tenants', function (): void {
    PaddleBillingProvider::useCashierClass(FakePaddleCashier::class);
    fakeBillingPanel('paddle', PaddleBillingProvider::make()->plans([Plan::make('pro')->price('pri_pro')]));
    $test = TestCase::current();

    $test->get('/paddle/alpha/billing')->assertRedirect('https://customer-portal.paddle.com/cpl_test');

    expect(FakePaddleCashier::$calls)->toBe([[
        'method' => 'POST',
        'uri' => 'customers/ctm_123/portal-sessions',
        'payload' => ['subscription_ids' => ['sub_alpha']],
    ]]);
});

// ---------------------------------------------------------------- Spark

it('explains that Spark is a paid package when it is missing', function (): void {
    $provider = SparkBillingProvider::make();

    expect($provider->isInstalled())->toBeFalse()
        ->and(fn () => $provider->ensureInstalled())->toThrow(BillingPackageMissing::class, 'spark.laravel.com');
});

it('sends tenants to the Spark billing portal', function (): void {
    SparkBillingProvider::useSparkClass(FakeSpark::class);
    [$alpha, $beta] = fakeBillingPanel('spark', SparkBillingProvider::make());
    $test = TestCase::current();

    $test->inertiaGet('/spark/alpha/projects')->assertOk();

    // Spark has its own plan picker: unsubscribed tenants go straight to the billing route.
    $test->get('/spark/beta/projects')->assertRedirect('http://localhost/spark/beta/billing');
    $test->get('/spark/beta/billing/plans')->assertRedirect('http://localhost/spark/beta/billing');
    $test->get('/spark/beta/billing')->assertRedirect('http://localhost/billing/fake-billable-team/'.$beta->getKey());
    $test->inertiaGet('/spark/alpha/billing')->assertStatus(409)->assertHeader('X-Inertia-Location', 'http://localhost/billing/fake-billable-team/'.$alpha->getKey());
});

it('uses the configured Spark path and billable type', function (): void {
    SparkBillingProvider::useSparkClass(FakeSpark::class);
    [$alpha] = fakeBillingPanel('spark', SparkBillingProvider::make()->path('/subscriptions/')->billableType('team'));
    $test = TestCase::current();

    $test->get('/spark/alpha/billing')->assertRedirect('http://localhost/subscriptions/team/'.$alpha->getKey());

    $user = new AlfaSix\Alfakit\Tests\Fixtures\Models\User;

    expect(SparkBillingProvider::make()->getPortalUrl($user))->toBe('http://localhost/billing');
});
