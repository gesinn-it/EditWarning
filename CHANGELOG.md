# Changelog

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- **BREAKING:** `action=editwarning` API requests must now be POSTed with a `csrf` token, and no longer
  accept a `user` parameter; the lock is always attributed to the authenticated session user
  (`ApiBase::getUser()`), never to an arbitrary request parameter. Anonymous requests are rejected.
  `resources/js/editwarning.js` was updated to use `mw.Api().postWithToken('csrf', ...)` accordingly.
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
- Fixed `EditWarningApi` performing database writes (lock/unlock) without CSRF protection or
  authentication: it never overrode `needsToken()`/`mustBePosted()`/`isWriteMode()`, and determined the
  acting user from a `user` request parameter instead of the authenticated session, allowing any page to
  lock or unlock articles on behalf of any user via a simple cross-site GET request.
- Fixed `EditWarningMessage::addLabel()`/`addLabelMsg()` substituting arbitrary values (e.g. a cancel URL
  built from a page title, which MediaWiki's default `$wgLegalTitleChars` allows to contain `"`) directly
  into HTML templates without escaping, allowing HTML attribute-breakout/script injection via a crafted
  page title; values are now escaped with `htmlspecialchars()` when added.
- Fixed `EditWarningMessage::processTemplate()` using `preg_replace()` to substitute label values, which
  interprets sequences like `$1` or `\0` in the replacement as backreferences instead of literal text;
  switched to `preg_replace_callback()` so label values are always substituted literally.
- Fixed `EditWarningApi` locking/unlocking a section always storing/removing the lock as section `0`
  (whole-article) because the `section` request parameter, despite being passed to `EditWarning::setSection()`,
  was never forwarded to `saveLock()`/`removeLock()`; the API's `section` parameter now has an effect.
- Fixed `EditWarningApi` echoing `articleid` back as a string instead of the declared integer type by using
  `extractRequestParams()` instead of raw `getVal()` calls.
- Fixed `EditWarningMessage::setMsg()` interpolating message parameters (username, timestamps, cancel URL)
  into the rendered warning/notice HTML without escaping, allowing HTML/script injection via a crafted
  username; parameters are now escaped with `htmlspecialchars()` before substitution.
- Fixed `EditWarningMessage::loadTemplate()` catching template read failures but re-throwing the base
  `\Exception` class directly instead of a proper SPL exception; both `loadTemplate()` and
  `processTemplate()` now throw `\RuntimeException`.
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
- Added Phan static analysis (`.phan/config.php`, `.phan/baseline.php`, `composer phan`,
  `make composer-phan`/`composer-phan-update-baseline`), run in CI for the MediaWiki 1.43/coverage job.
  Pre-existing issues in code untouched by this change are captured in the baseline rather than fixed.
