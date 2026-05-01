# Release Guide

This document describes how to release new versions of `vanvanni/marko-blade`.

## Prerequisites

- [ ] The repository is hosted on GitHub under `vanvanni/marko-blade`
- [ ] The package is registered on [Packagist](https://packagist.org/packages/submit)
- [ ] All changes are merged into the `main` branch
- [ ] All tests pass locally (`vendor/bin/pest`)

## Versioning

This package follows [Semantic Versioning](https://semver.org/):

- `MAJOR` — Breaking changes (e.g. dropping PHP support, renaming classes)
- `MINOR` — New features, backwards compatible
- `PATCH` — Bug fixes, backwards compatible

## Release Strategy: Start with RC

Before publishing a stable version, always release a **Release Candidate (RC)** first. This lets you verify the package installs and works correctly in a real project before locking the API.

### RC Release (e.g. v1.0.0-rc1)

```bash
git checkout main
git pull origin main
vendor/bin/pest

git tag v1.0.0-rc1
git push origin v1.0.0-rc1
```

Install the RC in a test project:

```bash
composer require vanvanni/marko-blade:1.0.0-rc1
```

If issues are found, fix them on `main` and tag the next RC:

```bash
git tag v1.0.0-rc2
git push origin v1.0.0-rc2
```

### Stable Release (e.g. v1.0.0)

Once the RC works correctly, promote it to stable:

```bash
git tag v1.0.0
git push origin v1.0.0
```

### Automated Release

Pushing any `v*.*.*` tag triggers the [Release workflow](../.github/workflows/release.yml) which:

1. Runs the full test suite
2. Creates a GitHub Release with auto-generated release notes

### Packagist Update

Packagist will automatically detect the new tag within a few minutes. If you have the Packagist webhook configured in your GitHub repository settings, the update is instant.

## Hotfix Release (e.g. v1.0.1)

If a bug is discovered after a stable release:

```bash
# Fix on main
git add .
git commit -m "fix: resolve issue with ..."
git push origin main

# Tag the patch
git tag v1.0.1
git push origin v1.0.1
```

## Rollback

Composer packages cannot be deleted from Packagist. If a release is broken:

1. Fix the issue on `main`
2. Tag a new patch release (`v1.0.1`) immediately
3. Advise users to run `composer update` to get the fix

## Checklist

- [ ] Tests pass locally
- [ ] `composer.json` version constraints are correct
- [ ] `CHANGELOG.md` is updated (if maintained)
- [ ] Tag follows `v*.*.*` format
- [ ] GitHub Release was created successfully
- [ ] Packagist shows the new version
