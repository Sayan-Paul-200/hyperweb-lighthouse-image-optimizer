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
- A bounded logging foundation, database writer, sanitizer, and log-retention maintenance provider exist as of Subphase 1.4.
- A schema-driven settings repository, sanitizer, result object, and typed settings accessors exist as of Subphase 2.1.
- WordPress Settings API registration, save-time sanitization, admin validation feedback, and minimal format-support guarding exist as of Subphase 2.2.
- A canonical environment capability layer for versions, image editor candidates, WebP/AVIF support, uploads status, runtime limits, and Action Scheduler readiness exists as of Subphase 2.3.
- A structured diagnostics framework with user-safe result objects, environment checks, temporary write/rename checks, and sample conversion diagnostics exists as of Subphase 2.4.
- Read-only source image value objects and a collector for attachment full, subsize, and original-image sources exist as of Subphase 3.1.
- Source MIME and animation validation primitives exist as of Subphase 3.2.
- Deterministic uploads-safe destination path resolution exists as of Subphase 3.3.
- Conversion result models, byte-savings calculations, and a stable conversion error/skip taxonomy exist as of Subphase 3.4.
- A callable WordPress image-editor converter with bounded temp output, validation, cleanup, and atomic sidecar moves exists as of Subphase 3.5.
- A pre-allocation resource guard exists as of Subphase 3.6.
- A pure-domain conversion policy service exists as of Subphase 3.7.
- Service-only attachment fingerprinting exists as of Subphase 4.1.
- A derivative repository for plugin-owned `_hwlio_derivatives` manifests and `_hwlio_status` summaries exists as of Subphase 4.2.
- Token-protected attachment locking, bounded stale-lock recovery, and token-safe lock diagnostics exist as of Subphase 4.3.
- A callable attachment processor for one-format, cursor-aware conversion batches exists as of Subphase 4.4.
- No visible settings UI, diagnostics UI, REST diagnostics endpoint, queue abstraction, runtime image optimization hooks, or frontend delivery exists yet.

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
  - [x] Subphase 1.4 - Implement logging foundation
- [ ] Phase 2 - Settings, Environment, and Diagnostics Foundation
  - [x] Subphase 2.1 - Settings schema and repository
  - [x] Subphase 2.2 - Settings API registration
  - [x] Subphase 2.3 - Environment and format support
  - [x] Subphase 2.4 - Diagnostics framework
- [ ] Phase 3 - Core Image Domain
  - [x] Subphase 3.1 - Source image value objects and collector
  - [x] Subphase 3.2 - MIME and animation validation
  - [x] Subphase 3.3 - Destination resolver
  - [x] Subphase 3.4 - Conversion result model and error taxonomy
  - [x] Subphase 3.5 - Converter implementation
  - [x] Subphase 3.6 - Resource guard
  - [x] Subphase 3.7 - Conversion policy
- [ ] Phase 4 - Attachment State, Metadata, and Cleanup
  - [x] Subphase 4.1 - Attachment fingerprint
  - [x] Subphase 4.2 - Derivative repository
  - [x] Subphase 4.3 - Attachment locking
  - [x] Subphase 4.4 - Attachment processor
  - [ ] Subphase 4.5 - Cleanup on attachment deletion
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
Phase 1 implementation subphases are complete. The phase remains unchecked at the phase level until supported WordPress activation, deactivation, uninstall, Action Scheduler, and log-table smoke tests are performed.

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

- Scheduling actual recurring maintenance actions for non-logging maintenance remains deferred to later queue/diagnostics phases.
- Diagnostics views remain deferred to later admin and REST phases.
- Attachment deletion cleanup remains deferred to Subphase 4.5.
- Settings API registration and UI remain deferred to Phase 2.2.
- No Action Scheduler queue jobs, image conversion, media scans, REST endpoints, admin screens, frontend delivery, Elementor integration, or WooCommerce integration were added.

## Subphase 1.4 - Implement Logging Foundation

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add logger contracts and a database-backed writer for the existing `hwlio_logs` table.
- [x] Define supported levels and stable machine-readable code normalization.
- [x] Redact sensitive context keys and likely absolute filesystem paths before storage.
- [x] Bound log messages and context payloads before writing `context_json`.
- [x] Add bounded log-retention cleanup based on `log_retention_days`.
- [x] Schedule retention cleanup through Action Scheduler only after `action_scheduler_init`.
- [x] Add unit and policy coverage for logging behavior and subphase scope.

### Files Added

```text
src/Logging/ActionSchedulerRecurringActionScheduler.php
src/Logging/DatabaseLogWriter.php
src/Logging/LogCode.php
src/Logging/LogDatabaseInterface.php
src/Logging/LogEntry.php
src/Logging/LogLevel.php
src/Logging/LogMaintenance.php
src/Logging/LogPruner.php
src/Logging/LogPrunerInterface.php
src/Logging/LogSanitizer.php
src/Logging/LogWriterInterface.php
src/Logging/Logger.php
src/Logging/LoggerInterface.php
src/Logging/NullLogDatabase.php
src/Logging/RecurringActionSchedulerInterface.php
src/Logging/WordPressLogDatabase.php
tests/Unit/Logging/DatabaseLogWriterTest.php
tests/Unit/Logging/FakeLogDatabase.php
tests/Unit/Logging/FakeLogPruner.php
tests/Unit/Logging/FakeLogWriter.php
tests/Unit/Logging/FakeRecurringActionScheduler.php
tests/Unit/Logging/LogCodeTest.php
tests/Unit/Logging/LogLevelTest.php
tests/Unit/Logging/LogMaintenanceTest.php
tests/Unit/Logging/LogPrunerTest.php
tests/Unit/Logging/LogSanitizerTest.php
tests/Unit/Logging/LoggerTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Plugin.php
tests/Unit/PluginTest.php
```

### Files Removed

```text
None
```

### Logging Services

- `LoggerInterface` exposes `log()`, `debug()`, `info()`, `warning()`, and `error()`.
- `Logger` normalizes, sanitizes, and writes entries while returning `false` instead of throwing on failures.
- `LogLevel` supports `debug`, `info`, `warning`, and `error`; invalid levels normalize to `error`.
- `LogCode` accepts lowercase machine-readable codes up to 64 characters; invalid codes normalize to `unknown`.
- `LogSanitizer` redacts sensitive keys such as password, token, secret, cookie, nonce, authorization, and API key variants.
- `LogSanitizer` redacts likely absolute Unix and Windows server paths from messages, context values, and job IDs.
- `DatabaseLogWriter` writes sanitized entries to `{$wpdb->prefix}hwlio_logs` through a narrow database adapter.
- `WordPressLogDatabase` validates table identifiers before inserts or retention deletes.
- `NullLogDatabase` fails safely when WordPress database services are unavailable.

### Hook Changes

```text
action_scheduler_init priority 10 -> HyperWeb\LighthouseImageOptimizer\Logging\LogMaintenance::ensure_scheduled()
hwlio_cleanup_logs priority 10    -> HyperWeb\LighthouseImageOptimizer\Logging\LogMaintenance::run_retention_cleanup()
```

`Plugin::create()` now composes providers in this order:

```text
UpgradeRunner
LogMaintenance
I18n
```

### Retention Behavior

- The canonical cleanup hook remains `hwlio_cleanup_logs`.
- The Action Scheduler group remains `hwlio`.
- `LogMaintenance` schedules one unique recurring cleanup action at a daily interval.
- Scheduling happens only from `action_scheduler_init`; no Action Scheduler APIs are called during bootstrap composition.
- `LogPruner` deletes logs older than `hwlio_settings['log_retention_days']`, defaulting to `30`.
- Invalid retention settings fall back to `30`.
- Each cleanup run deletes at most `500` rows.

### Verification

```text
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 50 tests and 1068 assertions
```

Additional scans are run as part of `LoggingScopePolicyTest` and confirm no REST routes, media conversion hooks, frontend delivery hooks, image editor conversion calls, optimization queue actions, or single/async Action Scheduler queue scheduling were introduced.

### Acceptance Criteria

- [x] Errors can be recorded without breaking the main operation.
- [x] Retention removes old rows in bounded batches.
- [x] Logs are sanitized before storage so absolute server paths are not persisted for later admin REST output.
- [ ] Supported WordPress runtime logging and Action Scheduler smoke tests pass.

Supported-environment logging, retention scheduling, and database-write smoke tests remain pending until this plugin is run inside a WordPress 6.5+ test installation.

### Deferred Work

- No admin log viewer, diagnostics REST endpoint, or log pagination was added.
- No image conversion, media scans, queue abstraction, automatic upload optimization, frontend delivery, Elementor integration, or WooCommerce integration was added.
- Settings API registration and UI remain deferred to Phase 2.2.
- Logging reads and admin diagnostics remain deferred to Subphase 6.8 and later diagnostics phases.

## Subphase 2.1 - Settings Schema and Repository

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Define all initial settings, types, defaults, groups, capabilities, descriptions, sanitizers, and validation rules in one schema.
- [x] Add a schema-driven settings sanitizer and repository.
- [x] Add filtered defaults through `hwlio_default_settings` with normalization after filtering.
- [x] Add immutable-style default merging and typed getters for current settings.
- [x] Refactor installer, uninstall policy, and log pruning to read settings through the repository.
- [x] Keep Settings API registration, UI, REST, environment checks, queueing, conversion, and delivery deferred.

### Files Added

```text
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
src/Settings/SettingsResult.php
src/Settings/SettingsSanitizer.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsSanitizerTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
tests/Unit/Settings/SettingsTestFilterShim.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Infrastructure/Installer.php
src/Infrastructure/Uninstaller.php
src/Logging/LogPruner.php
src/Settings/SettingsSchema.php
tests/Unit/Logging/LogPrunerTest.php
tests/Unit/Settings/SettingsSchemaTest.php
```

### Files Removed

```text
None
```

### Settings Schema

- `SettingsSchema::definitions()` now exposes metadata for every initial setting.
- Each setting has type, default, group, capability, description, sanitizer, validation rule, and internal/public metadata.
- `SettingsSchema::defaults()` remains the single source of default values and applies `hwlio_default_settings`.
- Filtered defaults are sanitized after filtering, unknown keys are dropped, and `schema_version` is forced to `1`.

### Repository API

```text
SettingsRepositoryInterface::read()
SettingsRepositoryInterface::ensure()
SettingsRepositoryInterface::save()
SettingsRepositoryInterface::all()
SettingsRepositoryInterface::get()
```

Typed accessors added for automatic optimization, delivery, enabled formats, format preference, per-format quality, minimum savings, retry limit, worker budget, queue concurrency, log retention, and uninstall cleanup settings.

### Sanitization Policy

- Unknown keys are dropped.
- Missing keys receive defaults.
- Booleans accept common WordPress-style truthy and falsy values.
- Format arrays accept only `webp` and `avif`, de-duplicate values, and fall back to defaults when empty.
- Numeric values are clamped:
  - quality: `1-100`
  - minimum savings percent: `0-100`
  - max retries: `0-10`
  - worker time budget: `1-120`
  - queue concurrency: `1-5`
  - log retention days: `1-3650`

### Consumer Refactors

- `Installer` now uses `SettingsRepository::ensure()` for settings initialization, upgrade merging, and invalid settings repair.
- `Installer::OPTION_SETTINGS` now points to `SettingsRepository::OPTION_NAME`.
- `Uninstaller` uses repository typed getters and still preserves data and derivatives when stored settings are invalid.
- `LogPruner` reads retention through `SettingsRepositoryInterface::log_retention_days()`.

### Verification

```text
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 61 tests and 2110 assertions
```

Additional source scans confirm only `WordPressOptionStore` wraps `get_option()`, runtime settings consumers no longer parse raw `hwlio_settings` arrays directly, and no Settings API registration, REST routes, admin/frontend assets, queue hooks, image conversion hooks, or delivery hooks were introduced.

### Acceptance Criteria

- [x] Invalid values cannot enter persisted settings through the repository.
- [x] Missing keys receive defaults after upgrades.
- [x] Feature modules read settings through the repository instead of scattered raw `get_option()` calls.
- [ ] Settings save successfully on single-site WordPress through the future Settings API.

WordPress runtime settings persistence remains pending until Settings API registration is added in Subphase 2.2 and this plugin is run inside a WordPress 6.5+ test installation.

### Deferred Work

- No Settings API registration, admin settings screen, validation feedback UI, REST endpoint, diagnostics UI, environment support detection, queue behavior, image conversion, frontend delivery, Elementor integration, or WooCommerce integration was added in Subphase 2.1.
- WebP/AVIF server support checks were implemented in Subphase 2.3.
- The diagnostics framework was implemented in Subphase 2.4; diagnostics UI and REST exposure remain deferred.

## Subphase 2.2 - Settings API Registration

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Register `hwlio_settings` with the WordPress Settings API.
- [x] Add Settings API wrapper services so registration behavior can be unit-tested without WordPress bootstrap.
- [x] Register the canonical option group and six basic settings sections.
- [x] Connect the Settings API sanitize callback to the schema-driven settings sanitizer.
- [x] Add capability enforcement and Settings API validation feedback.
- [x] Add minimal save-time WebP/AVIF support guarding through WordPress image APIs.
- [x] Keep visible settings screens, REST endpoints, assets, queues, image conversion, and delivery deferred.

### Files Added

```text
src/Settings/FormatSupportCheckerInterface.php
src/Settings/SettingsApiInterface.php
src/Settings/SettingsApiRegistrar.php
src/Settings/WordPressFormatSupportChecker.php
src/Settings/WordPressSettingsApi.php
tests/Unit/Settings/FakeFormatSupportChecker.php
tests/Unit/Settings/FakeSettingsApi.php
tests/Unit/Settings/SettingsApiRegistrarTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Plugin.php
tests/Unit/PluginTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Files Removed

```text
None
```

### Settings API Registration

- `SettingsApiRegistrar` is registered by the composition root and hooks only into `admin_init`.
- Option group: `hwlio_settings`.
- Option name: `hwlio_settings`.
- Settings page slug reserved for future UI: `hwlio-settings`.
- `register_setting()` uses `type => array`, `show_in_rest => false`, schema defaults, and a schema-backed sanitize callback.
- Basic sections are registered for general, formats and quality, processing, delivery, logging and cleanup, and advanced exclusions.
- No admin menu page, submenu page, settings field rendering, scripts, styles, or visible settings screen was added.

### Validation and Guard Behavior

- Saves require `manage_options`.
- Unauthorized saves return the existing sanitized settings and add a settings error.
- Malformed non-array payloads preserve existing settings and add a settings error.
- Unknown keys are dropped, missing keys are filled, booleans are normalized, numeric values are clamped, and format arrays are allowlisted through `SettingsSanitizer`.
- Unsupported `enabled_formats` entries are removed when WordPress image APIs conclusively report no support.
- If every submitted enabled format is unsupported, the previous persisted enabled formats are preserved and a settings error is recorded.
- `format_preference` remains an ordering preference; full environment support diagnostics are deferred to Subphase 2.3.

### Hook Changes

```text
admin_init priority 10 -> HyperWeb\LighthouseImageOptimizer\Settings\SettingsApiRegistrar::register_settings()
```

`Plugin::create()` now composes providers in this order:

```text
UpgradeRunner
SettingsApiRegistrar
LogMaintenance
I18n
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 68 tests and 2473 assertions
composer run quality: pass
git diff --check: pass
```

Additional source scans confirm only `WordPressSettingsApi` wraps Settings API globals, no visible settings UI or settings fields were introduced, no REST routes or REST hooks were added, no admin/frontend assets were added, and no queue, media scan, image conversion, or delivery hooks were introduced.

### Acceptance Criteria

- [x] Only authorized administrators can save settings through the Settings API sanitizer.
- [x] Malformed arrays, quality values, and enums are rejected, normalized, or preserved safely.
- [x] Unsupported enabled formats are guarded when support can be determined through WordPress image APIs.
- [ ] Settings save successfully on single-site WordPress.

Single-site WordPress settings-save smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation.

### Deferred Work

- No visible settings page, admin menu, field renderer, or asset loading was added.
- WebP/AVIF environment diagnostics, editor reporting, upload-directory checks, and Action Scheduler readiness diagnostics were implemented in Subphase 2.3.
- The diagnostics framework was implemented in Subphase 2.4; diagnostics UI and REST exposure remain deferred.
- No media scans, image conversion, queue jobs, REST endpoints, frontend delivery, Elementor integration, or WooCommerce integration was added.

## Subphase 2.3 - Environment and Format Support

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add testable environment inspection services.
- [x] Detect current PHP and WordPress versions against configured minimums.
- [x] Detect active WordPress image editor candidates and class availability.
- [x] Detect WebP and AVIF support independently through WordPress image APIs.
- [x] Distinguish MIME recognition from image-editor encoding support.
- [x] Inspect uploads base directory, upload errors, and writability without creating files.
- [x] Parse memory limit and max execution time without raising limits.
- [x] Detect Action Scheduler loaded and initialized state without scheduling or querying jobs.
- [x] Replace the settings-local format checker with the canonical environment provider.
- [x] Keep diagnostics UI, REST, temporary write/rename tests, sample conversion, queues, media scans, conversion, and delivery deferred.

### Files Added

```text
src/Infrastructure/ActionSchedulerStatus.php
src/Infrastructure/EnvironmentInspector.php
src/Infrastructure/EnvironmentProbeInterface.php
src/Infrastructure/EnvironmentReport.php
src/Infrastructure/FormatSupportProviderInterface.php
src/Infrastructure/FormatSupportResult.php
src/Infrastructure/MemoryLimit.php
src/Infrastructure/RuntimeConstraints.php
src/Infrastructure/UploadsStatus.php
src/Infrastructure/WordPressEnvironmentProbe.php
tests/Unit/Infrastructure/EnvironmentInspectorTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Infrastructure/FakeEnvironmentProbe.php
tests/Unit/Settings/FakeFormatSupportProvider.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Settings/SettingsApiRegistrar.php
tests/Unit/Settings/SettingsApiRegistrarTest.php
```

### Files Removed

```text
src/Settings/FormatSupportCheckerInterface.php
src/Settings/WordPressFormatSupportChecker.php
tests/Unit/Settings/FakeFormatSupportChecker.php
```

### Environment Services

- `EnvironmentInspector` builds an aggregate `EnvironmentReport` from a probe seam.
- `WordPressEnvironmentProbe` wraps WordPress/PHP runtime reads for versions, image editors, MIME recognition, encode support, uploads, runtime limits, and Action Scheduler readiness.
- `FormatSupportResult` status values are `supported`, `unsupported`, `misconfigured`, and `unknown`.
- WebP and AVIF support are evaluated separately.
- `UploadsStatus` reports `available`, `error`, `missing`, `not_writable`, or `unknown`.
- `RuntimeConstraints` parses memory and execution limits without calling `ini_set()`.
- `ActionSchedulerStatus` reports missing, loaded-not-initialized, ready, or unknown states without invoking scheduling APIs.

### Settings Integration

- `SettingsApiRegistrar` now depends on `FormatSupportProviderInterface`.
- Production settings registration uses `EnvironmentInspector::for_wordpress()`.
- `enabled_formats` blocks formats whose canonical support status is `unsupported` or `misconfigured`.
- `unknown` support preserves Subphase 2.2 behavior and does not block saving.
- `format_preference` remains ordering metadata only.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass, 244 files
composer run cs: pass
composer run stan: pass
composer run test: pass, 80 tests and 3677 assertions
composer run quality: pass
git diff --check: pass
```

Additional source scans confirm no `wp_get_image_editor()` calls, temporary-file conversion checks, REST routes, admin/frontend assets, media hooks, async/single queue scheduling, frontend delivery hooks, or automatic memory-limit raising were introduced.

### Acceptance Criteria

- [x] WebP and AVIF statuses are independent.
- [x] A server without AVIF remains fully usable for WebP when WebP is supported.
- [x] Missing support functions/extensions produce unknown or unsupported/misconfigured status without PHP warnings.
- [ ] Supported WordPress runtime environment smoke testing passes.

Runtime smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation.

### Deferred Work

- Structured diagnostic result objects, pass/warning/fail/info mapping, user-safe diagnostic messages, temporary write/rename tests, and sample conversion cleanup were deferred from Subphase 2.3 and implemented in Subphase 2.4.
- REST exposure remains deferred to the later admin/REST phase.
- No persistent options, database tables, metadata keys, scheduled actions, queue jobs, media scans, image conversion, frontend delivery, Elementor integration, or WooCommerce integration was added.

## Subphase 2.4 - Diagnostics Framework

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add structured diagnostic result objects with `pass`, `warning`, `fail`, and `info` statuses.
- [x] Add REST/admin-ready array serialization without registering REST routes or rendering HTML.
- [x] Sanitize diagnostic messages and details to redact secrets and absolute filesystem paths.
- [x] Map environment reports into PHP, WordPress, editor, format-support, uploads, runtime, and Action Scheduler checks.
- [x] Add temporary write/rename diagnostics with plugin-prefixed files and guaranteed cleanup attempts.
- [x] Add sample WebP/AVIF conversion diagnostics using WordPress image editor APIs behind a focused probe.
- [x] Keep diagnostics UI, REST endpoints, queue health execution, media scans, production conversion, metadata writes, scheduled actions, and delivery deferred.

### Files Added

```text
src/Diagnostics/DiagnosticFilesystemInterface.php
src/Diagnostics/DiagnosticReport.php
src/Diagnostics/DiagnosticResult.php
src/Diagnostics/DiagnosticSanitizer.php
src/Diagnostics/DiagnosticStatus.php
src/Diagnostics/EnvironmentDiagnostics.php
src/Diagnostics/SampleConversionDiagnostic.php
src/Diagnostics/SampleConversionProbeInterface.php
src/Diagnostics/SampleConversionResult.php
src/Diagnostics/TemporaryFileDiagnostic.php
src/Diagnostics/WordPressDiagnosticFilesystem.php
src/Diagnostics/WordPressSampleConversionProbe.php
tests/Unit/Diagnostics/DiagnosticReportTest.php
tests/Unit/Diagnostics/DiagnosticResultTest.php
tests/Unit/Diagnostics/DiagnosticSanitizerTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Diagnostics/EnvironmentDiagnosticsTest.php
tests/Unit/Diagnostics/FakeDiagnosticFilesystem.php
tests/Unit/Diagnostics/FakeSampleConversionProbe.php
tests/Unit/Diagnostics/SampleConversionDiagnosticTest.php
tests/Unit/Diagnostics/TemporaryFileDiagnosticTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Diagnostics Services

- `DiagnosticResult` and `DiagnosticReport` provide stable result IDs, statuses, codes, labels, messages, details, summaries, and `to_array()` output for future REST/admin consumers.
- `DiagnosticSanitizer` redacts sensitive keys and absolute Unix/Windows filesystem paths from diagnostic messages and details.
- `EnvironmentDiagnostics::for_wordpress()` creates callable PHP diagnostics without registering hooks.
- `EnvironmentDiagnostics::run()` consumes `EnvironmentInspector`, settings, temporary-file diagnostics, and sample-conversion diagnostics.
- `TemporaryFileDiagnostic` validates plugin-owned paths inside uploads before writing, renaming, or deleting.
- `SampleConversionDiagnostic` writes a tiny plugin-owned PNG fixture, calls a conversion probe, validates output, and removes source/output files on success and failure.
- `WordPressSampleConversionProbe` is the only new production class that calls `wp_get_image_editor()`, and only for the required sample conversion diagnostic.

### Hooks, Settings, Metadata, and Database Changes

```text
New hooks: none
New settings: none
New options: none
New tables: none
New metadata keys: none
New scheduled actions: none
REST routes: none
Admin menus/assets: none
Frontend hooks/assets: none
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 103 tests and 5530 assertions
composer run quality: pass
git diff --check: pass
```

Source scans confirm no REST routes, REST hooks, admin menus/assets, frontend delivery hooks, media upload hooks, attachment metadata writes, optimization queue actions, async/single optimization scheduling, or memory-limit mutation were introduced. `wp_get_image_editor()` appears only in `src/Diagnostics/WordPressSampleConversionProbe.php`.

### Acceptance Criteria

- [x] Diagnostics are callable from PHP and serialize to REST/admin-ready arrays without rendering HTML.
- [x] Temporary files are removed on success and failure when cleanup succeeds.
- [x] Cleanup failures are reported as structured diagnostics without exposing raw paths.
- [x] Result messages and details are sanitized for secrets and absolute filesystem paths.
- [x] Sample conversion uses WordPress image APIs behind a diagnostics-only probe.
- [ ] Supported WordPress runtime sample-conversion smoke testing passes.

Runtime smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation with writable uploads and available image editors.

### Deferred Work

- Diagnostics UI, diagnostics REST endpoints, and admin rendering remain deferred to Phase 6.
- Queue health, stale locks, conflict detection, persistent object cache interpretation, multisite policy warnings, and offload/CDN adapter diagnostics remain deferred until their owning services exist.
- No persistent audit-results table, production image conversion, media scanning, attachment metadata writes, frontend delivery, Elementor integration, or WooCommerce integration was added.

## Subphase 3.1 - Source Image Value Objects and Collector

**Status:** Complete
**Completed:** 2026-07-09

### Tasks

- [x] Add normalized source image value objects for full, subsize, and original-image records.
- [x] Add read-only attachment source and image file probe seams.
- [x] Add WordPress-backed adapters for `get_attached_file()`, `wp_get_attachment_metadata()`, uploads base lookup, MIME detection, dimensions, bytes, and modified time.
- [x] Collect current full display file, metadata sizes, and `original_image` relationship.
- [x] Resolve basename-only subsizes relative to the metadata full-file directory.
- [x] Reject unsafe, missing, unreadable, duplicate, and outside-uploads candidates as non-fatal collection issues.
- [x] Keep MIME allowlist decisions, animation checks, destination resolution, conversion, queueing, hooks, metadata writes, REST, UI, and delivery deferred.

### Files Added

```text
src/Image/AttachmentSourceProviderInterface.php
src/Image/ImageFileProbeInterface.php
src/Image/SourceCollector.php
src/Image/SourceImage.php
src/Image/SourceImageCollection.php
src/Image/SourceImageIssue.php
src/Image/WordPressAttachmentSourceProvider.php
src/Image/WordPressImageFileProbe.php
tests/Unit/Image/FakeAttachmentSourceProvider.php
tests/Unit/Image/FakeImageFileProbe.php
tests/Unit/Image/ImageScopePolicyTest.php
tests/Unit/Image/SourceCollectorTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
```

### Image Source Domain

- `SourceImage` stores attachment ID, WordPress size name, role, uploads-relative path, internal absolute path, MIME, dimensions, bytes, and modified time.
- `SourceImage::to_array()` intentionally omits the internal absolute path.
- `SourceImageCollection` returns valid sources and non-fatal issues together.
- `SourceImageIssue` records stable collection issue codes without exposing absolute filesystem paths in serialized output.
- `SourceCollector` is a callable service only and is not composed into plugin hooks.
- `WordPressAttachmentSourceProvider` uses read-only WordPress attachment APIs.
- `WordPressImageFileProbe` reads file facts after collector path validation and does not load or convert images.

### Hooks, Settings, Metadata, and Database Changes

```text
New hooks: none
New settings: none
New options: none
New tables: none
New metadata keys: none
Metadata writes: none
Scheduled actions: none
REST routes: none
Admin menus/assets: none
Frontend hooks/assets: none
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 115 tests and 6208 assertions
composer run quality: pass
git diff --check: pass
```

Source scans confirm `src/Image/` does not call `wp_get_image_editor()`, does not write attachment metadata, does not register REST routes/hooks, does not enqueue assets, does not schedule queue jobs, and does not introduce frontend delivery hooks.

### Acceptance Criteria

- [x] Fixture attachments with multiple sizes produce the expected normalized list.
- [x] A missing thumbnail does not invalidate the whole attachment.
- [x] Files outside uploads are rejected.
- [x] Traversal, absolute metadata paths, URL-like paths, null bytes, and realpaths outside uploads are rejected.
- [x] Collection issues serialize without exposing absolute paths.
- [ ] WordPress runtime source collection smoke testing passes.

Runtime smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation with representative attachment metadata.

### Deferred Work

- MIME allowlist validation and animated GIF/WebP detection were implemented in Subphase 3.2.
- Destination path resolution, temporary output paths, and sidecar naming remain deferred to Subphase 3.3.
- Conversion result taxonomy, converter implementation, resource guard, and conversion policy remain deferred to later Phase 3 subphases.
- No production image conversion, media hooks, queue jobs, REST endpoints, admin UI, frontend delivery, Elementor integration, or WooCommerce integration was added.

## Subphase 3.2 - MIME and Animation Validation

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add read-only source MIME and animation validation services.
- [x] Re-detect source MIME from file contents through the file probe instead of trusting filenames or metadata.
- [x] Centralize the initial source MIME policy for JPEG, PNG, and non-animated WebP.
- [x] Detect animated GIF and animated WebP safely enough to skip them before conversion phases.
- [x] Reject corrupt, unknown, unsupported, or MIME-mismatched sources with typed result codes.
- [x] Keep destination resolution, conversion, metadata writes, queues, REST, UI, and delivery deferred.

### Files Added

```text
src/Image/AnimationDetectorInterface.php
src/Image/AnimationStatus.php
src/Image/FileAnimationDetector.php
src/Image/SourceImageValidationCollection.php
src/Image/SourceImageValidationResult.php
src/Image/SourceImageValidator.php
src/Image/SourceMimePolicy.php
tests/Unit/Image/FakeAnimationDetector.php
tests/Unit/Image/FileAnimationDetectorTest.php
tests/Unit/Image/SourceImageValidatorTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
tests/Unit/Image/ImageScopePolicyTest.php
```

### Image Validation Domain

- `SourceImageValidator` validates one `SourceImage` or a `SourceImageCollection` without registering hooks.
- `SourceMimePolicy` accepts `image/jpeg`, `image/png`, and non-animated `image/webp` as initial source candidates.
- JPEG and PNG validation results expose future target formats `webp` and `avif`; WebP exposes only `avif`.
- `FileAnimationDetector` parses GIF image descriptor blocks and RIFF/WebP chunks for animation signals.
- Validation results use stable codes: `eligible`, `skipped_unsupported_source_mime`, `skipped_animated_image`, `source_invalid_mime`, `source_corrupt`, `source_animation_unknown`, `source_missing`, and `source_unreadable`.
- Validation serialization omits absolute source paths.

### Hooks, Settings, Metadata, and Database Changes

```text
New hooks: none
New settings: none
New options: none
New tables: none
New metadata keys: none
Metadata writes: none
Scheduled actions: none
REST routes: none
Admin menus/assets: none
Frontend hooks/assets: none
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 132 tests and 6846 assertions
composer run quality: pass
git diff --check: pass
```

Source scans confirm `src/Image/` does not call `wp_get_image_editor()`, does not write attachment metadata, does not register hooks/routes, does not enqueue assets, does not schedule queue jobs, and does not introduce frontend delivery behavior.

### Acceptance Criteria

- [x] Renamed non-image sources are rejected when real MIME cannot be detected.
- [x] Animated GIF and WebP sources are skipped with `skipped_animated_image`.
- [x] SVG and AVIF sources are not eligible for raster conversion.
- [x] Corrupt supported raster sources are rejected with `source_corrupt`.
- [x] MIME changes between collection and validation are rejected with `source_invalid_mime`.
- [ ] WordPress runtime validation smoke testing passes.

Runtime smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation with representative attachment files.

### Deferred Work

- Destination path resolution, temporary output paths, and deterministic sidecar names were implemented in Subphase 3.3.
- Conversion result taxonomy, converter implementation, resource guard, conversion policy, attachment metadata writes, queues, REST endpoints, admin UI, frontend delivery, Elementor integration, and WooCommerce integration remain deferred.

## Subphase 3.3 - Destination Resolver

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add callable-only destination resolver services.
- [x] Generate deterministic sidecar paths by appending `.hwlio.{format}` to the source filename.
- [x] Preserve uploads subdirectories and original source extensions.
- [x] Generate deterministic temporary paths in the destination directory using `{destination}.tmp`.
- [x] Validate source, destination, temporary, and existing realpaths against the uploads base.
- [x] Keep file writes, renames, deletes, conversion, metadata writes, queues, REST, UI, and delivery deferred.

### Files Added

```text
src/Image/DestinationPath.php
src/Image/DestinationResolutionResult.php
src/Image/DestinationResolver.php
tests/Unit/Image/DestinationResolverTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
tests/Unit/Image/ImageScopePolicyTest.php
```

### Destination Domain

- `DestinationResolver` resolves paths only and is not composed into plugin hooks.
- Supported target formats are `webp` and `avif`.
- Target MIME mapping is `webp => image/webp` and `avif => image/avif`.
- Sidecar examples: `2026/07/hero.jpg.hwlio.webp` and `2026/07/logo.png.hwlio.avif`.
- Temporary path example: `2026/07/hero.jpg.hwlio.webp.tmp`.
- `DestinationPath::to_array()` omits absolute final and temporary paths.
- `DestinationResolutionResult` returns stable codes including `resolved`, `invalid_target_format`, `uploads_unavailable`, `unsafe_source_path`, `source_outside_uploads`, `destination_outside_uploads`, `temporary_outside_uploads`, `destination_collision`, `temporary_collision`, `destination_realpath_outside_uploads`, and `temporary_realpath_outside_uploads`.

### Hooks, Settings, Metadata, and Database Changes

```text
New hooks: none
New settings: none
New options: none
New tables: none
New metadata keys: none
Metadata writes: none
Scheduled actions: none
REST routes: none
Admin menus/assets: none
Frontend hooks/assets: none
File writes/renames/deletes: none
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 143 tests and 7218 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `src/Image/` does not call conversion APIs, write/rename/delete files, allocate temporary files, write attachment metadata, register hooks/routes, enqueue assets, schedule queue jobs, or introduce frontend delivery behavior. Broader route/asset/queue scans only matched policy-test regex definitions.

### Acceptance Criteria

- [x] `logo.jpg` and `logo.png` produce different sidecars.
- [x] Every destination and temporary path remains inside uploads.
- [x] Repeated resolution returns the same result.
- [x] Full, subsize, and original sources preserve uploads subdirectories.
- [x] Unsafe, outside-uploads, and outside-realpath candidates are rejected.
- [x] Public serialization omits absolute paths.
- [ ] WordPress runtime destination resolution smoke testing remains pending in this plugin-only workspace.

Runtime smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation with representative attachment files.

### Deferred Work

- Destination writability checks, temporary file creation, cleanup, atomic rename, existing derivative reuse, and replacement behavior remain deferred.
- Conversion result taxonomy was implemented in Subphase 3.4.
- Converter implementation, resource guard, conversion policy, attachment metadata writes, queues, REST endpoints, admin UI, frontend delivery, Elementor integration, and WooCommerce integration remain deferred.

## Subphase 3.4 - Conversion Result Model and Error Taxonomy

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add pure conversion result model objects.
- [x] Define per-source/per-target result states: `success`, `skipped`, and `failed`.
- [x] Define stable success, skip, and failure codes for future converter and worker phases.
- [x] Add final derivative output metadata and byte-savings calculations.
- [x] Add bounded result-detail sanitization with path and sensitive-key redaction.
- [x] Keep conversion, file writes, metadata writes, queues, REST, UI, settings, delivery, Elementor, and WooCommerce deferred.

### Files Added

```text
src/Image/ConversionOutput.php
src/Image/ConversionResult.php
src/Image/ConversionResultCode.php
src/Image/ConversionResultCollection.php
src/Image/ConversionResultSanitizer.php
src/Image/ConversionSavings.php
tests/Unit/Image/ConversionResultTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
```

### Conversion Result Domain

- `ConversionResult` represents one source/target outcome and serializes source, destination, output, savings, status, code, target format, target MIME, message, and sanitized details.
- `ConversionResultCode` defines stable success codes `optimized` and `already_current`, skip codes including `skipped_not_smaller` and `skipped_resource_limit`, and failure codes including editor, conversion, output-validation, atomic-move, metadata, lock, queue, permission, and payload failures.
- `ConversionOutput` stores final derivative facts only: uploads-relative file path, MIME, width, height, bytes, quality, and generated timestamp.
- `ConversionSavings` computes source bytes, optional output bytes, savings bytes, savings percent, optional minimum savings percent, and optional minimum-threshold result.
- `ConversionResultSanitizer` redacts absolute paths and sensitive detail keys, replaces unsupported object/resource values, bounds nested detail payloads, and marks truncated details.
- `ConversionResultCollection` partitions success/skipped/failed results and provides summary counts.

### Hooks, Settings, Metadata, and Database Changes

```text
New hooks: none
New settings: none
New options: none
New tables: none
New metadata keys: none
Metadata writes: none
Scheduled actions: none
REST routes: none
Admin menus/assets: none
Frontend hooks/assets: none
File writes/renames/deletes: none
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 155 tests and 7857 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `src/Image/` does not call conversion APIs, write/rename/delete files, allocate temporary files, write attachment metadata, register hooks/routes, enqueue assets, schedule queue jobs, or introduce frontend delivery behavior. Broader route/asset/queue scans only matched policy-test regex definitions.

### Acceptance Criteria

- [x] Callers can branch on stable result status and codes without parsing log strings.
- [x] Success, skipped, and failed conversion outcomes are modeled.
- [x] Output metadata and byte savings serialize safely.
- [x] Absolute paths, sensitive keys, raw objects, resources, and error-like objects are not serialized.
- [x] Result collection summary counts are available for future job status reporting.
- [x] Full automated verification passed.

Runtime WordPress smoke testing is not required for this pure PHP model-only subphase.

### Deferred Work

- No image editor loading, conversion, resource guard, conversion policy, temporary file creation, cleanup, atomic rename, attachment metadata persistence, queues, REST endpoints, admin UI, frontend delivery, Elementor integration, or WooCommerce integration was added.
- Converter implementation remains deferred to Subphase 3.5.
- Resource guard remains deferred to Subphase 3.6.
- Conversion policy remains deferred to Subphase 3.7.

## Subphase 3.5 - Converter Implementation

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add a callable-only image converter service.
- [x] Add conversion request, editor, filesystem, and clock boundaries.
- [x] Use WordPress image editor APIs only behind the WordPress editor adapter.
- [x] Write generated output only to the deterministic temporary path from `DestinationPath`.
- [x] Validate source, temporary, and final paths before conversion, cleanup, and move operations.
- [x] Validate generated output MIME, dimensions, and positive byte size before finalizing.
- [x] Enforce minimum byte-savings percentage before moving output into place.
- [x] Avoid overwriting existing final sidecars.
- [x] Surface cleanup failures in sanitized result details.
- [x] Keep settings reads, resource policy, metadata writes, queues, REST, UI, frontend delivery, Elementor, and WooCommerce deferred.

### Files Added

```text
src/Image/ConversionClockInterface.php
src/Image/ConversionEditorInterface.php
src/Image/ConversionEditorResult.php
src/Image/ConversionFilesystemInterface.php
src/Image/ConversionRequest.php
src/Image/ImageConverter.php
src/Image/SystemConversionClock.php
src/Image/WordPressConversionEditor.php
src/Image/WordPressConversionFilesystem.php
tests/Unit/Image/FakeConversionClock.php
tests/Unit/Image/FakeConversionEditor.php
tests/Unit/Image/FakeConversionFilesystem.php
tests/Unit/Image/ImageConverterTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/ImageScopePolicyTest.php
```

### Converter Services

- `ImageConverter::convert()` coordinates one source/target conversion attempt and returns a `ConversionResult`.
- `ConversionRequest` carries a `SourceImage`, `DestinationPath`, quality, and minimum savings threshold; quality is clamped to `1-100`, and minimum savings is clamped to `0-100`.
- `WordPressConversionEditor` is the only new image-domain class that calls `wp_get_image_editor()`.
- `WordPressConversionFilesystem` is the only new image-domain class that calls native `rename()`, `wp_delete_file()`, or fallback `unlink()`.
- `ImageConverter` validates source, destination, and temporary path relationships before read, delete, or move operations.
- Existing final sidecars are treated as `destination_collision` and are not overwritten.
- Pre-existing validated temporary sidecars are cleaned before conversion.
- Temporary output is deleted on failed validation, failed savings threshold, editor failure when present, destination collision, and atomic move failure when possible.
- Final output is validated after the atomic move before returning `optimized`.

### Hooks, Settings, Metadata, and Database Changes

```text
New hooks: none
New settings: none
New options: none
New tables: none
New metadata keys: none
Metadata writes: none
Scheduled actions: none
REST routes: none
Admin menus/assets: none
Frontend hooks/assets: none
Runtime upload optimization hooks: none
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 173 tests and 8735 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `wp_get_image_editor()` appears in `src/Image/WordPressConversionEditor.php` only within `src/Image/`; native `rename()`, `wp_delete_file()`, and fallback `unlink()` appear in `src/Image/WordPressConversionFilesystem.php` only. `src/Image/` does not add REST routes, UI/assets, media hooks, metadata writes, optimization queue scheduling, direct GD/Imagick conversion functions, temp-file allocation, file copy/uploaded-file moves, memory-limit mutation, or frontend delivery hooks.

### Acceptance Criteria

- [x] Converter uses WordPress image APIs rather than direct GD/Imagick conversion calls.
- [x] Original source files are never written, renamed, deleted, or overwritten.
- [x] Existing final sidecars are not overwritten.
- [x] Generated output is validated before finalization.
- [x] Minimum byte-savings policy is enforced by request input.
- [x] Cleanup failures are reported without exposing absolute paths.
- [x] Converter remains callable-only and is not registered in runtime hooks.
- [ ] WordPress runtime WebP/AVIF conversion smoke testing passes.

Runtime conversion smoke testing remains pending until this plugin is run inside a WordPress 6.5+ test installation with writable uploads and WebP/AVIF-capable image editors.

### Deferred Work

- Resource guard and execution budget checks remain deferred to Subphase 3.6.
- Conversion policy, settings-derived format selection, environment support gating, and exclusion handling remain deferred to Subphase 3.7.
- Existing derivative reuse, fingerprinting, metadata persistence, attachment state, queues, upload hooks, REST endpoints, admin screens, frontend delivery, Elementor integration, and WooCommerce integration remain deferred to their owning phases.

## Subphase 3.6 - Resource Guard

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add a pure-domain pre-allocation resource guard.
- [x] Calculate image pixel count using `SourceImage` width and height.
- [x] Enforce a default maximum pixel count.
- [x] Estimate required memory using dimensions, channels, and working overhead multipliers.
- [x] Enforce a safety margin against the available memory limit without relying on runtime limit mutation.
- [x] Return detailed outcomes via an immutable `ResourceGuardResult`.
- [x] Apply the guard in `ImageConverter` before editor allocation.
- [x] Implement comprehensive pixel and memory estimate unit testing.

### Files Added

```text
src/Image/ResourceGuard.php
src/Image/ResourceGuardResult.php
tests/Unit/Image/ResourceGuardTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Image/ImageConverter.php
tests/Unit/Image/ImageConverterTest.php
```

### Resource Thresholds

- **Max Pixel Count:** 40,000,000 (filterable via `hwlio_max_pixel_count` when using the WordPress factory).
- **Assumed Channels:** 4 (RGBA).
- **Overhead Multiplier:** 1.8 (Provides buffer for base memory + destination memory).
- **Safety Margin:** 0.8 (Requires the estimate to fit within 80% of the available PHP limit).
- `ImageConverter::for_wordpress()` reads the effective PHP `memory_limit`, builds a `ResourceGuard`, and checks it before editor allocation.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 183 tests and 8947 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `ResourceGuard` calculates constraints without changing runtime limits. `ImageConverter` applies the guard before `WordPressConversionEditor` can call `wp_get_image_editor()`. No code calls `ini_set()`, direct GD/Imagick conversion functions, temp allocation, metadata writes, REST routes, UI/assets, Action Scheduler optimization scheduling, or frontend delivery hooks.

### Acceptance Criteria

- [x] Image processing memory requirement is estimated effectively.
- [x] Exceeding the maximum allowed pixel count gracefully fails the check.
- [x] Exceeding the available safety memory boundary gracefully fails the check.
- [x] Detailed error codes and machine-readable reasons allow skipping processes safely.
- [x] Oversized fixtures are skipped before editor allocation.
- [x] Unit test boundary checks handle unknown or unlimited configuration properly.

### Deferred Work

- Settings-derived resource policy decisions and per-job orchestration remain deferred to Subphase 3.7 (Conversion Policy) and Phase 4/5 attachment processing.
- The `hwlio_max_pixel_count` filter is used only by the WordPress factory path.
- Elementor/WooCommerce specific guards, queue integration, and REST validation remain deferred to later phases.

## Subphase 4.1 - Attachment Fingerprint

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add an attachment-domain fingerprint namespace.
- [x] Build cheap attachment fingerprints from `SourceImageCollection` data only.
- [x] Include full source path, byte size, modified time, normalized source metadata, and collection issues in fingerprint decisions.
- [x] Produce a 20-character queue-safe signature without hashing full image contents.
- [x] Compare queued signatures and stored master-plan-compatible fingerprint arrays against current state.
- [x] Return stable comparison codes for matches, missing/invalid fingerprints, source changes, and metadata hash changes.
- [x] Keep the implementation callable-only with no post meta writes, hooks, queues, REST routes, admin UI, conversion calls, or frontend delivery.

### Files Added

```text
src/Attachment/AttachmentFingerprint.php
src/Attachment/AttachmentFingerprintBuilder.php
src/Attachment/AttachmentFingerprintCode.php
src/Attachment/AttachmentFingerprintComparison.php
tests/Unit/Attachment/AttachmentFingerprintTest.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
```

### Fingerprint Fields

- `relative_file`: current full source uploads-relative path.
- `file_size`: current full source byte size.
- `modified_time`: current full source modified time.
- `metadata_hash`: SHA-256 hash of normalized source records and collection issue summaries.
- `signature`: 20-character SHA-256 prefix for future queue payloads.

### Comparison Codes

```text
fingerprint_match
fingerprint_missing
fingerprint_invalid
fingerprint_mismatch
source_path_changed
source_bytes_changed
source_modified_time_changed
metadata_hash_changed
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 196 tests and 9348 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `src/Attachment/` does not call WordPress metadata APIs, media hooks, REST APIs, admin/frontend asset APIs, queue scheduling APIs, conversion APIs, or file write/rename/delete functions.

### Acceptance Criteria

- [x] Replacing the attached file path invalidates the stored fingerprint.
- [x] Source byte and modified-time changes invalidate the stored fingerprint.
- [x] Regenerated subsizes and source collection issues change the metadata hash.
- [x] Unchanged attachments compare as matches and can be idempotently skipped by future phases.
- [x] Public serialization omits absolute paths.

### Deferred Work

- `_hwlio_derivatives` persistence, merge behavior, status summary meta, and stored-path validation remain deferred to Subphase 4.2.
- Attachment locks, stale lock recovery, and lock diagnostics remain deferred to Subphase 4.3.
- Attachment processing orchestration, derivative reuse checks, queue payload execution, and statistics remain deferred to Subphase 4.4 and Phase 5.
- Attachment deletion cleanup registration remains deferred to Subphase 4.5.

## Subphase 4.2 - Derivative Repository

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add a testable attachment meta store seam.
- [x] Add `DerivativeRepository` for `_hwlio_derivatives` reads, writes, merges, and deletion.
- [x] Add schema-versioned `DerivativeManifest` parsing and sanitization.
- [x] Add `AttachmentStatus` for `_hwlio_status` summaries.
- [x] Merge partial successful conversion results without losing existing ready formats or sizes.
- [x] Refuse to overwrite a stored manifest when its fingerprint no longer matches current source state.
- [x] Ignore unsafe or invalid stored metadata instead of trusting it.
- [x] Keep WordPress post-meta API calls isolated to `WordPressAttachmentMetaStore`.

### Files Added

```text
src/Attachment/AttachmentClockInterface.php
src/Attachment/AttachmentMetaStoreInterface.php
src/Attachment/AttachmentStatus.php
src/Attachment/DerivativeManifest.php
src/Attachment/DerivativeManifestSanitizer.php
src/Attachment/DerivativeRepository.php
src/Attachment/DerivativeRepositoryResult.php
src/Attachment/SystemAttachmentClock.php
src/Attachment/WordPressAttachmentMetaStore.php
tests/Unit/Attachment/DerivativeRepositoryTest.php
tests/Unit/Attachment/FakeAttachmentMetaStore.php
tests/Unit/Attachment/FixedAttachmentClock.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Infrastructure/WordPressDerivativeManifestProvider.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
```

### Repository API

```text
DerivativeRepository::for_wordpress()
DerivativeRepository::read( attachment_id )
DerivativeRepository::save_results( attachment_id, fingerprint, results, state )
DerivativeRepository::save_status( attachment_id, status )
DerivativeRepository::delete( attachment_id )
```

### Manifest and Status Behavior

- `_hwlio_derivatives` stores schema version `1`, attachment fingerprint, `updated_at`, source records, and ready WebP/AVIF format records.
- `_hwlio_status` stores normalized state, ready formats, `updated_at`, error code, and excluded flag.
- Only successful `ConversionResult` outputs are stored as ready derivatives.
- Skipped and failed results do not create derivative entries; they may update status and error code.
- Existing valid manifest entries are preserved across partial continuation writes.
- Same size/format retries replace that entry idempotently.
- Stored manifests with mismatched fingerprints are preserved and not overwritten.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 208 tests and 10224 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Only `src/Attachment/WordPressAttachmentMetaStore.php` calls `get_post_meta()`, `update_post_meta()`, or `delete_post_meta()`. No code calls `wp_update_attachment_metadata()`, media hooks, Action Scheduler optimization scheduling, REST routes, admin/frontend assets, delivery hooks, or filesystem mutation APIs in the attachment repository layer.

### Acceptance Criteria

- [x] Partial continuation writes can add AVIF after WebP without losing WebP.
- [x] Later subsize writes preserve existing full-size entries.
- [x] Invalid metadata is ignored and diagnosed rather than trusted.
- [x] Core `_wp_attachment_metadata` is never written.
- [x] `_hwlio_derivatives` remains the only authoritative derivative deletion manifest.

### Deferred Work

- Attachment locks, stale lock recovery, and lock diagnostics remain deferred to Subphase 4.3.
- Attachment processing orchestration, derivative reuse checks, queue payload execution, and statistics remain deferred to Subphase 4.4 and Phase 5.
- Attachment deletion cleanup registration remains deferred to Subphase 4.5.
- Filesystem existence, MIME, and dimension revalidation for derivative reuse remains deferred to processing and delivery phases.

## Subphase 4.3 - Attachment Locking

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add `_hwlio_lock` value object parsing, expiration checks, and token-safe serialization.
- [x] Add `AttachmentLockManager` for unique acquisition, token-checked release, callback wrapping, and bounded stale recovery.
- [x] Extend the attachment meta seam with unique add and exact-value delete operations.
- [x] Add a WordPress-backed scanner for bounded attachment lock lookup.
- [x] Add service-only lock diagnostics that return structured diagnostic results without exposing tokens.
- [x] Keep plugin composition unchanged and avoid scheduling recurring stale-lock recovery in this subphase.

### Files Added

```text
src/Attachment/AttachmentLock.php
src/Attachment/AttachmentLockDiagnostics.php
src/Attachment/AttachmentLockManager.php
src/Attachment/AttachmentLockRecoveryResult.php
src/Attachment/AttachmentLockResult.php
src/Attachment/AttachmentLockScannerInterface.php
src/Attachment/AttachmentLockTokenGeneratorInterface.php
src/Attachment/RandomAttachmentLockTokenGenerator.php
src/Attachment/WordPressAttachmentLockScanner.php
tests/Unit/Attachment/AttachmentLockManagerTest.php
tests/Unit/Attachment/FakeAttachmentLockScanner.php
tests/Unit/Attachment/FixedAttachmentLockTokenGenerator.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Attachment/AttachmentMetaStoreInterface.php
src/Attachment/WordPressAttachmentMetaStore.php
tests/Unit/Attachment/FakeAttachmentMetaStore.php
```

### Lock Behavior

- `_hwlio_lock` stores only `token`, `created_at`, and `expires_at`.
- Default lock TTL is `600` seconds; invalid TTL values fall back to `600`.
- Acquisition uses unique post-meta semantics so active workers cannot acquire the same attachment concurrently.
- Active existing locks return `lock_unavailable` and are not deleted.
- Expired or malformed locks are recovered by exact-value delete before one retry.
- Release deletes only when the current stored token matches.
- Missing lock release is idempotent and returns a warning result.
- `run_locked()` releases in a `finally` path when callbacks complete or throw.
- Public result and diagnostic serialization never exposes lock tokens.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 218 tests and 11097 assertions
composer run quality: pass
vendor/bin/phpunit tests/Unit/Attachment: pass, 35 tests and 831 assertions
git diff --check: pass
```

Source scans: pass. WordPress post-meta APIs remain isolated to `src/Attachment/WordPressAttachmentMetaStore.php`. No code calls `wp_update_attachment_metadata()`, media hooks, Action Scheduler optimization scheduling, REST routes, admin/frontend assets, delivery hooks, converter APIs, or filesystem mutation APIs in the attachment locking layer.

### Acceptance Criteria

- [x] First-worker unique acquisition succeeds and a second active worker receives `lock_unavailable`.
- [x] Stale and malformed locks can be recovered without manual database edits.
- [x] Exact-value deletion protects newer locks during recovery races.
- [x] Release succeeds only with the matching token and never deletes on token mismatch.
- [x] Bounded stale recovery scans at most `100` attachment IDs.
- [x] Lock diagnostics report clear, active, stale, and invalid states without tokens.

### Deferred Work

- Recurring scheduling of `hwlio_recover_stale_locks` remains deferred to Phase 5.5.
- Attachment processing orchestration, derivative reuse checks, queue payload execution, and status transitions remain deferred to Subphase 4.4 and Phase 5.
- Admin and REST exposure for lock diagnostics remains deferred to Phase 6.
- Attachment deletion cleanup registration remains deferred to Subphase 4.5.

## Subphase 3.7 - Conversion Policy

**Status:** Complete
**Completed:** 2026-07-10

### Tasks

- [x] Add a pure-domain conversion policy decision service.
- [x] Evaluate gates sequentially: format validity, exclusion, settings enablement, server support, source MIME eligibility, source MIME→target compatibility, pre-existing validation result, resource guard, and existing derivative reuse.
- [x] Map non-eligible validation results to stable `ConversionResultCode` taxonomy codes.
- [x] Check fingerprint freshness before skipping on existing derivative reuse.
- [x] Support force mode that bypasses only derivative reuse checks.
- [x] Add an immutable context value object for attachment-level policy inputs.
- [x] Add an immutable result value object for policy decision outputs.
- [x] Keep the implementation callable-only with no post meta writes, hooks, queues, REST routes, admin UI, or frontend delivery.

### Files Added

```text
src/Image/ConversionPolicy.php
src/Image/ConversionPolicyContext.php
src/Image/ConversionPolicyResult.php
tests/Unit/Image/ConversionPolicyTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/Image/FakeFormatSupportProvider.php
```

### Policy Gates

```text
Gate 1: Invalid target format         → invalid_target_format
Gate 2: Attachment-level exclusion     → skipped_excluded
Gate 3: Format not enabled in settings → skipped_target_not_enabled
Gate 4: Server encoding not supported  → skipped_target_not_supported
Gate 5: Source MIME not supported       → skipped_unsupported_source_mime
Gate 6: Source MIME → target mismatch  → skipped_unsupported_source_mime
Gate 7: Validation result non-eligible → mapped code from validation
Gate 8: Resource guard denied          → skipped_resource_limit
Gate 9: Existing derivative reuse      → already_current (skipped in force mode)
Pass:   All gates cleared              → eligible
```

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 256 tests and 11434 assertions
composer run quality: pass
```

Source scans: pass. `ConversionPolicy` reads settings, format support, resource guard, and MIME policy via injected interfaces only. No code calls WordPress metadata APIs, media hooks, Action Scheduler optimization scheduling, REST routes, admin/frontend assets, delivery hooks, converter APIs, or filesystem mutation APIs in the conversion policy layer.

### Acceptance Criteria

- [x] Policy correctly gates on each individual criterion.
- [x] Existing valid derivative with matching fingerprint skips conversion.
- [x] Force mode bypasses reuse and existing-derivative checks.
- [x] Force mode does not bypass exclusion.
- [x] Excluded attachments return `skipped_excluded`.
- [x] Unsupported formats return `skipped_target_not_supported`.
- [x] Disabled formats return `skipped_target_not_enabled`.
- [x] Resource guard failures return `skipped_resource_limit`.
- [x] All results use stable machine-readable codes from the established taxonomy.
- [x] No runtime hooks, queue scheduling, or metadata writes are introduced.

### Deferred Work

- Attachment processing orchestration, per-job gate application, derivative reuse filesystem checks, queue payload execution, and status transitions remain deferred to Subphase 4.4 and Phase 5.
- Developer path exclusion filters remain deferred to Phase 5 worker integration.
- Admin and REST exposure for policy results remains deferred to Phase 6.
- Elementor/WooCommerce-specific policy extensions remain deferred to Phase 8.

## Subphase 4.4 - Attachment Processor

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add `AttachmentProcessor` to orchestrate source collection, fingerprinting, repository reads, validation, conversion policy, destination resolution, conversion, and repository persistence.
- [x] Add `AttachmentProcessResult` as the job-level summary result.
- [x] Process one target format per run through `process_format()`.
- [x] Add cursor and completion metadata so future workers can resume bounded batches.
- [x] Preserve `process()` as a convenience wrapper that uses the first enabled format.
- [x] Persist successful partial results through `DerivativeRepository::save_results()`.
- [x] Mark no-source and fingerprint-failure exits through `_hwlio_status`.
- [x] Release attachment locks after success, handled skips, and unexpected exceptions.
- [x] Fire stable lifecycle actions when WordPress hooks are loaded.

### Files Added

```text
src/Attachment/AttachmentProcessResult.php
src/Attachment/AttachmentProcessor.php
tests/Unit/Attachment/AttachmentProcessResultTest.php
tests/Unit/Attachment/AttachmentProcessorTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Image/ConversionResultCollection.php
```

### Processor Behavior

- `AttachmentProcessor::process_format()` processes one attachment and one target format.
- The result reports target format, start cursor, next cursor, completion state, conversion-result summary, codes, and messages.
- Existing current derivatives are represented as `already_current` success results when manifest data can be mapped safely.
- Destination-resolution failures are mapped to failure or outside-uploads skip results instead of collapsing to `skipped_unknown`.
- Partial batches are saved as `partial`; all-success completed batches become `optimized`; completed all-skip/all-failure batches become `skipped` or `failed`.
- The processor is callable-only; it does not register media hooks, queue jobs, REST routes, admin UI, frontend delivery, or scheduled maintenance.

### Corrections Made During Review

- Fixed processor tests that targeted a nonexistent `get_full_source()` provider method.
- Updated lock fixtures to use the canonical `_hwlio_lock` field names: `token`, `created_at`, and `expires_at`.
- Added the missing immutable `ConversionResultCollection::with_added()` helper used by the processor.
- Added one positive processor orchestration test that converts WebP only and verifies lock release and manifest persistence.
- Changed the processor from all-enabled-format processing to one-format processing with cursor metadata.

### Verification

```text
composer run cs: pass
composer run stan: pass
composer run test: pass, 265 tests and 11653 assertions
vendor/bin/phpunit tests/Unit/Attachment/AttachmentProcessorTest.php tests/Unit/Attachment/AttachmentProcessResultTest.php: pass, 5 tests and 20 assertions
vendor/bin/phpunit tests/Unit/Image/ConversionPolicyTest.php tests/Unit/Image/ConversionResultTest.php: pass, 38 tests and 73 assertions
```

Source scans: pass. No code calls core attachment metadata writes, media hooks, Action Scheduler optimization scheduling, REST routes, admin/frontend assets, delivery hooks, direct conversion functions, or filesystem mutation APIs outside the existing converter/filesystem seams.

### Acceptance Criteria

- [x] One attachment can process predictably through collection, validation, policy, destination resolution, conversion, and repository persistence.
- [x] One target format is processed per run.
- [x] Batch cursor and completion metadata make later worker resumption possible.
- [x] Successful conversion results are saved without discarding previous manifest entries.
- [x] Lock release is attempted after handled and unexpected exits.
- [x] Failures and skips produce stable machine-readable codes.

### Deferred Work

- Action Scheduler queue payload execution remains deferred to Phase 5.
- New-upload hooks and automatic processing remain deferred to Phase 5.
- Admin, REST, and bulk-processing exposure remain deferred to Phase 6.
- Attachment deletion cleanup registration remains deferred to Subphase 4.5.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.
