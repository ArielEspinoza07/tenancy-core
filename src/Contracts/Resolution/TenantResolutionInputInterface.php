<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Resolution;

interface TenantResolutionInputInterface
{
    public ?string $host { get; }

    public ?string $path { get; }

    /** @var array<string, string> */
    public array $headers { get; }

    public int|string|null $sessionTenantId { get; }

    public int|string|null $userId { get; }

    public ?string $apiKey { get; }

    /** @var array<string, mixed> */
    public array $routeParameters { get; }

    /**
     * @param array{
     *     host?: string,
     *     path?: string,
     *     headers?: array<string,mixed>,
     *     sessionTenantId?: int|string,
     *     userId?: int|string,
     *     apiKey?: string,
     *     routeParameters?: array<string, mixed>,
     * } $data
     */
    public static function fromArray(array $data): static;

    public function header(string $name): ?string;
}
