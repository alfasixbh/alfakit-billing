<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Tests\Fixtures\AuthTenancy\Panels;
use AlfaSix\AlfakitBilling\BillingPlugin;
use AlfaSix\AlfakitBilling\Plan;
use AlfaSix\AlfakitBilling\Providers\StripeBillingProvider;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Models\BillableUser;
use AlfaSix\AlfakitBilling\Tests\TestCase;
use Laravel\Cashier\Cashier;

uses(TestCase::class);

/**
 * A panel without tenancy where the signed-in user is billed.
 */
function userBillingPanel(?Closure $plugin = null, ?Closure $panel = null): BillableUser
{
    Cashier::useCustomerModel(BillableUser::class);

    $billing = BillingPlugin::make()
        ->provider(StripeBillingProvider::make()->plans([Plan::make('pro')->price('price_pro')->amount(29, 'eur')->yearly()]))
        ->requiresSubscription();

    if ($plugin !== null) {
        $plugin($billing);
    }

    $configure = $panel;

    Panels::register('account', fn (Panel $panel): Panel => ($configure ?? fn (Panel $panel): Panel => $panel)($panel
        ->login()
        ->profile()
        ->userMenuItems([Action::make('docs')->label('Docs')->url('https://example.com/docs')])
        ->pages([Dashboard::class])
        ->plugin($billing)));

    $user = BillableUser::query()->create(['name' => 'Ada', 'email' => 'ada'.uniqid().'@example.com', 'password' => 'secret']);
    TestCase::current()->actingAs($user);

    return $user;
}

function subscribeUser(BillableUser $user): void
{
    $user->forceFill(['stripe_id' => 'cus_user'])->save();
    $user->subscriptions()->create(['type' => 'default', 'stripe_id' => 'sub_user', 'stripe_status' => 'active']);
}

it('sends unsubscribed users to the plans page', function (): void {
    userBillingPanel();
    $test = TestCase::current();

    $test->get('/account')->assertRedirect('http://localhost/account/billing/plans');
    $test->get('/account/billing')->assertRedirect('http://localhost/account/billing/plans');

    $json = json_encode($test->inertiaProps('/account/billing/plans')['page']['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    expect($json)->toContain('€29.00 per year')->toContain('http://localhost/account/billing?plan=pro');
});

it('keeps the account pages reachable without a subscription', function (): void {
    userBillingPanel();

    TestCase::current()->inertiaGet('/account/profile')->assertOk();
});

it('keeps the account-level endpoints of the header working without a subscription', function (): void {
    userBillingPanel(panel: fn (Panel $panel): Panel => $panel->databaseNotifications());
    $test = TestCase::current();

    // The header components (the bell, the language switcher, a starter kit's header data) call
    // these on every page, the plans page included; a redirect to the plans page broke them.
    $test->getJson('/account/_alfakit/notifications')->assertOk();
    $test->get('/account')->assertRedirect('http://localhost/account/billing/plans');
});

it('lets subscribed users through and opens their billing portal', function (): void {
    subscribeUser(userBillingPanel());
    $test = TestCase::current();

    $test->inertiaGet('/account')->assertOk();
    $test->get('/account/billing')->assertRedirect('https://billing.stripe.com/p/session/test_123');

    expect($test->stripe->find('/v1/billing_portal/sessions')['params']['customer'])->toBe('cus_user');
});

it('starts a Stripe Checkout for the user', function (): void {
    $user = userBillingPanel();
    $test = TestCase::current();

    $test->get('/account/billing?plan=pro')->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

    expect($user->refresh()->getAttribute('stripe_id'))->toBe('cus_test')
        ->and($test->stripe->find('/v1/checkout/sessions')['params']['line_items'])->toBe([['price' => 'price_pro', 'quantity' => 1]]);
});

it('sends the user back to the plans with a message when the provider fails (no Stripe keys)', function (): void {
    $user = userBillingPanel();
    $test = TestCase::current();
    config(['cashier.secret' => null, 'cashier.key' => null]);
    Illuminate\Support\Facades\Exceptions::fake();

    // Stripe answers "No API key provided": no error page, the plans page with a notice.
    $test->get('/account/billing?plan=pro')->assertRedirect('http://localhost/account/billing/plans');

    Illuminate\Support\Facades\Exceptions::assertReported(Stripe\Exception\AuthenticationException::class);
    expect(json_encode(session()->all()))->toContain("Payments aren't available right now")
        ->and($user->refresh()->subscriptions()->count())->toBe(0);
});

it('adds a Billing entry after the configured user menu items', function (): void {
    subscribeUser(userBillingPanel());
    $test = TestCase::current();

    $items = $test->inertiaProps('/account')['alfakit']['userMenu']['items'];

    expect(array_column($items, 'label'))->toBe(['Docs', 'Billing'])
        ->and($items[1]['url'])->toBe('http://localhost/account/billing');

    // Booting the panel again doesn't add a second entry.
    expect(array_column($test->inertiaProps('/account')['alfakit']['userMenu']['items'], 'label'))->toBe(['Docs', 'Billing']);
});

it('can leave the user menu alone and the subscription optional', function (): void {
    userBillingPanel(fn (BillingPlugin $plugin) => $plugin->userMenuItem(false)->requiresSubscription(false));

    $props = TestCase::current()->inertiaProps('/account');

    expect(array_column($props['alfakit']['userMenu']['items'], 'label'))->toBe(['Docs']);
});

it('refuses internal requests of unsubscribed users', function (): void {
    $user = userBillingPanel();
    subscribeUser($user);
    $test = TestCase::current();

    // A token from when the user was subscribed.
    $token = $test->inertiaProps('/account')['page']['token'];
    $user->subscriptions()->update(['stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);

    // A fresh model, as on a real next request (no subscriptions cached on the instance).
    $test->actingAs($user->fresh());

    // The panel's auth middleware runs on internal endpoints; its redirect becomes a 403.
    $test->postJson('/account/_alfakit/actions', ['token' => $token, 'action' => 'missing'])->assertForbidden();

    $user->subscriptions()->update(['stripe_status' => 'active', 'ends_at' => null]);
    $test->actingAs($user->fresh());

    // Subscribed again: the request reaches the endpoint (which has no such action).
    $test->postJson('/account/_alfakit/actions', ['token' => $token, 'action' => 'missing'])->assertNotFound();
});
