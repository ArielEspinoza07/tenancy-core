<?php

declare(strict_types=1);

namespace Tenancy\Contracts\Records;

interface TenantRecordInterface
{
    public int|string $id { get; }

    public string $name { get; }

    public string $slug { get; }

    public ?string $domain { get; }

    public string $status { get; }

    /** @var array<string, mixed> */
    public array $metadata { get; }

    public function isActive(): bool;

    public function isSuspended(): bool;

    public function isDeleted(): bool;

    public function isPending(): bool;
}
