<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Repositories\MembershipRepositoryInterface;

final class FakeMembershipRepository implements MembershipRepositoryInterface
{
    public bool $hasMembership = false;

    public function existsActiveMembership(int|string $userId, int|string $tenantId): bool
    {
        return $this->hasMembership;
    }
}
