# AGENTS.md

This repository is `tailwind-merge-php`, a PHP port of [tailwind-merge](https://github.com/dcastil/tailwind-merge) by dcastil. It merges Tailwind CSS class strings by removing style conflicts while preserving non-conflicting and non-Tailwind classes.

## Primary Goals

- Keep merge behavior correct for supported Tailwind CSS versions (currently v4.0–v4.3).
- Keep API and type contracts stable.
- Maintain strict PHP type safety (PHPStan level max).

## Repo Map

- Source code: `src/`
- Tests: `tests/`
- CI workflows: `.github/workflows/`

Deeper implementation notes live in:

- `.agents/tailwind-css-version-update.md` (Tailwind CSS version support workflow)

## Environment and Commands

- Language: PHP 8.1–8.5
- Package manager: Composer

Core commands:

- `composer test` — run the full test suite (no coverage)
- `composer test:coverage` — run tests with coverage (requires Xdebug)
- `composer test:lint` — check code style with PHP-CS-Fixer (dry-run)
- `composer test:lint:fix` — auto-fix code style issues
- `composer test:types` — run static analysis with PHPStan
- `composer test:types:baseline` — regenerate the PHPStan baseline

## Working Rules for Changes

1. Treat `src/Support/Config.php` as the behavioral source of truth for default class groups and conflicts.
2. If behavior changes, update tests in `tests/` in the same change.
3. Run `composer test:lint:fix` before committing to ensure consistent formatting.
4. Do not manually edit `vendor/`; it is a generated artifact.
5. Use `phpstan-baseline.neon` only for unavoidable PHPStan suppressions — do not suppress real issues.

## Documentation Sync Policy

Treat documentation updates as part of the same change, not as follow-up work.

Required when relevant:

1. Update `README.md` for any user-visible behavior, API, or version support changes.
2. Update `AGENTS.md` whenever agent workflow, repo conventions, required commands, or guardrails change.

Definition of done for every PR/change:

1. Code and tests are updated.
2. Relevant docs are updated in the same change set.
3. `AGENTS.md` guidance is reviewed and updated if the change affects how agents should work in this repo.
4. `.agents/*` guidance is reviewed and updated if the change affects the version update workflow.

## Tailwind CSS Version Support

For Tailwind CSS version support work, follow `.agents/tailwind-css-version-update.md`.

## Quick Validation Matrix

- Modifier or arbitrary-variant semantics: run `tests/Feature/ModifiersTest.php`, `tests/Feature/ArbitraryVariantsTest.php`.
- Class groups, conflicts, default config: run `tests/Feature/DefaultConfigTest.php`, `tests/Feature/ClassGroupConflictsTest.php`, `tests/Feature/TailwindCssVersionsTest.php`.
- Arbitrary values and properties: run `tests/Feature/ArbitraryValuesTest.php`, `tests/Feature/ArbitraryPropertiesTest.php`.
- Validators: run `tests/Unit/Validators/`.
- Full suite: `composer test`.
