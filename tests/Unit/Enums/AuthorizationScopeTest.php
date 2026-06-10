<?php

declare(strict_types=1);

use Tenancy\Enums\AuthorizationScope;

// --- Backing values ---

it('has the expected string backing values', function (AuthorizationScope $scope, string $value) {
    expect($scope->value)->toBe($value);
})->with([
    [AuthorizationScope::System, 'system'],
    [AuthorizationScope::Tenant, 'tenant'],
]);

it('can be created from its string value', function (AuthorizationScope $scope) {
    expect(AuthorizationScope::from($scope->value))->toBe($scope);
})->with(AuthorizationScope::cases());

// --- isSystem() ---

it('isSystem() returns true only for System', function () {
    expect(AuthorizationScope::System->isSystem())->toBeTrue()
        ->and(AuthorizationScope::Tenant->isSystem())->toBeFalse();
});

// --- isTenant() ---

it('isTenant() returns true only for Tenant', function () {
    expect(AuthorizationScope::Tenant->isTenant())->toBeTrue()
        ->and(AuthorizationScope::System->isTenant())->toBeFalse();
});

// --- Mutual exclusivity ---

it('exactly one flag is true per case', function (AuthorizationScope $scope) {
    $trueCount = (int) $scope->isSystem() + (int) $scope->isTenant();

    expect($trueCount)->toBe(1);
})->with(AuthorizationScope::cases());
