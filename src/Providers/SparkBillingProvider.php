<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Providers;

use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitBilling\Exceptions\BillingPackageMissing;
use AlfaSix\AlfakitBilling\Plan;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel Spark (paid, `spark.laravel.com`): Spark's billing portal handles plans,
 * checkout and invoices, so the billing route just sends the billable there. The
 * billable uses `Spark\Billable` and is listed in `config/spark.php` `billables`.
 */
class SparkBillingProvider extends AbstractBillingProvider
{
    public const BILLABLE_TRAIT = 'Spark\Billable';

    /** @var string Spark's main class; swapped by the tests. */
    protected static string $sparkClass = 'Spark\Spark';

    protected string|Closure $path = '/billing';

    protected string|Closure|null $billableType = null;

    public function getName(): string
    {
        return 'Spark';
    }

    public function isInstalled(): bool
    {
        return class_exists(static::$sparkClass);
    }

    /**
     * @internal Point the provider at a stand-in for `Spark\Spark` (tests).
     */
    public static function useSparkClass(string $class): void
    {
        static::$sparkClass = $class;
    }

    public static function getSparkClass(): string
    {
        return static::$sparkClass;
    }

    protected function getMissingPackageException(): BillingPackageMissing
    {
        return BillingPackageMissing::spark();
    }

    /**
     * Spark's billing portal path (`spark.path`, default `/billing`).
     */
    public function path(string|Closure $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return '/'.trim((string) $this->evaluate($this->path), '/');
    }

    /**
     * The billable type as configured in `spark.billables` (`user`, `team`…). Defaults to
     * the model's class name in kebab case (`Team` → `team`).
     */
    public function billableType(string|Closure|null $type): static
    {
        $this->billableType = $type;

        return $this;
    }

    public function getBillableType(Model $billable): string
    {
        $type = $this->evaluate($this->billableType, ['billable' => $billable], [Model::class => $billable]);

        return is_string($type) && $type !== '' ? $type : Str::kebab(class_basename($billable));
    }

    /**
     * Spark's portal URL for the billable: `/billing` for users, `/billing/{type}/{id}`
     * for other billables.
     */
    public function getPortalUrl(Model $billable): string
    {
        $type = $this->getBillableType($billable);

        return url($type === 'user' ? $this->getPath() : $this->getPath().'/'.$type.'/'.$billable->getKey());
    }

    public function usesCheckoutPage(): bool
    {
        return false;
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

        return $this->redirectAway($this->getPortalUrl($billable), $request);
    }

    /**
     * Spark chooses plans in its own portal (configured in `config/spark.php`).
     */
    public function checkout(Model $billable, Plan $plan, Panel $panel, Request $request): Response
    {
        return $this->redirectToPortal($billable, $panel, $request);
    }
}
