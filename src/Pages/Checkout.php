<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Pages;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Pages\Page;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Schemas\Components\Callout;
use AlfaSix\Alfakit\Schemas\Components\EmptyState;
use AlfaSix\Alfakit\Schemas\Components\Grid;
use AlfaSix\Alfakit\Schemas\Components\Section;
use AlfaSix\Alfakit\Schemas\Components\Text;
use AlfaSix\Alfakit\Schemas\Components\UnorderedList;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\AlfakitBilling\Http\Controllers\BillingController;
use AlfaSix\AlfakitBilling\Plan;
use AlfaSix\AlfakitBilling\Providers\AbstractBillingProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The plans page: lists the provider's plans, each with a button that starts the
 * provider's checkout through the billing route (`billing?plan=pro`). Unsubscribed
 * billables are sent here, so it never requires a subscription itself.
 *
 * On panels without tenancy it also registers the user billing route (`/billing`).
 */
class Checkout extends Page
{
    /** Route name (relative to the panel) of the billing route on panels without tenancy. */
    public const USER_BILLING_ROUTE = 'billing';

    protected static ?string $slug = 'billing-plans';

    protected static ?string $routePath = '/billing/plans';

    protected static bool $shouldRegisterNavigation = false;

    public static function registerRoutes(Panel $panel): void
    {
        parent::registerRoutes($panel);

        // Tenancy panels get `/{tenant}/billing` from `tenantBillingProvider()`.
        if (! $panel->hasTenancy()) {
            Route::get('/billing', BillingController::class)->name(self::USER_BILLING_ROUTE);
        }
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    public function mount(mixed ...$parameters): void
    {
        $provider = $this->getProvider();

        // Providers with their own plan picker (Spark) don't use this page.
        if ($provider !== null && ! $provider->usesCheckoutPage() && ($url = $provider->getBillingUrl($this->getPanel())) !== null) {
            throw new HttpResponseException(redirect()->to($url));
        }
    }

    public function getTitle(): string
    {
        return __('alfakit-billing::billing.checkout.title');
    }

    public function getSubheading(): ?string
    {
        return __('alfakit-billing::billing.checkout.subheading');
    }

    public function getProvider(): ?AbstractBillingProvider
    {
        return AbstractBillingProvider::forPanel($this->getPanel());
    }

    public function content(Schema $schema): Schema
    {
        $provider = $this->getProvider();
        $plans = $provider?->getPlans() ?? [];

        if ($provider === null || $plans === []) {
            return $schema->components([
                EmptyState::make(__('alfakit-billing::billing.checkout.empty.heading'))
                    ->description(__('alfakit-billing::billing.checkout.empty.description'))
                    ->icon('credit-card'),
            ]);
        }

        $panel = $this->getPanel();
        $billable = $provider->getBillable($panel);
        $isSubscribed = $billable !== null && $provider->isSubscribed($billable);
        $components = [];

        if ($isSubscribed) {
            $components[] = Callout::make(__('alfakit-billing::billing.checkout.subscribed.heading'))
                ->description(__('alfakit-billing::billing.checkout.subscribed.description'))
                ->success()
                ->actions([
                    Action::make('manageBilling')
                        ->label(__('alfakit-billing::billing.checkout.manage'))
                        ->icon('credit-card')
                        ->button()
                        ->url($provider->getBillingUrl($panel)),
                ]);
        }

        $components[] = Grid::make(['default' => 1, 'md' => min(count($plans), 2), 'xl' => min(count($plans), 3)])
            ->schema(array_values(array_map(
                fn (Plan $plan): Section => $this->makePlanSection($provider, $plan, $panel, $isSubscribed),
                $plans,
            )));

        return $schema->components($components);
    }

    protected function makePlanSection(AbstractBillingProvider $provider, Plan $plan, Panel $panel, bool $isSubscribed): Section
    {
        $schema = [];

        if (($price = $plan->getPriceLabel()) !== null) {
            $schema[] = Text::make($price)->size('xl')->weight('bold');
        }

        $trialDays = $provider->getTrialDays($plan);

        if ($trialDays !== null) {
            $schema[] = Text::make(trans_choice('alfakit-billing::billing.checkout.trial', $trialDays, ['days' => $trialDays]))->badge()->color('success');
        }

        if (($features = $plan->getFeatures()) !== []) {
            $schema[] = UnorderedList::make($features);
        }

        $section = Section::make($plan->getLabel())
            ->description($plan->getDescription())
            ->schema($schema);

        if ($plan->isHighlighted()) {
            $section->afterHeader([Text::make(__('alfakit-billing::billing.checkout.highlighted'))->badge()->color('primary')]);
        }

        if (! $isSubscribed) {
            $section->footerActions([
                Action::make('subscribe'.Str::studly(Str::slug($plan->getId(), '_')))
                    ->label($trialDays !== null ? __('alfakit-billing::billing.checkout.start_trial') : __('alfakit-billing::billing.checkout.subscribe'))
                    // A button, not the default link style: gray links read as plain text in a section footer.
                    ->button()
                    ->color($plan->isHighlighted() ? 'primary' : 'gray')
                    ->url($provider->getBillingUrl($panel, ['plan' => $plan->getId()])),
            ]);
        }

        return $section;
    }
}
