<?php

declare(strict_types=1);

use Tenancy\Support\HostNormalizer;

beforeEach(function () {
    $this->normalizer = new HostNormalizer();
});

// --- Null / empty inputs ---

it('returns null for null input', function () {
    expect($this->normalizer->normalize(null))->toBeNull();
});

it('returns null for empty string', function () {
    expect($this->normalizer->normalize(''))->toBeNull();
});

it('returns null for whitespace-only input', function () {
    expect($this->normalizer->normalize('   '))->toBeNull();
});

// --- Case normalization ---

it('lowercases uppercase hostnames', function () {
    expect($this->normalizer->normalize('EXAMPLE.COM'))->toBe('example.com');
});

it('lowercases mixed-case hostnames', function () {
    expect($this->normalizer->normalize('Tenant.Example.COM'))->toBe('tenant.example.com');
});

// --- Whitespace stripping ---

it('strips leading and trailing whitespace', function () {
    expect($this->normalizer->normalize('  example.com  '))->toBe('example.com');
});

// --- Scheme stripping ---

it('strips http scheme', function () {
    expect($this->normalizer->normalize('http://example.com'))->toBe('example.com');
});

it('strips https scheme', function () {
    expect($this->normalizer->normalize('https://example.com'))->toBe('example.com');
});

it('strips scheme from a full URL with port, path, query, and fragment', function () {
    expect($this->normalizer->normalize('https://example.com:8080/path?q=1#section'))->toBe('example.com');
});

// --- Port stripping ---

it('strips port from hostname', function () {
    expect($this->normalizer->normalize('example.com:8080'))->toBe('example.com');
});

it('strips port 443 from hostname', function () {
    expect($this->normalizer->normalize('example.com:443'))->toBe('example.com');
});

// --- Path / query / fragment stripping (no scheme) ---

it('strips path from hostname', function () {
    expect($this->normalizer->normalize('example.com/some/path'))->toBe('example.com');
});

it('strips query string from hostname', function () {
    expect($this->normalizer->normalize('example.com?foo=bar'))->toBe('example.com');
});

it('strips fragment from hostname', function () {
    expect($this->normalizer->normalize('example.com#anchor'))->toBe('example.com');
});

// --- Dot trimming ---

it('strips a leading dot', function () {
    expect($this->normalizer->normalize('.example.com'))->toBe('example.com');
});

it('strips a trailing dot', function () {
    expect($this->normalizer->normalize('example.com.'))->toBe('example.com');
});

// --- Subdomains ---

it('preserves subdomains', function () {
    expect($this->normalizer->normalize('tenant.example.com'))->toBe('tenant.example.com');
});

// --- IPv6 ---

it('strips brackets from an IPv6 literal', function () {
    expect($this->normalizer->normalize('[::1]'))->toBe('::1');
});

it('strips brackets and port from an IPv6 literal with port', function () {
    expect($this->normalizer->normalize('[::1]:8080'))->toBe('::1');
});

it('returns malformed IPv6 without closing bracket as-is', function () {
    expect($this->normalizer->normalize('[::1'))->toBe('[::1');
});
