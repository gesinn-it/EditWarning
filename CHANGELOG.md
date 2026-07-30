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
- `EditWarningMsg::getInstance()` no longer caches message instances as a per-type singleton; it now
  builds a fresh instance on every call.
- CI now installs gesinn.it's PageForms fork (`gesinn-it/mediawiki-extensions-PageForms`, pinned to
  `2.1.9`) as a test dependency, configurable via the `PF_REPO`/`PF_VERSION` Makefile variables, to
  verify compatibility with PageForms-driven (`action=formedit`) edits.

### Fixed
- Fixed `composer update` failing in CI with a `PluginBlockedException` by allow-listing the `dealerdirect/phpcodesniffer-composer-installer` Composer plugin.
- Fixed `EditWarning::addLock()` classifying locks by the inverse of their actual type: rows with
  `section = 0` (whole-article locks) were treated as section locks and vice versa, so
  `isArticleLocked()`/`isSectionLocked()` and friends never reflected the real lock state.
- Fixed a dynamic-property mismatch in `EditWarningLock` where the declared `$timestamp` property was
  never read from or written to; `getTimestamp()`/`setTimestamp()` used `$_timestamp` instead, silently
  creating an undeclared dynamic property on every lock object.
- Fixed `EditWarningMsg::getInstance()` caching the first rendered message per type (e.g. "ArticleWarning")
  for the lifetime of the PHP worker process, leaking one user's name/timestamp/cancel URL into every
  other user's warning message of the same type until the worker restarted.

### Added
- Added `codecov.yml` and enabled Codecov coverage upload in CI for the MediaWiki 1.43 test job; added a Codecov badge to the README.
- Added an integration test for `EditWarningHooks::logout()`, the only database-touching hook path that previously had no test coverage.
- Added unit and integration tests for `EditWarningLock`, `EditWarning`, `EditWarningMessage`, the
  `EditWarningCancelMsg`/`EditWarningInfoMsg`/`EditWarningWarnMsg` subclasses, and all decision branches
  of `EditWarningHooks::edit()` (new/updated/conflicting article and section locks, anonymous users,
  PageForms' `action=formedit`), raising line coverage from ~32% to ~84%.
