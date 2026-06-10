<?php

declare(strict_types=1);

namespace Tenancy\Resolution\Strategies;

use Tenancy\Context\TenantContext;
use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Repositories\TenantLookupInterface;
use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;
use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;
use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;

final readonly class PathTenantResolutionStrategy implements TenantResolutionStrategyInterface
{
    /**
     * @param list<non-empty-string> $reservedSegments
     */
    public function __construct(
        private TenantLookupInterface $repository,
        private ?string $prefix = null,
        private array $reservedSegments = [
            'login',
            'register',
            'logout',
            'password',
            'api',
            'admin',
            'assets',
            'storage',
            'health',
        ],
    ) {}
    public function supports(TenantResolutionInputInterface $input): bool
    {
        return $this->extractSlug($input->path) !== null;
    }

    public function resolve(TenantResolutionInputInterface $input): ?TenantContextInterface
    {
        $slug = $this->extractSlug($input->path);
        if ($slug === null) {
            return null;
        }

        $record = $this->repository->findBySlug($slug);
        if ($record === null) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $slug),
            );
        }

        if ($record->isSuspended()) {
            throw new TenantSuspendedException(
                sprintf('Tenant "%s" is suspended.', $record->id),
            );
        }

        if (! $record->isActive()) {
            throw new TenantNotFoundException(
                sprintf('Tenant "%s" not found.', $record->slug),
            );
        }

        return new TenantContext(
            record: $record,
            source: TenantResolutionSource::Path,
        );
    }

    private function extractSlug(?string $path): ?string
    {
        $segments = $this->segments($path);

        if ($segments === []) {
            return null;
        }

        if ($this->prefix !== null) {
            return $this->extractSlugAfterPrefix($segments);
        }

        return $this->sanitizeSlug(array_first($segments));
    }

    /**
     * @param list<non-empty-string> $segments
     */
    private function extractSlugAfterPrefix(array $segments): ?string
    {
        $prefix = $this->normalizeSegment($this->prefix);

        if ($prefix === null) {
            return null;
        }

        foreach ($segments as $index => $segment) {
            if ($segment !== $prefix) {
                continue;
            }

            return $this->sanitizeSlug($segments[$index + 1] ?? null);
        }

        return null;
    }

    /**
     * @return list<non-empty-string>
     */
    private function segments(?string $path): array
    {
        if ($path === null) {
            return [];
        }

        $path = mb_trim(mb_strtolower($path));

        if ($path === '') {
            return [];
        }

        // If someone accidentally passes a full URL, keep only the path.
        if (str_contains($path, '://')) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($parsedPath) ? $parsedPath : '';
        }

        // Remove query string and fragment.
        $path = preg_split('/[?#]/', $path, 2)[0] ?? $path;

        $path = mb_trim($path, '/');

        if ($path === '') {
            return [];
        }

        return explode('/', $path)
                |> (fn ($x) => array_filter($x, static fn (string $segment): bool => $segment !== ''))
                |> array_values(...);
    }

    private function sanitizeSlug(?string $segment): ?string
    {
        $segment = $this->normalizeSegment($segment);

        if ($segment === null) {
            return null;
        }

        if (in_array($segment, $this->normalizedReservedSegments(), true)) {
            return null;
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $segment)) {
            return null;
        }

        return $segment;
    }

    private function normalizeSegment(?string $segment): ?string
    {
        if ($segment === null) {
            return null;
        }

        $segment = mb_trim(mb_strtolower($segment), " \t\n\r\0\x0B/");

        return $segment !== '' ? $segment : null;
    }

    /**
     * @return list<non-empty-string>
     */
    private function normalizedReservedSegments(): array
    {
        return array_map(
            fn (string $segment): ?string => $this->normalizeSegment($segment),
            $this->reservedSegments,
        )
                |> array_filter(...)
                |> array_values(...);
    }
}
