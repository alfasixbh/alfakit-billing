<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Providers;

use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing;
use AlfaSix\AlfakitBilling\Plan;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stripe subscriptions through Laravel Cashier: Stripe Checkout to subscribe, the Stripe
 * Billing Portal to manage. The billable (tenant or user model) uses `Laravel\Cashier\Billable`.
 *
 * ```php
 * BillingPlugin::make()->provider(
 *     StripeBillingProvider::make()
 *         ->plans([Plan::make('pro')->price('price_123')->label('Pro')->features([...])])
 *         ->trialDays(14),
 * );
 * ```
 */
class StripeBillingProvider extends AbstractBillingProvider
{
    public const CASHIER_CLASS = 'Laravel\Cashier\Cashier';

    public const BILLABLE_TRAIT = 'Laravel\Cashier\Billable';

    /** `laravel/cashier` is optional (`suggest`): checked before every Cashier call. */
    protected static string $cashierClass = self::CASHIER_CLASS;

    protected bool|Closure $allowsPromotionCodes = false;

    /** @var array<string, mixed>|Closure */
    protected array|Closure $checkoutOptions = [];

    /** @var array<string, mixed>|Closure */
    protected array|Closure $customerOptions = [];

    /** @var array<string, mixed>|Closure */
    protected array|Closure $portalOptions = [];

    public function getName(): string
    {
        return 'Stripe';
    }

    public function isInstalled(): bool
    {
        return class_exists(static::$cashierClass);
    }

    /**
     * @internal Point the provider at another class than `Laravel\Cashier\Cashier` (tests).
     */
    public static function useCashierClass(string $class): void
    {
        static::$cashierClass = $class;
    }

    protected function getMissingPackageException(): BillingPackageMissing
    {
        return BillingPackageMissing::stripe();
    }

    /**
     * Let customers enter promotion codes on the Stripe Checkout page.
     */
    public function allowPromotionCodes(bool|Closure $condition = true): static
    {
        $this->allowsPromotionCodes = $condition;

        return $this;
    }

    /**
     * Extra Checkout Session options (`tax_id_collection`, `locale`, …), merged into
     * Cashier's `checkout()` call.
     *
     * @param  array<string, mixed>|Closure  $options
     */
    public function checkoutOptions(array|Closure $options): static
    {
        $this->checkoutOptions = $options;

        return $this;
    }

    /**
     * Options for creating the Stripe customer (`name`, `email`, `metadata`, …).
     *
     * @param  array<string, mixed>|Closure  $options
     */
    public function customerOptions(array|Closure $options): static
    {
        $this->customerOptions = $options;

        return $this;
    }

    /**
     * Extra Billing Portal session options (`configuration`, `locale`, `flow_data`, …).
     *
     * @param  array<string, mixed>|Closure  $options
     */
    public function portalOptions(array|Closure $options): static
    {
        $this->portalOptions = $options;

        return $this;
    }

    protected function checkSubscription(Model $billable): bool
    {
        $this->ensureInstalled();
        $this->assertBillableHas($billable, 'subscribed', self::BILLABLE_TRAIT);

        return method_exists($billable, 'subscribed') && (bool) $billable->subscribed($this->getSubscriptionType());
    }

    public function redirectToPortal(Model $billable, Panel $panel, Request $request): Response
    {
        $this->ensureInstalled();
        $this->assertBillableHas($billable, 'billingPortalUrl', self::BILLABLE_TRAIT);

        // A billable on a generic trial may not be a Stripe customer yet.
        if (method_exists($billable, 'hasStripeId') && ! $billable->hasStripeId() && method_exists($billable, 'createOrGetStripeCustomer')) {
            $billable->createOrGetStripeCustomer($this->getCustomerOptions($billable));
        }

        $url = method_exists($billable, 'billingPortalUrl')
            ? $billable->billingPortalUrl($this->getReturnUrl($panel), $this->getPortalOptions($billable))
            : null;

        return $this->redirectAway((string) $url, $request);
    }

    public function checkout(Model $billable, Plan $plan, Panel $panel, Request $request): Response
    {
        $this->ensureInstalled();
        $this->assertBillableHas($billable, 'newSubscription', self::BILLABLE_TRAIT);

        if ($plan->getPrices() === []) {
            throw new RuntimeException(__('alfakit-billing::billing.exceptions.plan_without_price', ['plan' => $plan->getId()]));
        }

        if (! method_exists($billable, 'newSubscription')) {
            return $this->redirectAway($this->getCancelUrl($panel, $plan), $request);
        }

        $builder = $billable->newSubscription($this->getSubscriptionType(), $plan->getPrices());

        if (($days = $this->getTrialDays($plan)) !== null) {
            $builder->trialDays($days);
        }

        if ((bool) $this->evaluate($this->allowsPromotionCodes, ['billable' => $billable, 'plan' => $plan])) {
            $builder->allowPromotionCodes();
        }

        $checkout = $builder->checkout([
            'success_url' => $this->getSuccessUrl($panel, $plan),
            'cancel_url' => $this->getCancelUrl($panel, $plan),
            ...(array) $this->evaluate($this->checkoutOptions, ['billable' => $billable, 'plan' => $plan], [Plan::class => $plan]),
        ], $this->getCustomerOptions($billable));

        return $this->redirectAway((string) $checkout->asStripeCheckoutSession()->url, $request);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCustomerOptions(Model $billable): array
    {
        return (array) $this->evaluate($this->customerOptions, ['billable' => $billable], [Model::class => $billable]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPortalOptions(Model $billable): array
    {
        return (array) $this->evaluate($this->portalOptions, ['billable' => $billable], [Model::class => $billable]);
    }
}
