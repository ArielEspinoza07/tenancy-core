<?php

declare(strict_types=1);

use Tenancy\Resolution\TenantResolutionInput;

// --- Defaults ---

it('defaults all fields to null or empty when built from an empty array', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($input->host)->toBeNull()
        ->and($input->path)->toBeNull()
        ->and($input->headers)->toBe([])
        ->and($input->sessionTenantId)->toBeNull()
        ->and($input->userId)->toBeNull()
        ->and($input->apiKey)->toBeNull()
        ->and($input->routeParameters)->toBe([]);
});

// --- Field mapping ---

it('maps each field from the array', function () {
    $input = TenantResolutionInput::fromArray([
        'host'            => 'example.com',
        'path'            => '/acme',
        'sessionTenantId' => 7,
        'userId'          => 42,
        'routeParameters' => ['slug' => 'acme'],
    ]);

    expect($input->host)->toBe('example.com')
        ->and($input->path)->toBe('/acme')
        ->and($input->sessionTenantId)->toBe(7)
        ->and($input->userId)->toBe(42)
        ->and($input->routeParameters)->toBe(['slug' => 'acme']);
});

it('trims whitespace from host and path', function () {
    $input = TenantResolutionInput::fromArray(['host' => '  example.com  ', 'path' => '  /acme  ']);

    expect($input->host)->toBe('example.com')
        ->and($input->path)->toBe('/acme');
});

it('returns null for host and path when they are blank strings', function () {
    $input = TenantResolutionInput::fromArray(['host' => '   ', 'path' => '   ']);

    expect($input->host)->toBeNull()
        ->and($input->path)->toBeNull();
});

// --- header() ---

it('header() retrieves a header by its exact name', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '1']]);

    expect($input->header('X-Tenant-ID'))->toBe('1');
});

it('header() is case-insensitive', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '1']]);

    expect($input->header('x-tenant-id'))->toBe('1')
        ->and($input->header('X-TENANT-ID'))->toBe('1');
});

it('header() normalizes underscores to dashes in the lookup name', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '1']]);

    expect($input->header('X_Tenant_ID'))->toBe('1');
});

it('header() returns null for a missing header', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($input->header('X-Tenant-ID'))->toBeNull();
});

it('header() trims the stored header value', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Tenant-ID' => '  42  ']]);

    expect($input->header('X-Tenant-ID'))->toBe('42');
});

it('headers with array values store the first element', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['Accept' => ['text/html', 'application/json']]]);

    expect($input->header('Accept'))->toBe('text/html');
});

it('headers with non-scalar values are ignored', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-Bad' => ['nested' => ['value']]]]);

    expect($input->header('X-Bad'))->toBeNull();
});

// --- apiKey resolution ---

it('apiKey is set from the explicit field', function () {
    $input = TenantResolutionInput::fromArray(['apiKey' => 'my-secret-key']);

    expect($input->apiKey)->toBe('my-secret-key');
});

it('apiKey is extracted from a Bearer Authorization header', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['Authorization' => 'Bearer my-token']]);

    expect($input->apiKey)->toBe('my-token');
});

it('apiKey is extracted from a case-insensitive Bearer prefix', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['Authorization' => 'BEARER my-token']]);

    expect($input->apiKey)->toBe('my-token');
});

it('apiKey is extracted from the X-API-Key header', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['X-API-Key' => 'header-key']]);

    expect($input->apiKey)->toBe('header-key');
});

it('explicit apiKey takes precedence over Authorization header', function () {
    $input = TenantResolutionInput::fromArray([
        'apiKey'  => 'explicit-key',
        'headers' => ['Authorization' => 'Bearer bearer-token'],
    ]);

    expect($input->apiKey)->toBe('explicit-key');
});

it('Bearer token takes precedence over X-API-Key header', function () {
    $input = TenantResolutionInput::fromArray([
        'headers' => [
            'Authorization' => 'Bearer bearer-token',
            'X-API-Key'     => 'header-key',
        ],
    ]);

    expect($input->apiKey)->toBe('bearer-token');
});

it('apiKey is null when Authorization header is not a Bearer token', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['Authorization' => 'Basic dXNlcjpwYXNz']]);

    expect($input->apiKey)->toBeNull();
});

it('apiKey is null when Bearer token is empty', function () {
    $input = TenantResolutionInput::fromArray(['headers' => ['Authorization' => 'Bearer   ']]);

    expect($input->apiKey)->toBeNull();
});

it('apiKey is null when no key is provided anywhere', function () {
    $input = TenantResolutionInput::fromArray([]);

    expect($input->apiKey)->toBeNull();
});
