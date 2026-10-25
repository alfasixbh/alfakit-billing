<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Contracts\Plugin;
use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitBilling\Http\Middleware\RedirectIfNotSubscribed;
use AlfaSix\AlfakitBilling\Pages\Checkout;
use AlfaSix\AlfakitBilling\Providers\AbstractBillingProvider;
use Closure;
use InvalidArgumentException;

/**
 * Subscription billing for a panel.
 *
 * ```php
 * $panel->plugin(
 *     BillingPlugin::make()
 *         ->provider(StripeBillingProvider::make()->plans([...])->trialDays(14))
 *         ->requiresSubscription(),
 * );
 * ```
 *
 * On tenancy panels the tenant is billed: the provider becomes the panel's
 * `tenantBillingProvider()` (billing route, "Billing" tenant menu entry) and
 * `requiresSubscription()` is `requiresTenantSubscription()`. Without tenancy the
 * signed-in user is billed through `/billing`, with a "Billing" user menu entry.
 */
class BillingPlugin implements Plugin
{
    public const ID = 'billing';

    protected ?AbstractBillingProvider $provider = null;

    protected bool|Closure $isSubscriptionRequired = false;

    /** @var class-string<Checkout> */
    protected string $checkoutPage = Checkout::class;

    protected bool|Closure $hasUserMenuItem = true;

    /** @var array<string, true> Panels whose user menu already has the billing entry. */
    protected array $userMenuPanels = [];

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    public static function get(?Panel $panel = null): static
    {
        $plugin = ($panel ?? alfakit()->getCurrentOrDefaultPanel())->getPlugin(self::ID);

        if (! $plugin instanceof static) {
            throw new InvalidArgumentException('The billing plugin is not registered on this panel.');
        }

        return $plugin;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function provider(?AbstractBillingProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): ?AbstractBillingProvider
    {
        return $this->provider;
    }

    /**
     * Send billables without an active subscription to the plans page.
     */
    public function requiresSubscription(bool|Closure $condition = true): static
    {
        $this->isSubscriptionRequired = $condition;

        return $this;
    }

    public function isSubscriptionRequired(): bool
    {
        return $this->provider !== null && (bool) value($this->isSubscriptionRequired);
    }

    /**
     * Use your own plans page (a subclass of `Checkout`).
     *
     * @param  class-string<Checkout>  $page
     */
    public function checkoutPage(string $page): static
    {
        $this->checkoutPage = $page;

        return $this;
    }

    /**
     * @return class-string<Checkout>
     */
    public function getCheckoutPage(): string
    {
        return $this->checkoutPage;
    }

    /**
     * Add a "Billing" entry to the user menu on panels without tenancy (on by default).
     */
    public function userMenuItem(bool|Closure $condition = true): static
    {
        $this->hasUserMenuItem = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([$this->checkoutPage])
            // Tenancy panels: the tenant is billed (no-ops on panels without tenancy).
            ->tenantBillingProvider($this->provider)
            ->requiresTenantSubscription(fn (): bool => $this->isSubscriptionRequired())
            // Panels without tenancy: the user is billed (the middleware no-ops on tenancy panels).
            ->authMiddleware([RedirectIfNotSubscribed::class.':'.RedirectIfNotSubscribed::USER])
            // Starter kits: the middleware above refuses the dashboard widgets' requests of a
            // user who must subscribe first, so don't offer them the widgets.
            ->hideStarterKitDashboardWhen(function () use ($panel): bool {
                if ($panel->hasTenancy() || ! $this->isSubscriptionRequired()) {
                    return false;
                }

                $provider = AbstractBillingProvider::forPanel($panel);
                $billable = $provider?->getBillable($panel);

                return $provider !== null && $billable !== null && ! $provider->isSubscribed($billable);
            });
    }

    public function boot(Panel $panel): void
    {
        if ($panel->hasTenancy() || $this->provider === null || isset($this->userMenuPanels[$panel->getId()])) {
            return;
        }

        $this->userMenuPanels[$panel->getId()] = true;

        // Append the entry to whatever user menu items the panel was configured with.
        $items = (fn (): array|Closure => $this->userMenuItems)->call($panel);
        $provider = $this->provider;

        $panel->userMenuItems(function () use ($panel, $items, $provider): array {
            $configured = (array) ($panel->evaluate($items) ?? []);

            if (! (bool) value($this->hasUserMenuItem) || ($url = $provider->getBillingUrl($panel)) === null) {
                return $configured;
            }

            return [
                ...$configured,
                Action::make('billing')
                    ->label(__('alfakit-billing::billing.navigation.billing'))
                    ->icon('credit-card')
                    ->url($url),
            ];
        });
    }
}
