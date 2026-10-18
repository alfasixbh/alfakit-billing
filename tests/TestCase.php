<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests;

use AlfaSix\Alfakit\Tests\TestCase as BaseTestCase;
use AlfaSix\AlfakitBilling\BillingServiceProvider;
use AlfaSix\AlfakitBilling\Providers\PaddleBillingProvider;
use AlfaSix\AlfakitBilling\Providers\SparkBillingProvider;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes\FakePaddleCashier;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes\FakeStripeClient;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Models\BillableTeam;
use AlfaSix\AlfakitBilling\Tests\Fixtures\Models\FakeBillableTeam;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\CashierServiceProvider;
use LogicException;
use Pest\TestSuite;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;

abstract class TestCase extends BaseTestCase
{
    public FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        // Cashier talks to a local fake instead of api.stripe.com.
        $this->stripe = new FakeStripeClient;
        ApiRequestor::setHttpClient($this->stripe);
        Cashier::useCustomerModel(BillableTeam::class);

        FakeBillableTeam::reset();
        FakePaddleCashier::$calls = [];
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        PaddleBillingProvider::useCashierClass('Laravel\Paddle\Cashier');
        SparkBillingProvider::useSparkClass('Spark\Spark');

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            CashierServiceProvider::class,
            BillingServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cashier.key', 'pk_test_fake');
        $app['config']->set('cashier.secret', 'sk_test_fake');
        $app['config']->set('cashier.webhook.secret', null);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }

    /**
     * The running test (Pest binds `$this` to it; this keeps static analysis typed).
     */
    public static function current(): static
    {
        $test = TestSuite::getInstance()->test;

        if (! $test instanceof static) {
            throw new LogicException('No test of this suite is running.');
        }

        return $test;
    }

    public function inertiaGet(string $url): TestResponse
    {
        return $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);
    }

    /**
     * @return array<string, mixed>
     */
    public function inertiaProps(string $url): array
    {
        return $this->inertiaGet($url)->assertOk()->json('props');
    }
}
