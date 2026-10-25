<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Tests\Fixtures\AuthTenancy\Panels;
use AlfaSix\Alfakit\Tests\Fixtures\Models\Project;
use AlfaSix\Alfakit\Tests\Fixtures\Models\TenantUser;
use AlfaSix\Alfakit\Tests\Fixtures\Tenancy\ProjectResource;
use AlfaSix\AlfakitBilling\BillingPlugin;
use AlfaSix\AlfakitBilling\Pages\Checkout;
use AlfaSix\AlfakitBilling\Plan;
use AlfaSix\AlfakitBilling\Providers\StripeBillingProvider;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Models\BillableTeam;
use AlfaSix\AlfakitBilling\Tests\TestCase;

uses(TestCase::class);

/**
 * A tenancy panel billed through Stripe, with a member of `alpha` and `beta`.
 *
 * @return array{0: Panel, 1: TenantUser, 2: BillableTeam, 3: BillableTeam}
 */
function stripeBillingPanel(?Closure $configure = null): array
{
    $provider = StripeBillingProvider::make()
        ->plans([
            Plan::make('basic')->price('price_basic')->amount(9, 'usd')->monthly()->features(['1 project']),
            Plan::make('pro')->price('price_pro')->label('Pro')->description('For growing teams')->priceLabel('$29 / month')
                ->features(['Unlimited projects', 'Priority support'])->highlighted(),
            Plan::make('legacy')->price('price_legacy')->hidden(),
        ])
        ->trialDays(14);

    if ($configure !== null) {
        $configure($provider);
    }

    $panel = Panels::register('stripe', fn (Panel $panel): Panel => $panel
        ->tenant(BillableTeam::class, slugAttribute: 'slug', ownershipRelationship: 'team')
        ->resources([ProjectResource::class])
        ->pages([Dashboard::class])
        ->plugin(BillingPlugin::make()->provider($provider)->requiresSubscription()));

    $user = TenantUser::query()->create(['name' => 'Ada', 'email' => 'ada'.uniqid().'@example.com', 'password' => 'secret']);
    $alpha = BillableTeam::query()->create(['name' => 'Alpha', 'slug' => 'alpha']);
    $beta = BillableTeam::query()->create(['name' => 'Beta', 'slug' => 'beta']);
    $user->teams()->attach([$alpha->id, $beta->id]);

    // Alpha has an active Stripe subscription; Beta has none.
    $alpha->forceFill(['stripe_id' => 'cus_alpha'])->save();
    $alpha->subscriptions()->create(['type' => 'default', 'stripe_id' => 'sub_alpha', 'stripe_status' => 'active', 'stripe_price' => 'price_pro', 'quantity' => 1]);

    TestCase::current()->actingAs($user);

    return [$panel, $user, $alpha, $beta];
}

it('registers the provider as the tenant billing provider with the plans page', function (): void {
    [$panel] = stripeBillingPanel();

    expect($panel->getPlugin('billing'))->toBeInstanceOf(BillingPlugin::class)
        ->and($panel->getTenantBillingProvider())->toBeInstanceOf(StripeBillingProvider::class)
        ->and($panel->isTenantSubscriptionRequired())->toBeTrue()
        ->and($panel->getPages())->toContain(Checkout::class)
        ->and(BillingPlugin::get($panel)->getProvider()?->getPlans())->toHaveKeys(['basic', 'pro'])->not()->toHaveKey('legacy');
});

it('adds a Billing entry to the tenant menu', function (): void {
    stripeBillingPanel();

    $menu = collect(TestCase::current()->inertiaProps('/stripe/alpha/projects')['alfakit']['tenancy']['menu']);

    expect($menu->pluck('url'))->toContain('http://localhost/stripe/alpha/billing')
        ->and($menu->firstWhere('url', 'http://localhost/stripe/alpha/billing')['label'])->toBe('Billing');
});

it('lets subscribed tenants through and sends the others to the plans page', function (): void {
    stripeBillingPanel();
    $test = TestCase::current();

    $test->inertiaGet('/stripe/alpha/projects')->assertOk();
    $test->get('/stripe/beta/projects')->assertRedirect('http://localhost/stripe/beta/billing/plans');
    $test->get('/stripe/beta')->assertRedirect('http://localhost/stripe/beta/billing/plans');

    // The billing route of an unsubscribed tenant goes to the plans page too.
    $test->get('/stripe/beta/billing')->assertRedirect('http://localhost/stripe/beta/billing/plans');
});

it('refuses internal requests of unsubscribed tenants', function (): void {
    [, , , $beta] = stripeBillingPanel();
    $test = TestCase::current();

    // A token from when the tenant was subscribed.
    $beta->subscriptions()->create(['type' => 'default', 'stripe_id' => 'sub_beta', 'stripe_status' => 'active']);
    $token = $test->inertiaProps('/stripe/beta/projects/create')['page']['token'];
    $beta->subscriptions()->update(['stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);

    $test->postJson('/stripe/beta/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => ['name' => 'Free ride']])
        ->assertRedirect('http://localhost/stripe/beta/billing/plans');

    expect(Project::query()->where('name', 'Free ride')->exists())->toBeFalse();
});

it('renders the plans page for unsubscribed tenants', function (): void {
    stripeBillingPanel();

    $page = TestCase::current()->inertiaProps('/stripe/beta/billing/plans')['page'];
    $json = json_encode($page['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    expect($page['title'])->toBe('Choose a plan')
        ->and($json)->toContain('Basic')
        ->toContain('$9.00 per month')
        ->toContain('1 project')
        ->toContain('Pro')
        ->toContain('For growing teams')
        ->toContain('$29 / month')
        ->toContain('Unlimited projects')
        ->toContain('Most popular')
        ->toContain('14-day free trial')
        ->toContain('Start free trial')
        ->toContain('http://localhost/stripe/beta/billing?plan=pro')
        ->not()->toContain('price_legacy')
        ->not()->toContain('You have an active subscription');
});

it('renders the plan and billing portal actions as buttons', function (): void {
    stripeBillingPanel();

    $find = function (array $content, string $prefix): array {
        $found = [];
        $walk = function ($node) use (&$walk, &$found, $prefix): void {
            if (! is_array($node)) {
                return;
            }
            if (($node['type'] ?? null) === 'Action' && str_starts_with((string) ($node['name'] ?? ''), $prefix)) {
                $found[] = $node;
            }
            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($content);

        return $found;
    };

    $plans = $find(TestCase::current()->inertiaProps('/stripe/beta/billing/plans')['page']['content'], 'subscribe');
    $portal = $find(TestCase::current()->inertiaProps('/stripe/alpha/billing/plans')['page']['content'], 'manageBilling');

    expect($plans)->not()->toBeEmpty()
        ->and(array_column($plans, 'variant'))->each->toBe('button')
        ->and($portal)->toHaveCount(1)
        ->and($portal[0]['variant'])->toBe('button');
});

it('shows subscribed tenants a link to the billing portal on the plans page', function (): void {
    stripeBillingPanel();

    $json = json_encode(TestCase::current()->inertiaProps('/stripe/alpha/billing/plans')['page']['content'], JSON_UNESCAPED_SLASHES);

    expect($json)->toContain('You have an active subscription')
        ->toContain('http://localhost/stripe/alpha/billing')
        ->not()->toContain('billing?plan=');
});

it('starts a Stripe Checkout session for the chosen plan', function (): void {
    [, , , $beta] = stripeBillingPanel(fn (StripeBillingProvider $provider) => $provider->allowPromotionCodes());
    $test = TestCase::current();

    $test->get('/stripe/beta/billing?plan=pro')->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

    $session = $test->stripe->find('/v1/checkout/sessions');

    expect($test->stripe->find('/v1/customers'))->not()->toBeNull()
        ->and($beta->refresh()->getAttribute('stripe_id'))->toBe('cus_test')
        ->and($session['params']['customer'])->toBe('cus_test')
        ->and($session['params']['mode'])->toBe('subscription')
        ->and($session['params']['line_items'])->toBe([['price' => 'price_pro', 'quantity' => 1]])
        ->and($session['params']['subscription_data']['trial_end'])->toBeGreaterThan(now()->addDays(13)->getTimestamp())
        ->and($session['params']['allow_promotion_codes'])->toBe('true')
        ->and($session['params']['success_url'])->toBe('http://localhost/stripe/beta')
        ->and($session['params']['cancel_url'])->toBe('http://localhost/stripe/beta/billing/plans');
});

it('uses custom success and cancel URLs and per-plan trials', function (): void {
    stripeBillingPanel(fn (StripeBillingProvider $provider) => $provider
        ->successUrl(fn (Plan $plan): string => url('/thanks/'.$plan->getId()))
        ->cancelUrl('https://example.com/cancelled')
        ->plans([Plan::make('pro')->price(['price_pro', 'price_seats'])->trialDays(null)]));
    $test = TestCase::current();

    $test->get('/stripe/beta/billing?plan=pro')->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

    $params = $test->stripe->find('/v1/checkout/sessions')['params'];

    expect($params['success_url'])->toBe('http://localhost/thanks/pro')
        ->and($params['cancel_url'])->toBe('https://example.com/cancelled')
        ->and($params['line_items'])->toBe([['price' => 'price_pro', 'quantity' => 1], ['price' => 'price_seats', 'quantity' => 1]])
        ->and($params['subscription_data'])->not()->toHaveKey('trial_end');
});

it('answers Inertia visits with a full page location', function (): void {
    stripeBillingPanel();

    TestCase::current()->inertiaGet('/stripe/beta/billing?plan=basic')
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_123');
});

it('sends subscribed tenants to the Stripe Billing Portal', function (): void {
    stripeBillingPanel(fn (StripeBillingProvider $provider) => $provider->portalOptions(['locale' => 'auto']));
    $test = TestCase::current();

    $test->get('/stripe/alpha/billing')->assertRedirect('https://billing.stripe.com/p/session/test_123');

    // Already subscribed: choosing a plan opens the portal instead of a second subscription.
    $test->get('/stripe/alpha/billing?plan=pro')->assertRedirect('https://billing.stripe.com/p/session/test_123');

    $portal = $test->stripe->find('/v1/billing_portal/sessions');

    expect($portal['params'])->toBe(['customer' => 'cus_alpha', 'return_url' => 'http://localhost/stripe/alpha', 'locale' => 'auto'])
        ->and($test->stripe->find('/v1/checkout/sessions'))->toBeNull();
});

it('rejects unknown plans', function (): void {
    stripeBillingPanel();

    TestCase::current()->get('/stripe/beta/billing?plan=legacy')->assertNotFound();
});

it('treats a generic trial as subscribed unless turned off', function (): void {
    [, , , $beta] = stripeBillingPanel();
    $beta->forceFill(['trial_ends_at' => now()->addWeek()])->save();
    $test = TestCase::current();

    $test->inertiaGet('/stripe/beta/projects')->assertOk();

    BillingPlugin::get(alfakit()->getPanel('stripe'))->getProvider()?->allowGenericTrial(false);

    $test->get('/stripe/beta/projects')->assertRedirect('http://localhost/stripe/beta/billing/plans');
});

it('supports a custom subscription type and subscription check', function (): void {
    stripeBillingPanel(fn (StripeBillingProvider $provider) => $provider->type('main'));
    $test = TestCase::current();

    // Alpha's subscription is of type `default`.
    $test->get('/stripe/alpha/projects')->assertRedirect('http://localhost/stripe/alpha/billing/plans');

    BillingPlugin::get(alfakit()->getPanel('stripe'))->getProvider()?->subscribedUsing(fn (BillableTeam $billable): bool => $billable->getAttribute('slug') === 'beta');

    $test->inertiaGet('/stripe/beta/projects')->assertOk();
});

it('does not require a subscription when the plugin does not', function (): void {
    $panel = Panels::register('optional', fn (Panel $panel): Panel => $panel
        ->tenant(BillableTeam::class, slugAttribute: 'slug', ownershipRelationship: 'team')
        ->resources([ProjectResource::class])
        ->plugin(BillingPlugin::make()->provider(StripeBillingProvider::make())));

    $user = TenantUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']);
    $team = BillableTeam::query()->create(['name' => 'Free', 'slug' => 'free']);
    $user->teams()->attach($team->id);

    expect($panel->isTenantSubscriptionRequired())->toBeFalse();

    TestCase::current()->actingAs($user);
    TestCase::current()->inertiaGet('/optional/free/projects')->assertOk();
});

it('explains how to install Laravel Cashier when it is missing', function (): void {
    StripeBillingProvider::useCashierClass('Missing\Cashier\Cashier');

    try {
        [, , $alpha] = stripeBillingPanel();
        $provider = StripeBillingProvider::make();

        expect($provider->isInstalled())->toBeFalse()
            ->and(fn () => $provider->ensureInstalled())->toThrow(AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing::class, 'laravel/cashier');

        $response = TestCase::current()->get("/stripe/{$alpha->slug}");

        expect($response->exception)->toBeInstanceOf(AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing::class);
    } finally {
        StripeBillingProvider::useCashierClass(StripeBillingProvider::CASHIER_CLASS);
    }
});
