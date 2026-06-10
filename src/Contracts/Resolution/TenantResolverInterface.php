<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Resolution;

use Tenancy\Contracts\Context\TenantContextInterface;

interface TenantResolverInterface
{
    public function resolve(TenantResolutionInputInterface $input): TenantContextInterface;
}
