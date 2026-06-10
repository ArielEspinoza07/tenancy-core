<?php

declare(strict_types=1);

namespace Tenancy\Resolution;

use Tenancy\Contracts\Resolution\TenantResolutionStrategyInterface;

final class TenantResolverRegistry
{
    /**
     * @param list<TenantResolutionStrategyEntry> $entries
     * @param list<TenantResolutionStrategyEntry>|null $sorted
     */
    public function __construct(
        private array $entries = [],
        private ?array $sorted = null,
    ) {}

    public function add(
        TenantResolutionStrategyInterface $strategy,
        int $priority = 0,
    ): void {
        $this->entries[] = new TenantResolutionStrategyEntry(
            strategy: $strategy,
            priority: $priority,
        );
        $this->sorted = null;
    }

    /**
     * @return list<TenantResolutionStrategyEntry>
     */
    public function ordered(): array
    {
        if ($this->sorted === null) {
            $entries = $this->entries;
            usort(
                $entries,
                static fn (
                    TenantResolutionStrategyEntry $a,
                    TenantResolutionStrategyEntry $b,
                ): int => $b->priority <=> $a->priority,
            );
            $this->sorted = $entries;
        }

        return $this->sorted;
    }

    /**
     * @return list<TenantResolutionStrategyInterface>
     */
    public function strategies(): array
    {
        return array_map(
            static fn (TenantResolutionStrategyEntry $entry): TenantResolutionStrategyInterface => $entry->strategy,
            $this->ordered(),
        );
    }

    /**
     * @return list<TenantResolutionStrategyEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }
}
