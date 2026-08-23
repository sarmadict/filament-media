# Publishing the Composer package

The package `composer.json` is prepared as a normal Composer library:

- package name: `sarmadict/filament-media`;
- type: `library`;
- PSR-4 namespace: `Sarmadict\FilamentMedia\` -> `src/`;
- Laravel package discovery through `extra.laravel.providers`;
- no hard-coded package `version` field.

## Recommended repository layout

The root of the package repository should contain:

```text
composer.json
README.md
LICENSE
CHANGELOG.md
config/
database/
docs/
resources/
src/
tests/
```

If you split the bundled package into its own Git repository, the contents of `packages/sarmadict/filament-media/` become the repository root.

## Validate before publishing

```bash
composer validate --strict
composer install
composer test
composer format:check
```

## Versioning

Use Git tags instead of adding a `version` field to `composer.json`:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

Composer derives package versions from VCS tags/branches for normal VCS repositories.

## Packagist

Create the public Git repository, push the package, then submit that repository to Packagist under the package name `sarmadict/filament-media`. After the first indexed release, consumers install it with:

```bash
composer require sarmadict/filament-media
```

## Bundled application's local repository

The supplied project uses a Composer `path` repository only because the package is physically included in the same ZIP. Once the package is published and reachable through Composer, remove the root application's `repositories` path entry and run:

```bash
composer update sarmadict/filament-media
```
