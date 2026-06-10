<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Resolution\Strategies\HeaderTenantSlugResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Tests\Fixtures\FakeTenantLookup;

beforeEach(function () {
    $this->lookup = new FakeTenantLookup();
    $this->strategy = new HeaderTenantSlugResolutionStrategy($this->lookup);
});

// --- supports() ---

it('supports() returns true when the X-Tenant-Slug header contains a valid slug', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => 'acme']]);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when the header is absent', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when the header is blank', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => '   ']]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false for slugs with invalid characters', function (string $slug) {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => $slug]]);

    expect($this->strategy->supports($input))->toBeFalse();
})->with([
    'starts with dash'  => '-acme',
    'ends with dash'    => 'acme-',
    'has underscore'    => 'acme_corp',
    'has dot'           => 'acme.corp',
    'has space'         => 'acme corp',
]);

it('supports() is case-insensitive and normalizes to lowercase', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => 'ACME']]);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() uses a custom header name when configured', function () {
    $strategy = new HeaderTenantSlugResolutionStrategy($this->lookup, headerName: 'X-Workspace');
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Workspace' => 'acme']]);

    expect($strategy->supports($input))->toBeTrue();
});

// --- resolve() ---

it('resolve() returns null when the header is absent', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when tenant is not found', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => 'unknown']]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantSuspendedException when tenant is suspended', function () {
    $this->lookup->record = makeTenantRecord(TenantStatus::Suspended);
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => 'acme']]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = makeTenantRecord($status);
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => 'acme']]);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() returns a TenantContext with Header source', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-Slug' => 'acme']]);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::Header);
});
