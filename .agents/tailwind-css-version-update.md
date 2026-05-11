# Tailwind CSS version update workflow

Use this guide when updating tailwind-merge-php for a new Tailwind CSS version.

The main risk in this work is false confidence: Tailwind release notes, deployed docs, source code, and website docs can land at different times. Treat every feature as something to verify from official sources before changing `src/Support/Config.php` or parser behavior.

## Official sources

- Tailwind CSS releases: https://github.com/tailwindlabs/tailwindcss/releases
- Tailwind CSS source and PRs: https://github.com/tailwindlabs/tailwindcss
- Tailwind CSS release tag pattern: `https://github.com/tailwindlabs/tailwindcss/releases/tag/vX.Y.Z`
- Tailwind CSS deployed docs: https://tailwindcss.com/docs
- Tailwind CSS website source and docs PRs: https://github.com/tailwindlabs/tailwindcss.com
- Prior tailwind-merge (JS) support PRs for reference, for example v4.2 support: https://github.com/dcastil/tailwind-merge/pull/651
- Prior tailwind-merge-php support PRs for comparison: https://github.com/tales-from-a-dev/tailwind-merge-php/pulls

When docs for a just-released Tailwind version are not deployed yet, use the `tailwindcss.com` repository and its PRs to find future docs pages, sidebar order, and link targets.

## Investigation checklist

1. Read the Tailwind CSS release notes for the target version.
2. Open every linked Tailwind CSS PR that mentions utilities, variants, parser syntax, arbitrary values, or behavior changes.
3. Inspect Tailwind CSS source and tests when release notes or PR descriptions are ambiguous. Do not infer support for arbitrary values or variables from naming patterns alone.
4. Check the `tailwindcss.com` repository for docs PRs when deployed docs lag behind the release.
5. Compare against the corresponding tailwind-merge (JS) Tailwind-support PR to reuse the same structure and testing style.
6. Make a feature list before coding. Split work into simple utility additions, ordering/docs updates, conflict changes, and parser/config API changes.
7. Record no-op decisions in the PR or issue summary, especially when a Tailwind PR looked relevant but does not require a tailwind-merge-php change.

## Triage questions for each Tailwind change

- Does it emit new class names? If yes, it likely needs `classGroups` entries in `src/Support/Config.php`.
- Does it change which utilities conflict? If yes, update `conflictingClassGroups` or `conflictingClassGroupModifiers`.
- Does it add a new arbitrary value or arbitrary variable form? Verify the exact accepted syntax in Tailwind source before adding validators in `src/Validators/`.
- Does it add a slash form that is part of the base class rather than a postfix modifier? Prefer config-driven lookup such as `postfixLookupClassGroups` before changing parser logic.
- Does it change modifier, variant, prefix, important, or arbitrary-variant syntax? Parser changes are higher risk and need focused parser/modifier tests.
- Does it only change generated CSS for an existing class without changing class syntax or conflicts? It may not require a tailwind-merge-php change.
- Does it remove or rename classes? Check whether keeping old classes would create incorrect merges for the supported Tailwind range.

## Default config ordering and links

Keep `src/Support/Config.php` ordered according to the Tailwind CSS docs sidebar, not alphabetically and not by implementation convenience.

For new utility groups:

- Use `tailwindcss.com` or the website repository to find the final docs URL for each `@see` docblock comment.
- If the public docs are not deployed, inspect docs PRs in `tailwindlabs/tailwindcss.com`.
- Replace temporary `TBD` links before finishing the change.
- Keep related utility groups in sidebar order even when they share a prefix.

There is an important exception to sidebar order: class-group order affects validator precedence for groups that share the same prefix path. The class map tries exact child paths first, then runs validators in the order they were registered. If a broad validator appears before a more specific validator for the same prefix, the broad group can claim the class before the specific group sees it.

When groups with the same prefix use validators:

- Put the most specific validators first.
- Put broad or catch-all validators last for that prefix.
- Treat color validators as especially sensitive because they often accept arbitrary values or otherwise broad value sets.
- Prefer correctness over strict sidebar order inside that shared-prefix cluster.

For example, if a `text-*` color group uses a broad color validator, keep it after more specific `text-*` groups such as font-size-related groups so ambiguous classes resolve to the intended class group.

Examples learned during the Tailwind CSS v4.3 update:

- `tab-size` belongs after `text-indent` and before `vertical-align`.
- `zoom` belongs after `translate`.
- Scrollbar groups should follow the sidebar order for scrollbar color, scrollbar gutter, and scrollbar width.
- PRs that look relevant to existing utilities, such as changes around `start` and `end`, still need source review before changing tailwind-merge-php.

## Implementation guidance

Use `src/Support/Config.php` as the behavioral source of truth for utilities and conflicts.

Prefer config changes over parser changes. Parser and merge hot paths run on every merge call, so keep extra work small and opt-in:

- Avoid scanning or balancing brackets in validators when a cheap prefix or character check can reject most inputs.
- New validators go in `src/Validators/` as a dedicated class implementing `ValidatorInterface`. Use the `ValidatesArbitraryValue` or `ValidateArbitraryVariable` traits for shared matching logic when applicable.
- Add `@see` docblock comments only for non-obvious behavior, especially when a config entry compensates for parser ambiguity.
- Mark utilities removed in this Tailwind version with `@deprecated since Tailwind CSS vX.Y.Z` docblock comments on their class group entry.

For slash syntax, remember that tailwind-merge-php initially treats the part after `/` as a possible postfix modifier. If the full class can belong to a different group, use `postfixLookupClassGroups` rather than a syntax-specific parser branch.

The Tailwind CSS v4.3 named container query support is the reference pattern for this project:

- `@container-size` is a container type class.
- `@container-size/sidebar` combines container type and container name.
- The parser already exposes a possible postfix modifier, so no bracket-counting parser change is needed.
- A `NamedContainerQueryValidator` class was added in `src/Validators/` to handle the named container query syntax.
- `container-named` creates a conflict with `container-type`, but not the reverse, because a later type-only class should preserve the earlier container name.
- `postfixLookupClassGroups: ['container-type']` tells merge logic to try resolving the full slash class only after the pre-slash class resolves to `container-type`.

## Testing checklist

Add or update tests based on the kind of change:

- Tailwind version compatibility: `tests/Feature/TailwindCssVersionsTest.php`
- Default config class map coverage: `tests/Unit/ClassMapTest.php`
- Class group conflicts: `tests/Feature/ClassGroupConflictsTest.php` and `tests/Feature/ConflictsAcrossClassGroupsTest.php`
- Parser or modifier semantics: `tests/Feature/ModifiersTest.php`, `tests/Feature/ArbitraryVariantsTest.php`
- Validators: `tests/Unit/Validators/`

For new version support, add a `v{XY}Provider()` data provider method and a corresponding test method to `tests/Feature/TailwindCssVersionsTest.php`:

```php
public static function v44Provider(): array
{
    return [
        // Last class wins within the new class group
        ['new-util-a new-util-b', 'new-util-b'],
        // Cross-group conflict in both orders when asymmetric
        ['old-util new-util', 'new-util'],
        ['new-util old-util', 'old-util'],
    ];
}

#[DataProvider('v44Provider')]
public function testItHandlesV44FeaturesCorrectly(string|array $input, string $output): void
{
    $this->assertSame($output, (new TailwindMerge())->merge($input));
}
```

For each new class group, include tests for:

- Last class wins within the new class group.
- Cross-group conflicts in both orders when conflicts are asymmetric.
- Arbitrary values and arbitrary variables only when Tailwind source confirms support.
- Important modifier (`!`) and variant combinations when parser or conflict behavior is involved.
- Unknown or invalid classes remaining untouched.

After updating `src/Support/Config.php`, run `tests/Unit/ClassMapTest.php` to verify the class map is correct. The test will report which expected class group entries are missing or wrong — update the assertions to match the new expected output.

## Documentation checklist

Update docs in the same change when behavior or public API changes:

- `README.md` for the supported Tailwind CSS version range (badge and prose).
- `AGENTS.md` for the supported Tailwind CSS version range in Primary Goals.
- `AGENTS.md` and `.agents/*` when the update process or repo guidance changes.

## Validation checklist

Run targeted tests while iterating, then run the full validation set before finalizing:

```sh
composer test:lint:fix
composer test:types
composer test
git diff --check
```
