<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Resolution\Strategies\HeaderTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Tests\Fixtures\FakeTenantLookup;

beforeEach(function () {
    $this->lookup = new FakeTenantLookup();
    $this->strategy = new HeaderTenantResolutionStrategy($this->lookup);
});

// --- supports() ---

it('supports() returns true when the X-Tenant-ID header is present', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '42']]);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when the header is absent', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when the header is blank', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '   ']]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() uses a custom header name when configured', function () {
    $strategy = new HeaderTenantResolutionStrategy($this->lookup, headerName: 'X-Custom-Tenant');
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Custom-Tenant' => '1']]);

    expect($strategy->supports($input))->toBeTrue();
});

// --- resolve() ---

it('resolve() returns null when the header is absent', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when tenant is not found', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '99']]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantSuspendedException when tenant is suspended', function () {
    $this->lookup->record = makeTenantRecord(TenantStatus::Suspended);
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '1']]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = makeTenantRecord($status);
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '1']]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() passes the id as an integer when the header value is numeric', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '42']]);

    expect($this->strategy->resolve($input))->not->toBeNull();
});

it('resolve() passes the id as a string when the header value is non-numeric', function () {
    $this->lookup->record = makeTenantRecord();
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => 'uuid-abc123']]);

    expect($this->strategy->resolve($input))->not->toBeNull();
});

it('resolve() returns a TenantContext with Header source', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '1']]);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::Header);
});
