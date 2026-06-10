# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-09

### Added
- `ChainTenantResolver` — runs all registered strategies and validates consistency across results
- `TenantResolverRegistry` — priority-ordered registry for resolution strategies
- `TenantResolutionInput` — immutable value object built from raw request data; resolves API keys from `Authorization: Bearer` and `X-API-Key` headers automatically
- Seven built-in resolution strategies: `SubdomainTenantResolutionStrategy`, `CustomDomainTenantResolutionStrategy`, `PathTenantResolutionStrategy`, `HeaderTenantResolutionStrategy`, `HeaderTenantSlugResolutionStrategy`, `SessionTenantResolutionStrategy`, `ApiKeyTenantResolutionStrategy`
- `CurrentTenant` — request-scoped holder for the resolved `TenantContext`; `get()` throws `TenantNotResolvedException` when called before resolution
- `TenantAccessGuard` — checks user membership; system context always bypasses the check
- `TenantPermissionChecker` — delegates permission lookups to `TenantPermissionRepositoryInterface`
- `TenantRecord` and `TenantApiKeyRecord` — concrete value objects implementing the record interfaces
- `HostNormalizer` — strips scheme, port, path, query, fragment; handles IPv6 literals; lowercases and trims
- Full exception hierarchy under `TenantException`
- 272 tests at 100% code coverage (Pest 4 + PHPUnit)
- PHPStan level 8 with zero errors
- GitHub Actions CI workflow

[Unreleased]: https://github.com/arielespinoza07/tenancy-core/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/arielespinoza07/tenancy-core/releases/tag/v1.0.0
