<?php

declare(strict_types=1);

use Tenancy\Enums\TenantResolutionSource;
use Tenancy\Enums\TenantStatus;
use Tenancy\Exceptions\TenantNotFoundException;
use Tenancy\Exceptions\TenantSuspendedException;
use Tenancy\Resolution\Strategies\PathTenantResolutionStrategy;
use Tenancy\Resolution\TenantResolutionInput;
use Tenancy\Tests\Fixtures\FakeTenantLookup;
use Tenancy\Tests\Fixtures\FakeTenantResolutionInput;

beforeEach(function () {
    $this->lookup = new FakeTenantLookup();
    $this->strategy = new PathTenantResolutionStrategy($this->lookup);
});

// --- supports() ---

it('supports() returns true when the first path segment is a valid slug', function () {
    $input = TenantResolutionInput::fromArray(['path' => '/acme/dashboard']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when path is null', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when path is empty', function () {
    $input = TenantResolutionInput::fromArray(['path' => '/']);

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() returns false when the first segment is reserved', function (string $path) {
    $input = TenantResolutionInput::fromArray(['path' => $path]);

    expect($this->strategy->supports($input))->toBeFalse();
})->with([
    '/login',
    '/register',
    '/logout',
    '/password',
    '/api',
    '/admin',
    '/assets',
    '/storage',
    '/health',
]);

it('supports() returns false for slugs that fail the pattern', function (string $path) {
    $input = TenantResolutionInput::fromArray(['path' => $path]);

    expect($this->strategy->supports($input))->toBeFalse();
})->with([
    'starts with dash'  => '/-acme',
    'ends with dash'    => '/acme-',
    'has underscore'    => '/acme_corp',
]);

// --- supports() with prefix ---

it('supports() with prefix returns true when the slug follows the prefix', function () {
    $strategy = new PathTenantResolutionStrategy($this->lookup, prefix: 't');
    $input = TenantResolutionInput::fromArray(['path' => '/t/acme']);

    expect($strategy->supports($input))->toBeTrue();
});

it('supports() with prefix returns false when prefix is not found in path', function () {
    $strategy = new PathTenantResolutionStrategy($this->lookup, prefix: 't');
    $input = TenantResolutionInput::fromArray(['path' => '/acme']);

    expect($strategy->supports($input))->toBeFalse();
});

it('supports() with prefix returns false when nothing follows the prefix', function () {
    $strategy = new PathTenantResolutionStrategy($this->lookup, prefix: 't');
    $input = TenantResolutionInput::fromArray(['path' => '/t']);

    expect($strategy->supports($input))->toBeFalse();
});

it('supports() returns false when path trims to empty after lowercasing', function () {
    $input = new FakeTenantResolutionInput(path: '   ');

    expect($this->strategy->supports($input))->toBeFalse();
});

it('supports() handles a full URL passed as path and extracts the slug', function () {
    $input = TenantResolutionInput::fromArray(['path' => 'https://example.com/acme/dashboard']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('supports() returns false when the prefix is whitespace-only', function () {
    $strategy = new PathTenantResolutionStrategy($this->lookup, prefix: '   ');
    $input = TenantResolutionInput::fromArray(['path' => '/acme']);

    expect($strategy->supports($input))->toBeFalse();
});

// --- Path normalization ---

it('strips the query string from the path before extracting the slug', function () {
    $input = TenantResolutionInput::fromArray(['path' => '/acme?foo=bar']);

    expect($this->strategy->supports($input))->toBeTrue();
});

it('strips the fragment from the path before extracting the slug', function () {
    $input = TenantResolutionInput::fromArray(['path' => '/acme#section']);

    expect($this->strategy->supports($input))->toBeTrue();
});

// --- resolve() ---

it('resolve() returns null when path is absent', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($this->strategy->resolve($input))->toBeNull();
});

it('resolve() throws TenantNotFoundException when tenant is not found', function () {
    $this->lookup->record = null;
    $input = TenantResolutionInput::fromArray(['path' => '/unknown-tenant']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
});

it('resolve() throws TenantSuspendedException when tenant is suspended', function () {
    $this->lookup->record = makeTenantRecord(TenantStatus::Suspended);
    $input = TenantResolutionInput::fromArray(['path' => '/acme']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantSuspendedException::class);
});

it('resolve() throws TenantNotFoundException when tenant is not active', function (TenantStatus $status) {
    $this->lookup->record = makeTenantRecord($status);
    $input = TenantResolutionInput::fromArray(['path' => '/acme']);

    expect(fn () => $this->strategy->resolve($input))->toThrow(TenantNotFoundException::class);
})->with([TenantStatus::Pending, TenantStatus::Deleted]);

it('resolve() returns a TenantContext with Path source', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $input = TenantResolutionInput::fromArray(['path' => '/acme/dashboard']);

    $context = $this->strategy->resolve($input);

    expect($context->record)->toBe($record)
        ->and($context->source)->toBe(TenantResolutionSource::Path);
});

it('resolve() resolves the slug after a prefix', function () {
    $record = makeTenantRecord();
    $this->lookup->record = $record;
    $strategy = new PathTenantResolutionStrategy($this->lookup, prefix: 't');
    $input = TenantResolutionInput::fromArray(['path' => '/t/acme/dashboard']);

    $context = $strategy->resolve($input);

    expect($context->record)->toBe($record);
});
