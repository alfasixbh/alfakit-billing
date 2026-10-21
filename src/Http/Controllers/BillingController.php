<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Http\Controllers;

use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing;
use AlfaSix\AlfakitBilling\Providers\AbstractBillingProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The billing route (`/{tenant}/billing`, or `/billing` for user billing).
 *
 * - `?plan=pro`: start the checkout for that plan (subscribed billables go to the portal).
 * - otherwise: subscribed billables go to the provider's billing portal, the others to
 *   the plans page (or the portal when the provider has no plans page, like Spark).
 */
class BillingController
{
    public function __invoke(Request $request): Response
    {
        $panel = alfakit()->getCurrentOrDefaultPanel();
        $provider = AbstractBillingProvider::forPanel($panel);

        abort_if($provider === null, 404);

        $provider->ensureInstalled();

        $billable = $provider->getBillable($panel);

        abort_if($billable === null, 403);

        $isSubscribed = $provider->isSubscribed($billable);
        $planId = $request->query('plan');

        if (is_string($planId) && $planId !== '' && ! $isSubscribed) {
            $plan = $provider->getPlan($planId);

            abort_if($plan === null, 404);

            try {
                return $provider->checkout($billable, $plan, $panel, $request);
            } catch (BillingPackageMissing $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                return $this->unavailable($exception, $provider, $panel, $provider->getCheckoutUrl($panel));
            }
        }

        if (! $isSubscribed && ($checkoutUrl = $provider->getCheckoutUrl($panel)) !== null) {
            return redirect()->to($checkoutUrl);
        }

        try {
            return $provider->redirectToPortal($billable, $panel, $request);
        } catch (BillingPackageMissing $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->unavailable($exception, $provider, $panel, $panel->getUrl());
        }
    }

    /**
     * The provider failed (missing or wrong keys, an unknown price, the provider down): log
     * it and send the user back with a message instead of an error page.
     */
    protected function unavailable(Throwable $exception, AbstractBillingProvider $provider, Panel $panel, ?string $url): Response
    {
        report($exception);

        Notification::make()
            ->danger()
            ->title(__('alfakit-billing::billing.unavailable.title'))
            ->body(app()->isLocal()
                ? __('alfakit-billing::billing.unavailable.hint', ['provider' => $provider->getName()])
                : __('alfakit-billing::billing.unavailable.body'))
            ->send();

        return redirect()->to($url ?? $panel->getUrl());
    }
}
