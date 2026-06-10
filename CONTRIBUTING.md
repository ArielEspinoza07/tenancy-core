# Contributing to Tenancy

Contributions are welcome — bug fixes, new features, documentation improvements, or anything else that makes the library better.

---

## Prerequisites

- PHP 8.5+
- [Composer](https://getcomposer.org/)

---

## Setup

```bash
git clone https://github.com/arielespinoza07/tenancy-core
cd tenancy-core
composer install
```

---

## Development workflow

All common tasks are available as Composer scripts:

| Script | What it does |
|--------|-------------|
| `composer run lint` | Check code style with Laravel Pint (read-only) |
| `composer run lint:fix` | Auto-fix code style |
| `composer run analyse` | Run PHPStan static analysis |
| `composer run test` | Run the Pest test suite |
| `composer run test:coverage` | Run tests with code coverage report |
| `composer run check` | Run `lint`, `analyse`, and `test` in one shot |

Run `composer run check` before opening a PR to make sure everything passes locally.

---

## Code conventions

These mirror the project's core philosophy — keep them consistent across all new code:

- Every PHP file must start with `declare(strict_types=1);`
- All classes must be `final`
- Data Transfer Objects and Value Objects must be `readonly`
- Use strong typing everywhere — no `mixed` unless truly unavoidable
- Keep classes small and focused (SOLID, KISS, YAGNI)
- Do not add docblocks to code you did not change

---

## Submitting changes

1. Branch off `main` with a descriptive name (e.g. `feat/add-progress-bar`, `fix/loader-cache`).
2. Make your changes and ensure `composer run check` passes with no errors.
3. Open a Pull Request against `main` with a clear description of what changed and why.
4. All CI checks must be green before the PR can be merged.

---

## Reporting issues

Please use [GitHub Issues](https://github.com/arielespinoza07/tenancy-core/issues) to report bugs or request features. Include a minimal reproduction case when reporting bugs.