<?php

declare(strict_types=1);

namespace Tenancy\Resolution;

use Tenancy\Contracts\Context\TenantContextInterface;
use Tenancy\Contracts\Resolution\TenantResolutionInputInterface;
use Tenancy\Contracts\Resolution\TenantResolverInterface;
use Tenancy\Exceptions\TenantResolutionConflictException;

final readonly class ChainTenantResolver implements TenantResolverInterface
{
    public function __construct(
        private TenantResolverRegistry $registry,
    ) {}

    public function resolve(TenantResolutionInputInterface $input): TenantContextInterface
    {
        $resolved = [];
        foreach ($this->registry->strategies() as $strategy) {
            if (! $strategy->supports($input)) {
                continue;
            }

            $context = $strategy->resolve($input);

            if ($context !== null) {
                $resolved[] = $context;
            }
        }

        if ($resolved === []) {
            throw new TenantResolutionConflictException();
        }

        $first = array_first($resolved);
        foreach ($resolved as $context) {
            if ($context->record->id !== $first->record->id) {
                throw new TenantResolutionConflictException();
            }
        }

        return $first;
    }
}
