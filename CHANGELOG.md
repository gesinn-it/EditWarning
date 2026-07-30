# Changelog

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- **BREAKING:** Dropped support for MediaWiki 1.35; the minimum supported version is now 1.39. `extension.json` now declares `requires.MediaWiki >= 1.39.0` and uses `manifest_version` 2.
- Replaced deprecated `wfGetDB()`/`DB_MASTER` calls with `MediaWikiServices::getDBLoadBalancer()->getConnection( DB_REPLICA/DB_PRIMARY )` in `EditWarningHooks` and `EditWarningApi`.
- Raised the minimum required PHP version to 8.1 in `composer.json`, in line with dropping MediaWiki 1.35/PHP 7.4.
- Local `make install`/`make ci` now default to MySQL instead of SQLite, matching the CI matrix.

### Fixed
- Fixed `composer update` failing in CI with a `PluginBlockedException` by allow-listing the `dealerdirect/phpcodesniffer-composer-installer` Composer plugin.

### Added
- Added `codecov.yml` and enabled Codecov coverage upload in CI for the MediaWiki 1.43 test job; added a Codecov badge to the README.
- Added an integration test for `EditWarningHooks::logout()`, the only database-touching hook path that previously had no test coverage.
