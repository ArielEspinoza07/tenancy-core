# Tenancy Core

Framework-agnostic tenancy core for PHP applications, providing tenant resolution, tenant context, access guards, and authorization contracts.

---

## Requirements

- PHP 8.5+

---

## Installation

```bash
composer require arielespinoza07/tenancy-core
```

---

## Concepts

The package is built around four responsibilities:

| Responsibility | Class |
|---|---|
| Resolve which tenant owns a request | `ChainTenantResolver` + strategies |
| Hold the resolved tenant for the request | `CurrentTenant` |
| Check whether a user can access a tenant | `TenantAccessGuard` |
| Check whether a user has a permission within a tenant | `TenantPermissionChecker` |

All heavy lifting (database queries, session reads, etc.) is behind interfaces that **you** implement for your framework and data layer.

---

## Implementing the interfaces

### TenantLookupInterface

Used by most resolution strategies to fetch a tenant by slug, domain, or ID.

```php
use Tenancy\Contracts\Repositories\TenantLookupInterface;
use Tenancy\Contracts\Records\TenantRecordInterface;

final class EloquentTenantLookup implements TenantLookupInterface
{
    public function findBySlug(string $slug): ?TenantRecordInterface
    {
        return Tenant::whereSlug($slug)->first()?->toTenantRecord();
    }

    public function findByDomain(string $domain): ?TenantRecordInterface
    {
        return Tenant::whereDomain($domain)->first()?->toTenantRecord();
    }

    public function findById(int|string $id): ?TenantRecordInterface
    {
        return Tenant::find($id)?->toTenantRecord();
    }
}
```

### TenantRecordInterface

The package ships a ready-to-use concrete implementation: `Tenancy\Records\TenantRecord`. You can instantiate it directly from whatever data source your application uses:

```php
use Tenancy\Records\TenantRecord;
use Tenancy\Enums\TenantStatus;

new TenantRecord(
    id: $row->id,
    name: $row->name,
    slug: $row->slug,
    domain: $row->domain,
    metadata: $row->metadata,
    tenantStatus: TenantStatus::from($row->status),
);
```

If the built-in record does not fit your data model, implement `TenantRecordInterface` directly:

```php
use Tenancy\Contracts\Records\TenantRecordInterface;

final readonly class MyTenantRecord implements TenantRecordInterface
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $slug,
        public string $status,
        public string $billingStatus,
        public ?string $domain,
        public array $metadata,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active'
            && in_array($this->billingStatus, ['paid', 'trial']);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended'
            || $this->billingStatus === 'overdue';
    }

    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
```

### MembershipRepositoryInterface

Used by `TenantAccessGuard` to check whether a user belongs to a tenant:

```php
use Tenancy\Contracts\Repositories\MembershipRepositoryInterface;

final class EloquentMembershipRepository implements MembershipRepositoryInterface
{
    public function existsActiveMembership(int|string $userId, int|string $tenantId): bool
    {
        return Membership::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->exists();
    }
}
```

### TenantPermissionRepositoryInterface

Used by `TenantPermissionChecker`:

```php
use Tenancy\Contracts\Repositories\TenantPermissionRepositoryInterface;

final class EloquentTenantPermissionRepository implements TenantPermissionRepositoryInterface
{
    public function userHasPermission(int|string $tenantId, int|string $userId, string $permission): bool
    {
        return Role::forTenant($tenantId)
            ->forUser($userId)
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }
}
```

### TenantApiKeyLookupInterface

Used by `ApiKeyTenantResolutionStrategy`. It receives the plain-text key from the request and must return a `TenantApiKeyRecordInterface` — or `null` if the key does not exist.

API keys should be stored **hashed** in your database, so the implementation hashes the incoming plain-text key before querying. The package's concrete `TenantApiKeyRecord` and `TenantRecord` can be returned directly:

```php
use DateTimeImmutable;
use Tenancy\Contracts\Records\TenantApiKeyRecordInterface;
use Tenancy\Contracts\Repositories\TenantApiKeyLookupInterface;
use Tenancy\Enums\TenantStatus;
use Tenancy\Records\TenantApiKeyRecord;
use Tenancy\Records\TenantRecord;

final class EloquentTenantApiKeyLookup implements TenantApiKeyLookupInterface
{
    public function findByPlainTextKey(string $plainTextKey): ?TenantApiKeyRecordInterface
    {
        $row = ApiKey::with('tenant')
            ->where('key_hash', hash('sha256', $plainTextKey))
            ->first();

        if ($row === null) {
            return null;
        }

        return new TenantApiKeyRecord(
            tenant: new TenantRecord(
                id: $row->tenant->id,
                name: $row->tenant->name,
                slug: $row->tenant->slug,
                domain: $row->tenant->domain,
                metadata: $row->tenant->metadata ?? [],
                tenantStatus: TenantStatus::from($row->tenant->status),
            ),
            revoked: (bool) $row->revoked,
            expiresAt: $row->expires_at
                ? new DateTimeImmutable($row->expires_at)
                : null,
        );
    }
}
```

`TenantApiKeyRecord::isActive()` then handles expiry and revocation checks internally — the strategy throws `TenantNotFoundException` if it returns `false`.

---

## Wiring up the resolver

Build a `TenantResolverRegistry`, add strategies in priority order (higher number = tried first), then wrap it in `ChainTenantResolver`:

```php
use Tenancy\Resolution\ChainTenantResolver;
use Tenancy\Resolution\TenantResolverRegistry;
use Tenancy\Resolution\Strategies\SubdomainTenantResolutionStrategy;
use Tenancy\Resolution\Strategies\ApiKeyTenantResolutionStrategy;
use Tenancy\Support\HostNormalizer;

$normalizer = new HostNormalizer();
$lookup     = new EloquentTenantLookup();

$registry = new TenantResolverRegistry();
$registry
    ->add(new ApiKeyTenantResolutionStrategy($apiKeyLookup), priority: 20)
    ->add(new SubdomainTenantResolutionStrategy($lookup, $normalizer, 'example.com'), priority: 10);

$resolver = new ChainTenantResolver($registry);
```

---

## Resolving a request

Build a `TenantResolutionInput` from the incoming request and call `resolve()`:

```php
use Tenancy\Resolution\TenantResolutionInput;

$input = TenantResolutionInput::fromArray([
    'host'            => $request->getHost(),
    'path'            => $request->getPathInfo(),
    'headers'         => $request->headers->all(),
    'sessionTenantId' => $session->get('tenant_id'),
    'userId'          => $auth->id(),
]);

$context = $resolver->resolve($input);  // throws on failure
```

Then store it in `CurrentTenant` for the duration of the request:

```php
use Tenancy\Context\CurrentTenant;

$currentTenant = new CurrentTenant();
$currentTenant->set($context);

// Later in the request lifecycle:
$context   = $currentTenant->get();          // throws TenantNotResolvedException if not set
$tenantId  = $currentTenant->get()->record->id;
$isSystem  = $currentTenant->get()->isSystem();
```

#### Lifecycle in long-running servers

In **PHP-FPM** every request runs in a fresh process, so `CurrentTenant` is naturally reset between requests.

In **long-running servers** (Laravel Octane, Swoole, RoadRunner) the same process handles multiple requests. If `CurrentTenant` is registered as a singleton it will carry the previous request's tenant into the next one.

Always call `clear()` at the end of each request — typically in a terminating middleware:

```php
// Framework-agnostic terminating middleware example
public function terminate(): void
{
    $this->currentTenant->clear();
}
```

If your framework supports request-scoped bindings, binding `CurrentTenant` per-request is the cleanest solution and makes the manual `clear()` unnecessary.

---

## Checking access and permissions

```php
use Tenancy\Access\TenantAccessGuard;
use Tenancy\Authorization\TenantPermissionChecker;

$guard = new TenantAccessGuard(new EloquentMembershipRepository());
$guard->ensureAccess($userId, $context);  // throws TenantAccessDeniedException

$checker = new TenantPermissionChecker(new EloquentTenantPermissionRepository());
$checker->ensureCan($userId, $context, 'posts.publish');  // throws TenantPermissionDeniedException
```

---

## Available resolution strategies

| Strategy | Reads from | Default header / key |
|---|---|---|
| `SubdomainTenantResolutionStrategy` | Subdomain of a configured base domain | — |
| `CustomDomainTenantResolutionStrategy` | Full custom domain mapped to a tenant | — |
| `PathTenantResolutionStrategy` | First URL path segment (or after a prefix) | — |
| `HeaderTenantResolutionStrategy` | Request header (tenant ID) | `X-Tenant-ID` |
| `HeaderTenantSlugResolutionStrategy` | Request header (tenant slug) | `X-Tenant-Slug` |
| `SessionTenantResolutionStrategy` | Session value | — |
| `ApiKeyTenantResolutionStrategy` | Bearer token, `X-API-Key` header, or explicit field | `Authorization` / `X-API-Key` |

`ChainTenantResolver` runs all registered strategies, collecting results. If all results agree on the same tenant it returns the first; if they conflict it throws `TenantResolutionConflictException`.

---

## Exception hierarchy

```
TenantException
└── TenantAuthorizationException
│   ├── TenantAccessDeniedException
│   └── TenantPermissionDeniedException
└── TenantResolutionException
    ├── TenantNotFoundException
    ├── TenantNotResolvedException
    ├── TenantResolutionConflictException
    └── TenantSuspendedException
```

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for setup instructions, code conventions, and PR guidelines.

---
## License

[MIT License](LICENSE)
