<?php

declare(strict_types=1);

use Tenancy\Enums\TenantStatus;

// --- Backing values ---

it('has the expected string backing values', function (TenantStatus $status, string $value) {
    expect($status->value)->toBe($value);
})->with([
    [TenantStatus::Active,    'active'],
    [TenantStatus::Pending,   'pending'],
    [TenantStatus::Suspended, 'suspended'],
    [TenantStatus::Deleted,   'deleted'],
]);

it('can be created from its string value', function (TenantStatus $status) {
    expect(TenantStatus::from($status->value))->toBe($status);
})->with(TenantStatus::cases());

// --- default() ---

it('default() returns Active', function () {
    expect(TenantStatus::default())->toBe(TenantStatus::Active);
});

// --- Individual flags ---

it('isActive() returns true only for Active', function () {
    expect(TenantStatus::Active->isActive())->toBeTrue()
        ->and(TenantStatus::Pending->isActive())->toBeFalse()
        ->and(TenantStatus::Suspended->isActive())->toBeFalse()
        ->and(TenantStatus::Deleted->isActive())->toBeFalse();
});

it('isPending() returns true only for Pending', function () {
    expect(TenantStatus::Pending->isPending())->toBeTrue()
        ->and(TenantStatus::Active->isPending())->toBeFalse()
        ->and(TenantStatus::Suspended->isPending())->toBeFalse()
        ->and(TenantStatus::Deleted->isPending())->toBeFalse();
});

it('isSuspended() returns true only for Suspended', function () {
    expect(TenantStatus::Suspended->isSuspended())->toBeTrue()
        ->and(TenantStatus::Active->isSuspended())->toBeFalse()
        ->and(TenantStatus::Pending->isSuspended())->toBeFalse()
        ->and(TenantStatus::Deleted->isSuspended())->toBeFalse();
});

it('isDeleted() returns true only for Deleted', function () {
    expect(TenantStatus::Deleted->isDeleted())->toBeTrue()
        ->and(TenantStatus::Active->isDeleted())->toBeFalse()
        ->and(TenantStatus::Pending->isDeleted())->toBeFalse()
        ->and(TenantStatus::Suspended->isDeleted())->toBeFalse();
});

// --- Mutual exclusivity ---

it('exactly one flag is true per case', function (TenantStatus $status) {
    $trueCount = (int) $status->isActive()
        + (int) $status->isPending()
        + (int) $status->isSuspended()
        + (int) $status->isDeleted();

    expect($trueCount)->toBe(1);
})->with(TenantStatus::cases());
