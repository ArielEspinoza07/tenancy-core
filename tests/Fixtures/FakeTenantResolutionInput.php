<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;

final class FakeTenantResolutionInput implements TenantResolutionInputInterface
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $routeParameters
     */
    public function __construct(
        public ?string $host = null,
        public ?string $path = null,
        public array $headers = [],
        public int|string|null $sessionTenantId = null,
        public int|string|null $userId = null,
        public ?string $apiKey = null,
        public array $routeParameters = [],
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            host: $data['host'] ?? null,
            path: $data['path'] ?? null,
            headers: $data['headers'] ?? [],
            sessionTenantId: $data['sessionTenantId'] ?? null,
            userId: $data['userId'] ?? null,
            apiKey: $data['apiKey'] ?? null,
            routeParameters: $data['routeParameters'] ?? [],
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }
}
