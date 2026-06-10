<?php

declare(strict_types=1);

namespace Tenancy\Resolution;

use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;

final readonly class TenantResolutionInput implements TenantResolutionInputInterface
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $routeParameters
     */
    private function __construct(
        public ?string $host = null,
        public ?string $path = null,
        public array $headers = [],
        public int|null|string $sessionTenantId = null,
        public int|null|string $userId = null,
        public ?string $apiKey = null,
        public array $routeParameters = [],
    ) {}

    public static function fromArray(array $data): static
    {
        $headers = self::normalizeHeaders($data['headers'] ?? []);

        return new self(
            host: self::nullableString($data['host'] ?? null),
            path: self::nullableString($data['path'] ?? null),
            headers: $headers,
            sessionTenantId: $data['sessionTenantId'] ?? null,
            userId: $data['userId'] ?? null,
            apiKey: self::resolveApiKey($data['apiKey'] ?? null, $headers),
            routeParameters: $data['routeParameters'] ?? [],
        );
    }

    public function header(string $name): ?string
    {
        $key = self::normalizeHeaderName($name);

        return $this->headers[$key] ?? null;
    }

    /**
     * @param array<string, string> $headers
     */
    private static function resolveApiKey(mixed $explicitApiKey, array $headers): ?string
    {
        $apiKey = self::nullableString($explicitApiKey);

        if ($apiKey !== null) {
            return $apiKey;
        }

        $authorization = $headers[self::normalizeHeaderName('Authorization')] ?? null;

        if ($authorization !== null) {
            $authorization = mb_trim($authorization);

            if (str_starts_with(mb_strtolower($authorization), 'bearer ')) {
                $token = mb_trim(mb_substr($authorization, 7));

                if ($token !== '') {
                    return $token;
                }
            }
        }

        $headerApiKey = $headers[self::normalizeHeaderName('X-API-Key')] ?? null;

        if ($headerApiKey !== null) {
            $headerApiKey = mb_trim($headerApiKey);

            if ($headerApiKey !== '') {
                return $headerApiKey;
            }
        }

        return null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = mb_trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $key = self::normalizeHeaderName((string) $name);

            if (is_array($value)) {
                $value = reset($value);
            }

            if (! is_scalar($value)) {
                continue;
            }

            $normalized[$key] = mb_trim((string) $value);
        }

        return $normalized;
    }

    private static function normalizeHeaderName(string $name): string
    {
        return $name
                |> trim(...)
                |> (fn ($x) => str_replace('_', '-', $x))
                |> strtolower(...);
    }
}
