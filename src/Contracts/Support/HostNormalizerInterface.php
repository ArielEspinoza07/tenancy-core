<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Support;

interface HostNormalizerInterface
{
    public function normalize(?string $host): ?string;
}
