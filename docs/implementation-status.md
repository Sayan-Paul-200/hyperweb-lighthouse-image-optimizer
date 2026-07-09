# HyperWeb Lighthouse Image Optimizer Implementation Status

This document tracks implementation progress against `docs/HWLIO-master-implementation-plan.md`.

## Baseline Snapshot

**Date:** 2026-07-09  
**Plugin slug:** `hyperweb-lighthouse-image-optimizer`  
**Internal prefix:** `hwlio`  
**Package name:** `Hyperweb_Lighthouse_Image_Optimizer`  
**Planned namespace:** `HyperWeb\LighthouseImageOptimizer`

### Plugin Metadata

The current scaffold entry file is `hyperweb-lighthouse-image-optimizer.php`.

```text
Plugin Name: HyperWeb Lighthouse Image Optimizer
Plugin URI: https://hyperweblabs.in/
Description: Optimize WordPress images for better Lighthouse performance by generating and serving WebP or AVIF versions, reducing image payloads, and preserving original files.
Version: 1.0.0
Author: Sayan Paul
Author URI: https://github.com/Sayan-Paul-200/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: hyperweb-lighthouse-image-optimizer
Domain Path: /languages
```

### Pre-Subphase 0.1 File Tree

The repository initially contained 23 files, including 15 PHP files.

```text
hyperweb-lighthouse-image-optimizer.php
index.php
LICENSE.txt
README.txt
uninstall.php
admin/class-hyperweb-lighthouse-image-optimizer-admin.php
admin/index.php
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
admin/partials/hyperweb-lighthouse-image-optimizer-admin-display.php
docs/HWLIO-master-implementation-plan.md
includes/class-hyperweb-lighthouse-image-optimizer-activator.php
includes/class-hyperweb-lighthouse-image-optimizer-deactivator.php
includes/class-hyperweb-lighthouse-image-optimizer-i18n.php
includes/class-hyperweb-lighthouse-image-optimizer-loader.php
includes/class-hyperweb-lighthouse-image-optimizer.php
includes/index.php
languages/hyperweb-lighthouse-image-optimizer.pot
public/class-hyperweb-lighthouse-image-optimizer-public.php
public/index.php
public/css/hyperweb-lighthouse-image-optimizer-public.css
public/js/hyperweb-lighthouse-image-optimizer-public.js
public/partials/hyperweb-lighthouse-image-optimizer-public-display.php
```

### Current Scaffold Assessment

- The project is a WordPress Plugin Boilerplate scaffold, not a partially completed optimizer.
- The plugin entry point, activation hook, deactivation hook, admin class, public class, uninstall guard, and GPL license are present.
- The activation class delegates to the namespaced installer as of Subphase 1.2.
- The deactivation class delegates to non-destructive scheduled-maintenance cleanup as of Subphase 1.3.
- The uninstall file preserves data by default and delegates to opt-in cleanup services as of Subphase 1.3.
- The legacy admin and public classes are inert coordinator placeholders and do not enqueue assets.
- Placeholder admin/frontend CSS, JavaScript, and display partials were removed in Subphase 0.4.
- A namespaced composition root, hook registrar, hook-provider contract, and i18n provider exist as of Subphase 1.1.
- The legacy runtime core class, legacy hook loader, and legacy i18n class were removed in Subphase 1.1.
- The README is boilerplate and does not yet describe the product accurately.
- The POT file exists but is empty.
- Composer autoloading and development quality tooling exist as of Subphase 0.2.
- Action Scheduler 3.9.3 is bundled as an unmodified upstream subtree as of Subphase 0.3.
- Minimal settings defaults, lifecycle installer services, activation diagnostics, and a log table schema exist as of Subphase 1.2.
- Deactivation/uninstall lifecycle policy, safe derivative cleanup primitives, and bounded multisite uninstall orchestration exist as of Subphase 1.3.
- No settings repository/UI, queue abstraction, log writer, diagnostics UI, or image optimization services exist yet.

## Phase Status

- [ ] Phase 0 - Repository Baseline and Scaffold Hardening
  - [x] Subphase 0.1 - Create the development baseline
  - [x] Subphase 0.2 - Add Composer and quality tooling
  - [x] Subphase 0.3 - Harden the bootstrap
  - [x] Subphase 0.4 - Remove performance-negative placeholder behavior
- [ ] Phase 1 - Application Foundation and Lifecycle
  - [x] Subphase 1.1 - Build the composition root
  - [x] Subphase 1.2 - Implement installation and upgrade routines
  - [x] Subphase 1.3 - Implement activation, deactivation, and uninstall policy
  - [ ] Subphase 1.4 - Implement logging foundation
- [ ] Phase 2 - Settings, Environment, and Diagnostics Foundation
- [ ] Phase 3 - Core Image Domain
- [ ] Phase 4 - Attachment State, Metadata, and Cleanup
- [ ] Phase 5 - Action Scheduler Queue and Automatic Processing
- [ ] Phase 6 - Admin Screens, REST API, and Bulk Processing
- [ ] Phase 7 - Frontend Modern-Format Delivery
- [ ] Phase 8 - Loading Optimization and Layout Stability
- [ ] Phase 9 - WooCommerce Integration
- [ ] Phase 10 - Elementor and CSS Background Integration
- [ ] Phase 11 - CDN, Offload, Multisite, and Conflict Adapters
- [ ] Phase 12 - Page-Level Diagnostics and Lighthouse-Oriented Reporting
- [ ] Phase 13 - WP-CLI and Developer Operations
- [ ] Phase 14 - Testing, Performance, Security, and Release

Phase 0 implementation subphases are complete. The phase remains unchecked at the phase level until a supported WordPress 6.5+ activation smoke test is performed.

## Subphase 0.1 - Create the Development Baseline

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Record the current file tree and plugin metadata.
- [x] Create a clean Git baseline commit.
- [x] Add `.gitignore`, `.editorconfig`, and project coding conventions.
- [x] Add `CHANGELOG.md` and confirm the `docs/` directory exists.
- [x] Confirm the master plan exists at `docs/HWLIO-master-implementation-plan.md`.
- [x] Add this implementation status document.

### Files Added

```text
.editorconfig
.gitattributes
.gitignore
CHANGELOG.md
docs/implementation-status.md
```

### Files Changed

```text
None
```

### Files Removed

```text
None
```

### Verification

```text
PHP CLI: 8.1.25
PHP syntax checks: all 15 existing PHP files pass php -l
WordPress activation smoke test: not run in this plugin-only workspace
```

### Acceptance Criteria

- [x] The original scaffold can be restored from version control.
- [x] All existing PHP files pass syntax checks.
- [ ] The plugin activates without fatal errors on the minimum target environment.

The activation smoke test remains pending until this plugin is run inside a WordPress 6.5+ test installation. No PHP syntax issues were found in the plugin-only workspace.

### Deferred Work

- Composer, PSR-4 autoloading, PHPCS, PHPUnit, and static analysis are deferred to Subphase 0.2.
- Bootstrap constants, minimum requirement handling, Composer autoload loading, and Action Scheduler loading are deferred to Subphase 0.3.
- Removal of global placeholder admin/frontend assets is deferred to Subphase 0.4.

## Decision Log

No ADRs have been required. Foundational decisions from the master plan remain unchanged.

## Subphase 0.2 - Add Composer and Quality Tooling

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add Composer PSR-4 autoloading for `src/`.
- [x] Add development dependencies for PHPCS, WordPress Coding Standards, PHPCompatibilityWP, PHPUnit, PHPStan, and WordPress stubs.
- [x] Add scripts for linting, coding standards, unit tests, static analysis, and aggregate quality checks.
- [x] Generate and commit `composer.lock` for reproducible dependency resolution.
- [x] Keep local `vendor/` ignored so dev-only packages are not committed.
- [x] Add a minimal namespaced class and PHPUnit test proving Composer autoloading works.

### Files Added

```text
composer.json
composer.lock
phpcs.xml.dist
phpstan.neon.dist
phpunit.xml.dist
src/Plugin.php
tests/Unit/PluginTest.php
tools/lint-php.php
```

### Files Changed

```text
.gitattributes
.gitignore
CHANGELOG.md
docs/implementation-status.md
```

### Files Removed

```text
None
```

### Tooling Added

- Composer PSR-4 runtime namespace: `HyperWeb\LighthouseImageOptimizer\` mapped to `src/`.
- Composer PSR-4 dev namespace: `HyperWeb\LighthouseImageOptimizer\Tests\` mapped to `tests/`.
- Composer scripts: `lint`, `cs`, `stan`, `test`, and `quality`.
- Static analyzer: PHPStan.
- Unit test runner: PHPUnit 9.6.
- Coding standards: PHPCS with WordPress Coding Standards and PHPCompatibilityWP.
- WordPress stubs: `php-stubs/wordpress-stubs`.

### Verification

```text
composer validate --strict: pass
composer install: pass after approved network access
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 1 test and 1 assertion
composer run quality: pass
```

### Acceptance Criteria

- [x] A trivial namespaced class autoloads from the plugin.
- [x] Quality commands run locally.
- [x] No dev-only vendor packages are required by the production plugin source tree.

### Artifact Policy

- `composer.json` and `composer.lock` are committed.
- `vendor/` is ignored and must not be committed during development.
- Release packaging must later generate and include the required no-dev Composer runtime autoload files so production sites do not need to run Composer.

### Deferred Work

- The plugin entry file does not load `vendor/autoload.php` yet; bootstrap loading is deferred to Subphase 0.3.
- Action Scheduler queue abstractions and workers are deferred to Phase 5.
- WordPress activation smoke testing remains pending until a WordPress 6.5+ test installation is available.
- Existing boilerplate admin/frontend asset hooks remain in place until Subphase 0.4.

## Subphase 0.3 - Harden the Bootstrap

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add constants for plugin file, path, URL, basename, version, DB version, schema version, minimum WordPress, and minimum PHP.
- [x] Add plugin header requirements for WordPress 6.5 and PHP 7.4.
- [x] Add graceful minimum-requirement handling for runtime and activation contexts.
- [x] Load the Composer autoloader after PHP/version checks.
- [x] Bundle and load Action Scheduler before `plugins_loaded` priority 0.
- [x] Keep activation/deactivation registration in the entry file.
- [x] Add pure requirement checks and unit coverage.

### Files Added

```text
libraries/action-scheduler/
src/Infrastructure/Requirements.php
tests/Unit/Infrastructure/RequirementsTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
hyperweb-lighthouse-image-optimizer.php
phpcs.xml.dist
phpstan.neon.dist
```

### Files Removed

```text
None
```

### Bootstrap Changes

- Plugin version moved from `1.0.0` to `0.1.0-alpha.3` to reflect the current pre-stable phase.
- The entry file defines stable constants for versioning, paths, URL, basename, and platform requirements.
- Unsupported PHP/WordPress versions, missing `vendor/autoload.php`, or missing Action Scheduler loader now produce a safe disabled state with an admin notice.
- Activation with unmet bootstrap requirements deactivates the plugin and displays a clear failure message.
- Composer autoload and `libraries/action-scheduler/action-scheduler.php` are loaded before the legacy plugin class runs.
- No Action Scheduler scheduling APIs are called in this subphase.

### Bundled Library

```text
Action Scheduler: 3.9.3
Source: https://github.com/woocommerce/action-scheduler/tree/3.9.3
Install method: git subtree
Path: libraries/action-scheduler/
```

Action Scheduler 3.9.3 was selected instead of 4.0.0 because this plugin targets WordPress 6.5+, while Action Scheduler 4.0.0 requires WordPress 6.8+.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 5 tests and 14 assertions
composer run quality: pass
git diff --check: pass
libraries/action-scheduler/action-scheduler.php exists: pass
No plugin-owned PHP code calls Action Scheduler as_* APIs yet: pass
vendor/ remains ignored and untracked: pass
```

### Acceptance Criteria

- [x] Unsupported environments receive a clear admin-facing activation failure or safe disabled state.
- [ ] Supported environments activate normally.
- [x] No business logic executes in the entry file.

Supported-environment activation remains pending until this plugin is run inside a WordPress 6.5+ test installation. Static and unit-level bootstrap checks passed in this plugin-only workspace.

### Deferred Work

- No settings, queue abstraction, conversion worker, diagnostics, REST endpoints, or image optimization behavior was added.
- Action Scheduler APIs must not be used until `action_scheduler_init` or a later safe hook in later phases.
- Global placeholder admin/frontend asset hooks were removed in Subphase 0.4.

## Subphase 0.4 - Remove Performance-Negative Placeholder Behavior

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Remove public CSS/JS enqueue hook registration.
- [x] Restrict admin assets to plugin-owned screens, initially none until screens exist.
- [x] Remove jQuery dependency from placeholder assets.
- [x] Remove or rewrite boilerplate comments that misdescribe final behavior.
- [x] Add policy coverage that prevents scaffold asset hooks and jQuery placeholders from returning unnoticed.

### Files Added

```text
tests/Unit/ScaffoldAssetPolicyTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
hyperweb-lighthouse-image-optimizer.php
includes/class-hyperweb-lighthouse-image-optimizer.php
admin/class-hyperweb-lighthouse-image-optimizer-admin.php
public/class-hyperweb-lighthouse-image-optimizer-public.php
phpcs.xml.dist
phpstan.neon.dist
```

### Files Removed

```text
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
admin/partials/hyperweb-lighthouse-image-optimizer-admin-display.php
public/css/hyperweb-lighthouse-image-optimizer-public.css
public/js/hyperweb-lighthouse-image-optimizer-public.js
public/partials/hyperweb-lighthouse-image-optimizer-public-display.php
```

### Hook and Asset Changes

- Removed registration of `admin_enqueue_scripts` hooks from the legacy plugin coordinator.
- Removed registration of `wp_enqueue_scripts` hooks from the legacy plugin coordinator.
- Removed all plugin-owned `wp_enqueue_style()` and `wp_enqueue_script()` calls.
- Removed placeholder jQuery-dependent JavaScript wrappers.
- Left the plugin with only the existing bootstrap-safe textdomain hook.

### Quality Coverage

- PHPCS now covers the changed legacy coordinator files in addition to the entry file, `src/`, and `tests/`.
- PHPStan now analyzes `admin/`, `includes/`, `public/`, `src/`, and `tests/`.
- PHPUnit includes a scaffold asset policy test that scans plugin-owned runtime source for global placeholder asset hooks and jQuery usage.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 7 tests and 79 assertions
composer run quality: pass
git diff --check: pass
No runtime plugin source registers placeholder asset hooks or jQuery usage: pass
```

### Acceptance Criteria

- [x] Activating the plugin adds no frontend stylesheet or script requests.
- [x] Activating the plugin adds no plugin assets to unrelated admin screens.

The acceptance criteria are demonstrated by source inspection and automated policy coverage in this plugin-only workspace. Browser-level or WordPress admin network-panel verification remains pending until a WordPress 6.5+ test installation is available.

### Deferred Work

- Real admin screens, settings pages, Media Library controls, and REST controllers are deferred to later phases.
- Frontend delivery behavior remains disabled and is deferred to Phase 7.
- Full README replacement remains deferred to Subphase 14.8.

## Subphase 1.1 - Build the Composition Root

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Promote `src/Plugin.php` into the namespaced application composition root.
- [x] Define service construction and hook registration boundaries.
- [x] Replace the legacy loader with an equivalent namespaced hook registrar.
- [x] Ensure one shared registrar instance is used for provider hook registration.

### Files Added

```text
src/Infrastructure/HookProviderInterface.php
src/Infrastructure/HookRegistrar.php
src/Infrastructure/I18n.php
tests/Unit/Infrastructure/HookRegistrarTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
hyperweb-lighthouse-image-optimizer.php
phpcs.xml.dist
src/Plugin.php
tests/Unit/PluginTest.php
```

### Files Removed

```text
includes/class-hyperweb-lighthouse-image-optimizer.php
includes/class-hyperweb-lighthouse-image-optimizer-loader.php
includes/class-hyperweb-lighthouse-image-optimizer-i18n.php
```

### Runtime Changes

- The WordPress entry file now runs `HyperWeb\LighthouseImageOptimizer\Plugin::create()->run()` after Composer and Action Scheduler load.
- `src/Plugin.php` constructs shared services and registers hook providers.
- `HookRegistrar` collects action/filter definitions and registers them in one pass.
- `I18n` is the only active hook provider and registers the existing `plugins_loaded` textdomain hook.
- No admin services, delivery services, settings repositories, queues, or image services are constructed in this subphase.

### Hook Changes

```text
plugins_loaded -> HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n::load_textdomain()
```

No frontend hooks, admin enqueue hooks, delivery hooks, Action Scheduler jobs, REST routes, settings hooks, or image-processing hooks were introduced.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 11 tests and 105 assertions
composer run quality: pass
git diff --check: pass
No runtime plugin source registers placeholder asset hooks or jQuery usage: pass
No legacy runtime core instantiation remains: pass
No delivery classes are present or active: pass
```

### Acceptance Criteria

- [x] Core modules can be registered without circular dependencies.
- [x] Admin-only classes are not unnecessarily instantiated on frontend requests.
- [x] Delivery classes are not active while delivery is disabled.

The acceptance criteria are demonstrated by the composition-root tests, hook registrar tests, source inspection, and policy scan in this plugin-only workspace. WordPress runtime activation remains pending until a WordPress 6.5+ test installation is available.

### Deferred Work

- Installation and upgrade routines were added in Subphase 1.2.
- Activation/deactivation/uninstall policy remains deferred to Subphase 1.3.
- Logging foundation remains deferred to Subphase 1.4.
- Settings, admin screens, REST controllers, queues, image conversion, and frontend delivery remain deferred to later phases.

## Subphase 1.2 - Implement Installation and Upgrade Routines

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add installer services for idempotent option initialization and upgrades.
- [x] Add the minimal settings defaults schema for `hwlio_settings`.
- [x] Add a controlled `hwlio_logs` table schema and `dbDelta()` installer.
- [x] Wire activation to the namespaced installer.
- [x] Add a runtime version/schema upgrade check on `plugins_loaded` priority `1`.
- [x] Add unit coverage for settings defaults, log table SQL, install/upgrade behavior, failure diagnostics, hook registration, and provider composition.

### Files Added

```text
src/Infrastructure/DbDeltaLogTableInstaller.php
src/Infrastructure/Installer.php
src/Infrastructure/InstallerResult.php
src/Infrastructure/LogTableInstallerInterface.php
src/Infrastructure/OptionStoreInterface.php
src/Infrastructure/UpgradeRunner.php
src/Infrastructure/WordPressOptionStore.php
src/Logging/LogTableSchema.php
src/Settings/SettingsSchema.php
tests/Unit/Infrastructure/FakeLogTableInstaller.php
tests/Unit/Infrastructure/FakeOptionStore.php
tests/Unit/Infrastructure/InstallerTest.php
tests/Unit/Infrastructure/UpgradeRunnerTest.php
tests/Unit/Logging/LogTableSchemaTest.php
tests/Unit/Settings/SettingsSchemaTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
includes/class-hyperweb-lighthouse-image-optimizer-activator.php
phpcs.xml.dist
src/Plugin.php
tests/Unit/PluginTest.php
```

### Files Removed

```text
None
```

### Options and Database Changes

- `hwlio_settings` is initialized from `Settings\SettingsSchema::defaults()` and remains autoloaded.
- `hwlio_version` stores the current plugin version.
- `hwlio_db_version` stores the current database schema version.
- `hwlio_activation_state` stores bounded setup diagnostics with autoload disabled.
- `{$wpdb->prefix}hwlio_logs` is created through `dbDelta()` when WordPress database APIs are available.

### Log Table Columns and Indexes

```text
id bigint(20) unsigned AUTO_INCREMENT
created_at_gmt datetime
level varchar(20)
code varchar(64)
message text
attachment_id bigint(20) unsigned NULL
job_id varchar(191) NULL
context_json longtext NULL

Indexes: created_at_gmt, level, attachment_id
```

### Runtime Changes

- Activation now calls `HyperWeb\LighthouseImageOptimizer\Infrastructure\Installer::for_wordpress()->install()`.
- The composition root now registers `UpgradeRunner` before `I18n`.
- `UpgradeRunner` checks stored version/schema state on `plugins_loaded` priority `1` and reruns the installer only when needed.
- Missing or unverifiable log-table creation is recorded as an activation-state warning and does not fatal or deactivate the plugin.
- Invalid non-array settings are repaired back to defaults and recorded as an activation-state warning.

### Hook Changes

```text
plugins_loaded priority 1  -> HyperWeb\LighthouseImageOptimizer\Infrastructure\UpgradeRunner::maybe_upgrade()
plugins_loaded priority 10 -> HyperWeb\LighthouseImageOptimizer\Infrastructure\I18n::load_textdomain()
```

No media scans, image processing hooks, Action Scheduler jobs, REST routes, frontend delivery hooks, admin enqueue hooks, or asset hooks were introduced.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 20 tests and 216 assertions
composer run quality: pass
git diff --check: pass
No plugin-owned PHP code calls Action Scheduler as_* APIs yet: pass
No runtime plugin source registers placeholder asset hooks or jQuery usage: pass
No media scan, queue, delivery, or conversion classes are active: pass
```

### Acceptance Criteria

- [x] Activation initializes required options safely.
- [x] Upgrades rerun idempotently when stored versions or schemas are stale.
- [x] The initial log table schema is controlled and installed through `dbDelta()`.
- [x] Log table creation failures are non-fatal and recorded in activation diagnostics.
- [x] Existing settings values are preserved while missing defaults are filled.
- [x] Invalid settings are repaired and recorded.
- [ ] Supported WordPress activation creates the table without fatal errors.

Supported-environment activation and database verification remain pending until this plugin is run inside a WordPress 6.5+ test installation.

### Deferred Work

- Activation/deactivation/uninstall retention policy was added in Subphase 1.3.
- Log writing, retention cleanup, and diagnostics views remain deferred to Subphase 1.4 and later.
- `hwlio_statistics_cache` remains deferred until a statistics service exists.
- Settings validation/repository behavior remains deferred to Phase 2.1.
- No Action Scheduler queue jobs, image conversion, media scans, REST endpoints, admin screens, frontend delivery, Elementor integration, or WooCommerce integration were added.

## Subphase 1.3 - Implement Activation, Deactivation, and Uninstall Policy

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Keep activation setup-only and avoid Media Library scans.
- [x] Add non-destructive deactivation cleanup for plugin-owned recurring maintenance hooks.
- [x] Add opt-in uninstall cleanup policy based on saved settings.
- [x] Preserve settings, logs, metadata, and derivatives by default on uninstall.
- [x] Add safe derivative cleanup routines that never delete source/original paths.
- [x] Add bounded multisite uninstall orchestration.
- [x] Add unit coverage for default preservation, explicit cleanup, invalid settings, derivative path safety, original preservation, and multisite batching.

### Files Added

```text
src/Infrastructure/ActionSchedulerScheduledActionCleaner.php
src/Infrastructure/Deactivator.php
src/Infrastructure/DerivativeCleanup.php
src/Infrastructure/DerivativeCleanupInterface.php
src/Infrastructure/DerivativeManifestProviderInterface.php
src/Infrastructure/FilesystemInterface.php
src/Infrastructure/LifecyclePolicy.php
src/Infrastructure/LifecycleResult.php
src/Infrastructure/NetworkUninstaller.php
src/Infrastructure/PluginDataCleanerInterface.php
src/Infrastructure/ScheduledActionCleanerInterface.php
src/Infrastructure/Uninstaller.php
src/Infrastructure/WordPressDerivativeManifestProvider.php
src/Infrastructure/WordPressFilesystem.php
src/Infrastructure/WordPressPluginDataCleaner.php
tests/Unit/Infrastructure/DeactivatorTest.php
tests/Unit/Infrastructure/DerivativeCleanupTest.php
tests/Unit/Infrastructure/FakeDerivativeCleanup.php
tests/Unit/Infrastructure/FakeDerivativeManifestProvider.php
tests/Unit/Infrastructure/FakeFilesystem.php
tests/Unit/Infrastructure/FakePluginDataCleaner.php
tests/Unit/Infrastructure/FakeScheduledActionCleaner.php
tests/Unit/Infrastructure/NetworkUninstallerTest.php
tests/Unit/Infrastructure/UninstallerTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
hyperweb-lighthouse-image-optimizer.php
includes/class-hyperweb-lighthouse-image-optimizer-deactivator.php
phpcs.xml.dist
phpstan.neon.dist
src/Infrastructure/OptionStoreInterface.php
src/Infrastructure/WordPressOptionStore.php
tests/Unit/Infrastructure/FakeOptionStore.php
uninstall.php
```

### Files Removed

```text
None
```

### Lifecycle Changes

- Deactivation loads Composer and Action Scheduler opportunistically, then delegates to `Infrastructure\Deactivator`.
- `ActionSchedulerScheduledActionCleaner` unschedules only known plugin-owned recurring maintenance hooks in the `hwlio` group.
- Default uninstall preserves all plugin options, logs, metadata, and derivative files.
- Explicit `delete_derivatives_on_uninstall` runs derivative cleanup against attachment-owned `_hwlio_derivatives` metadata.
- Explicit `delete_data_on_uninstall` deletes plugin-owned options, the plugin log table, and plugin-owned attachment meta keys.
- Invalid or missing uninstall settings fall back to safe defaults and preserve everything.
- Multisite uninstall uses bounded site batches and restores the previous blog after each site.

### Owned Identifiers

```text
Action Scheduler group: hwlio
Maintenance hooks:
- hwlio_cleanup_logs
- hwlio_recover_stale_locks
- hwlio_reconcile_statistics

Attachment meta keys:
- _hwlio_derivatives
- _hwlio_status
- _hwlio_excluded
- _hwlio_lock
```

### Safety Rules Added

- Derivative cleanup accepts only uploads-relative metadata paths.
- Absolute paths, traversal segments, null bytes, symlinks, directories, missing files, and files resolving outside uploads are rejected.
- Source/original paths listed in derivative metadata are preserved even if malformed format entries point to them.
- No recursive directory deletion is performed.
- No core attachment metadata is modified.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 29 tests and 334 assertions
composer run quality: pass
git diff --check: pass
No media scan, queue worker, conversion, REST, frontend delivery, or admin asset hooks introduced: pass
Only plugin-owned Action Scheduler unscheduling call introduced: pass
No original deletion path introduced: pass
```

### Acceptance Criteria

- [x] Activation performs no Media Library scan.
- [x] Deactivation preserves all files and metadata.
- [x] Default uninstall preserves derivatives and settings unless configured otherwise.
- [x] Destructive uninstall tests confirm originals survive.
- [ ] Supported WordPress deactivation/uninstall smoke tests pass.

Supported-environment deactivation/uninstall smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation.

### Deferred Work

- Scheduling actual recurring maintenance actions remains deferred to later queue/logging phases.
- Log writing, retention cleanup, and diagnostics views remain deferred to Subphase 1.4 and later.
- Attachment deletion cleanup remains deferred to Subphase 4.5.
- Full settings validation/repository behavior remains deferred to Phase 2.1.
- No Action Scheduler queue jobs, image conversion, media scans, REST endpoints, admin screens, frontend delivery, Elementor integration, or WooCommerce integration were added.
