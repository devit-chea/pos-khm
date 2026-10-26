<?php

namespace Modules\Woocommerce\Tests\Fakes;

use Modules\Woocommerce\Contracts\WooCommerceClient;
use RuntimeException;
use Throwable;

final class FakeWooCommerceClient implements WooCommerceClient
{
    /** @var array<int, array{method:string, resource:string, parameters:array}> */
    public array $requests = [];

    /** @var list<int> */
    public array $transactionLevels = [];

    /** @var list<int> */
    public array $requestTransactionDepths = [];

    private int $baselineTransactionLevel;

    /** @var array<string, list<mixed>> */
    private array $responses = [];

    public function __construct()
    {
        $this->baselineTransactionLevel = app('db')->connection()->transactionLevel();
    }

    public function queue(string $method, string $resource, mixed ...$responses): self
    {
        $key = $this->key($method, $resource);
        $this->responses[$key] = array_merge($this->responses[$key] ?? [], $responses);

        return $this;
    }

    public function get(string $resource, array $parameters = []): mixed
    {
        return $this->respond('GET', $resource, $parameters);
    }

    public function post(string $resource, array $payload): mixed
    {
        return $this->respond('POST', $resource, $payload);
    }

    private function respond(string $method, string $resource, array $parameters): mixed
    {
        $this->requests[] = compact('method', 'resource', 'parameters');
        $level = app('db')->connection()->transactionLevel();
        $this->transactionLevels[] = $level;
        $this->requestTransactionDepths[] = $level - $this->baselineTransactionLevel;
        $key = $this->key($method, $resource);
        if (empty($this->responses[$key])) {
            throw new RuntimeException("No fake response queued for {$method} {$resource}.");
        }

        $response = array_shift($this->responses[$key]);
        if ($response instanceof Throwable) {
            throw $response;
        }

        return is_callable($response) ? $response($parameters, $this) : $response;
    }

    private function key(string $method, string $resource): string
    {
        return strtoupper($method).' '.$resource;
    }
}
