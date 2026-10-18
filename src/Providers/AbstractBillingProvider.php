<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Providers;

use AlfaSix\Alfakit\Billing\Providers\Contracts\BillingProvider;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Support\Concerns\EvaluatesClosures;
use AlfaSix\AlfakitBilling\BillingPlugin;
use AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing;
use AlfaSix\AlfakitBilling\Exceptions\InvalidBillable;
use AlfaSix\AlfakitBilling\Http\Controllers\BillingController;
use AlfaSix\AlfakitBilling\Http\Middleware\RedirectIfNotSubscribed;
use AlfaSix\AlfakitBilling\Pages\Checkout;
use AlfaSix\AlfakitBilling\Plan;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared configuration of the AlfaKit billing providers: plans, the subscription type,
 * trials, redirect URLs and who the billable is (the current tenant on tenancy panels,
 * the signed-in user otherwise).
 */
abstract class AbstractBillingProvider implements BillingProvider
{
    use EvaluatesClosures;

    /** @var array<int, Plan>|Closure */
    protected array|Closure $plans = [];

    protected string|Closure $subscriptionType = 'default';

    protected int|Closure|null $trialDays = null;

    protected string|Closure|null $successUrl = null;

    protected string|Closure|null $cancelUrl = null;

    protected string|Closure|null $returnUrl = null;

    protected ?Closure $resolveBillableUsing = null;

    protected ?Closure $subscribedUsing = null;

    protected bool $allowsGenericTrial = true;

    public static function make(): static
    {
        return new static;
    }

    /**
     * The provider's display name, used in error messages.
     */
    abstract public function getName(): string;

    /**
     * Whether the billing package this provider is built on is installed.
     */
    abstract public function isInstalled(): bool;

    abstract protected function getMissingPackageException(): BillingPackageMissing;

    /**
     * Send the billable to the provider's billing portal (manage plan, payment methods, invoices).
     */
    abstract public function redirectToPortal(Model $billable, Panel $panel, Request $request): Response;

    /**
     * Start a subscription to `$plan` (usually a redirect to a hosted checkout).
     */
    abstract public function checkout(Model $billable, Plan $plan, Panel $panel, Request $request): Response;

    /**
     * Whether the billable has an active subscription of the configured type.
     */
    abstract protected function checkSubscription(Model $billable): bool;

    public function ensureInstalled(): void
    {
        if (! $this->isInstalled()) {
            throw $this->getMissingPackageException();
        }
    }

    // ---------------------------------------------------------------- Configuration

    /**
     * @param  array<int, Plan>|Closure  $plans
     */
    public function plans(array|Closure $plans): static
    {
        $this->plans = $plans;

        return $this;
    }

    /**
     * Visible plans keyed by id.
     *
     * @return array<string, Plan>
     */
    public function getPlans(): array
    {
        $plans = [];

        foreach ((array) $this->evaluate($this->plans) as $plan) {
            if ($plan instanceof Plan && $plan->isVisible()) {
                $plans[$plan->getId()] = $plan;
            }
        }

        return $plans;
    }

    public function getPlan(string $id): ?Plan
    {
        return $this->getPlans()[$id] ?? null;
    }

    /**
     * The subscription type (Cashier's "name"): `default` unless changed.
     */
    public function type(string|Closure $type): static
    {
        $this->subscriptionType = $type;

        return $this;
    }

    public function getSubscriptionType(): string
    {
        return (string) $this->evaluate($this->subscriptionType);
    }

    /**
     * Free trial days for new subscriptions (plans can override with `Plan::trialDays()`).
     */
    public function trialDays(int|Closure|null $days): static
    {
        $this->trialDays = $days;

        return $this;
    }

    public function getTrialDays(?Plan $plan = null): ?int
    {
        $days = $plan !== null && $plan->hasTrialDays() ? $plan->getTrialDays() : $this->evaluate($this->trialDays, ['plan' => $plan], $plan === null ? [] : [Plan::class => $plan]);

        return is_numeric($days) && (int) $days > 0 ? (int) $days : null;
    }

    /**
     * Treat a generic trial (`trial_ends_at` on the billable, no subscription yet) as
     * subscribed. On by default, like Cashier's `onTrial()`.
     */
    public function allowGenericTrial(bool $condition = true): static
    {
        $this->allowsGenericTrial = $condition;

        return $this;
    }

    /**
     * Where the checkout returns after a successful payment (default: the panel home).
     */
    public function successUrl(string|Closure|null $url): static
    {
        $this->successUrl = $url;

        return $this;
    }

    /**
     * Where a cancelled checkout returns (default: the plans page).
     */
    public function cancelUrl(string|Closure|null $url): static
    {
        $this->cancelUrl = $url;

        return $this;
    }

    /**
     * Where the billing portal's "back" link goes (default: the panel home).
     */
    public function returnUrl(string|Closure|null $url): static
    {
        $this->returnUrl = $url;

        return $this;
    }

    /**
     * Choose the billable yourself: `fn (Panel $panel, ?Model $tenant, $user) => $tenant?->owner`.
     */
    public function billable(?Closure $callback): static
    {
        $this->resolveBillableUsing = $callback;

        return $this;
    }

    /**
     * Replace the subscription check: `fn (Model $billable): bool => $billable->subscribed('main')`.
     */
    public function subscribedUsing(?Closure $callback): static
    {
        $this->subscribedUsing = $callback;

        return $this;
    }

    // ---------------------------------------------------------------- Resolution

    /**
     * The billable for the current request: the tenant on tenancy panels, else the user.
     */
    public function getBillable(?Panel $panel = null): ?Model
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();
        $tenant = $panel->hasTenancy() ? $panel->getTenant() : null;
        $user = $panel->auth()->user();

        $billable = $this->resolveBillableUsing !== null
            ? $this->evaluate($this->resolveBillableUsing, ['panel' => $panel, 'tenant' => $tenant, 'user' => $user], [Panel::class => $panel])
            : ($panel->hasTenancy() ? $tenant : $user);

        return $billable instanceof Model ? $billable : null;
    }

    public function isSubscribed(Model $billable): bool
    {
        if ($this->subscribedUsing !== null) {
            return (bool) $this->evaluate($this->subscribedUsing, ['billable' => $billable, 'type' => $this->getSubscriptionType()], [Model::class => $billable]);
        }

        if ($this->checkSubscription($billable)) {
            return true;
        }

        return $this->allowsGenericTrial && method_exists($billable, 'onGenericTrial') && (bool) $billable->onGenericTrial();
    }

    /**
     * Fail clearly when the billable lacks the package's `Billable` trait.
     */
    protected function assertBillableHas(Model $billable, string $method, string $trait): void
    {
        if (! method_exists($billable, $method)) {
            throw InvalidBillable::missingTrait($billable, $trait);
        }
    }

    // ---------------------------------------------------------------- Routes & URLs

    public function getRouteAction(): string|Closure|array
    {
        return BillingController::class;
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectIfNotSubscribed::class;
    }

    /**
     * Whether unsubscribed billables choose a plan on the AlfaKit plans page (Stripe,
     * Paddle) or in the provider's own portal (Spark).
     */
    public function usesCheckoutPage(): bool
    {
        return true;
    }

    /**
     * The billing route: `/{tenant}/billing` on tenancy panels, `/billing` otherwise.
     *
     * @param  array<string, string>  $query
     */
    public function getBillingUrl(?Panel $panel = null, array $query = []): ?string
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();

        if ($panel->hasTenancy()) {
            $url = $panel->getTenantBillingUrl();
        } else {
            $url = Route::has($panel->generateRouteName(Checkout::USER_BILLING_ROUTE)) ? $panel->route(Checkout::USER_BILLING_ROUTE) : null;
        }

        if ($url === null || $query === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }

    /**
     * The plans page registered on the panel, if any.
     *
     * @return class-string<Checkout>|null
     */
    public function getCheckoutPage(?Panel $panel = null): ?string
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();

        foreach ($panel->getPages() as $page) {
            if (is_a($page, Checkout::class, true)) {
                return $page;
            }
        }

        return null;
    }

    public function getCheckoutUrl(?Panel $panel = null): ?string
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();
        $page = $this->usesCheckoutPage() ? $this->getCheckoutPage($panel) : null;

        if ($page === null || ($panel->hasTenancy() && $panel->getTenant() === null)) {
            return null;
        }

        return $page::getUrl(panel: $panel);
    }

    /**
     * Where unsubscribed billables are sent: the plans page, else the billing route.
     */
    public function getUnsubscribedRedirectUrl(?Panel $panel = null): string
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();

        return $this->getCheckoutUrl($panel) ?? $this->getBillingUrl($panel) ?? $panel->getUrl();
    }

    public function getSuccessUrl(Panel $panel, ?Plan $plan = null): string
    {
        return $this->resolveUrl($this->successUrl, $panel, $plan) ?? $panel->getUrl();
    }

    public function getCancelUrl(Panel $panel, ?Plan $plan = null): string
    {
        return $this->resolveUrl($this->cancelUrl, $panel, $plan) ?? $this->getCheckoutUrl($panel) ?? $panel->getUrl();
    }

    public function getReturnUrl(Panel $panel): string
    {
        return $this->resolveUrl($this->returnUrl, $panel) ?? $panel->getUrl();
    }

    protected function resolveUrl(string|Closure|null $url, Panel $panel, ?Plan $plan = null): ?string
    {
        $resolved = $this->evaluate($url, ['panel' => $panel, 'plan' => $plan, 'tenant' => $panel->hasTenancy() ? $panel->getTenant() : null], [Panel::class => $panel]);

        return is_string($resolved) && $resolved !== '' ? $resolved : null;
    }

    /**
     * A redirect to another site that also works for Inertia visits (a full page load).
     */
    protected function redirectAway(string $url, Request $request): Response
    {
        if ($request->hasHeader('X-Inertia')) {
            return Inertia::location($url);
        }

        return redirect()->away($url);
    }

    /**
     * The billing provider configured for `$panel`: the billing plugin's, else a panel
     * `tenantBillingProvider()` that is one of these providers.
     */
    public static function forPanel(Panel $panel): ?self
    {
        if ($panel->hasPlugin(BillingPlugin::ID)) {
            $plugin = $panel->getPlugin(BillingPlugin::ID);

            if ($plugin instanceof BillingPlugin && ($provider = $plugin->getProvider()) !== null) {
                return $provider;
            }
        }

        $provider = $panel->getTenantBillingProvider();

        return $provider instanceof self ? $provider : null;
    }
}
