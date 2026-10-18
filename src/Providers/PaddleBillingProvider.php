<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Providers;

use AlfaSix\Alfakit\Localization\Locale;
use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing;
use AlfaSix\AlfakitBilling\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paddle Billing subscriptions through Laravel Cashier Paddle (`laravel/cashier-paddle`):
 * the Paddle.js overlay checkout to subscribe, the Paddle customer portal to manage. The
 * billable uses `Laravel\Paddle\Billable`.
 *
 * Cashier Paddle is an optional dependency: nothing here references its classes directly,
 * and every entry point throws a clear exception when it is not installed.
 */
class PaddleBillingProvider extends AbstractBillingProvider
{
    public const BILLABLE_TRAIT = 'Laravel\Paddle\Billable';

    /** @var string Cashier Paddle's facade class; swapped by the tests. */
    protected static string $cashierClass = 'Laravel\Paddle\Cashier';

    public function getName(): string
    {
        return 'Paddle';
    }

    public function isInstalled(): bool
    {
        return class_exists(static::$cashierClass);
    }

    /**
     * @internal Point the provider at a stand-in for `Laravel\Paddle\Cashier` (tests).
     */
    public static function useCashierClass(string $class): void
    {
        static::$cashierClass = $class;
    }

    public static function getCashierClass(): string
    {
        return static::$cashierClass;
    }

    protected function getMissingPackageException(): BillingPackageMissing
    {
        return BillingPackageMissing::paddle();
    }

    protected function checkSubscription(Model $billable): bool
    {
        $this->ensureInstalled();
        $this->assertBillableHas($billable, 'subscribed', self::BILLABLE_TRAIT);

        return method_exists($billable, 'subscribed') && (bool) $billable->subscribed($this->getSubscriptionType());
    }

    /**
     * Paddle's checkout is an overlay opened by Paddle.js, so this renders a small page
     * that loads Paddle.js and opens it (`Paddle.Checkout.open()` with Cashier's options).
     * Trials are part of the Paddle price, so `trialDays()` does not apply here.
     */
    public function checkout(Model $billable, Plan $plan, Panel $panel, Request $request): Response
    {
        $this->ensureInstalled();
        $this->assertBillableHas($billable, 'subscribe', self::BILLABLE_TRAIT);

        if ($plan->getPrices() === []) {
            throw new RuntimeException(__('alfakit-billing::billing.exceptions.plan_without_price', ['plan' => $plan->getId()]));
        }

        // An Inertia visit can't render a plain page: reload the URL as a full page visit.
        if ($request->hasHeader('X-Inertia')) {
            return Inertia::location($request->fullUrl());
        }

        $checkout = method_exists($billable, 'subscribe') ? $billable->subscribe($plan->getPrices(), $this->getSubscriptionType()) : null;

        if (is_object($checkout) && method_exists($checkout, 'returnTo')) {
            $checkout = $checkout->returnTo($this->getSuccessUrl($panel, $plan)) ?? $checkout;
        }

        $options = match (true) {
            is_object($checkout) && method_exists($checkout, 'options') => (array) $checkout->options(),
            is_object($checkout) && method_exists($checkout, 'toArray') => (array) $checkout->toArray(),
            default => [],
        };

        $locale = app()->getLocale();

        return response()->view('alfakit-billing::paddle-checkout', [
            'options' => $options,
            'cancelUrl' => $this->getCancelUrl($panel, $plan),
            'locale' => str_replace('_', '-', $locale),
            'direction' => Locale::make($locale)->getDirection()->value,
            'plan' => $plan,
        ]);
    }

    /**
     * Opens a Paddle customer portal session (`POST /customers/{id}/portal-sessions`)
     * for the billable's Paddle customer and its subscriptions.
     */
    public function redirectToPortal(Model $billable, Panel $panel, Request $request): Response
    {
        $this->ensureInstalled();

        $customer = $billable->getAttribute('customer');
        $customerId = $customer instanceof Model ? $customer->getAttribute('paddle_id') : null;

        if (! is_string($customerId) || $customerId === '') {
            return redirect()->to($this->getCheckoutUrl($panel) ?? $this->getReturnUrl($panel));
        }

        $subscriptionIds = [];

        foreach ((array) collect($billable->getAttribute('subscriptions'))->all() as $subscription) {
            if ($subscription instanceof Model && is_string($id = $subscription->getAttribute('paddle_id'))) {
                $subscriptionIds[] = $id;
            }
        }

        $cashier = static::$cashierClass;
        $response = $cashier::api('POST', "customers/{$customerId}/portal-sessions", array_filter(['subscription_ids' => $subscriptionIds]));
        $payload = is_object($response) && method_exists($response, 'json') ? $response->json() : $response;
        $url = data_get($payload, 'data.urls.general.overview');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException(__('alfakit-billing::billing.exceptions.portal_unavailable', ['provider' => $this->getName()]));
        }

        return $this->redirectAway($url, $request);
    }
}
