<?php

declare(strict_types=1);

use Tenancy\Enums\TenantStatus;

it('stores all constructor properties', function () {
    $record = makeTenantRecord(
        id: 42,
        name: 'Acme Corp',
        slug: 'acme-corp',
        domain: 'acme.example.com',
        metadata: ['plan' => 'pro'],
    );

    expect($record->id)->toBe(42)
        ->and($record->name)->toBe('Acme Corp')
        ->and($record->slug)->toBe('acme-corp')
        ->and($record->domain)->toBe('acme.example.com')
        ->and($record->metadata)->toBe(['plan' => 'pro'])
        ->and($record->status)->toBe(TenantStatus::Active->value);
});

it('accepts a string id', function () {
    $record = makeTenantRecord(TenantStatus::Active, 'uuid-1234');

    expect($record->id)->toBe('uuid-1234');
});

it('exposes status as a string matching the enum value', function (TenantStatus $status) {
    expect(makeTenantRecord($status)->status)->toBe($status->value);
})->with(TenantStatus::cases());

it('isActive() is true only for active status', function () {
    expect(makeTenantRecord(TenantStatus::Active)->isActive())->toBeTrue()
        ->and(makeTenantRecord(TenantStatus::Pending)->isActive())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Suspended)->isActive())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Deleted)->isActive())->toBeFalse();
});

it('isSuspended() is true only for suspended status', function () {
    expect(makeTenantRecord(TenantStatus::Suspended)->isSuspended())->toBeTrue()
        ->and(makeTenantRecord(TenantStatus::Active)->isSuspended())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Pending)->isSuspended())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Deleted)->isSuspended())->toBeFalse();
});

it('isDeleted() is true only for deleted status', function () {
    expect(makeTenantRecord(TenantStatus::Deleted)->isDeleted())->toBeTrue()
        ->and(makeTenantRecord(TenantStatus::Active)->isDeleted())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Pending)->isDeleted())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Suspended)->isDeleted())->toBeFalse();
});

it('isPending() is true only for pending status', function () {
    expect(makeTenantRecord(TenantStatus::Pending)->isPending())->toBeTrue()
        ->and(makeTenantRecord(TenantStatus::Active)->isPending())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Suspended)->isPending())->toBeFalse()
        ->and(makeTenantRecord(TenantStatus::Deleted)->isPending())->toBeFalse();
});

it('only one status flag is true at a time', function (TenantStatus $status) {
    $record = makeTenantRecord($status);
    $trueCount = array_sum([
        (int) $record->isActive(),
        (int) $record->isSuspended(),
        (int) $record->isDeleted(),
        (int) $record->isPending(),
    ]);

    expect($trueCount)->toBe(1);
})->with(TenantStatus::cases());
