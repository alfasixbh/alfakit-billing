<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes;

use Stripe\HttpClient\ClientInterface;
use Stripe\Util\CaseInsensitiveArray;

/**
 * A Stripe HTTP client that records requests and answers them locally, so Cashier runs
 * its real code without reaching api.stripe.com.
 */
class FakeStripeClient implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, params: array<string, mixed>}> */
    public array $requests = [];

    /**
     * @param  string  $method
     * @param  string  $absUrl
     * @param  array<int, string>  $headers
     * @param  array<string, mixed>  $params
     * @param  bool  $hasFile
     * @param  string  $apiMode
     * @param  int|null  $maxNetworkRetries
     * @return array{0: string, 1: int, 2: CaseInsensitiveArray}
     */
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => strtoupper($method), 'path' => $path, 'params' => $params];

        $body = match (true) {
            $path === '/v1/customers' => ['id' => 'cus_test', 'object' => 'customer'],
            str_starts_with($path, '/v1/customers/') => ['id' => basename($path), 'object' => 'customer'],
            $path === '/v1/checkout/sessions' => ['id' => 'cs_test_123', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_123'],
            $path === '/v1/billing_portal/sessions' => ['id' => 'bps_test', 'object' => 'billing_portal.session', 'url' => 'https://billing.stripe.com/p/session/test_123'],
            default => ['error' => ['message' => "Unexpected Stripe request {$method} {$path}"]],
        };

        return [(string) json_encode($body), isset($body['error']) ? 400 : 200, new CaseInsensitiveArray(['Request-Id' => 'req_test'])];
    }

    /**
     * @return array{method: string, path: string, params: array<string, mixed>}|null
     */
    public function find(string $path): ?array
    {
        foreach ($this->requests as $request) {
            if ($request['path'] === $path) {
                return $request;
            }
        }

        return null;
    }
}
