<?php

declare(strict_types=1);

namespace Tenancy\Tests\Fixtures;

use Tenancy\Contracts\Support\HostNormalizerInterface;

final class FakeHostNormalizer implements HostNormalizerInterface
{
    public function normalize(?string $host): ?string
    {
        return $host;
    }
}
