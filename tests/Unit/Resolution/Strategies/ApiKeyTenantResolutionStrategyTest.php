<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Records\TenantApiKeyRecord;
use Tenancy\Resolution\Strategies\ApiKeyTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Tests\Fixtures\FakeTenantApiKeyLookup;

beforeEach(function () {
    $this->lookup = new FakeTenantApiKeyLookup();
    $this->strategy = new ApiKeyTenantResolutionStrategy($this->lookup);
});

// --- supports() ---

it('supports() returns true when an explicit apiKey is present', function () {
    $input = TenantResolutionInput::fromArray(['apiKey' => 'secret-key']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns true when a Bearer token is in the Authorization header', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['Authorization' => 'Bearer secret-token']]);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns true when X-API-Key header is present', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-API-Key' => 'secret-key']]);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when no api key is provided', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when apiKey is an empty string', function () {
    $input = TenantResolutionInput::fromArray(['apiKey' => '   ']);

    expect($this->strategy->supports($input))->toBeFalse();
});

// --- resolve() ---

it('resolve() returns null when no api key is present', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when the key is not found', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['apiKey' => 'unknown-key']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantNotFoundException when the api key is revoked', function () {
    $this->lookup->record = new TenantApiKeyRecord(tenant: makeTenantRecord(), revoked: true);
    $input = TenantResolutionInput::fromArray(['apiKey' => 'revoked-key']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantNotFoundException when the api key is expired', function () {
    $this->lookup->record = new TenantApiKeyRecord(
        tenant: makeTenantRecord(),
        revoked: false,
        expiresAt: new DateTimeImmutable('-1 hour'),
    );
    $input = TenantResolutionInput::fromArray(['apiKey' => 'expired-key']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantSuspendedException when the tenant is suspended', function () {
    $this->lookup->record = new TenantApiKeyRecord(
        tenant: makeTenantRecord(TenantStatus::Suspended),
        revoked: false,
    );
    $input = TenantResolutionInput::fromArray(['apiKey' => 'valid-key']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when the tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = new TenantApiKeyRecord(
        tenant: makeTenantRecord($status),
        revoked: false,
    );
    $input = TenantResolutionInput::fromArray(['apiKey' => 'valid-key']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() returns a TenantContext with ApiKey source for a valid key and active tenant', function () {
    $record = makeTenantRecord();
    $this->lookup->record = new TenantApiKeyRecord(tenant: $record, revoked: false);
    $input = TenantResolutionInput::fromArray(['apiKey' => 'valid-key']);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::ApiKey);
});
