<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Http\Middleware;

use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitBilling\BillingPlugin;
use AlfaSix\AlfakitBilling\Pages\Checkout;
use AlfaSix\AlfakitBilling\Providers\AbstractBillingProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets billables with an active subscription through and sends the others to the plans
 * page (or the billing route). On tenancy panels AlfaKit applies it to tenant routes
 * (`requiresTenantSubscription()`); with the `user` parameter the billing plugin applies
 * it to the authenticated routes of panels without tenancy.
 */
class RedirectIfNotSubscribed
{
    public const USER = 'user';

    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $panel = alfakit()->getCurrentOrDefaultPanel();
        $provider = AbstractBillingProvider::forPanel($panel);

        if ($provider === null || $this->shouldSkip($request, $panel, $provider, $scope)) {
            return $next($request);
        }

        $billable = $provider->getBillable($panel);

        if ($billable === null || $provider->isSubscribed($billable)) {
            return $next($request);
        }

        return redirect()->to($provider->getUnsubscribedRedirectUrl($panel));
    }

    protected function shouldSkip(Request $request, Panel $panel, AbstractBillingProvider $provider, ?string $scope): bool
    {
        if ($scope === self::USER) {
            // Tenancy panels bill the tenant through `requiresTenantSubscription()`.
            if ($panel->hasTenancy() || ! $panel->hasPlugin(BillingPlugin::ID)) {
                return true;
            }

            $plugin = $panel->getPlugin(BillingPlugin::ID);

            if (! $plugin instanceof BillingPlugin || ! $plugin->isSubscriptionRequired()) {
                return true;
            }
        }

        // The pages where a billable subscribes, and the account pages, stay reachable.
        return $request->routeIs(
            $panel->generateRouteName('tenant.billing'),
            $panel->generateRouteName(Checkout::USER_BILLING_ROUTE),
            $panel->generateRouteName(($provider->getCheckoutPage($panel) ?? Checkout::class)::getRelativeRouteName()),
            $panel->generateRouteName('auth.*'),
        );
    }
}
