<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitBilling\Tests\Fixtures\Fakes;

/**
 * Stands in for `Laravel\Paddle\Cashier` (records `api()` calls instead of calling Paddle).
 */
class FakePaddleCashier
{
    /** @var array<int, array{method: string, uri: string, payload: array<string, mixed>}> */
    public static array $calls = [];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function api(string $method, string $uri, array $payload = []): array
    {
        static::$calls[] = ['method' => $method, 'uri' => $uri, 'payload' => $payload];

        return ['data' => ['urls' => ['general' => ['overview' => 'https://customer-portal.paddle.com/cpl_test']]]];
    }
}
