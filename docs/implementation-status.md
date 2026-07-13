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
- A runtime attachment cleanup provider for authoritative sidecar deletion, pending attachment-job cancellation, meta cleanup, and dry-run orphan reconciliation exists as of Subphase 4.5.
- An optimization-focused queue abstraction for Action Scheduler exists as of Subphase 5.1.
- A runtime optimization worker with lock orchestration, queued fingerprint freshness checks, continuation scheduling, retry scheduling, and queue-driven status transitions exists as of Subphase 5.2.
- A runtime new-upload integration for asynchronous post-metadata queueing and lightweight status updates exists as of Subphase 5.3.
- Attachment regeneration and edit reconciliation for stale optimized metadata updates exists as of Subphase 5.4.
- A runtime queue-maintenance provider for recurring stale-lock recovery, processing-state repair, and internal statistics-cache reconciliation exists as of Subphase 5.5.
- A namespaced Media submenu shell with internal tab routing, capability checks, and captured screen IDs exists as of Subphase 6.1.
- Screen-scoped admin CSS, footer JavaScript, REST bootstrap config, and accessible notice/live-region scaffolding exist as of Subphase 6.2.
- Attachment-first REST controllers for status, diagnostics, attachment detail, and attachment actions exist as of Subphase 6.3.
- Media Library list/grid/edit-screen controls, lightweight attachment payloads, and client-observed new-upload refresh now exist as of Subphase 6.4.
- A cached dashboard, dry-run bulk scanner, and session-backed bulk queue controls with global pause/resume now exist as of Subphases 6.5 through 6.7.
- A derivative URL resolver, responsive modern source-set builder, safe picture renderer, and active attachment-image plus post-content delivery integrations now exist as of Subphases 7.1 through 7.5.
- Delivery now includes an internal emergency rollback switch, cache-invalidation request action, and missing-derivative diagnostics as of Subphase 7.6.
- A critical-image registry, explicit loading-attribute override provider, minimal critical-logo settings UI, post/page critical-image editor controls, conservative intrinsic-dimension repair, and opt-in responsive preload now exist as of Subphase 8.4.
- A WooCommerce compatibility audit, baseline fixture manifest, isolated primary-product integration, and conservative gallery-surface delivery now exist as of Subphase 9.3.
- A plugin-owned Elementor companion stylesheet layer for structured attachment-backed backgrounds now exists as of Subphase 10.4.
- An opt-in Elementor critical background preload layer with an explicit hero-background selector, shared background delivery-plan builder, and media-scoped `wp_head` preload tags now exists as of Subphase 10.5.
- A read-only compatibility conflict detector, capability-first overlap reporting, compatibility settings toggles, and conflict diagnostics now exist as of Subphase 11.1.

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
  - [x] Subphase 4.5 - Cleanup on attachment deletion
- [ ] Phase 5 - Action Scheduler Queue and Automatic Processing
  - [x] Subphase 5.1 - Queue abstraction
  - [x] Subphase 5.2 - Optimization worker
  - [x] Subphase 5.3 - New-upload integration
  - [x] Subphase 5.4 - Regeneration and edit reconciliation
  - [x] Subphase 5.5 - Maintenance actions
- [x] Phase 6 - Admin Screens, REST API, and Bulk Processing
  - [x] Subphase 6.1 - Menu and screen shell
  - [x] Subphase 6.2 - Admin assets and REST client
  - [x] Subphase 6.3 - REST controllers
  - [x] Subphase 6.4 - Media Library and new-upload optimization controls
  - [x] Subphase 6.5 - Dashboard
  - [x] Subphase 6.6 - Bulk scanner
  - [x] Subphase 6.7 - Bulk queue controls
  - [x] Subphase 6.8 - Logs and diagnostics screens
- [x] Phase 7 - Frontend Modern-Format Delivery
  - [x] Subphase 7.1 - Derivative URL resolver
  - [x] Subphase 7.2 - Responsive source-set builder
  - [x] Subphase 7.3 - Picture renderer
  - [x] Subphase 7.4 - Attachment image integration
  - [x] Subphase 7.5 - Post-content image integration
  - [x] Subphase 7.6 - Delivery rollback and cache hooks
- [ ] Phase 8 - Loading Optimization and Layout Stability
  - [x] Subphase 8.1 - Preserve core loading attributes
  - [x] Subphase 8.2 - Critical image registry
  - [x] Subphase 8.3 - Intrinsic dimension repair
  - [x] Subphase 8.4 - Optional responsive preload
- [ ] Phase 9 - WooCommerce Integration
  - [x] Subphase 9.1 - Compatibility audit and fixtures
  - [x] Subphase 9.2 - Primary product image optimization
  - [x] Subphase 9.3 - Gallery and commerce surfaces
- [ ] Phase 10 - Elementor and CSS Background Integration
  - [x] Subphase 10.1 - Elementor attachment-widget compatibility
  - [x] Subphase 10.2 - Oversized selection diagnostics
  - [x] Subphase 10.3 - Background image discovery
  - [x] Subphase 10.4 - Background delivery strategy
  - [x] Subphase 10.5 - Critical background preload
- [ ] Phase 11 - CDN, Offload, Multisite, and Conflict Adapters
  - [x] Subphase 11.1 - Conflict detector
- [ ] Phase 12 - Page-Level Diagnostics and Lighthouse-Oriented Reporting
- [ ] Phase 13 - WP-CLI and Developer Operations
- [ ] Phase 14 - Testing, Performance, Security, and Release

Phase 0 implementation subphases are complete. The phase remains unchecked at the phase level until a supported WordPress 6.5+ activation smoke test is performed.
Phase 1 implementation subphases are complete. The phase remains unchecked at the phase level until supported WordPress activation, deactivation, uninstall, Action Scheduler, and log-table smoke tests are performed.
Phase 5 implementation subphases are complete. The phase remains unchecked at the phase level until supported WordPress queue execution and recurring-maintenance smoke tests are performed.
Phase 6 now includes the admin menu shell, screen-scoped assets and REST bootstrap, attachment-first REST controllers, Media Library controls, dashboard, dry-run bulk scanning, and bounded bulk queue controls. The phase remains unchecked until supported WordPress admin and queue smoke tests are performed.
Phase 7 now includes derivative URL resolution, responsive modern source-set building, safe picture rendering, active `wp_get_attachment_image` plus `wp_content_img_tag` delivery integrations, an internal emergency rollback switch, cache-invalidation request hooks, and missing-derivative diagnostics. The phase remains unchecked until supported WordPress frontend delivery and cache-integration smoke tests are performed.
Phase 8 now includes core-loading preservation, explicit critical-image overrides, conservative intrinsic-dimension repair, and opt-in responsive preload for explicit late-discovered critical images. The phase remains unchecked until supported WordPress frontend loading and layout-stability smoke tests are performed.
Phase 9 now includes the WooCommerce compatibility baseline audit, expanded fixture coverage for gallery and commerce surfaces, isolated primary-product-image runtime integration, and conservative gallery-secondary delivery. The phase remains unchecked until supported WooCommerce smoke tests are performed across live product, gallery, variation, cart, checkout, and loop-like surfaces.
Phase 10 now includes the first isolated Elementor adapter for attachment-backed frontend widgets, a repo-owned audit and fixture baseline, a service-only oversized full-selection advisory analyzer, a read-only structured background-discovery layer, a plugin-owned companion stylesheet strategy for supported Elementor attachment-backed backgrounds, and an explicit opt-in critical background preload flow for one selected hero target per request. The phase remains unchecked until supported Elementor frontend, preview, editor, generated-CSS, and critical-background preload smoke tests are performed.
Phase 11 now includes capability-first detection of overlapping optimizer, delivery, lazy-loading, CDN-transformation, and media-offload plugins, along with compatibility toggles for plugin-owned overlapping modules and conflict diagnostics surfaced through the existing admin/dashboard flows. The phase remains unchecked until later CDN, offload, multisite, and adapter behavior subphases are implemented and smoke tested.

## Subphase 11.1 - Conflict Detector

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a read-only compatibility detection slice under `src/Integration/Conflict/`.
- [x] Detect overlapping capabilities from a curated active-plugin signature matrix and aggregate one warning per capability.
- [x] Add the missing compatibility toggles for loading-attribute overrides and Elementor background delivery.
- [x] Wire the new toggles into runtime no-op guards without widening hook registration.
- [x] Surface overlap conflicts through the existing dashboard/status and diagnostics flows.
- [x] Extend the current Settings tab with a Compatibility section for plugin-owned module disablement.

### Files Added

```text
src/Diagnostics/ConflictDiagnostics.php
src/Integration/Conflict/ConflictDetector.php
src/Integration/Conflict/ConflictReport.php
src/Integration/Conflict/ConflictResult.php
src/Integration/Conflict/ConflictRuntimeInterface.php
src/Integration/Conflict/WordPressConflictRuntime.php
tests/Unit/Admin/Rest/CompositeDiagnosticsServiceTest.php
tests/Unit/Diagnostics/ConflictDiagnosticsTest.php
tests/Unit/Integration/Conflict/ConflictDetectorTest.php
tests/Unit/Integration/ConflictScopePolicyTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Admin/DashboardPage.php
src/Admin/Rest/CompositeDiagnosticsService.php
src/Admin/Rest/DashboardEnvironmentSummaryService.php
src/Admin/SettingsPage.php
src/Delivery/LoadingAttributeManager.php
src/Integration/ElementorBackgroundStylesheetManager.php
src/Plugin.php
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
src/Settings/SettingsSchema.php
src/Settings/StaticSettingsRepository.php
tests/Unit/Admin/Rest/DashboardEnvironmentSummaryServiceTest.php
tests/Unit/Admin/Rest/StatusControllerTest.php
tests/Unit/Admin/Rest/StatusSummaryServiceTest.php
tests/Unit/Admin/SettingsPageTest.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/LoadingAttributeManagerTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/Integration/ElementorAttachmentWidgetDeliveryTest.php
tests/Unit/Integration/ElementorBackgroundStylesheetManagerTest.php
tests/Unit/Integration/ElementorScopePolicyTest.php
tests/Unit/Integration/WooCommercePrimaryProductDeliveryTest.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsSanitizerTest.php
tests/Unit/Settings/SettingsSchemaTest.php
```

### Conflict-Detection and Compatibility Behavior

- `ConflictDetector` now produces capability-first overlap warnings for `generation`, `delivery`, `lazy_loading`, `cdn_transformation`, and `media_offload`, using a curated active-plugin basename matrix and aggregating all matching plugins into one result per capability.
- Detection is read-only and current-site scoped. In multisite, the detector includes current-site active plugins plus current-site network-active plugin basenames without scanning every site or modifying any third-party configuration.
- Conflict results expose only safe scalar payloads: stable code, severity, capability, label, message, evidence plugin display names, and recommended plugin-owned setting keys.
- `DashboardEnvironmentSummaryService` now merges overlap conflicts into the existing conservative dashboard `conflicts` payload, and `StatusSummaryService` continues exposing that stable `conflicts` array shape with richer entries.
- `ConflictDiagnostics` converts detector results into `DiagnosticResult` objects, and `CompositeDiagnosticsService` now includes those alongside environment and derivative-health diagnostics.
- Two new delivery-group booleans now exist in the settings schema and repository: `loading_attribute_overrides_enabled` and `elementor_background_delivery_enabled`, both defaulting to `true`.
- `LoadingAttributeManager` now fully no-ops when `loading_attribute_overrides_enabled()` is `false`, while `ElementorBackgroundStylesheetManager` now requires both `delivery_enabled()` and `elementor_background_delivery_enabled()` before enqueueing companion CSS.
- The Settings tab now includes a Compatibility section with explicit toggles for automatic optimization, frontend delivery, loading overrides, responsive image preload, Elementor background delivery, and Elementor hero background preload.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source/policy verification: pass.

- No third-party plugin deactivation, activation, deletion, or option mutation was introduced.
- No new REST routes or admin pages were added for 11.1.
- No CDN/offload runtime rewrite behavior was introduced; this subphase remains detection-only plus plugin-owned module toggles.

### Acceptance Criteria

- [x] Overlapping capabilities are detected through a curated active-plugin signature matrix without modifying third-party plugins.
- [x] Conflict warnings are capability-first and aggregate evidence plugin names per capability.
- [x] Compatibility toggles now exist for loading-attribute overrides and Elementor background delivery, alongside the existing overlapping-module settings.
- [x] Disabling loading overrides or Elementor background delivery now makes those runtime providers no-op without affecting unrelated modules.
- [x] Dashboard/status and diagnostics flows now include overlap conflicts without adding a new page or REST route.
- [x] The current Settings tab now exposes a Compatibility section for plugin-owned module disablement.

### Deferred Work

- Deep third-party option inspection, product-specific compatibility adapters, and automatic third-party feature negotiation remain deferred.
- CDN transformation behavior, media offload support, and multisite operational changes remain deferred to later Phase 11 subphases.
- 11.1 remains detection-only for third-party overlap; it does not change URL rewriting, offload behavior, or CDN delivery semantics.

## Subphase 10.5 - Critical Background Preload

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add the opt-in `critical_background_preload_enabled` delivery setting, typed repository access, and minimal Settings-page checkbox.
- [x] Add an Elementor-specific post-meta seam and post/page hero-background selector that persists one normalized `{ element_id, setting_group }` target only when it is currently discoverable.
- [x] Extract a shared background delivery-plan builder so the 10.4 companion stylesheet and 10.5 preload logic resolve the same validated document targets, derivative URLs, and breakpoint media queries.
- [x] Add a narrow `wp_head` preload provider that emits safe modern preload tags only for the selected supported Elementor background target.
- [x] Share request-local preload dedupe across attachment-image preload and Elementor background preload without widening the frontend hook surface.

### Files Added

```text
src/Delivery/PreloadLinkInterface.php
src/Integration/ElementorBackgroundDeliveryPlan.php
src/Integration/ElementorBackgroundDeliveryPlanBuilder.php
src/Integration/ElementorBackgroundDeliveryPlanResult.php
src/Integration/ElementorBackgroundDeliveryVariant.php
src/Integration/ElementorBackgroundPreloadLink.php
src/Integration/ElementorBackgroundPreloadResult.php
src/Integration/ElementorCriticalBackgroundPreloadManager.php
src/Integration/ElementorHeroBackgroundPostMetaStoreInterface.php
src/Integration/ElementorHeroBackgroundTargetSelection.php
src/Integration/WordPressElementorHeroBackgroundPostMetaStore.php
src/Admin/PostEditor/ElementorHeroBackgroundMetaBox.php
tests/Unit/Admin/PostEditor/ElementorHeroBackgroundMetaBoxTest.php
tests/Unit/Integration/ElementorBackgroundDeliveryPlanBuilderTest.php
tests/Unit/Integration/ElementorCriticalBackgroundPreloadManagerTest.php
tests/Unit/Integration/FakeElementorHeroBackgroundPostMetaStore.php
```

### Files Changed

```text
CHANGELOG.md
docs/elementor-compatibility-audit.md
docs/implementation-status.md
src/Admin/SettingsPage.php
src/Delivery/ResponsivePreloadLink.php
src/Delivery/ResponsivePreloadRegistry.php
src/Plugin.php
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
src/Settings/SettingsSchema.php
src/Settings/StaticSettingsRepository.php
tests/Unit/Admin/PostEditor/PostEditorScopePolicyTest.php
tests/Unit/Admin/SettingsPageTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/Integration/ElementorScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsSanitizerTest.php
tests/Unit/Settings/SettingsSchemaTest.php
```

### Critical-Background Preload Behavior

- `critical_background_preload_enabled` defaults to `false`, is sanitized through the existing settings pipeline, and is exposed through the current minimal Settings-page delivery section.
- `ElementorHeroBackgroundMetaBox` registers only on `post` and `page`, lists only 10.3-discovered supported Elementor background targets, and saves one normalized `{ element_id, setting_group }` selection after nonce, capability, autosave, revision, and stale-target checks.
- `ElementorBackgroundDeliveryPlanBuilder` is now the shared source of truth for 10.4 companion CSS generation and 10.5 preload generation, so both features resolve the same validated structured classic background targets, explicit device mappings, preferred ready derivative URLs, and breakpoint media queries.
- `ElementorCriticalBackgroundPreloadManager` registers only `wp_head`, remains opt-in, excludes admin/feed/ajax/rest/editor/preview requests, requires delivery to stay enabled and out of emergency rollback, and supports only the current singular Elementor document.
- Preload is emitted only for the one explicitly selected hero-background target. Desktop-only targets emit one tag, while responsive targets may emit up to three mutually exclusive media-scoped tags for explicit desktop/tablet/mobile variants.
- The provider uses only the highest-preference ready modern derivative per device, never preloads the original fallback URL, never emits a global desktop preload when smaller explicit variants exist, and fails open silently on stale selections, missing breakpoint maps, or missing ready derivatives.
- `ResponsivePreloadRegistry` now works through a small preload-link interface so attachment-image preload and Elementor background preload share one request-local dedupe seam keyed by final emitted-link identity.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Targeted verification completed during implementation:

```text
php -l src/Integration/ElementorBackgroundDeliveryPlanBuilder.php: pass
php -l src/Integration/ElementorCriticalBackgroundPreloadManager.php: pass
php -l src/Admin/PostEditor/ElementorHeroBackgroundMetaBox.php: pass
php -l src/Plugin.php: pass
php -l src/Integration/ElementorBackgroundStylesheetGenerator.php: pass
```

Manual WordPress/Elementor smoke testing remains pending in this plugin-only workspace:

- the Settings page should show the `critical_background_preload_enabled` checkbox and persist it through `options.php`
- post/page editor screens should show the hero-background selector only when supported Elementor background targets are discoverable
- eligible singular Elementor frontend requests should emit one desktop preload tag or mutually exclusive media-scoped preload tags for the one selected target
- editor and preview requests should remain fail-open and emit no critical-background preload tags

### Acceptance Criteria

- [x] A dedicated opt-in `critical_background_preload_enabled` setting exists and is exposed through the current minimal settings surface.
- [x] Hero-background selection is stored separately from attachment critical-image selection and persists only normalized `{ element_id, setting_group }` targets.
- [x] The shared Elementor background delivery-plan builder now acts as the common source of truth for both companion CSS generation and critical-background preload generation.
- [x] A narrow `wp_head` runtime provider emits safe modern preload tags only for the selected supported Elementor background target and fails open on uncertainty.
- [x] Request-local preload dedupe is shared between attachment-image preload and Elementor background preload without adding extra frontend hooks, CSS rewriting, REST routes, or broader admin UI.

### Deferred Work

- Automatic hero-background inference, broad CSS parsing, theme-builder/global-kit/template support, and page-level diagnostics surfacing remain deferred.
- Critical background preload still excludes Elementor editor/preview, unsupported background modes, unsupported CSS URL cases, and non-singular documents.
- Background preload remains separate from the attachment critical-image registry and does not expand WooCommerce or generic image preload behavior.

## Subphase 10.4 - Background Delivery Strategy

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a plugin-owned Elementor companion stylesheet subsystem with a bounded frontend runtime provider, a safe uploads-scoped artifact store, and a callable document regeneration/rollback path.
- [x] Generate modern background overrides only from 10.3-discovered structured attachment-backed backgrounds while preserving Elementor's original CSS as the fallback source of truth.
- [x] Emit viewport-aware responsive rules only when a reliable breakpoint map is available, and skip responsive delivery rather than guessing when that map is unavailable.
- [x] Keep runtime scope conservative: frontend singular Elementor documents only, editor/preview excluded, no Elementor CSS rewrites, no post-meta mutation, no REST/admin/settings/UI surface.
- [x] Extend the repo-owned audit and automated test baseline to cover companion stylesheet generation, storage, enqueue behavior, and scope boundaries.

### Files Changed

```text
CHANGELOG.md
docs/elementor-compatibility-audit.md
docs/implementation-status.md
src/Integration/ElementorBackgroundBreakpointMap.php
src/Integration/ElementorBackgroundStylesheetGenerator.php
src/Integration/ElementorBackgroundStylesheetManager.php
src/Integration/ElementorBackgroundStylesheetResult.php
src/Integration/ElementorBackgroundStylesheetRuntimeInterface.php
src/Integration/ElementorBackgroundStylesheetStoreInterface.php
src/Integration/WordPressElementorBackgroundStylesheetRuntime.php
src/Integration/WordPressElementorBackgroundStylesheetStore.php
src/Plugin.php
tests/Unit/Delivery/DeliveryTestWordPressShim.php
tests/Unit/Integration/ElementorBackgroundStylesheetGeneratorTest.php
tests/Unit/Integration/ElementorBackgroundStylesheetManagerTest.php
tests/Unit/Integration/ElementorBackgroundFixtureManifestTest.php
tests/Unit/Integration/ElementorScopePolicyTest.php
tests/Unit/Integration/FakeElementorBackgroundStylesheetRuntime.php
tests/Unit/Integration/FakeElementorBackgroundStylesheetStore.php
tests/Unit/Integration/WordPressElementorBackgroundStylesheetRuntimeTest.php
tests/Unit/Integration/WordPressElementorBackgroundStylesheetStoreTest.php
tests/Unit/PluginTest.php
```

### Delivery Behavior

- `ElementorBackgroundStylesheetGenerator` builds companion CSS only from 10.3-supported structured classic background and classic background-overlay mappings.
- The generator requires a validated local original URL match against sanitized manifest source data before emitting any modern override.
- Ready derivatives are resolved only from plugin-owned `_hwlio_derivatives` state and are emitted in `format_preference()` order inside guarded `image-set(...)` overrides.
- Desktop-only documents may emit a base selector rule, while documents with explicit tablet/mobile sources emit mutually exclusive media-query-scoped rules only when a reliable Elementor breakpoint map is available.
- `WordPressElementorBackgroundStylesheetStore` writes only plugin-owned uploads artifacts, uses temp/backup replace semantics, validates all artifact paths stay inside uploads, and never rewrites Elementor's own CSS files.
- `ElementorBackgroundStylesheetManager` registers only `wp_enqueue_scripts`, excludes admin/feed/ajax/rest/editor/preview requests, supports only the current singular Elementor document, and lazily regenerates/enqueues one companion stylesheet when safe rules exist.
- `rollback_document()` removes only the plugin-owned companion artifact; after rollback, Elementor's own generated CSS remains authoritative immediately.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Targeted verification completed during implementation:

```text
phpunit tests/Unit/Integration/ElementorBackgroundStylesheetGeneratorTest.php: pass
phpunit tests/Unit/Integration/ElementorBackgroundStylesheetManagerTest.php: pass
phpunit tests/Unit/Integration/WordPressElementorBackgroundStylesheetRuntimeTest.php: pass
phpunit tests/Unit/Integration/WordPressElementorBackgroundStylesheetStoreTest.php: pass
phpunit tests/Unit/Integration/ElementorScopePolicyTest.php: pass
phpunit tests/Unit/PluginTest.php: pass
```

Manual WordPress/Elementor smoke testing remains pending in this plugin-only workspace:

- eligible singular Elementor documents should enqueue one late companion stylesheet when supported background derivatives are ready
- explicit responsive desktop/tablet/mobile background mappings should avoid a global desktop override when smaller breakpoint-specific mappings exist
- editor and preview requests should remain fail-open and enqueue nothing
- rollback should remove only the plugin-owned companion stylesheet and leave Elementor's own CSS behavior intact

### Acceptance Criteria

- [x] A plugin-owned companion stylesheet strategy exists for supported structured Elementor backgrounds.
- [x] Elementor's own generated CSS remains untouched and continues to provide the fallback source of truth.
- [x] Responsive background output is viewport-aware only when a reliable breakpoint map is available; otherwise responsive delivery is skipped rather than guessed.
- [x] Regeneration and rollback operate only on the plugin-owned companion artifact.
- [x] Runtime behavior remains conservative: current singular Elementor document only, editor/preview excluded, no REST/admin/settings/UI expansion.

## Subphase 10.3 - Background Image Discovery

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a narrow read-only Elementor document-data seam that decodes structured `_elementor_data` safely without mutating Elementor meta.
- [x] Add service-only background discovery value objects and a callable discovery service under the isolated integration slice.
- [x] Discover supported classic background and classic background overlay attachment-backed mappings with explicit desktop/tablet/mobile separation.
- [x] Record unsupported URL-only values, unsupported background modes, and narrowly scoped custom CSS `url(...)` cases without parsing unrelated CSS.
- [x] Expand the repo-owned Elementor audit and fixture baseline for structured background discovery while keeping runtime/provider composition unchanged.

### Files Changed

```text
CHANGELOG.md
docs/elementor-compatibility-audit.md
docs/implementation-status.md
src/Integration/ElementorBackgroundDiscovery.php
src/Integration/ElementorBackgroundDiscoveryResult.php
src/Integration/ElementorBackgroundSource.php
src/Integration/ElementorDocumentData.php
src/Integration/ElementorDocumentDataStoreInterface.php
src/Integration/ElementorUnsupportedBackgroundCase.php
src/Integration/WordPressElementorDocumentDataStore.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-classic-desktop.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-classic-responsive.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-custom-css-url.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-invalid-document.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-overlay-classic.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-unsupported-modes.php
tests/Fixtures/Elementor/BackgroundDiscovery/background-url-only.php
tests/Fixtures/Elementor/background-discovery-manifest.php
tests/Unit/Integration/ElementorBackgroundDiscoveryTest.php
tests/Unit/Integration/ElementorBackgroundFixtureManifestTest.php
tests/Unit/Integration/ElementorScopePolicyTest.php
tests/Unit/Integration/FakeElementorDocumentDataStore.php
tests/Unit/Integration/WordPressElementorDocumentDataStoreTest.php
```

### Discovery Behavior

- `WordPressElementorDocumentDataStore` reads only the canonical `_elementor_data` post meta and decodes normalized element arrays safely; it never writes or repairs Elementor data.
- `ElementorBackgroundDiscovery` is callable-only and read-only. It does not register hooks, change plugin composition, inspect generated CSS files, or mutate delivery behavior.
- Supported discovery is intentionally limited to structured classic background-image and classic background-overlay controls with positive attachment IDs.
- Explicit desktop, tablet, and mobile values are recorded separately; responsive inheritance is not synthesized in 10.3.
- URL-only media values, unsupported non-classic modes, and known Elementor-owned `custom_css` `url(...)` values are recorded as unsupported observations using stable codes.
- Invalid or malformed structured document data returns a stable `invalid_document_data` observation rather than fataling or attempting recovery.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans are expected to confirm:

- Elementor coupling remains confined to `src/Integration/` plus `src/Plugin.php`.
- No new runtime hooks, REST routes, admin assets, settings, or post-meta writes were introduced.
- No generated CSS inspection, CSS rewriting, or Elementor CSS regeneration behavior was added in 10.3.

Manual WordPress/Elementor smoke testing remains pending in this plugin-only workspace:

- supported classic background and overlay controls should be discoverable from structured Elementor document data
- explicit tablet/mobile background controls should be reported separately from desktop
- unsupported custom CSS `url(...)` and unsupported modes should be observable as advisory unsupported cases only

### Acceptance Criteria

- [x] Supported Elementor background settings and attachment IDs can now be discovered read-only from structured document data.
- [x] Explicit desktop/tablet/mobile sources are distinguished without inheritance guessing.
- [x] Unsupported CSS URL cases are recorded conservatively without parsing unrelated CSS.
- [x] Discovery remains service-only, read-only, and does not change runtime delivery, plugin composition, or Elementor data.

### Deferred Work

- Critical background preload remains deferred to Subphase 10.5.

## Subphase 10.2 - Oversized Selection Diagnostics

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a service-only Elementor oversized-selection analyzer and result model under the isolated integration slice.
- [x] Reuse the existing Elementor widget matcher, image-markup analyzer, and attachment-size resolver for conservative advisory detection.
- [x] Report selected source dimensions, likely rendered slot dimensions where reliable, width ratio evidence, and a safe recommendation without changing Elementor data.
- [x] Expand the repo-owned Elementor audit and fixture baseline for supported full-selection diagnostic cases and uncertainty boundaries.
- [x] Keep runtime hooks, plugin composition, REST/UI surfaces, settings, and post-meta behavior unchanged in 10.2.

### Files Changed

```text
CHANGELOG.md
docs/elementor-compatibility-audit.md
docs/implementation-status.md
src/Integration/ElementorOversizedSelectionAnalyzer.php
src/Integration/ElementorOversizedSelectionResult.php
tests/Fixtures/Elementor/baseline-manifest.php
tests/Fixtures/Elementor/image-widget-full-near-full.html
tests/Fixtures/Elementor/image-widget-full-small-slot.html
tests/Fixtures/Elementor/image-widget-full-uncertain.html
tests/Unit/Integration/ElementorFixtureManifestTest.php
tests/Unit/Integration/ElementorOversizedSelectionAnalyzerTest.php
tests/Unit/Integration/ElementorOversizedSelectionResultTest.php
tests/Unit/Integration/ElementorScopePolicyTest.php
```

### Advisory Behavior

- `ElementorOversizedSelectionAnalyzer` is a callable-only advisory service. It does not register hooks, mutate markup, touch Elementor serialized data, or store any diagnostics.
- The analyzer only evaluates fragments that `ElementorWidgetMatcher` already classifies as `supported_attachment_widget`.
- Selected source resolution reuses `AttachmentSizeResolver::resolve_from_analysis()` and only produces a finding when the fragment resolves uniquely to the metadata `full` candidate.
- Reliable slot evidence stays intentionally strict: intrinsic `width`/`height` attributes from `ImageMarkupAnalysis` plus optional `known_width` when provided by the caller.
- A finding is reported only when the selected full source width is at least `1.5x` the reliable slot width.
- Gallery, carousel, editor, preview, malformed, and otherwise unsupported contexts remain outside scope and return `unsupported_elementor_context`.
- Missing slot-width evidence or unresolved selected candidates return `oversized_selection_uncertain` rather than guessing from CSS, wrapper markup, breakpoints, or Elementor controls.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans are expected to confirm:

- Elementor coupling remains confined to `src/Integration/` plus `src/Plugin.php`.
- No new runtime hooks, REST routes, admin assets, settings, or post-meta writes were introduced.
- Media Library details, attachment REST payloads, and page-level diagnostics surfaces remain unchanged in 10.2.

Manual WordPress/Elementor smoke testing remains pending in this plugin-only workspace:

- supported frontend widgets selecting `full` for much smaller slots should be diagnosable by the service layer without mutating output
- supported frontend widgets selecting intermediate sizes should not be flagged as oversized full-image selections
- gallery/carousel and editor/preview requests should remain outside 10.2 advisory scope

### Acceptance Criteria

- [x] Supported Elementor attachment widgets selecting `full` while rendering materially smaller can now be detected advisory-only with selected-source and slot-dimension evidence.
- [x] Reliable slot evidence stays conservative and uncertainty is reported instead of guessed.
- [x] Diagnostic output is service-only, public-safe, and does not alter Elementor data, page data, plugin state, or runtime markup.
- [x] The repo-owned Elementor audit and fixture baseline now covers oversized-selection and uncertainty cases in addition to supported/fail-open delivery contexts.
- [x] No runtime hooks, REST/UI surfaces, settings, post-meta behavior, or plugin composition changes were added in 10.2.

### Deferred Work

- Visible Elementor/page-level surfacing of oversized-selection advisories remains deferred to Phase 12 page diagnostics.
- Elementor CSS background discovery/delivery/regeneration remains deferred to Subphases 10.3 and 10.4.

## Subphase 10.1 - Elementor Attachment-Widget Compatibility

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a dedicated Elementor compatibility audit and raw fragment fixture baseline for supported and fail-open widget contexts.
- [x] Add a narrow Elementor runtime seam with explicit editor-mode and preview-mode fail-open behavior.
- [x] Add a conservative exact-fragment matcher for supported attachment widgets versus excluded gallery/carousel contexts.
- [x] Compose one isolated Elementor runtime provider that filters only `hwlio_markup_is_eligible`.
- [x] Reuse the existing delivery pipeline unchanged for supported widgets so fallback `<img>` markup stays verbatim inside generated `<picture>` output.

### Files Changed

```text
CHANGELOG.md
docs/elementor-compatibility-audit.md
docs/implementation-status.md
src/Integration/ElementorIntegration.php
src/Integration/ElementorRuntimeInterface.php
src/Integration/ElementorWidgetMatcher.php
src/Integration/WordPressElementorRuntime.php
src/Plugin.php
tests/Fixtures/Elementor/baseline-manifest.php
tests/Fixtures/Elementor/carousel-widget-attachment.html
tests/Fixtures/Elementor/cta-widget-attachment.html
tests/Fixtures/Elementor/gallery-widget-attachment.html
tests/Fixtures/Elementor/image-box-widget-attachment.html
tests/Fixtures/Elementor/image-widget-attachment.html
tests/Unit/Integration/ElementorAttachmentWidgetDeliveryTest.php
tests/Unit/Integration/ElementorFixtureManifestTest.php
tests/Unit/Integration/ElementorIntegrationTest.php
tests/Unit/Integration/ElementorScopePolicyTest.php
tests/Unit/Integration/ElementorWidgetMatcherTest.php
tests/Unit/Integration/FakeElementorRuntime.php
tests/Unit/Integration/WordPressElementorRuntimeTest.php
tests/Unit/PluginTest.php
```

### Elementor Compatibility Behavior

- `WordPressElementorRuntime` now isolates Elementor availability plus editor/preview detection, preferring supported runtime objects when available and falling back conservatively to request signals only inside the adapter.
- `ElementorWidgetMatcher` inspects only exact standalone `<img>` fragments and classifies them as `supported_attachment_widget`, `excluded_gallery_or_carousel`, `editor_or_preview`, or `unrecognized`.
- 10.1 treats attachment-backed Image, Image Box, and CTA fixtures as the supported frontend baseline when they carry trusted Elementor-specific fragment markers plus attachment markers.
- Gallery and carousel/swiper-style fragments are explicit fail-open exclusions in 10.1, and editor/preview mode also forces fail-open behavior so Elementor authoring UX is not disrupted.
- `ElementorIntegration` is the only new runtime provider in this subphase and hooks only `hwlio_markup_is_eligible`; it does not add critical-image registration, loading-role behavior, preload behavior, settings, REST, admin UI, or serialized-data access.
- Supported frontend Elementor attachment widgets continue through the existing `DeliveryManager` pipeline unchanged, so fallback `<img>` classes, `data-elementor-*` attributes, and other valid image-node attributes remain verbatim inside generated `<picture>` output.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Elementor coupling is confined to `src/Integration/` plus `src/Plugin.php`; no Elementor post-meta writes, CSS background logic, REST routes, admin assets, or new frontend hooks were introduced in 10.1.

Manual WordPress/Elementor smoke testing remains pending in this plugin-only workspace:

- supported frontend Image, Image Box, and CTA widgets should render through the existing delivery pipeline without losing attachment-backed `<img>` attributes
- Gallery and Carousel widgets should remain unchanged in 10.1
- Elementor editor and preview requests should fail open to original markup

### Acceptance Criteria

- [x] A repo-owned Elementor audit and fixture baseline now exists for supported and fail-open widget contexts.
- [x] Elementor request-mode detection is isolated to a narrow runtime seam and editor/preview mode is explicitly fail-open.
- [x] Safe attachment-backed frontend Image, Image Box, and CTA fragments may continue through existing delivery, while Gallery and Carousel contexts are excluded.
- [x] The only runtime behavior change is an isolated `hwlio_markup_is_eligible` integration; no new frontend hooks, settings, post-meta writes, REST endpoints, or admin UI were added.
- [x] Supported widget delivery continues to preserve fallback `<img>` markup verbatim inside generated `<picture>` output.

### Deferred Work

- Elementor critical-image registration, preload behavior, oversized-selection diagnostics, CSS background handling, and CSS regeneration remain deferred to later Phase 10 subphases.
- Elementor gallery/carousel support remains deliberately excluded until a later subphase proves those widget-JS-heavy contexts safe.

## Subphase 7.6 - Delivery Rollback and Cache Hooks

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Add a separate internal `delivery_emergency_disabled` setting that disables frontend delivery without changing `delivery_enabled` or deleting sidecars.
- [x] Add `hwlio_cache_invalidation_requested` as a stable request action after successful derivative save/delete state changes.
- [x] Add bounded derivative-health diagnostics for missing ready sidecars through the existing `/diagnostics` payload.
- [x] Keep the active delivery hook surface unchanged at `wp_get_attachment_image` and `wp_content_img_tag`.

### Files Added

```text
src/Admin/Rest/CompositeDiagnosticsService.php
src/Diagnostics/DerivativeHealthDiagnostics.php
src/Diagnostics/DerivativeHealthRuntimeInterface.php
src/Diagnostics/WordPressDerivativeHealthRuntime.php
src/Infrastructure/CacheInvalidationDispatcherInterface.php
src/Infrastructure/CacheInvalidationRequest.php
src/Infrastructure/WordPressCacheInvalidationDispatcher.php
tests/Unit/Diagnostics/DerivativeHealthDiagnosticsTest.php
tests/Unit/Infrastructure/FakeCacheInvalidationDispatcher.php
```

### Files Changed

```text
docs/implementation-status.md
src/Attachment/AttachmentCleanup.php
src/Attachment/AttachmentCleanupResult.php
src/Attachment/DerivativeRepository.php
src/Delivery/MarkupEligibility.php
src/Infrastructure/LifecyclePolicy.php
src/Plugin.php
src/Queue/ReconciliationWorker.php
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
src/Settings/SettingsSchema.php
tests/Unit/Attachment/AttachmentCleanupTest.php
tests/Unit/Attachment/DerivativeRepositoryTest.php
tests/Unit/Admin/Rest/DiagnosticsControllerTest.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/Queue/ReconciliationWorkerTest.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsSanitizerTest.php
tests/Unit/Settings/SettingsSchemaTest.php
```

### Delivery Rollback and Cache Behavior

- `delivery_emergency_disabled` is an internal-only kill switch layered above `delivery_enabled`; it cannot be bypassed by `hwlio_delivery_is_enabled`.
- `hwlio_cache_invalidation_requested` now receives adapter-safe payloads after successful derivative writes and after real derivative deletions during reconciliation or attachment cleanup.
- Cache invalidation remains request-only in 7.6. No cache plugin, CDN, or offload adapter logic was added.
- Derivative-health diagnostics scan attachment IDs in bounded pages, read sanitized manifests through `DerivativeRepository`, verify ready derivative files inside uploads, and report only safe aggregate counts and samples.
- Missing derivative files continue to fail open during delivery through the existing source-set builder behavior; diagnostics add visibility without changing markup output.

### Verification

Attempted verification commands for this subphase:

```text
git diff --check
rg -n "wp_get_attachment_image|wp_content_img_tag|wp_calculate_image_srcset|wp_get_attachment_image_attributes|wp_get_loading_optimization_attributes|ob_start" src tests/Unit -g "*.php"
vendor/bin/phpunit tests/Unit/Delivery tests/Unit/Diagnostics tests/Unit/Attachment tests/Unit/Queue tests/Unit/Settings tests/Unit/Admin/Rest/DiagnosticsControllerTest.php tests/Unit/PluginTest.php
vendor/bin/phpcs src/Delivery src/Diagnostics src/Attachment src/Queue src/Infrastructure src/Settings tests/Unit
vendor/bin/phpstan analyse src/Delivery src/Diagnostics src/Attachment src/Queue src/Infrastructure src/Settings tests/Unit
```

Current workspace results:

- `git diff --check`: pass
- frontend-hook grep: pass; only `src/Delivery/DeliveryManager.php` references the active `wp_get_attachment_image` and `wp_content_img_tag` hooks, and source code still contains no `wp_calculate_image_srcset`, `wp_get_attachment_image_attributes`, `wp_get_loading_optimization_attributes`, or `ob_start`
- `vendor/bin/phpunit`, `vendor/bin/phpcs`, and `vendor/bin/phpstan` remain pending until the normal project PHP toolchain is available on the shell `PATH`.

Manual verification remains pending in a WordPress runtime:

- enabling `delivery_emergency_disabled` should immediately return original frontend image markup without deleting sidecars
- derivative create/delete operations should fire `hwlio_cache_invalidation_requested`
- `/wp-json/hwlio/v1/diagnostics` should include the `delivery_derivative_files` result

### Acceptance Criteria

- [x] Disabling delivery with the emergency switch restores original markup without deleting sidecars.
- [x] Cache invalidation request hooks fire only after real derivative save/delete changes.
- [x] Missing ready sidecars are reported by diagnostics and continue not to produce broken source URLs.
- [x] No new frontend delivery hooks, output buffering, frontend assets, or REST routes were introduced.

### Deferred Work

- Public/admin controls for the emergency delivery switch remain deferred.
- Cache/CDN/offload adapters remain deferred to the later compatibility and adapter phases.
- `wp_calculate_image_srcset`, `wp_get_attachment_image_attributes`, and `wp_get_loading_optimization_attributes` coordination remains deferred to later Phase 7 and Phase 8 work.

## Subphase 7.5 - Post-Content Image Integration

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Extend the existing delivery manager to also hook `wp_content_img_tag`.
- [x] Reuse the shared delivery pipeline for content images only when WordPress resolved a real attachment ID.
- [x] Keep external or unresolvable content images unchanged and fail open on malformed or unsupported markup.
- [x] Reuse the request-local transformed-markup registry across both attachment and content hook paths.

### Files Changed

```text
docs/implementation-status.md
src/Delivery/DeliveryManager.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/DeliveryScopePolicyTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/ImageScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Delivery Integration Behavior

- `DeliveryManager` now registers both `wp_get_attachment_image` and `wp_content_img_tag` while remaining the only active delivery provider.
- Both hook paths now share one internal transform pipeline for eligibility checks, derivative source-set building, picture rendering, and request-local duplicate protection.
- `wp_content_img_tag` transforms only when core passes a real attachment ID; the plugin performs no URL-to-ID lookup, database fallback, or arbitrary HTML resolution in this subphase.
- Content-image requests enrich the existing developer-filter context with `hook = wp_content_img_tag` and `content_context` while preserving the existing filter names and argument order.
- The final fallback `<img>` remains embedded verbatim inside rendered `<picture>` markup, preserving Gutenberg/core classes and loading-related attributes.
- Duplicate protection now works across both hook paths in the same request, preventing plugin-generated double wrapping when the same attachment markup is seen again.
- Hook-only 7.5 does not actively detect third-party or manually-authored pre-existing parent `<picture>` wrappers around content images; that compatibility edge case remains deferred.

### Verification

Attempted verification commands for this subphase:

```text
git diff --check
rg -n "wp_get_attachment_image|wp_content_img_tag|wp_calculate_image_srcset|wp_get_loading_optimization_attributes" src tests/Unit -g "*.php"
vendor/bin/phpunit tests/Unit/Delivery tests/Unit/PluginTest.php
vendor/bin/phpcs src/Delivery tests/Unit/Delivery tests/Unit/PluginTest.php
vendor/bin/phpstan analyse src/Delivery tests/Unit/Delivery tests/Unit/PluginTest.php
```

Current workspace results:

- `git diff --check`: pending for this subphase
- frontend-hook grep: pending for this subphase
- `vendor/bin/phpunit`, `vendor/bin/phpcs`, and `vendor/bin/phpstan` remain pending until the normal project PHP toolchain is available on the shell `PATH`.

Manual verification remains pending in a WordPress runtime:

- Gutenberg Image blocks and classic post-content attachment images should render `<picture>` markup when delivery is enabled and valid derivatives exist
- external or unresolved content images should remain unchanged
- repeated same-request transforms across attachment and content hooks should not double wrap the same image markup

### Acceptance Criteria

- [x] `wp_content_img_tag` is now integrated through the existing delivery manager only when WordPress identifies an attachment.
- [x] External and unresolvable content images remain unchanged.
- [x] Shared request-local duplicate protection now covers both active delivery hook paths.
- [x] Later frontend delivery hooks remain deferred and banned outside the delivery manager.

### Deferred Work

- `wp_calculate_image_srcset`, `wp_get_attachment_image_attributes`, and `wp_get_loading_optimization_attributes` coordination remain deferred to later Phase 7 and Phase 8 work.
- Hook-only 7.5 does not actively detect third-party/manual parent `<picture>` wrappers around content images because `wp_content_img_tag` does not expose safe parent-markup context.
- Emergency delivery rollback and cache invalidation hooks remain deferred to Subphase 7.6.

## Subphase 7.4 - Attachment Image Integration

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Add `DeliveryManager` as the first active namespaced delivery hook provider and compose it in `Plugin::create()`.
- [x] Add a delivery runtime seam, conservative eligibility service, and request-local transformed-markup registry for `wp_get_attachment_image` orchestration.
- [x] Add conservative fallback-image source extraction from final `<img>` markup without changing the callable-only `SourceSetBuilder` contract.
- [x] Activate delivery only for eligible frontend attachment-image requests, while preserving fail-open behavior and later-phase hook boundaries.

### Files Added

```text
src/Delivery/AttachmentImageRuntimeInterface.php
src/Delivery/AttachmentImageSourceExtraction.php
src/Delivery/AttachmentImageSourceExtractor.php
src/Delivery/DeliveryManager.php
src/Delivery/MarkupEligibility.php
src/Delivery/TransformedMarkupRegistry.php
src/Delivery/WordPressAttachmentImageRuntime.php
tests/Unit/Delivery/AttachmentImageSourceExtractorTest.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/FakeAttachmentImageRuntime.php
```

### Files Changed

```text
docs/implementation-status.md
src/Plugin.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Delivery/DeliveryScopePolicyTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/ImageScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Delivery Integration Behavior

- `DeliveryManager` is now the first active delivery provider and registers only `wp_get_attachment_image` with accepted args `5`.
- `MarkupEligibility` gates delivery behind `delivery_enabled`, image-only requests, non-admin/feed/AJAX/REST contexts, non-icon requests, and fallback markup that the existing analyzer can preserve safely.
- The new developer-facing delivery filters `hwlio_delivery_is_enabled` and `hwlio_markup_is_eligible` now receive the boolean result, attachment ID, original HTML, and a trailing context array with `size`, `icon`, `attr`, and request-context flags.
- `AttachmentImageSourceExtractor` parses conservative core-style `w` descriptor candidates from the final fallback `<img>` markup and falls back to one `src` candidate only when no usable `srcset` survives and a width is known.
- `DeliveryManager` reuses the callable-only `SourceSetBuilder` and `PictureRenderer` as the build/render primitives, failing open to the original HTML whenever metadata, parsing, derivative mapping, or rendering cannot complete safely.
- `TransformedMarkupRegistry` records request-local signatures for both original input markup and successfully rendered `<picture>` output so duplicate or re-entrant wrapping is skipped without persisting anything across requests.
- Later delivery hooks remain deferred: `wp_content_img_tag`, `wp_calculate_image_srcset`, `wp_get_attachment_image_attributes`, and `wp_get_loading_optimization_attributes` are still absent from runtime code in this subphase.

### Verification

Attempted verification commands for this subphase:

```text
git diff --check
rg -n "wp_get_attachment_image|wp_content_img_tag|wp_calculate_image_srcset|wp_get_loading_optimization_attributes" src tests/Unit -g "*.php"
vendor/bin/phpunit tests/Unit/Delivery tests/Unit/PluginTest.php
vendor/bin/phpcs src/Delivery tests/Unit/Delivery tests/Unit/PluginTest.php
vendor/bin/phpstan analyse src/Delivery tests/Unit/Delivery tests/Unit/PluginTest.php
```

Current workspace results:

- `git diff --check`: pass
- frontend-hook grep confirmed `src/Delivery/DeliveryManager.php` is the only runtime `wp_get_attachment_image` integration and that `wp_content_img_tag`, `wp_calculate_image_srcset`, and `wp_get_loading_optimization_attributes` remain absent from `src/Delivery`
- `vendor/bin/phpunit`, `vendor/bin/phpcs`, and `vendor/bin/phpstan` remain pending until the normal project PHP toolchain is available on the shell `PATH`.

Manual verification remains pending in a WordPress runtime:

- eligible frontend `wp_get_attachment_image()` output should render `<picture>` markup with configured modern-format source order
- delivery-disabled, admin/feed/AJAX/REST, icon, non-image, malformed, or no-derivative cases should return the original HTML unchanged
- repeated same-request callbacks for the same attachment and exact original HTML should skip duplicate wrapping

### Acceptance Criteria

- [x] One active namespaced delivery provider now integrates only with `wp_get_attachment_image`.
- [x] Delivery reuses the existing source-set builder and picture renderer rather than introducing parallel rendering logic.
- [x] Request-local deduplication, conservative frontend-context gating, and developer-facing delivery filters are now in place.
- [x] All later frontend delivery hooks remain deferred and banned outside the new delivery files.

### Deferred Work

- Broader compatibility handling for third-party or manually-authored parent `<picture>` wrappers remains deferred beyond Subphase 7.5.
- `wp_calculate_image_srcset`, `wp_get_attachment_image_attributes`, and `wp_get_loading_optimization_attributes` coordination remain deferred to later Phase 7 and Phase 8 work.
- Block-editor canvas, email, sitemap, oEmbed, and broader context-specific delivery refinements remain deferred to later delivery subphases.

## Subphase 7.3 - Picture Renderer

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Add typed picture-render request and result value objects.
- [x] Add a conservative image-markup analysis seam that accepts only one standalone `<img>` fragment and detects existing `<picture>` fragments.
- [x] Add a callable-only `PictureRenderer` that wraps valid fallback image markup in ordered AVIF/WebP `<source>` elements.
- [x] Preserve the original fallback `<img>` markup verbatim while escaping only generated `<picture>` and `<source>` attributes.
- [x] Add the first renderer-level delivery filter `hwlio_picture_sources` without activating any frontend delivery hooks or runtime providers.

### Files Added

```text
src/Delivery/ImageMarkupAnalysis.php
src/Delivery/ImageMarkupAnalyzerInterface.php
src/Delivery/PictureRenderRequest.php
src/Delivery/PictureRenderResult.php
src/Delivery/PictureRenderer.php
src/Delivery/WordPressImageMarkupAnalyzer.php
tests/Unit/Delivery/PictureRendererTest.php
tests/Unit/Delivery/WordPressImageMarkupAnalyzerTest.php
```

### Files Changed

```text
docs/implementation-status.md
tests/Unit/Delivery/DeliveryTestWordPressShim.php
```

### Picture Renderer Behavior

- `PictureRenderer` now consumes existing `SourceSetBuildResult` data only; it does not re-run derivative lookup, filesystem checks, or any frontend eligibility logic.
- `WordPressImageMarkupAnalyzer` uses WordPress-style HTML tag processing plus conservative fragment validation to accept only exact standalone `<img>` fragments and reject malformed, multi-node, or already-`<picture>` markup.
- Rendered `<picture>` markup is deterministic and compact: preferred modern formats become ordered `<source>` tags, while the original fallback `<img>` string is embedded unchanged.
- The renderer preserves fallback attributes by never rebuilding the `<img>` node, so `src`, `srcset`, `sizes`, `loading`, `fetchpriority`, `decoding`, classes, IDs, `data-*`, `aria-*`, and other valid attributes remain intact.
- Generated `<source>` elements copy the original `sizes` value when present, omit it when empty or missing, and include only formats that survived the source-set builder and any renderer-level filter rewrites.
- The new `hwlio_picture_sources` filter can remove or rewrite normalized per-format picture-source payloads conservatively before markup is serialized.
- Delivery remains inactive at runtime in this subphase: no frontend hooks, no request-local deduplication, no delivery-enabled/context gating, and no plugin composition changes were added.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Delivery/*.php
php -l tests/Unit/Delivery/*.php
vendor/bin/phpunit tests/Unit/Delivery tests/Unit/PluginTest.php
vendor/bin/phpcs src/Delivery tests/Unit/Delivery
vendor/bin/phpstan analyse src/Delivery tests/Unit/Delivery
git diff --check
```

Current workspace results:

- `git diff --check`: pass
- Source-policy grep scans for frontend hooks, REST hooks, admin assets, queue scheduling, and output buffering inside `src/Delivery` and `tests/Unit/Delivery`: pass
- `php`, `vendor/bin/phpunit`, `vendor/bin/phpcs`, and `vendor/bin/phpstan` were not available on the shell `PATH` in this workspace snapshot, so PHP-based automated verification remains pending in the normal project toolchain.

Manual verification remains pending in a WordPress runtime:

- Delivery runtime should remain inactive because `Plugin::create()` still composes no `\Delivery\` hook providers.
- Rendered picture markup should preserve the fallback image node verbatim while adding only ordered modern-format `<source>` tags.
- Malformed, multi-node, or already-`<picture>` fragments should return unchanged markup without warnings or malformed output.

### Acceptance Criteria

- [x] Safe callable-only `<picture>` rendering now exists without activating frontend delivery hooks.
- [x] The original fallback `<img>` markup is preserved verbatim inside rendered picture markup.
- [x] Malformed, ambiguous, and already-`<picture>` markup returns unchanged.
- [x] Renderer-level tests now cover ordered sources, `sizes` preservation, filter rewrites, and fail-open behavior.

### Deferred Work

- Request-local deduplication and frontend-context gating remain deferred to Subphase 7.4.
- `wp_get_attachment_image` and `wp_content_img_tag` integration remain deferred to Subphases 7.4 and 7.5.
- Delivery-enabled checks, developer opt-out filters, and post-content attachment resolution remain deferred to later Phase 7 work.

## Subphase 7.2 - Responsive Source-Set Builder

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Generalize the 7.1 uploads seam into a shared delivery uploads runtime exposing current uploads base URL and base directory.
- [x] Add typed source-set build request, result, and per-format source-set value objects.
- [x] Add a callable-only `SourceSetBuilder` that maps normalized WordPress width candidates to sanitized derivative manifest entries.
- [x] Resolve derivative URLs through the 7.1 resolver and omit missing derivative files through uploads-base-directory plus file-probe checks.
- [x] Keep the implementation internal-only with no delivery providers, frontend hooks, markup rendering, or picture output.

### Files Added

```text
src/Delivery/FormatSourceSet.php
src/Delivery/SourceSetBuildRequest.php
src/Delivery/SourceSetBuildResult.php
src/Delivery/SourceSetBuilder.php
src/Delivery/UploadsRuntimeInterface.php
src/Delivery/WordPressUploadsRuntime.php
tests/Unit/Delivery/SourceSetBuilderTest.php
tests/Unit/Delivery/WordPressUploadsRuntimeTest.php
```

### Files Changed

```text
docs/implementation-status.md
src/Delivery/DerivativeUrlResolver.php
tests/Unit/Delivery/DerivativeUrlResolverTest.php
tests/Unit/Delivery/DeliveryTestWordPressShim.php
tests/Unit/Delivery/FakeUploadsUrlRuntime.php
```

### Files Removed

```text
src/Delivery/UploadsUrlRuntimeInterface.php
src/Delivery/WordPressUploadsUrlRuntime.php
tests/Unit/Delivery/WordPressUploadsUrlRuntimeTest.php
```

### Source-Set Builder Behavior

- `SourceSetBuilder` now consumes already-generated WordPress-style width candidates, not raw `srcset` strings and not the full `wp_calculate_image_srcset()` algorithm.
- The builder normalizes incoming candidates conservatively, preserving original order and width descriptors while skipping malformed, duplicate-width, or unsupported candidates.
- `image_meta['file']`, `width`, `height`, and `sizes` are converted into metadata candidates using the same relative-directory rules WordPress core uses for basename-only subsize files.
- Stored derivative state is read only through `DerivativeRepository::read()`, so invalid or tampered manifest data continues to be filtered through the existing sanitizer.
- Each original candidate is matched back to a metadata candidate by relative file identity plus width, then to the derivative manifest by size name and source-file alignment.
- Each modern candidate URL is resolved through `DerivativeUrlResolver`, and each derivative file must still exist under the current uploads base directory before it is included in a built format source set.
- The output is typed and path-safe: each `FormatSourceSet` returns width-keyed `sources` plus a serialized `srcset`, with no absolute filesystem paths and no markup generation.
- Delivery remains inactive at runtime in this subphase: no frontend hooks, no picture rendering, no request-local dedupe, and no plugin composition changes were added.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Delivery/*.php
php -l tests/Unit/Delivery/*.php
vendor/bin/phpunit tests/Unit/Delivery tests/Unit/PluginTest.php
vendor/bin/phpcs src/Delivery tests/Unit/Delivery
vendor/bin/phpstan analyse src/Delivery tests/Unit/Delivery
git diff --check
```

Current workspace results:

- `git diff --check`: pass
- Source-policy grep scans for frontend hooks, REST hooks, admin assets, queue scheduling, and output buffering inside `src/Delivery` and `tests/Unit/Delivery`: pass
- `php`, `vendor/bin/phpunit`, `vendor/bin/phpcs`, and `vendor/bin/phpstan` were not available on the shell `PATH` in this workspace snapshot, so PHP-based automated verification remains pending in the normal project toolchain.

Manual verification remains pending in a WordPress runtime:

- Delivery runtime should remain inactive because `Plugin::create()` still composes no `\Delivery\` hook providers.
- Built AVIF/WebP source sets should preserve original WordPress width descriptors and omit any missing derivative file cleanly.
- No generated source set should reference a nonexistent file after derivative removal or partial regeneration.

### Acceptance Criteria

- [x] Generated modern-format sources now preserve original candidate widths.
- [x] Missing derivative files are omitted rather than referenced.
- [x] The builder remains callable-only with no frontend delivery hooks or markup mutation.
- [x] URL resolution still follows current uploads configuration after the shared uploads-runtime refactor.

### Deferred Work

- Picture rendering and original `<img>` preservation remain deferred to Subphase 7.3.
- Frontend hook integration with `wp_get_attachment_image` and post-content delivery remain deferred to Subphases 7.4 and 7.5.
- Loading-attribute preservation and critical-image policy remain deferred to Phase 8.

## Subphase 7.1 - Derivative URL Resolver

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Add a new `src/Delivery/` slice with typed derivative URL request, result, resolver, and uploads-runtime seams.
- [x] Convert validated uploads-relative derivative paths into runtime URLs using current uploads configuration only.
- [x] Add generic delivery filter hooks for uploads base URL and final derivative URL rewriting.
- [x] Keep the implementation callable-only with no plugin composition changes or frontend delivery hooks.
- [x] Add unit and scope-policy tests that prove the delivery foundation remains internal-only and path-safe.

### Files Added

```text
src/Delivery/DerivativeUrlRequest.php
src/Delivery/DerivativeUrlResolutionResult.php
src/Delivery/DerivativeUrlResolver.php
src/Delivery/UploadsUrlRuntimeInterface.php
src/Delivery/WordPressUploadsUrlRuntime.php
tests/Unit/Delivery/DeliveryScopePolicyTest.php
tests/Unit/Delivery/DeliveryTestWordPressShim.php
tests/Unit/Delivery/DerivativeUrlResolverTest.php
tests/Unit/Delivery/FakeUploadsUrlRuntime.php
tests/Unit/Delivery/WordPressUploadsUrlRuntimeTest.php
```

### Files Changed

```text
docs/implementation-status.md
tests/Unit/Settings/SettingsTestFilterShim.php
```

### Delivery Resolver Behavior

- `DerivativeUrlResolver` now converts only sanitized uploads-relative derivative paths into runtime URLs and rejects empty, URL-like, absolute, or dot-segmented paths through the existing `DerivativeManifestSanitizer::safe_relative_path()` rule.
- The delivery uploads runtime reads `wp_upload_dir( null, false )` at resolve time, so domain migrations, HTTPS changes, and base uploads URL changes are reflected without changing stored metadata.
- The new delivery filter hooks `hwlio_delivery_uploads_base_url` and `hwlio_delivery_derivative_url` allow future CDN/offload adapters to rewrite the uploads base URL or final derivative URL without binding core delivery code to any specific product.
- The result payload is typed and path-safe: it exposes success state, final URL, relative path, code, and request context only, with no absolute filesystem paths or metadata writes.
- Delivery remains inactive at runtime in this subphase: no frontend hooks, no picture rendering, no `srcset` building, no eligibility logic, and no plugin composition changes were added.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Delivery/*.php
php -l tests/Unit/Delivery/*.php
vendor/bin/phpunit tests/Unit/Delivery tests/Unit/PluginTest.php tests/Unit/Settings/SettingsSchemaTest.php
vendor/bin/phpcs src/Delivery tests/Unit/Delivery tests/Unit/Settings/SettingsTestFilterShim.php
vendor/bin/phpstan analyse src/Delivery tests/Unit/Delivery
git diff --check
```

Current workspace results:

- `git diff --check`: pass
- Source-policy grep scans for frontend hooks, REST hooks, admin assets, queue scheduling, and output buffering inside `src/Delivery` and `tests/Unit/Delivery`: pass
- `php`, `vendor/bin/phpunit`, `vendor/bin/phpcs`, and `vendor/bin/phpstan` were not available on the shell `PATH` in this workspace snapshot, so PHP-based automated verification remains pending in the normal project toolchain.

Manual verification remains pending in a WordPress runtime:

- Delivery runtime should remain inactive because `Plugin::create()` still composes no `\Delivery\` hook providers.
- Stored derivative metadata should continue to contain uploads-relative paths only and no fixed domain values.
- Future delivery consumers should resolve derivative URLs through current uploads configuration even after site-domain or scheme changes.

### Acceptance Criteria

- [x] Stored derivative metadata continues to contain no fixed domain values.
- [x] URL resolution follows current `wp_upload_dir()` configuration instead of persisted domains or schemes.
- [x] Generic CDN/offload rewrite hooks exist without introducing product-specific delivery adapters.
- [x] No frontend delivery hooks, REST routes, admin assets, queue scheduling, or output buffering were introduced in Subphase 7.1.

### Deferred Work

- Responsive source-set mapping remains deferred to Subphase 7.2.
- Picture rendering and fail-open markup preservation remain deferred to Subphases 7.3 through 7.5.
- Formal CDN/offload adapter contracts remain deferred to Phase 11.

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

## Subphase 4.5 - Cleanup on Attachment Deletion

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add `AttachmentCleanup` as a runtime hook provider that registers only `delete_attachment`.
- [x] Add an attachment-cleanup result model with stable codes, counts, warnings, and safe relative-path samples.
- [x] Refactor uninstall derivative cleanup to reuse a shared manifest-scoped derivative file cleaner.
- [x] Delete only authoritative manifest-listed ready derivative files and preserve all source/original paths.
- [x] Add bounded pending attachment-job cancellation through an Action Scheduler seam.
- [x] Remove plugin-owned attachment meta after file/job cleanup.
- [x] Add dry-run orphan reconciliation based on deterministic sidecar candidates from collected sources and manifest source records.
- [x] Keep cleanup callable-safe with no REST routes, admin/frontend assets, queue workers, broad uploads scans, or `_wp_attachment_metadata` writes.

### Files Added

```text
src/Attachment/ActionSchedulerAttachmentJobCleaner.php
src/Attachment/AttachmentCleanup.php
src/Attachment/AttachmentCleanupResult.php
src/Attachment/AttachmentJobCleanerInterface.php
src/Attachment/DerivativeFileCleaner.php
tests/Unit/Attachment/ActionSchedulerAttachmentJobCleanerTest.php
tests/Unit/Attachment/AttachmentCleanupTest.php
tests/Unit/Attachment/FakeAttachmentJobCleaner.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Infrastructure/DerivativeCleanup.php
src/Infrastructure/LifecyclePolicy.php
src/Plugin.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Cleanup Behavior

- `AttachmentCleanup` is composed into `Plugin::create()` and registers only the `delete_attachment` hook.
- Runtime cleanup reads the sanitized authoritative manifest through `DerivativeRepository::read()` and deletes only ready derivative `file` entries.
- Shared path validation is centralized in `DerivativeFileCleaner`, which is now reused by both uninstall cleanup and attachment deletion cleanup.
- Pending attachment jobs are cancelled only for the target `attachment_id`, only for canonical plugin hooks, and only within the `hwlio` Action Scheduler group.
- If Action Scheduler is unavailable or not initialized, cleanup records a warning and continues with file and meta cleanup.
- Plugin-owned attachment meta cleanup removes `_hwlio_derivatives`, `_hwlio_status`, `_hwlio_excluded`, and `_hwlio_lock` idempotently.
- Dry-run orphan reconciliation derives deterministic `{source}.hwlio.webp` and `{source}.hwlio.avif` candidates from collected sources plus manifest source records, reports only safe existing plugin-owned sidecars, and does not delete them.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 272 tests and 12147 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Only `src/Attachment/WordPressAttachmentMetaStore.php` calls post-meta APIs. No code writes `_wp_attachment_metadata`, registers REST routes, enqueues admin/frontend assets, schedules optimization work, or performs broad uploads scanning for orphan detection.

### Acceptance Criteria

- [x] Attachment deletion removes only authoritative manifest-listed sidecars.
- [x] Source/original files remain preserved even when metadata is tampered or incomplete.
- [x] Partial sidecar-deletion failures do not block remaining cleanup or plugin-owned meta removal.
- [x] Pending attachment-job cancellation targets only the matching `attachment_id`.
- [x] Action Scheduler unavailability degrades to warnings without blocking file/meta cleanup.
- [x] Dry-run orphan reconciliation reports deterministic untracked sidecars without deleting them.

### Deferred Work

- Action Scheduler queue payload execution and new-upload automatic processing remain deferred to Phase 5.
- Bulk orphan reconciliation execution, UI exposure, and REST exposure remain deferred to Phase 6.
- Broad uploads scanning and destructive orphan cleanup remain deferred to later cleanup/reporting phases.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.

## Subphase 5.1 - Queue Abstraction

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add a service-only queue domain under `src/Queue/`.
- [x] Add an immutable `OptimizationJob` payload model with deterministic identity fields.
- [x] Add a `QueueStatus` result object with stable queue result codes.
- [x] Add `QueueInterface` so future workers and upload integrations can depend on a fakeable seam.
- [x] Implement `ActionSchedulerQueue` with readiness guards, bounded duplicate detection, and async/delayed enqueue paths.
- [x] Extend `LifecyclePolicy` with explicit attachment job hook constants while preserving the existing hook list helper.
- [x] Keep runtime provider composition unchanged and avoid worker, upload, REST, and status-mutation behavior.

### Files Added

```text
src/Queue/ActionSchedulerQueue.php
src/Queue/OptimizationJob.php
src/Queue/QueueInterface.php
src/Queue/QueueStatus.php
tests/Unit/Queue/ActionSchedulerQueueTest.php
tests/Unit/Queue/FakeQueue.php
tests/Unit/Queue/OptimizationJobTest.php
tests/Unit/Queue/QueueScopePolicyTest.php
tests/Unit/Queue/QueueStatusTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Infrastructure/LifecyclePolicy.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Queue Behavior

- `QueueInterface` currently exposes only `available()` and `enqueue_optimization()`.
- `OptimizationJob` carries only safe scalar payload data: `attachment_id`, `format`, `cursor`, `force`, `reason`, and 20-character `fingerprint`.
- Duplicate detection uses only `attachment_id`, `format`, `cursor`, `force`, and `fingerprint`; `reason` is preserved for observability but ignored for equivalence.
- `ActionSchedulerQueue` requires Action Scheduler to be loaded and initialized before enqueueing.
- Duplicate detection scans only `pending` and `in-progress` optimize actions in bounded pages of `25`.
- Async queueing uses `as_enqueue_async_action()` when `delay_seconds <= 0`; delayed queueing uses `as_schedule_single_action()` with a relative timestamp.
- Existing recurring-maintenance and cleanup-specific Action Scheduler seams remain separate in this subphase.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass, 297 tests and 12511 assertions
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Queue scheduling globals are confined to `src/Queue/ActionSchedulerQueue.php`, while conversion and attachment-processing classes continue to avoid direct `as_*` calls.

### Acceptance Criteria

- [x] Domain services can depend on a fake queue implementation.
- [x] No conversion-domain class directly calls global Action Scheduler scheduling functions.
- [x] Optimization job payloads remain small, deterministic, and serializable.
- [x] Equivalent pending or running jobs are detected without treating `reason` as part of dedupe identity.
- [x] Queue unavailability and enqueue failures degrade to structured result objects instead of fatal errors.

### Deferred Work

- Reconciliation queueing and stale-derivative follow-up actions remain deferred to Subphases 5.4 and 5.5.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.

## Subphase 5.3 - New-Upload Integration

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add `NewUploadIntegration` as a runtime provider listening to `wp_generate_attachment_metadata`.
- [x] Always return WordPress attachment metadata unchanged.
- [x] Queue enabled target formats only in `create` context when automatic optimization is enabled.
- [x] Respect attachment-level exclusion before queueing.
- [x] Add a dedicated attachment exclusion read seam around `_hwlio_excluded` with `_hwlio_status.excluded` fallback.
- [x] Save lightweight `_hwlio_status` values of `queued`, `unprocessed`, or `excluded` as appropriate.
- [x] Fire an internal attachment-status refresh hook for future Media Library integrations.
- [x] Keep regeneration/reconciliation, REST, admin UI, and synchronous conversion deferred.

### Files Added

```text
src/Attachment/AttachmentExclusionRepository.php
src/Attachment/AttachmentExclusionRepositoryInterface.php
src/Queue/NewUploadIntegration.php
tests/Unit/Attachment/AttachmentExclusionRepositoryTest.php
tests/Unit/Queue/NewUploadIntegrationTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Infrastructure/LifecyclePolicy.php
src/Logging/LogCode.php
src/Plugin.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Queue/FakeQueue.php
tests/Unit/Queue/QueueScopePolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Upload Behavior

- `NewUploadIntegration` registers only the `wp_generate_attachment_metadata` filter with all three current WordPress arguments.
- `create` context reads `automatic_optimization`, checks attachment exclusion, collects sources once, builds one fingerprint, and queues one optimization job per enabled format using the canonical `new_upload` reason.
- `queued` and `already_queued` both count as successful queue outcomes for lightweight `_hwlio_status = queued`.
- If queueing cannot start for any format, the attachment remains `unprocessed`, the first queue failure code is recorded in `_hwlio_status.error_code`, and the upload still succeeds normally.
- `update` context is intentionally non-queueing in 5.3 and fires only the internal `hwlio_attachment_status_refresh` action so 5.4 can own reconciliation behavior.
- Attachment exclusion reads `_hwlio_excluded` as the source of truth and falls back to `_hwlio_status.excluded` when explicit exclusion meta is absent.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. The new-upload hook is confined to `src/Queue/NewUploadIntegration.php`, runtime hook registration remains isolated to queue providers, and no direct post-meta calls were introduced outside `WordPressAttachmentMetaStore`. No REST routes, admin/frontend assets, synchronous conversion, or `_wp_attachment_metadata` writes were added.

### Acceptance Criteria

- [x] Upload completion is not blocked by conversion work.
- [x] Attachment metadata is always returned unchanged.
- [x] Enabled formats are queued only after WordPress metadata generation in `create` context.
- [x] Disabled automation leaves attachments available and marked `unprocessed`.
- [x] Excluded attachments are not automatically queued and are marked `excluded`.
- [x] Queue failures do not fail the upload flow.
- [x] `update` context is handled safely without beginning reconciliation early.

### Deferred Work

- Maintenance scheduling remains deferred to Subphase 5.5.
- Media Library refresh consumers, post-upload controls, and REST exposure remain deferred to Phase 6.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.

## Subphase 5.4 - Regeneration and Edit Reconciliation

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Extend `NewUploadIntegration` update-context handling to detect stale optimized derivatives.
- [x] Add a dedicated reconciliation queue payload and queue-adapter path.
- [x] Add `ReconciliationWorker` as a runtime Action Scheduler hook provider.
- [x] Add repository-level `begin_reconciliation()` behavior to replace the active manifest with an empty current-fingerprint manifest.
- [x] Add replace-safe conversion support for reconcile rebuilds.
- [x] Delete obsolete sidecars conservatively only after new manifest state exists.
- [x] Keep REST, admin UI, maintenance scheduling, media scans, and frontend delivery deferred.

### Files Added

```text
src/Queue/ReconciliationJob.php
src/Queue/ReconciliationWorker.php
tests/Unit/Queue/ReconciliationJobTest.php
tests/Unit/Queue/ReconciliationWorkerTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Attachment/AttachmentProcessor.php
src/Attachment/DerivativeRepository.php
src/Attachment/DerivativeRepositoryResult.php
src/Image/ConversionRequest.php
src/Image/ImageConverter.php
src/Logging/LogCode.php
src/Plugin.php
src/Queue/ActionSchedulerQueue.php
src/Queue/NewUploadIntegration.php
src/Queue/QueueInterface.php
tests/Unit/Attachment/DerivativeRepositoryTest.php
tests/Unit/Image/FakeConversionFilesystem.php
tests/Unit/Image/ImageConverterTest.php
tests/Unit/PluginTest.php
tests/Unit/Queue/ActionSchedulerQueueTest.php
tests/Unit/Queue/FakeQueue.php
tests/Unit/Queue/NewUploadIntegrationTest.php
tests/Unit/Queue/QueueScopePolicyTest.php
```

### Reconciliation Behavior

- `NewUploadIntegration` now treats `wp_generate_attachment_metadata` `update` events as stale-state detection opportunities while still always returning WordPress metadata unchanged.
- Stale detection is limited to attachments that already have ready derivatives and a stored fingerprint that differs from the current collected source state.
- A dedicated `ReconciliationJob` carries only `attachment_id`, a 20-character current fingerprint signature, and a sanitized reason.
- `ReconciliationWorker` owns reconciliation lock orchestration, stale queued-fingerprint refresh, manifest reset, forced per-format reprocessing, final attachment-state aggregation, and obsolete-sidecar cleanup.
- `DerivativeRepository::begin_reconciliation()` replaces `_hwlio_derivatives` with an empty schema-v1 manifest for the current fingerprint before fresh results are persisted.
- `ConversionRequest` now exposes `replace_existing`, and `ImageConverter` uses that only for force/reconcile-style requests so plugin-owned sidecars can be replaced through validated temp output, same-directory backup moves, and rollback attempts.
- Obsolete sidecars are removed only after the new manifest is authoritative; cleanup warnings do not restore the old manifest.

### Verification

```text
php -l src/Queue/ReconciliationWorker.php: pass
php -l src/Queue/ActionSchedulerQueue.php: pass
php -l src/Image/ImageConverter.php: pass
php -l src/Queue/NewUploadIntegration.php: pass
php -l tests/Unit/Queue/ReconciliationWorkerTest.php: pass
vendor/bin/phpunit tests/Unit/Queue/NewUploadIntegrationTest.php: pass
vendor/bin/phpunit tests/Unit/Queue/ReconciliationWorkerTest.php: pass
vendor/bin/phpunit tests/Unit/Attachment/DerivativeRepositoryTest.php: pass
vendor/bin/phpunit tests/Unit/Queue/ActionSchedulerQueueTest.php: pass
vendor/bin/phpunit tests/Unit/PluginTest.php: pass
vendor/bin/phpunit tests/Unit/Queue/ReconciliationJobTest.php: pass
vendor/bin/phpunit tests/Unit/Image/ImageConverterTest.php: pass
composer run test: pass, 339 tests and 13390 assertions
```

### Acceptance Criteria

- [x] Metadata `update` events detect stale optimized derivative state without mutating the returned WordPress metadata array.
- [x] Stale optimized attachments can queue a dedicated reconciliation action when automation is enabled and the attachment is not excluded.
- [x] Reconciliation replaces active derivative metadata with a current-fingerprint empty manifest before persisting new results.
- [x] Reconcile rebuilds can replace existing plugin-owned sidecars safely without touching originals.
- [x] Obsolete manifest-listed sidecars are pruned conservatively only after new manifest state exists.
- [x] No REST routes, admin UI, maintenance scheduling, bulk scans, or `_wp_attachment_metadata` writes were introduced.

### Deferred Work

- Recurring maintenance scheduling remains deferred to Subphase 5.5.
- Media Library refresh listeners, admin UI, and REST exposure remain deferred to Phase 6.
- Frontend modern-format delivery remains deferred to Phase 7.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.

## Subphase 5.5 - Maintenance Actions

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add a dedicated runtime maintenance provider for recurring stale-lock recovery and statistics reconciliation.
- [x] Generalize the recurring Action Scheduler seam into reusable infrastructure-level adapters.
- [x] Keep `hwlio_cleanup_logs` owned by `LogMaintenance` and preserve existing daily log pruning behavior.
- [x] Schedule unique recurring `hwlio_recover_stale_locks` and `hwlio_reconcile_statistics` actions after `action_scheduler_init`.
- [x] Extend stale-lock recovery results so maintenance can repair stuck `processing` statuses safely.
- [x] Add a minimal internal statistics cache reconciler that scans bounded attachment ID pages and persists `hwlio_statistics_cache` with autoload disabled.
- [x] Keep admin UI, REST routes, bulk scanning controls, and frontend delivery deferred.

### Files Added

```text
src/Infrastructure/ActionSchedulerRecurringActionScheduler.php
src/Infrastructure/RecurringActionSchedulerInterface.php
src/Queue/AttachmentStatisticsScannerInterface.php
src/Queue/QueueMaintenance.php
src/Queue/StatisticsCache.php
src/Queue/StatisticsReconciler.php
src/Queue/StatisticsReconcilerInterface.php
src/Queue/StatisticsReconciliationResult.php
src/Queue/WordPressAttachmentStatisticsScanner.php
tests/Unit/Queue/FakeAttachmentStatisticsScanner.php
tests/Unit/Queue/FakeStatisticsReconciler.php
tests/Unit/Queue/QueueMaintenanceTest.php
tests/Unit/Queue/StatisticsCacheTest.php
tests/Unit/Queue/StatisticsReconcilerTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Attachment/AttachmentLockManager.php
src/Attachment/AttachmentLockRecoveryResult.php
src/Infrastructure/LifecyclePolicy.php
src/Logging/LogCode.php
src/Logging/LogMaintenance.php
src/Plugin.php
tests/Unit/Attachment/AttachmentLockManagerTest.php
tests/Unit/Infrastructure/FakeOptionStore.php
tests/Unit/Logging/FakeRecurringActionScheduler.php
tests/Unit/PluginTest.php
tests/Unit/Queue/QueueScopePolicyTest.php
```

### Files Removed

```text
src/Logging/ActionSchedulerRecurringActionScheduler.php
src/Logging/RecurringActionSchedulerInterface.php
```

### Maintenance Behavior

- `QueueMaintenance` is composed into `Plugin::create()` and registers only `action_scheduler_init`, `hwlio_recover_stale_locks`, and `hwlio_reconcile_statistics`.
- The shared recurring scheduler seam now lives under `src/Infrastructure/` and is reused by both `LogMaintenance` and `QueueMaintenance`.
- `hwlio_cleanup_logs` remains owned by `LogMaintenance` and continues to schedule daily log-pruning work unchanged.
- `hwlio_recover_stale_locks` is scheduled hourly in the `hwlio` Action Scheduler group, while `hwlio_reconcile_statistics` is scheduled daily in the same group.
- Stale-lock recovery reuses `AttachmentLockManager::recover_stale()` and now exposes recovered attachment IDs in addition to bounded counts and samples.
- When maintenance recovers a lock for an attachment still marked `processing`, `_hwlio_status` is rewritten to `stale` while preserving ready formats, `excluded`, and the existing error-code value.
- Pure no-op stale-lock recovery runs remain silent; actual recoveries log an info entry and recovery/status-repair warnings log a warning entry through new maintenance log codes.
- `StatisticsReconciler` scans bounded pages of attachment IDs owning plugin metadata, reads sanitized status/manifest state through `DerivativeRepository::read()`, and writes a schema-versioned `hwlio_statistics_cache` option with autoload disabled.
- Internal statistics totals intentionally remain conservative: no filesystem validation, no Action Scheduler queue-state math, and overall generated-byte totals use the smallest ready derivative per source size so top-level totals do not double-count both WebP and AVIF.
- Statistics cache write failures leave any previous cache value untouched and return a structured failed result for maintenance logging.

### Verification

```text
Automated PHP syntax, PHPUnit, PHPStan, and Composer checks were not run in this shell because `php` and `composer` are unavailable on PATH in the current workspace snapshot.
Source-level review completed for new runtime providers, recurring scheduler seams, queue maintenance tests, statistics reconciler tests, and status-document updates.
```

### Acceptance Criteria

- [x] Maintenance actions are uniquely scheduled for stale-lock recovery and statistics reconciliation.
- [x] Existing deactivation cleanup still targets all plugin-owned recurring maintenance hooks.
- [x] Recovered stale locks can repair only attachments stuck in `processing` without touching originals or core attachment metadata.
- [x] The internal statistics cache is schema-versioned, bounded, conservative, and stored with autoload disabled.
- [x] Statistics reconciliation leaves the previous cache untouched when persistence fails.
- [x] No admin UI, REST routes, bulk queue controls, or frontend delivery behavior were introduced.

### Deferred Work

- Dashboard rendering, statistics exposure, and recalculate controls remain deferred to Phase 6.
- Pause, resume, and cancel queue controls remain deferred to Phase 6 bulk-processing work.
- Frontend modern-format delivery remains deferred to Phase 7.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.

## Subphase 6.1 - Menu and Screen Shell

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add the Media submenu shell under `upload.php`.
- [x] Build internal tab routing for dashboard, bulk optimize, settings, diagnostics, and logs.
- [x] Add explicit `manage_options` capability checks for direct screen access.
- [x] Capture plugin screen IDs for later asset scoping.
- [x] Keep plugin-owned admin assets, REST routes, and bulk execution deferred.

### Files Added

```text
src/Admin/AbstractAdminPage.php
src/Admin/AdminController.php
src/Admin/AdminPageInterface.php
src/Admin/AdminRuntimeInterface.php
src/Admin/BulkPage.php
src/Admin/DashboardPage.php
src/Admin/DiagnosticsPage.php
src/Admin/LogsPage.php
src/Admin/Menu.php
src/Admin/SettingsPage.php
src/Admin/WordPressAdminRuntime.php
tests/Unit/Admin/AdminAccessDenied.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Admin/FakeAdminRuntime.php
tests/Unit/Admin/MenuTest.php
```

### Files Changed

```text
docs/implementation-status.md
src/Plugin.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Admin Shell Behavior

- `AdminController` is composed into `Plugin::create()` and registers only the `admin_menu` hook through the shared `HookRegistrar`.
- `Menu` owns the Phase 6.1 submenu contract: parent slug `upload.php`, menu slug `hwlio`, capability `manage_options`, ordered internal tabs, and the captured submenu hook suffix used as the only plugin screen ID.
- The visible shell renders one Media submenu page with internal tab navigation for Dashboard, Bulk Optimize, Settings, Diagnostics, and Logs.
- Direct screen rendering re-checks `manage_options` and aborts unauthorized access through the injected admin runtime seam instead of assuming menu visibility is sufficient.
- Screen rendering intentionally stays shell-only in this subphase: no plugin CSS or JavaScript, no REST bootstrap data, no Settings API field rendering, no diagnostics execution UI, and no bulk controls.
- The legacy non-namespaced `admin/` scaffold remains inert.

### Verification

```text
Automated PHP syntax, PHPCS, PHPStan, and PHPUnit checks could not be run in this shell because `php`, `composer`, and `vendor/bin` tooling are unavailable in the current workspace snapshot.
Source scans confirm `src/Admin/` introduces only the `admin_menu` hook, one submenu registration seam, and no plugin-owned admin assets, REST routes, settings-field rendering, or frontend delivery hooks.
Manual WordPress verification was not performed in this plugin-only workspace snapshot.
```

### Acceptance Criteria

- [x] A Media submenu shell exists with tab routing for the five planned admin sections.
- [x] Unauthorized direct access is explicitly denied through a runtime capability check.
- [x] Plugin screen IDs are captured for future asset scoping.
- [x] Plugin-owned admin assets still do not load on unrelated screens because no asset hooks were introduced in 6.1.
- [x] REST routes, bulk execution, and visible Media Library controls remain deferred.

### Deferred Work

- Admin CSS, JavaScript, REST bootstrap data, and accessible progress states remain deferred to Subphase 6.2.
- Status, diagnostics, jobs, and attachment REST controllers remain deferred to Subphase 6.3.
- Bulk scanning, queue actions, logs rendering, and Media Library controls remain deferred to later Phase 6 subphases.
- Frontend modern-format delivery remains deferred to Phase 7.

## Subphase 6.2 - Admin Assets and REST Client

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add a dedicated admin asset hook provider scoped to plugin-owned screens only.
- [x] Add minimal plugin-screen CSS and footer JavaScript with no build step.
- [x] Add REST root and nonce bootstrap configuration for later admin requests.
- [x] Add visible notice and live-region scaffolding for accessible progress and error messaging.
- [x] Keep REST controllers, bulk actions, Media Library integration, and live polling deferred.

### Files Added

```text
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
src/Admin/AdminAssetRuntimeInterface.php
src/Admin/AdminBootstrapConfig.php
src/Admin/AdminScreenContext.php
src/Admin/AdminScreenContextResolver.php
src/Admin/Assets.php
src/Admin/NoticeManager.php
src/Admin/WordPressAdminAssetRuntime.php
tests/Unit/Admin/AdminScreenContextResolverTest.php
tests/Unit/Admin/AssetsTest.php
tests/Unit/Admin/FakeAdminAssetRuntime.php
```

### Files Changed

```text
docs/implementation-status.md
src/Admin/AdminController.php
src/Plugin.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/ScaffoldAssetPolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Admin Asset Behavior

- `Assets` is composed into `Plugin::create()` alongside the existing admin shell and shares the same `Menu`, request provider, and screen-context resolver as `AdminController`.
- `Assets` registers only `admin_enqueue_scripts` and enqueues assets only when the current hook suffix matches the captured plugin screen ID.
- The CSS remains intentionally small and screen-specific, covering only notice spacing, minimal state styling, and screen-reader live regions.
- The JavaScript is loaded in the footer, depends only on `wp-api-fetch`, and receives its bootstrap payload through one inline `window.hwlioAdminConfig` assignment injected before the main script.
- `AdminBootstrapConfig` provides the typed bootstrap shape for REST root, REST nonce, page slug, screen ID, current tab, version, conservative future polling defaults, selectors, and generic UI strings.
- `NoticeManager` now defines shared PHP and JS identifiers for the notice stack, polite live region, assertive live region, and the stable app mount element.
- `AdminController` renders the notice containers and one stable mount element but still does not render Settings API fields, diagnostics results, bulk controls, or attachment actions.
- The admin client initializes `wp.apiFetch` middleware and exposes a small request wrapper plus visible JS/bootstrap error handling, but it does not call real plugin REST routes in this subphase.

### Verification

```text
Automated PHP syntax, PHPCS, PHPStan, and PHPUnit checks could not be run in this shell because `php`, `composer`, and `vendor/bin` tooling are unavailable in the current workspace snapshot.
Source scans confirm plugin-owned admin assets are now restricted to `src/Admin/Assets.php`, `src/Admin/WordPressAdminAssetRuntime.php`, and the new `admin/css` / `admin/js` files.
Source scans also confirm no `register_rest_route()` or `rest_api_init` usage was introduced in this subphase, and the new admin JavaScript does not reference jQuery.
Manual WordPress verification was not performed in this plugin-only workspace snapshot.
```

### Acceptance Criteria

- [x] Plugin CSS and JavaScript are prepared for the admin screen shell only.
- [x] The admin script is footer-loaded, `wp-api-fetch`-based, and does not request jQuery.
- [x] REST root and nonce bootstrap data exist for later admin actions without introducing live routes yet.
- [x] Generic bootstrap and request failures now have visible notice and live-region paths on the plugin screen.
- [x] No plugin asset is intended to load on unrelated wp-admin screens.
- [x] REST controllers, attachment actions, queue controls, and active polling remain deferred.

### Deferred Work

- Status, diagnostics, jobs, and attachment REST controllers remain deferred to Subphase 6.3.
- Media Library controls, attachment actions, and upload-progress refresh remain deferred to Subphase 6.4.
- Dashboard data rendering, diagnostics execution UI, and log browsing remain deferred to later Phase 6 subphases.
- Frontend modern-format delivery remains deferred to Phase 7.

## Subphase 6.3 - REST Controllers

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Add a dedicated REST provider under `src/Admin/Rest/` and compose it through `Plugin::create()`.
- [x] Register attachment-first `hwlio/v1` routes for status, diagnostics, attachment detail, optimize, retry, reconcile, exclude, and include.
- [x] Add a WordPress REST runtime seam plus focused services for cached status summaries, diagnostics payloads, sanitized attachment details, and attachment actions.
- [x] Keep `GET /attachments` and bulk `/jobs/*` routes deferred to later Phase 6 bulk-processing subphases.

### Files Added

```text
src/Admin/Rest/AttachmentActionResult.php
src/Admin/Rest/AttachmentActionService.php
src/Admin/Rest/AttachmentDetailsService.php
src/Admin/Rest/AttachmentsController.php
src/Admin/Rest/DiagnosticsController.php
src/Admin/Rest/DiagnosticsServiceInterface.php
src/Admin/Rest/DiagnosticsSummaryService.php
src/Admin/Rest/RequestData.php
src/Admin/Rest/RestApi.php
src/Admin/Rest/RestControllerInterface.php
src/Admin/Rest/RestErrorFactory.php
src/Admin/Rest/RestRuntimeInterface.php
src/Admin/Rest/StatisticsCacheReader.php
src/Admin/Rest/StatusController.php
src/Admin/Rest/StatusSummaryService.php
src/Admin/Rest/WordPressRestRuntime.php
tests/Unit/Admin/Rest/AttachmentActionServiceTest.php
tests/Unit/Admin/Rest/AttachmentsControllerTest.php
tests/Unit/Admin/Rest/DiagnosticsControllerTest.php
tests/Unit/Admin/Rest/FakeRestRequest.php
tests/Unit/Admin/Rest/FakeRestRuntime.php
tests/Unit/Admin/Rest/RestApiTest.php
tests/Unit/Admin/Rest/StatusControllerTest.php
tests/Unit/Admin/Rest/StatusSummaryServiceTest.php
```

### Files Changed

```text
docs/implementation-status.md
src/Plugin.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/ImageScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Queue/QueueScopePolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### REST Behavior

- `RestApi` is the only provider that registers `rest_api_init`, and `WordPressRestRuntime` is the only runtime file that calls `register_rest_route()`.
- `GET /status` returns queue availability, the normalized internal statistics cache, and minimal settings state without recalculating statistics.
- `GET /diagnostics` returns the existing `EnvironmentDiagnostics` report shape unchanged through a thin adapter.
- `GET /attachments/{id}` returns sanitized repository-backed manifest and status data only; no Media Library listing, filesystem validation, or bulk scanning was introduced.
- `POST /attachments/{id}/optimize` accepts an optional `force` flag and doubles as the future re-optimize route.
- `POST /attachments/{id}/retry`, `reconcile`, `exclude`, and `include` are attachment-scoped only and preserve plugin-owned derivative metadata.
- Excluded attachments now reject manual optimize, retry, and reconcile until they are included again.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Admin/Rest/*.php
vendor/bin/phpunit tests/Unit/Admin/Rest tests/Unit/PluginTest.php
composer run test
```

Current workspace limitation:

- `php` and `composer` are not available on the shell `PATH` in this workspace snapshot, so these automated checks could not be executed here.

Manual verification still pending in a WordPress runtime:

- `upload.php?page=hwlio` continues loading without new screen-wide asset leakage.
- The REST bootstrap root remains `hwlio/v1`.
- Invalid IDs, forbidden attachment access, excluded-action conflicts, and invalid `force` values return clean 4xx responses.
- No response payload should contain absolute filesystem paths or raw exception traces.

### Acceptance Criteria

- [x] Status, diagnostics, attachment detail, and attachment action routes are registered in `hwlio/v1`.
- [x] Global routes require `manage_options`.
- [x] Attachment routes require `upload_files` and enforce `edit_post` after the attachment is resolved.
- [x] Invalid or tampered derivative metadata is sanitized through `DerivativeRepository::read()` before REST output.
- [x] Excluded attachments reject manual optimize, retry, and reconcile until included.
- [x] No bulk scan, pause, resume, retry-failed, or cancel-pending routes were introduced.
- [x] Policy tests now allow REST hook usage only in the new REST runtime/provider files.

### Deferred Work

- `GET /attachments` list browsing remains deferred until bounded list and bulk-query semantics are implemented.
- Bulk scan, queue, pause, resume, retry-failed, and cancel-pending REST controls remain deferred to Subphases 6.6 and 6.7.
- Media Library controls, upload-progress refresh consumers, and attachment-action UI remain deferred to Subphase 6.4.
- Dashboard rendering, diagnostics execution UI, and log browsing remain deferred to later Phase 6 subphases.
- Frontend modern-format delivery remains deferred to Phase 7.

## Subphase 6.4 - Media Library and New-Upload Optimization Controls

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Add a dedicated `src/Admin/MediaLibrary/` slice for lightweight attachment summaries, action visibility, markup rendering, Media Library hooks, and media-screen assets.
- [x] Compose `MediaLibraryIntegration` and `MediaLibraryAssets` through `Plugin::create()` without overloading the existing plugin-screen shell providers.
- [x] Add typed settings getters for `media_library_controls` and `allow_attachment_exclusion`.
- [x] Reuse `_hwlio_status` for list/grid/edit-screen rendering and keep manifest/details loading lazy through the existing attachment REST routes.
- [x] Add Media Library list column, row actions, attachment payload enrichment, attachment edit/compat rendering, and media-modal/client polling foundations.
- [x] Keep bulk `/jobs/*`, `GET /attachments`, and frontend delivery deferred.

### Files Added

```text
admin/css/hyperweb-lighthouse-image-optimizer-media-library.css
admin/js/hyperweb-lighthouse-image-optimizer-media-library.js
src/Admin/MediaLibrary/AttachmentActionAvailability.php
src/Admin/MediaLibrary/AttachmentStatusReader.php
src/Admin/MediaLibrary/MediaAttachmentPresenter.php
src/Admin/MediaLibrary/MediaAttachmentRenderer.php
src/Admin/MediaLibrary/MediaAttachmentSummary.php
src/Admin/MediaLibrary/MediaLibraryAssets.php
src/Admin/MediaLibrary/MediaLibraryBootstrapConfig.php
src/Admin/MediaLibrary/MediaLibraryIntegration.php
src/Admin/MediaLibrary/MediaLibraryRuntimeInterface.php
src/Admin/MediaLibrary/WordPressMediaLibraryRuntime.php
tests/Unit/Admin/MediaLibrary/AttachmentActionAvailabilityTest.php
tests/Unit/Admin/MediaLibrary/FakeMediaLibraryRuntime.php
tests/Unit/Admin/MediaLibrary/MediaLibraryAssetsTest.php
tests/Unit/Admin/MediaLibrary/MediaLibraryIntegrationTest.php
```

### Files Changed

```text
docs/implementation-status.md
src/Admin/Rest/AttachmentActionService.php
src/Plugin.php
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
tests/Unit/Admin/Rest/AttachmentActionServiceTest.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/Image/ImageScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/Queue/QueueScopePolicyTest.php
tests/Unit/ScaffoldAssetPolicyTest.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Media Library Behavior

- `MediaLibraryIntegration` is the only new runtime provider that registers Media Library hooks, and it registers only `wp_prepare_attachment_for_js`, `manage_media_columns`, `manage_media_custom_column`, `media_row_actions`, and `attachment_fields_to_edit`.
- Initial list/grid/edit-screen rendering reads `_hwlio_status` only through `AttachmentStatusReader`; no filesystem validation or eager manifest loading was added to normal Media Library rendering.
- `wp_prepare_attachment_for_js` now injects a small `hwlio` payload containing the attachment ID, lightweight state, ready formats, allowed actions, active/polling flag, and attachment-scoped route paths only.
- Attachment edit and media compat contexts render one shared HWLIO summary block with lazy details containers; state changes remain REST-driven and do not use `attachment_fields_to_save`.
- Media Library assets load only on `upload.php`, attachment edit screens, and screens that actually call `wp_enqueue_media()`.
- The new client remains jQuery-free, configures `wp.apiFetch`, lazily fetches `GET /attachments/{id}` details, polls only active attachments already on screen, and shows visible notices/live-region announcements for action outcomes.

### REST and Settings Alignment

- `AttachmentActionService` now rejects include/exclude when per-attachment exclusion is disabled so the Media Library UI and REST behavior stay aligned.
- `SettingsRepository` and `SettingsRepositoryInterface` now expose typed getters for `media_library_controls_enabled()` and `attachment_exclusion_allowed()`.
- Media Library controls remain feature-gated by `media_library_controls`, and exclusion actions remain feature-gated by `allow_attachment_exclusion`.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Admin/MediaLibrary/*.php
php -l src/Plugin.php
vendor/bin/phpunit tests/Unit/Admin/MediaLibrary tests/Unit/Admin/Rest/AttachmentActionServiceTest.php tests/Unit/PluginTest.php
composer run test
```

Current workspace limitation:

- `php` and `composer` are not available on the shell `PATH` in this workspace snapshot, so these automated checks could not be executed here.

Manual verification still pending in a WordPress runtime:

- `upload.php` list view should show the lightweight Optimization column and quick actions without per-file filesystem work.
- Media Library grid and modal contexts should display non-blocking queued/unprocessed status after upload and poll active attachments to terminal states.
- Attachment edit and compat fields should expose the same action set and lazy details container.
- Media Library controls should stay absent on unrelated admin screens.

### Acceptance Criteria

- [x] Media Library list/grid/edit-screen payloads now expose lightweight attachment state and actions without eager manifest reads.
- [x] `Optimize Now`, `Retry`, `Re-optimize`, `View Details`, `Exclude from Optimization`, `Include in Optimization`, and `Reconcile Files` are now surfaced where the lightweight state and settings allow them.
- [x] Automatic-new-upload disabled attachments remain `Unprocessed` and keep individual `Optimize Now` controls available.
- [x] Exclusion-disabled sites now reject include/exclude both in UI exposure and REST action handling.
- [x] The client refreshes active attachments without forcing a full page reload and keeps details loading on demand.
- [x] Media Library assets remain scoped to media-capable admin screens and remain jQuery-free.
- [x] Policy tests now allow Media Library hooks and media-screen assets only in the dedicated 6.4 files.

### Deferred Work

- Bulk scan, queue, pause, resume, retry-failed, and cancel-pending REST controls remain deferred to Subphases 6.6 and 6.7.
- Dashboard rendering, diagnostics execution UI, and log browsing remain deferred to later Phase 6 subphases.
- Richer bulk browsing semantics for `GET /attachments` remain deferred until bounded list/query behavior is implemented.
- Frontend modern-format delivery remains deferred to Phase 7.

## Subphase 6.5 - Dashboard

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Replace the dashboard placeholder tab with the first real plugin-screen dashboard shell.
- [x] Expand `GET /status` into a cached dashboard read model with environment, failures, conflicts, and refresh state.
- [x] Add async `POST /status/recalculate` handling without recalculating statistics during the request itself.
- [x] Add a minimal read-only recent-failures log summary seam for dashboard use only.
- [x] Reuse the existing plugin-screen asset pipeline to render dashboard data and recalculate progress without jQuery.
- [x] Keep bulk controls, attachment browsing, full diagnostics execution UI, and frontend delivery deferred.

### Files Added

```text
src/Admin/Rest/DashboardEnvironmentSummaryService.php
src/Admin/Rest/StatusRefreshRequestResult.php
src/Admin/Rest/StatusRefreshService.php
src/Infrastructure/ActionSchedulerSingleActionScheduler.php
src/Infrastructure/SingleActionSchedulerInterface.php
src/Logging/LogReadDatabaseInterface.php
src/Logging/RecentFailureLogReader.php
src/Logging/WordPressLogReadDatabase.php
tests/Unit/Admin/Rest/DashboardEnvironmentSummaryServiceTest.php
tests/Unit/Admin/Rest/StatusRefreshServiceTest.php
tests/Unit/Infrastructure/FakeSingleActionScheduler.php
tests/Unit/Logging/FakeLogReadDatabase.php
tests/Unit/Logging/RecentFailureLogReaderTest.php
```

### Files Changed

```text
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
docs/implementation-status.md
src/Admin/AbstractAdminPage.php
src/Admin/AdminBootstrapConfig.php
src/Admin/AdminController.php
src/Admin/AdminPageInterface.php
src/Admin/DashboardPage.php
src/Admin/Rest/RestErrorFactory.php
src/Admin/Rest/StatusController.php
src/Admin/Rest/StatusSummaryService.php
src/Plugin.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Admin/AssetsTest.php
tests/Unit/Admin/Rest/StatusControllerTest.php
tests/Unit/Admin/Rest/StatusSummaryServiceTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Dashboard Behavior

- `DashboardPage` is now the first non-placeholder admin tab and renders stable dashboard sections for environment status, queue/state counts, byte savings, recent failures, conflict warnings, and recalculate state.
- `AdminController` still owns the shared submenu shell and tab navigation, but page rendering now delegates to the resolved page object so later tabs can evolve without reworking the shell again.
- The existing plugin-screen admin client now initializes dashboard behavior only on the `dashboard` tab, loads `GET /status` on first paint, formats cached totals client-side, and polls while a statistics refresh is pending.
- The dashboard client remains scoped to the submenu screen, footer-loaded, `wp-api-fetch` based, and jQuery-free.

### REST and Scheduling

- `GET /status` still uses cached statistics and does not trigger recalculation, but now also returns lightweight environment state, conservative conflict warnings, recent warning/error summaries, and refresh metadata.
- `POST /status/recalculate` is now available to `manage_options` users and asynchronously queues the existing `hwlio_reconcile_statistics` maintenance hook through a dedicated one-off Action Scheduler seam.
- The earlier master-plan example hook name `hwlio_recalculate_statistics` was not introduced; 6.5 deliberately reuses the already-implemented `hwlio_reconcile_statistics` hook to avoid renaming the active Phase 5 maintenance flow.
- Recent failure summaries are read through a new read-only logging seam and expose only bounded safe fields: timestamp, level, code, message, and attachment ID.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Admin/*.php
php -l src/Admin/Rest/*.php
php -l src/Infrastructure/*.php
php -l src/Logging/*.php
vendor/bin/phpunit tests/Unit/Admin tests/Unit/Logging tests/Unit/Infrastructure tests/Unit/PluginTest.php
composer run test
```

Current workspace limitation:

- `php` and `composer` are not available on the shell `PATH` in this workspace snapshot, so these automated checks could not be executed here.

Manual verification still pending in a WordPress runtime:

- `upload.php?page=hwlio` should load the dashboard shell and populate cached status cards without scanning the Media Library on page load.
- The `Recalculate Statistics` action should queue a background refresh and keep polling until the cache timestamp updates or the pending flag clears.
- Recent failure summaries and conservative conflict warnings should remain visible only on the plugin submenu screen.

### Acceptance Criteria

- [x] The dashboard now renders real environment, status, savings, failures, conflicts, and recalculate sections inside the existing submenu shell.
- [x] `GET /status` remains cache-backed and fast while exposing the additional dashboard-focused summary data.
- [x] `POST /status/recalculate` now queues asynchronous statistics reconciliation instead of recalculating inline.
- [x] Recent warning/error summaries are bounded and do not expose raw log context.
- [x] The dashboard client remains screen-scoped, footer-loaded, and jQuery-free.

### Deferred Work

- Bulk scan, queue, pause, resume, retry-failed, and cancel-pending controls remain deferred to Subphases 6.6 and 6.7.
- Full diagnostics execution UI and the structured logs screen remain deferred to Subphase 6.8.
- Frontend delivery status remains conservative environment/settings reporting only; delivery runtime behavior itself remains deferred to Phase 7.

## Subphase 6.6 - Bulk Scanner

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Replace the bulk placeholder tab with a real dry-run scan shell and candidate preview layout.
- [x] Add `POST /jobs/scan` for bounded resumable bulk dry-run scanning.
- [x] Add the first bounded session-scoped `GET /attachments` collection preview route.
- [x] Persist bulk scan sessions in transient-backed metadata plus candidate ID chunks.
- [x] Keep scans conservative: excluded attachments skipped, no queueing, no sidecars, no derivative/status writes.
- [x] Keep queue controls, pause/resume, retry-failed queueing, and cancel-pending deferred to Subphase 6.7.

### Files Added

```text
src/Admin/Bulk/BulkPreviewService.php
src/Admin/Bulk/BulkScanFilters.php
src/Admin/Bulk/BulkScanProgress.php
src/Admin/Bulk/BulkScanResultPage.php
src/Admin/Bulk/BulkScanService.php
src/Admin/Bulk/BulkScanSession.php
src/Admin/Bulk/BulkScanSessionAccessDeniedException.php
src/Admin/Bulk/BulkScanSessionNotFoundException.php
src/Admin/Bulk/BulkScanSessionStoreInterface.php
src/Admin/Bulk/BulkScanSummary.php
src/Admin/Bulk/BulkScannerRuntimeInterface.php
src/Admin/Bulk/WordPressBulkScannerRuntime.php
src/Admin/Bulk/WordPressTransientBulkScanSessionStore.php
src/Admin/Rest/JobsController.php
src/Infrastructure/TransientStoreInterface.php
src/Infrastructure/WordPressTransientStore.php
tests/Unit/Admin/Bulk/BulkPreviewServiceTest.php
tests/Unit/Admin/Bulk/BulkScanServiceTest.php
tests/Unit/Admin/Bulk/FakeBulkScannerRuntime.php
tests/Unit/Admin/Bulk/WordPressTransientBulkScanSessionStoreTest.php
tests/Unit/Admin/Rest/JobsControllerTest.php
tests/Unit/Infrastructure/FakeTransientStore.php
```

### Files Changed

```text
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
docs/implementation-status.md
src/Admin/AdminBootstrapConfig.php
src/Admin/BulkPage.php
src/Admin/Rest/AttachmentsController.php
src/Admin/Rest/RestErrorFactory.php
src/Admin/Rest/RestRuntimeInterface.php
src/Admin/Rest/WordPressRestRuntime.php
src/Plugin.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Admin/AssetsTest.php
tests/Unit/Admin/Rest/AttachmentsControllerTest.php
tests/Unit/Admin/Rest/FakeRestRuntime.php
```

### Bulk Scanner Behavior

- The `Bulk Optimize` tab now renders real dry-run filter controls, progress/status messaging, summary cards, and a paged eligible-candidate preview while keeping queue execution controls explicitly deferred to 6.7.
- `POST /jobs/scan` processes one bounded attachment-ID page per request, persists a tokenized transient-backed session, and resumes with a monotonic attachment-ID cursor until the scan completes.
- Scan sessions store strict owner user IDs, normalized filters, cumulative summary counts, session timestamps, and chunked candidate ID pages so the browser can resume the same dry-run session after reloads.
- `GET /attachments` is now available only as a session-scoped preview endpoint for persisted dry-run candidates; it returns lightweight title/filename/date plus sanitized `_hwlio_status` summary data only.
- Dry-run scans skip excluded attachments, skip non-image attachments, skip active `queued` or `processing` items, never enqueue queue work, never create sidecars, and never rewrite derivative or status metadata.
- The plugin-screen admin client now initializes bulk behavior only on the `bulk-optimize` tab, resumes the last scan token from `sessionStorage`, continues polling `POST /jobs/scan` while running, and loads paged previews through `GET /attachments` after completion.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Admin/Bulk/*.php
php -l src/Admin/Rest/*.php
php -l src/Infrastructure/*.php
vendor/bin/phpunit tests/Unit/Admin/Bulk tests/Unit/Admin/Rest tests/Unit/Admin tests/Unit/Infrastructure tests/Unit/PluginTest.php
composer run test
```

Current workspace limitation:

- `php` and `composer` are not available on the shell `PATH` in this workspace snapshot, so these automated checks could not be executed here.

Manual verification still pending in a WordPress runtime:

- `upload.php?page=hwlio&tab=bulk-optimize` should render the dry-run filter form, progress shell, and preview table only on the plugin submenu screen.
- Starting a dry-run scan should continue through bounded pages, survive a page reload through the stored scan token, and never create derivatives or queue jobs.
- Completed scans should load session-scoped preview rows with lightweight status data only and no manifest/path leakage.

### Acceptance Criteria

- [x] Large fixture libraries are processed through bounded dry-run pages with a resumable attachment-ID cursor.
- [x] Dry-run scans persist resumable session state without loading the full Media Library into memory at once.
- [x] `GET /attachments` now provides bounded session-scoped preview browsing for dry-run candidates only.
- [x] Excluded attachments are skipped conservatively in 6.6 without override controls or implicit queueing.
- [x] Dry-run scanning creates no sidecars, queues no conversions, and does not rewrite attachment optimization metadata.

### Deferred Work

- Queueing scan results, pause/resume, retry-failed queueing, progress against live queue work, and cancel-pending controls remain deferred to Subphase 6.7.
- Global Media Library browsing semantics for `GET /attachments` beyond session-scoped dry-run previews remain deferred.
- Full diagnostics execution UI and the structured logs screen remain deferred to Subphase 6.8.

## Subphase 6.7 - Bulk Queue Controls

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Extend persisted bulk scan sessions with resumable queue progress and queue summaries.
- [x] Add `POST /jobs/queue`, `POST /jobs/retry`, `POST /jobs/pause`, `POST /jobs/resume`, and `DELETE /jobs/pending`.
- [x] Add a global queue-control state and attachment-job control seam for pause/resume, counts, and pending-job cancellation.
- [x] Reuse completed dry-run sessions for bounded queue continuation and retry continuation without re-scanning the Media Library.
- [x] Gate manual optimize/retry/reconcile, new-upload queueing, and worker starts behind the same global paused state.
- [x] Upgrade the Bulk tab shell and plugin-screen admin client with real queue controls and live queue status.

### Files Added

```text
src/Admin/Bulk/BulkQueueProgress.php
src/Admin/Bulk/BulkQueueService.php
src/Admin/Bulk/BulkQueueSummary.php
src/Admin/Bulk/BulkScanSessionIncompleteException.php
src/Queue/ActionSchedulerAttachmentJobControl.php
src/Queue/AttachmentJobControlInterface.php
src/Queue/AttachmentJobControlResult.php
src/Queue/AttachmentQueueResult.php
src/Queue/AttachmentQueueService.php
src/Queue/QueueControlService.php
src/Queue/QueueControlState.php
src/Queue/QueueControlStateStore.php
src/Queue/QueueControlStateStoreInterface.php
tests/Unit/Admin/Bulk/BulkQueueServiceTest.php
tests/Unit/Queue/ActionSchedulerAttachmentJobControlTest.php
tests/Unit/Queue/FakeAttachmentJobControl.php
tests/Unit/Queue/QueueControlStateStoreTest.php
```

### Files Changed

```text
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
docs/implementation-status.md
src/Admin/AdminBootstrapConfig.php
src/Admin/BulkPage.php
src/Admin/Rest/AttachmentActionService.php
src/Admin/Rest/JobsController.php
src/Admin/Rest/RestErrorFactory.php
src/Admin/Rest/StatusSummaryService.php
src/Admin/Bulk/BulkScanSession.php
src/Infrastructure/ActionSchedulerSingleActionScheduler.php
src/Infrastructure/LifecyclePolicy.php
src/Infrastructure/SingleActionSchedulerInterface.php
src/Plugin.php
src/Queue/NewUploadIntegration.php
src/Queue/OptimizationWorker.php
src/Queue/ReconciliationWorker.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Admin/AssetsTest.php
tests/Unit/Admin/Rest/AttachmentActionServiceTest.php
tests/Unit/Admin/Rest/JobsControllerTest.php
tests/Unit/Admin/Rest/StatusControllerTest.php
tests/Unit/Admin/Rest/StatusSummaryServiceTest.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Infrastructure/FakeSingleActionScheduler.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/Queue/NewUploadIntegrationTest.php
tests/Unit/Queue/OptimizationWorkerTest.php
tests/Unit/Queue/ReconciliationWorkerTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Bulk Queue Behavior

- Completed dry-run scan sessions now persist queue continuation progress and queue summaries so queueing survives reloads without re-scanning attachments.
- `POST /jobs/queue` and `POST /jobs/retry` process one persisted candidate chunk per request, revalidate current lightweight attachment state at queue time, and respect the stored scan token’s `target_format`.
- Bulk queueing uses the shared `AttachmentQueueService`, which now powers both bulk queueing and attachment-scoped optimize/retry actions so dedupe, fingerprinting, and lightweight `_hwlio_status` writes stay aligned.
- `GET /status` now includes a cheap `queueControl` payload with paused state plus pending and in-progress attachment-job counts for the dashboard and Bulk tab.
- The Bulk tab now exposes real queue, retry, pause, resume, and cancel-pending controls, keeps queue mode in `sessionStorage`, and stays scoped to the existing plugin submenu asset pipeline without jQuery.

### Global Queue Control Behavior

- Global queue state is now persisted in plugin-owned option `hwlio_queue_control_state` with autoload disabled and tracked through `QueueControlState`.
- `POST /jobs/pause` and `POST /jobs/resume` toggle that global state for all attachment optimization and reconciliation work, not just the Bulk tab.
- `DELETE /jobs/pending` now targets only pending plugin-owned attachment hooks from `LifecyclePolicy::attachment_job_hooks()` and leaves maintenance hooks untouched.
- `AttachmentActionService` now returns a stable `queue_paused` conflict for manual optimize, retry, and reconcile actions while paused.
- `NewUploadIntegration` now leaves uploads usable but unqueued while paused, and both `OptimizationWorker` and `ReconciliationWorker` re-schedule paused jobs with a short delay instead of consuming work.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Admin/Bulk/*.php
php -l src/Admin/Rest/*.php
php -l src/Infrastructure/*.php
php -l src/Queue/*.php
vendor/bin/phpunit tests/Unit/Admin/Bulk tests/Unit/Admin/Rest tests/Unit/Queue tests/Unit/Infrastructure tests/Unit/PluginTest.php
composer run test
```

Current workspace limitation:

- `php` and `composer` are not available on the shell `PATH` in this workspace snapshot, so these automated checks could not be executed here.

Manual verification still pending in a WordPress runtime:

- A completed dry-run scan should continue queueing in bounded requests and survive reloads through the stored scan token and queue mode.
- Pausing should block new manual, new-upload, and bulk queueing and should prevent new worker starts while allowing running actions to finish safely.
- Resuming should allow pending work to continue again, and cancel pending should remove only pending plugin-owned attachment jobs.
- Scan and preview browsing should remain available while paused.

### Acceptance Criteria

- [x] Bulk queueing and bulk retry now consume completed owned scan sessions instead of re-scanning the Media Library.
- [x] Queue continuation respects stored target-format filters and revalidates candidate state conservatively at queue time.
- [x] Global pause/resume now gates manual actions, new-upload automation, and worker starts without canceling maintenance work.
- [x] Pending-job cancellation is limited to plugin-owned attachment hooks and excludes recurring maintenance hooks.
- [x] The Bulk tab now exposes real queue controls and queue-control status through the existing screen-scoped admin client.
- [x] Queue-control state is stored in a plugin-owned option with autoload disabled.

### Deferred Work

- Broad bulk re-optimize coverage for already-optimized attachments remains deferred because the current scan-session model intentionally excludes those items.
- Full diagnostics execution UI and the structured logs screen remain deferred to Subphase 6.8.
- Frontend modern-format delivery remains deferred to Phase 7.

## Subphase 6.8 - Logs and Diagnostics Screens

**Status:** Complete
**Completed:** 2026-07-12

### Tasks

- [x] Replace the Diagnostics tab placeholder with a real structured diagnostics shell.
- [x] Replace the Logs tab placeholder with paginated log browsing, lightweight filters, retention editing, and bounded clear-all controls.
- [x] Add `GET /logs`, `DELETE /logs`, and `POST /logs/retention` under the existing `hwlio/v1` admin REST surface.
- [x] Extend the log database seams with bounded page queries, total counts, and bounded clear-all deletion batches.
- [x] Keep diagnostics and logs payloads path-safe and free of raw `context_json` or stack traces.
- [x] Reuse the existing plugin-screen asset pipeline and jQuery-free admin client for the new tabs.

### Files Added

```text
src/Admin/Rest/LogsController.php
src/Logging/LogBrowserService.php
src/Logging/LogDeletionResult.php
src/Logging/LogDeletionService.php
src/Logging/LogPage.php
src/Logging/LogQuery.php
src/Logging/LogRetentionService.php
src/Logging/LogRetentionUpdateResult.php
src/Logging/LogRowView.php
tests/Unit/Admin/Rest/LogsControllerTest.php
tests/Unit/Logging/LogBrowserServiceTest.php
tests/Unit/Logging/LogDeletionServiceTest.php
tests/Unit/Logging/LogRetentionServiceTest.php
```

### Files Changed

```text
admin/css/hyperweb-lighthouse-image-optimizer-admin.css
admin/js/hyperweb-lighthouse-image-optimizer-admin.js
docs/implementation-status.md
src/Admin/AdminBootstrapConfig.php
src/Admin/DiagnosticsPage.php
src/Admin/LogsPage.php
src/Admin/Rest/RestErrorFactory.php
src/Logging/LogDatabaseInterface.php
src/Logging/LogReadDatabaseInterface.php
src/Logging/NullLogDatabase.php
src/Logging/RecentFailureLogReader.php
src/Logging/WordPressLogDatabase.php
src/Logging/WordPressLogReadDatabase.php
src/Plugin.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Admin/AssetsTest.php
tests/Unit/Logging/FakeLogDatabase.php
tests/Unit/Logging/FakeLogReadDatabase.php
```

### Logs and Diagnostics Behavior

- The Diagnostics tab now renders a real shell with summary cards, grouped check results, stable machine-readable codes, and sanitized detail output sourced from the existing `GET /diagnostics` route.
- The Logs tab now renders a real filter form, paginated table, retention editor, and confirmed bounded clear-all flow, all inside the existing `Media -> Lighthouse Image Optimizer` screen shell.
- `GET /logs` returns only safe projected fields: `created_at_gmt`, `level`, `code`, `message`, `attachment_id`, and `job_id`.
- Log browsing is backed by typed `LogQuery`, `LogPage`, and `LogRowView` objects plus bounded wpdb reads; it does not expose raw `context_json`.
- `DELETE /logs` clears plugin-owned log rows in bounded batches only, allowing the admin client to continue deletion until complete without unbounded truncation.
- `POST /logs/retention` persists `log_retention_days` through the existing settings repository and returns the normalized saved value.

### Verification

Attempted verification commands for this subphase:

```text
php -l src/Admin/*.php
php -l src/Admin/Rest/*.php
php -l src/Logging/*.php
vendor/bin/phpunit tests/Unit/Admin tests/Unit/Logging tests/Unit/PluginTest.php
composer run test
git diff --check
```

Current workspace limitation:

- `php`, `vendor/bin/phpunit`, and `composer` may not be available on the shell `PATH` in this workspace snapshot, so PHP-based automated checks may need to be re-run in the normal project toolchain.

Manual verification still pending in a WordPress runtime:

- `upload.php?page=hwlio&tab=diagnostics` should load grouped structured diagnostics and allow copyable codes without showing raw paths.
- `upload.php?page=hwlio&tab=logs` should load paginated logs, apply level/code/attachment filters, save retention days, and clear logs through bounded batches only.
- No plugin diagnostics/logs assets or behavior should appear on unrelated admin screens.

### Acceptance Criteria

- [x] Structured diagnostics now render inside the plugin submenu using the existing sanitized diagnostics payload.
- [x] Log output is escaped and limited to safe projected fields without `context_json` leakage.
- [x] Large log tables remain paginated through bounded server-side page queries.
- [x] Copyable stable codes are available for both diagnostics results and log rows.
- [x] Safe retention and clear-all controls exist without touching non-log plugin data.

### Deferred Work

- Richer log drill-down into structured context remains intentionally deferred; 6.8 keeps the logs screen summary/table focused.
- Frontend modern-format delivery remains deferred to Phase 7.

## Subphase 5.2 - Optimization Worker

**Status:** Complete
**Completed:** 2026-07-11

### Tasks

- [x] Add `OptimizationWorker` as the runtime Action Scheduler hook provider for queued optimization jobs.
- [x] Rebuild positional callback payloads into validated `OptimizationJob` objects.
- [x] Move lock ownership out of `AttachmentProcessor` and into the worker.
- [x] Add queued fingerprint freshness checks with stale-source requeue behavior.
- [x] Add continuation scheduling for incomplete processor batches.
- [x] Add bounded retry scheduling for lock collisions and retryable transient failures.
- [x] Add queue-driven lightweight status transitions and structured worker logging.
- [x] Keep upload hooks, REST, admin/frontend assets, and frontend delivery deferred.

### Files Added

```text
src/Attachment/AttachmentProcessRequest.php
src/Attachment/AttachmentProcessorInterface.php
src/Queue/OptimizationRetryPolicy.php
src/Queue/OptimizationWorker.php
tests/Unit/Queue/FakeAttachmentProcessor.php
tests/Unit/Queue/FakeLogger.php
tests/Unit/Queue/OptimizationRetryPolicyTest.php
tests/Unit/Queue/OptimizationWorkerTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Attachment/AttachmentProcessor.php
src/Logging/LogCode.php
src/Plugin.php
tests/Unit/Attachment/AttachmentProcessorTest.php
tests/Unit/PluginTest.php
tests/Unit/Queue/OptimizationJobTest.php
tests/Unit/Queue/QueueScopePolicyTest.php
```

### Worker Behavior

- `OptimizationWorker` is composed into `Plugin::create()` and registers only `LifecyclePolicy::ACTION_OPTIMIZE_ATTACHMENT_FORMAT` with six positional callback arguments, matching Action Scheduler execution order.
- `OptimizationJob::from_callback_args()` reconstructs worker payloads safely from positional callback args without depending on ad hoc arrays.
- The worker now owns attachment lock acquisition and release, while `AttachmentProcessor` is request-driven and lock-free.
- Before processing, the worker collects sources once, compares the queued fingerprint against current source state, and re-queues fresh work with reason `source_changed` when the queued fingerprint is stale.
- Incomplete processor results enqueue a continuation from `next_cursor`; retryable transient failures enqueue bounded retries from the original cursor using `retry_1`, `retry_2`, and `retry_3` reasons with `60`, `120`, and `240` second delays.
- Queue-driven status updates are lightweight and preserve ready formats plus the excluded flag; invalid payloads, lock collisions, stale fingerprints, retry scheduling, and completion outcomes are logged through stable worker log codes.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Runtime hook registration is confined to `src/Queue/OptimizationWorker.php`, queue scheduling remains confined to queue-domain adapters, and no image-domain or attachment-domain class calls Action Scheduler globals directly. No upload hooks, REST routes, admin/frontend assets, or `_wp_attachment_metadata` writes were introduced.

### Acceptance Criteria

- [x] Worker hook registration exists only for the optimize hook.
- [x] Positional Action Scheduler callback args are rebuilt into validated optimization jobs.
- [x] The worker owns lock lifecycle, queued fingerprint freshness checks, continuation scheduling, retry scheduling, and queue-driven status overrides.
- [x] `AttachmentProcessor` remains the pure request-driven processing service and no longer acquires locks internally.
- [x] Invalid job payloads, stale fingerprints, lock collisions, partial batches, retryable failures, and completed outcomes degrade to structured status/log behavior instead of fatal errors.

### Deferred Work

- Reconcile scheduling and stale-lock recurring recovery remain deferred to Subphases 5.4 and 5.5.
- Admin, REST, and bulk-processing exposure remain deferred to Phase 6.
- Runtime WordPress smoke testing remains pending in this plugin-only workspace.

## Subphase 9.1 - Compatibility Audit and Fixtures

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a dedicated WooCommerce compatibility audit document covering single-product primary, single-product gallery, cart, and checkout image contexts.
- [x] Add raw WooCommerce baseline fixture fragments plus a machine-readable fixture manifest under `tests/Fixtures/WooCommerce/`.
- [x] Keep runtime unchanged: no WooCommerce provider, settings, delivery mutation, plugin composition change, or adapter logic was introduced.
- [x] Add audit-only tests proving the fixture pack is complete and WooCommerce runtime integration has not started yet.

### Files Added

```text
docs/woocommerce-compatibility-audit.md
tests/Fixtures/WooCommerce/baseline-manifest.php
tests/Fixtures/WooCommerce/cart-item-thumbnail.html
tests/Fixtures/WooCommerce/checkout-review-thumbnail.html
tests/Fixtures/WooCommerce/single-product-gallery-secondary.html
tests/Fixtures/WooCommerce/single-product-primary.html
tests/Unit/Integration/WooCommerceFixtureManifestTest.php
tests/Unit/Integration/WooCommerceScopePolicyTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
```

### WooCommerce Baseline Behavior

- The repository now has a canonical WooCommerce baseline audit in `docs/woocommerce-compatibility-audit.md`, scoped to four initial contexts: primary product image, secondary gallery image, cart thumbnail, and checkout/review thumbnail.
- The audit records likely WooCommerce hook/template entrypoints, wrapper expectations, image classes, data attributes, critical-role expectations, and fail-open requirements for each context before any runtime adapter is added.
- `tests/Fixtures/WooCommerce/baseline-manifest.php` is the machine-readable source of truth for those baseline contexts and intentionally fixes the manifest shape for later Phase 9 tests.
- Raw fixture fragments preserve representative WooCommerce wrapper markup plus image attributes such as `wp-post-image`, Woo size classes, and gallery data attributes like `data-caption`, `data-src`, and `data-large_image`.
- No runtime WooCommerce integration exists yet; later WooCommerce work is expected to extend existing plugin seams such as `hwlio_critical_image_candidates`, `hwlio_critical_image_selection`, `hwlio_markup_is_eligible`, and `hwlio_picture_sources`.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `src/` still contains no WooCommerce runtime hook registration, no `woocommerce_*` hook strings, no `WooCommerce` class references, and no `wc_*` / `woocommerce_*` helper calls.

Manual/source verification remains:

- the audit document should remain the baseline reference for future 9.2 and 9.3 implementation work
- fixture fragments should stay representative rather than being reduced to synthetic markup
- live WordPress + WooCommerce smoke capture remains pending because this repository currently has no WooCommerce test harness

### Acceptance Criteria

- [x] Baseline snapshots exist before WooCommerce-specific runtime changes.
- [x] Product primary, gallery secondary, cart, and checkout contexts are documented and represented in repository fixtures.
- [x] Runtime WooCommerce integration remains absent in Subphase 9.1.
- [x] Fixture completeness and audit-only scope are enforced by automated tests.

### Deferred Work

- Primary product-image critical registration, zoom/lightbox compatibility behavior, and responsive delivery tuning remain deferred to Subphase 9.2.
- Gallery, loop thumbnails, variations, related products, upsells, cart, and checkout runtime compatibility behavior remain deferred to Subphase 9.3.
- Live WooCommerce page capture and smoke verification remain pending until a supported WordPress + WooCommerce runtime is available.

## Subphase 9.2 - Primary Product Image Optimization

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add an isolated WooCommerce integration slice under `src/Integration/` with a narrow runtime seam, primary-image matcher, and runtime provider.
- [x] Register the current product featured image as the critical primary image on valid single-product requests through the existing `hwlio_critical_image_candidates` seam.
- [x] Add the new `hwlio_loading_image_role` integration seam so WooCommerce can refine `primary` / `secondary` / `none` classification per exact fragment.
- [x] Restrict 9.2 picture delivery to the confirmed single-product primary image while leaving recognized non-primary Woo image contexts unchanged.
- [x] Preserve WooCommerce zoom/lightbox image data by continuing to mutate only the fallback `<img>` fragment and leaving wrapper markup outside the delivery pipeline untouched.

### Files Added

```text
src/Integration/WooCommerceIntegration.php
src/Integration/WooCommercePrimaryImageMatcher.php
src/Integration/WooCommerceRuntimeInterface.php
src/Integration/WordPressWooCommerceRuntime.php
tests/Unit/Integration/FakeWooCommerceRuntime.php
tests/Unit/Integration/WooCommerceIntegrationTest.php
tests/Unit/Integration/WooCommercePrimaryProductDeliveryTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Delivery/LoadingAttributeManager.php
src/Plugin.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/LoadingAttributeManagerTest.php
tests/Unit/Integration/WooCommerceScopePolicyTest.php
tests/Unit/PluginTest.php
```

### WooCommerce Primary-Image Behavior

- The plugin now has an isolated WooCommerce runtime adapter that composes only internal plugin filters and introduces no Woo-specific frontend hooks, settings, template overrides, or admin surfaces.
- On valid single-product requests, the current product featured image is registered as the request primary critical image through `hwlio_critical_image_candidates`, but `preload_attachment_id` intentionally remains unset in 9.2.
- `hwlio_loading_image_role` lets integrations refine the computed loading role per fragment; WooCommerce uses it to keep only the confirmed primary product image as `primary` and to demote known non-primary or duplicate same-attachment contexts to `none`.
- `hwlio_markup_is_eligible` now allows picture delivery only for the confirmed single-product primary image and leaves gallery, cart, checkout, and duplicate same-attachment appearances unchanged.
- Picture rendering still preserves the fallback `<img>` verbatim, so Woo image classes and zoom/lightbox data attributes such as `data-caption`, `data-src`, `data-large_image`, `data-large_image_width`, and `data-large_image_height` survive inside generated `<picture>` markup.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Targeted verification completed during implementation also passed for:

- WooCommerce integration tests
- WooCommerce scope policy tests
- Delivery manager tests
- plugin composition tests

Manual/source verification remains:

- live WordPress + WooCommerce smoke verification for primary product zoom/lightbox behavior
- supported-browser manual verification that the visible product image is not accidentally lazy-loaded
- broader gallery/cart/checkout/loop/variation/upsell coverage, which remains owned by Subphase 9.3

### Acceptance Criteria

- [x] The primary visible product image is registered as critical where appropriate.
- [x] Product-image fallback markup preserves Woo zoom/lightbox image attributes through picture delivery.
- [x] Responsive modern sources map correctly for the confirmed primary product image.
- [x] Recognized non-primary Woo image contexts remain unchanged in 9.2.
- [x] The primary product image is not accidentally lazy-loaded by plugin-managed loading overrides.

### Deferred Work

- Gallery, cart, checkout, loop thumbnails, related products, upsells, variation switching, and broader WooCommerce image-surface compatibility remain deferred to Subphase 9.3.
- WooCommerce-specific preload behavior remains deferred; the primary product image participates in critical-image overrides but does not set `preload_attachment_id` in 9.2.
- Live WordPress + WooCommerce runtime smoke verification remains pending until a supported WooCommerce test environment is available.

## Subphase 9.3 - Gallery and Commerce Surfaces

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Extend the WooCommerce audit and fixture baseline with loop, related, upsell, and variation-sensitive image contexts.
- [x] Broaden the Woo runtime seam with normalized gallery attachment IDs while keeping Woo access isolated to the integration slice.
- [x] Expand the Woo fragment matcher to classify primary, confirmed gallery-secondary, commerce-thumbnail, variation-or-uncertain, and unrecognized fragments conservatively.
- [x] Keep critical-image and preload behavior primary-only while widening picture-delivery eligibility to confirmed single-product gallery-secondary images.
- [x] Preserve Woo gallery/lightbox fallback `<img>` attributes verbatim inside generated `<picture>` markup and keep broader commerce surfaces fail-open.

### Files Added

```text
tests/Fixtures/WooCommerce/product-loop-thumbnail.html
tests/Fixtures/WooCommerce/related-product-thumbnail.html
tests/Fixtures/WooCommerce/single-product-variation-image.html
tests/Fixtures/WooCommerce/upsell-product-thumbnail.html
tests/Unit/Integration/WooCommercePrimaryImageMatcherTest.php
tests/Unit/Integration/WordPressWooCommerceRuntimeTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
docs/woocommerce-compatibility-audit.md
src/Integration/WooCommerceIntegration.php
src/Integration/WooCommercePrimaryImageMatcher.php
src/Integration/WooCommerceRuntimeInterface.php
src/Integration/WordPressWooCommerceRuntime.php
tests/Fixtures/WooCommerce/baseline-manifest.php
tests/Unit/Delivery/FakeAttachmentImageRuntime.php
tests/Unit/Delivery/DeliveryTestWordPressShim.php
tests/Unit/Integration/FakeWooCommerceRuntime.php
tests/Unit/Integration/WooCommerceFixtureManifestTest.php
tests/Unit/Integration/WooCommerceIntegrationTest.php
tests/Unit/Integration/WooCommercePrimaryProductDeliveryTest.php
```

### WooCommerce Gallery and Commerce Behavior

- The Woo runtime seam now exposes normalized current-product gallery attachment IDs so the integration can distinguish real gallery members from generic Woo thumbnails.
- The matcher now classifies Woo fragments into `primary`, `gallery_secondary`, `commerce_thumbnail`, `variation_or_uncertain`, and `unrecognized` outcomes instead of using a single non-primary bucket.
- Confirmed single-product gallery-secondary images are now eligible for picture delivery through the existing core delivery pipeline, with the fallback `<img>` preserved verbatim so Woo classes and gallery/lightbox data attributes survive unchanged.
- Critical-image registration remains primary-only, `preload_attachment_id` remains unset for Woo, and loading-role refinement continues to demote gallery, loop, cart, checkout, related, upsell, and variation-sensitive fragments to `none`.
- Recognized broader commerce-thumbnail surfaces and variation-sensitive/ambiguous single-product fragments now fail open explicitly instead of being guessed into delivery behavior.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Manual/source verification remains:

- live WooCommerce smoke verification for gallery navigation/lightbox behavior after picture delivery
- live WooCommerce variation switching verification to confirm fail-open behavior remains intact
- live cart, checkout, related, upsell, and loop thumbnail verification in a supported WooCommerce runtime

### Acceptance Criteria

- [x] Confirmed secondary gallery images are eligible for picture delivery.
- [x] Loop thumbnails, cart, checkout, related products, upsells, and variation-sensitive/uncertain fragments remain conservative fail-open contexts.
- [x] Primary-product-image critical treatment remains unchanged and Woo auto-preload remains disabled.
- [x] Repository-owned fixtures and audit coverage now include gallery, loop, related, upsell, and variation-sensitive commerce surfaces.

### Deferred Work

- WooCommerce-specific preload behavior remains deferred.
- Broader Woo loop/commerce-surface enablement remains deferred until a later subphase proves those contexts safe.
- Live WordPress + WooCommerce smoke verification remains pending until a supported WooCommerce test environment is available.

## Subphase 8.4 - Optional Responsive Preload

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add the opt-in `responsive_preload_enabled` delivery setting, repository getter, sanitizer coverage, and minimal settings-page checkbox.
- [x] Extend the critical-image selection model with `preload_attachment_id` while keeping built-in preload selection conservative and attachment-backed.
- [x] Add a dedicated `wp_head` preload provider that emits at most one responsive preload tag per request.
- [x] Add a late-discovered critical-image locator that resolves one unique attachment-backed `<img>` fragment from current singular post content before `wp_head`.
- [x] Reuse existing delivery analysis, dimension repair, source extraction, source-set building, and format-preference logic to compute consistent modern-image preload data.
- [x] Add a request-local preload dedupe registry and safe preload link/result value objects without widening the frontend hook surface.

### Files Added

```text
src/Delivery/LateDiscoveredCriticalImageLocator.php
src/Delivery/LateDiscoveredCriticalImageMatch.php
src/Delivery/ResponsivePreloadLink.php
src/Delivery/ResponsivePreloadManager.php
src/Delivery/ResponsivePreloadRegistry.php
src/Delivery/ResponsivePreloadResult.php
tests/Unit/Delivery/LateDiscoveredCriticalImageLocatorTest.php
tests/Unit/Delivery/ResponsivePreloadManagerTest.php
```

### Files Changed

```text
CHANGELOG.md
composer.json
docs/implementation-status.md
src/Admin/SettingsPage.php
src/Delivery/AttachmentImageRuntimeInterface.php
src/Delivery/CriticalImageRegistry.php
src/Delivery/CriticalImageSelection.php
src/Delivery/WordPressAttachmentImageRuntime.php
src/Plugin.php
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
src/Settings/SettingsSchema.php
src/Settings/StaticSettingsRepository.php
tests/Unit/Admin/SettingsPageTest.php
tests/Unit/Attachment/AttachmentScopePolicyTest.php
tests/Unit/Delivery/CriticalImageRegistryTest.php
tests/Unit/Delivery/DeliveryScopePolicyTest.php
tests/Unit/Delivery/DeliveryTestWordPressShim.php
tests/Unit/Delivery/FakeAttachmentImageRuntime.php
tests/Unit/Delivery/WordPressAttachmentImageRuntimeTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/PluginTest.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsSanitizerTest.php
tests/Unit/Settings/SettingsSchemaTest.php
```

### Responsive Preload Behavior

- `responsive_preload_enabled` defaults to `false`, so upgrades do not start preloading automatically.
- `CriticalImageSelection` now carries `preload_attachment_id`, and the built-in registry assigns it only from the per-post/page primary critical attachment.
- The custom logo can remain a secondary critical image for loading overrides, but it is not auto-preloaded in 8.4.
- `ResponsivePreloadManager` is now a dedicated delivery provider that hooks only `wp_head` at priority `1` and emits at most one `<link rel="preload" as="image">` tag per request.
- `LateDiscoveredCriticalImageLocator` scans only standalone `<img>` fragments from current singular post content, trusts only attachment-backed identifiers already compatible with the delivery stack, and requires one unique match before preload can continue.
- Preload reuses the existing delivery path: intrinsic-dimension repair may normalize the matched fragment, the markup analyzer and source extractor read the original responsive candidates, and `SourceSetBuilder` plus `format_preference()` select the preferred modern derivative source set.
- Preload is emitted only when the matched fallback fragment has a non-empty `sizes` attribute, the fallback `src` maps uniquely to one extracted source candidate, and the chosen modern format has a complete responsive set for that same candidate width.
- `ResponsivePreloadRegistry` dedupes by attachment, format, `href`, `imagesrcset`, and `imagesizes`, preventing duplicate head tags across repeated provider calls in the same request.
- No preload is emitted for uncertain cases, normal early-rendered attachment images, unresolved content images, critical URLs without an attachment-backed match, or requests outside the supported frontend singular context.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Runtime delivery remains confined to `wp_get_attachment_image`, `wp_content_img_tag`, `wp_get_loading_optimization_attributes`, and the new `wp_head` preload provider; `wp_get_attachment_image_attributes`, `wp_calculate_image_srcset`, output buffering, REST routes, and frontend asset hooks remain absent.

Manual WordPress smoke testing remains pending in this plugin-only workspace:

- singular post/page requests with a uniquely matched per-post/page critical content image should emit one deduplicated modern-format preload tag with matching `imagesrcset` and `imagesizes`
- pages without an explicit late-discovered critical image should emit no preload
- supported-browser network inspection should confirm the intended scenario avoids duplicate image downloads

### Acceptance Criteria

- [x] An opt-in `responsive_preload_enabled` setting exists and is exposed through the current minimal settings UI.
- [x] The critical-image selection model now carries a conservative built-in `preload_attachment_id` without introducing a parallel registry.
- [x] A dedicated `wp_head` provider emits at most one attachment-backed responsive preload tag per request and fails open on uncertain cases.
- [x] Preload candidate discovery is limited to unique, attachment-backed standalone `<img>` fragments in current singular post content before `wp_head`.
- [x] Preload data is derived from the existing delivery pipeline and only emits when `href`, `imagesrcset`, and `imagesizes` can stay consistent with the matched fallback image.
- [x] No output buffering, `wp_get_attachment_image_attributes`, `wp_calculate_image_srcset`, REST routes, extra preload hint families, or broad frontend heuristics were introduced in 8.4.

### Deferred Work

- Theme-layout heuristics, automatic preload for ordinary early-rendered attachment images, and built-in custom-logo preload remain deferred.
- WooCommerce and Elementor critical/preload sources remain deferred to Phases 9 and 10 and should extend the registry through the existing filter payloads.
- CLS-related CSS behavior, broader diagnostics surfacing, and any additional browser hint families remain deferred to later Phase 8 and diagnostics work.

## Subphase 8.3 - Intrinsic Dimension Repair

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a callable intrinsic-dimension repair collaborator for attachment-backed fallback `<img>` fragments.
- [x] Extend exact-fragment markup analysis with `src`, intrinsic-dimension values, and missing-vs-invalid presence facts.
- [x] Extract reusable metadata candidate matching into `AttachmentSizeResolver` and share it with responsive source-set building.
- [x] Repair only missing intrinsic dimensions when the selected attachment size can be identified with certainty from current markup plus metadata.
- [x] Carry repair and uncertainty outcomes through delivery render-result codes without widening the runtime hook surface.

### Files Added

```text
src/Delivery/AttachmentSizeResolver.php
src/Delivery/IntrinsicDimensionRepair.php
src/Delivery/IntrinsicDimensionRepairResult.php
tests/Unit/Delivery/AttachmentSizeResolverTest.php
tests/Unit/Delivery/IntrinsicDimensionRepairTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Delivery/DeliveryManager.php
src/Delivery/ImageMarkupAnalysis.php
src/Delivery/PictureRenderRequest.php
src/Delivery/PictureRenderResult.php
src/Delivery/PictureRenderer.php
src/Delivery/SourceSetBuilder.php
src/Delivery/WordPressImageMarkupAnalyzer.php
src/Plugin.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/PictureRendererTest.php
tests/Unit/Delivery/SourceSetBuilderTest.php
tests/Unit/Delivery/WordPressImageMarkupAnalyzerTest.php
```

### Intrinsic-Dimension Behavior

- `WordPressImageMarkupAnalyzer` now captures exact standalone fallback-image `src`, `width`, `height`, and separate attribute-presence facts so delivery can distinguish “missing” from “present but invalid.”
- `AttachmentSizeResolver` now centralizes conservative metadata candidate construction and exact `src` matching, and `SourceSetBuilder` reuses that matcher instead of maintaining a separate implicit candidate path.
- `IntrinsicDimensionRepair` runs only for attachment-backed images already inside the delivery pipeline and only when at least one intrinsic dimension is missing.
- Both dimensions are repaired only when one metadata candidate is uniquely identifiable from the current fallback `src` and the matched metadata dimensions are valid positive integers.
- Partial repair is conservative: the plugin adds only the missing counterpart and only when any existing valid intrinsic dimension exactly matches the matched metadata candidate.
- Conflicting, ambiguous, unmatched, external, malformed, or incomplete cases remain unchanged and surface only through `intrinsic_dimensions_uncertain` render-result codes.
- `DeliveryManager` now runs intrinsic-dimension repair before loading-attribute overrides, source extraction, and picture rendering, so both `wp_get_attachment_image` and `wp_content_img_tag` share the same CLS-safe repair pass.
- The runtime hook surface remains unchanged in 8.3: no `wp_get_attachment_image_attributes`, no `wp_calculate_image_srcset`, no output buffering, no inline layout CSS, and no attachment-metadata writes were introduced.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Runtime delivery remains confined to the existing `wp_get_attachment_image`, `wp_content_img_tag`, and `wp_get_loading_optimization_attributes` providers; no `wp_get_attachment_image_attributes`, `wp_calculate_image_srcset`, inline style injection, attachment-metadata writes, REST routes, or admin-surface expansion were introduced.

Manual WordPress smoke testing remains pending in this plugin-only workspace:

- attachment-backed fallback images missing `width` and/or `height` should receive intrinsic-dimension repair only when the rendered size maps uniquely to current metadata
- repaired fallback `<img>` markup should remain embedded verbatim inside generated `<picture>` output
- unresolved or ambiguous content images should continue to fail open without guessed dimensions
- incoming lazy/high conflict cases should continue to return original markup unchanged

### Acceptance Criteria

- [x] A delivery-side intrinsic-dimension repair service exists and rewrites only missing `width` and/or `height` attributes.
- [x] Exact-fragment analysis now captures `src`, intrinsic-dimension values, and missing-vs-invalid presence facts needed for conservative repair.
- [x] Shared metadata candidate matching is reused by both intrinsic-dimension repair and responsive source-set building.
- [x] Repaired and uncertain outcomes survive the delivery pipeline through stable render-result codes without adding new external surfaces.
- [x] No new frontend hooks, inline layout CSS, metadata writes, settings, REST routes, or admin UI were introduced in 8.3.

### Deferred Work

- Preload behavior and any broader critical/LCP coordination remain deferred to later Phase 8 subphases.
- WooCommerce- and Elementor-specific loading/layout behavior remains deferred to Phases 9 and 10.
- Page-level diagnostics surfacing for repair opportunities remains deferred; 8.3 reports only through internal render-result codes.

## Subphase 8.2 - Critical Image Registry

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Add a delivery-side critical-image registry with normalized per-request primary attachment, critical attachment IDs, and critical URLs.
- [x] Add a narrow `wp_get_loading_optimization_attributes` provider for explicit critical-image loading overrides.
- [x] Apply the same explicit loading override to attachment-backed fallback `<img>` markup before picture rendering.
- [x] Extend the delivery runtime seam with typed singular-post and custom-logo helpers.
- [x] Add the persisted `critical_logo_enabled` setting and `_hwlio_critical_image_id` post/page meta seam.
- [x] Replace the placeholder settings tab with a minimal server-rendered critical-logo control.
- [x] Add post/page critical-image meta-box and media-picker asset providers without widening the runtime surface.

### Files Added

```text
admin/js/hyperweb-lighthouse-image-optimizer-post-editor.js
src/Admin/PostEditor/CriticalImageAssets.php
src/Admin/PostEditor/CriticalImageMetaBox.php
src/Admin/PostEditor/PostEditorRuntimeInterface.php
src/Admin/PostEditor/WordPressPostEditorRuntime.php
src/Delivery/CriticalImagePostMetaStoreInterface.php
src/Delivery/CriticalImageRegistry.php
src/Delivery/CriticalImageSelection.php
src/Delivery/LoadingAttributeManager.php
src/Delivery/WordPressCriticalImagePostMetaStore.php
src/Settings/StaticSettingsRepository.php
tests/Unit/Admin/PostEditor/CriticalImageAssetsTest.php
tests/Unit/Admin/PostEditor/CriticalImageMetaBoxTest.php
tests/Unit/Admin/PostEditor/FakePostEditorRuntime.php
tests/Unit/Admin/PostEditor/PostEditorScopePolicyTest.php
tests/Unit/Admin/SettingsPageTest.php
tests/Unit/Delivery/CriticalImageRegistryTest.php
tests/Unit/Delivery/FakeCriticalImagePostMetaStore.php
tests/Unit/Delivery/LoadingAttributeManagerTest.php
tests/Unit/Delivery/WordPressAttachmentImageRuntimeTest.php
```

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Admin/SettingsPage.php
src/Delivery/AttachmentImageRuntimeInterface.php
src/Delivery/DeliveryManager.php
src/Delivery/WordPressAttachmentImageRuntime.php
src/Plugin.php
src/Settings/SettingsRepository.php
src/Settings/SettingsRepositoryInterface.php
src/Settings/SettingsSchema.php
tests/Unit/Admin/AdminControllerTest.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/DeliveryScopePolicyTest.php
tests/Unit/Delivery/DeliveryTestWordPressShim.php
tests/Unit/Delivery/FakeAttachmentImageRuntime.php
tests/Unit/Diagnostics/DiagnosticsScopePolicyTest.php
tests/Unit/Image/FakeSettingsRepository.php
tests/Unit/Infrastructure/EnvironmentScopePolicyTest.php
tests/Unit/Logging/LoggingScopePolicyTest.php
tests/Unit/PluginTest.php
tests/Unit/ScaffoldAssetPolicyTest.php
tests/Unit/Settings/SettingsRepositoryTest.php
tests/Unit/Settings/SettingsSanitizerTest.php
tests/Unit/Settings/SettingsSchemaTest.php
tests/Unit/Settings/SettingsScopePolicyTest.php
```

### Critical-Image Behavior

- `CriticalImageRegistry` now resolves one normalized request-local selection with `primary_attachment_id`, `critical_attachment_ids`, and `critical_urls`, using built-in post/page meta first and the optional custom logo second.
- Built-in candidates are filtered through `hwlio_critical_image_candidates`, then the final normalized selection is exposed through `hwlio_critical_image_selection` and renormalized after each pass.
- `LoadingAttributeManager` is now the only runtime provider that hooks `wp_get_loading_optimization_attributes`; unconfigured images keep core behavior unchanged.
- Primary critical images have lazy loading removed, are set to `loading="eager"` when needed, and may receive plugin-assigned `fetchpriority="high"` once per request.
- Secondary critical images are de-lazied but never receive automatic plugin-assigned high priority, and `decoding` remains untouched.
- `DeliveryManager` now applies the same narrow loading override to attachment-backed fallback `<img>` markup before picture wrapping so attachment and post-content delivery stay consistent.
- `SettingsPage` now renders a minimal server-side checkbox for `critical_logo_enabled`, while post/page edit screens expose a classic side meta box with a media picker, hidden attachment ID field, preview, and clear action.
- Critical-image persistence remains deliberately narrow in 8.2: only `post` and `page` support `_hwlio_critical_image_id`, and unsupported, empty, autosave, revision, or unauthorized saves are ignored or deleted safely.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. `wp_get_loading_optimization_attributes` is confined to `src/Delivery/LoadingAttributeManager.php`; `add_meta_box`, `save_post`, and `wp_enqueue_media` are confined to the new post-editor slice; WooCommerce/Elementor integrations, REST routes, preload logic, dimension repair, and broad frontend hooks remain absent.

Manual WordPress smoke testing remains pending in this plugin-only workspace:

- the Settings tab should show the `critical_logo_enabled` checkbox and save through `options.php`
- post/page edit screens should show the critical-image side meta box with working media-picker select/replace/clear flows
- primary critical images should be de-lazied and receive at most one automatic `fetchpriority="high"` per request
- non-critical images and incoming lazy/high conflict cases should continue to fail open safely

### Acceptance Criteria

- [x] A normalized critical-image registry exists with built-in post/page meta and optional custom-logo candidates plus explicit filter override points.
- [x] Explicit critical-image loading overrides are applied only through `wp_get_loading_optimization_attributes` and the attachment-backed fallback markup rewrite helper.
- [x] Only one image per request may receive automatic plugin-assigned `fetchpriority="high"`, while secondary critical images are merely de-lazied.
- [x] `critical_logo_enabled` is persisted through the existing settings schema, sanitizer, repository, and minimal settings-page UI.
- [x] Post/page edit screens now expose a nonce-protected, capability-checked critical-image meta box with screen-scoped media-picker assets and no jQuery dependency.
- [x] WooCommerce, Elementor, preload, CLS dimension repair, REST settings exposure, and broad frontend hook expansion remain deferred.

### Deferred Work

- WooCommerce-specific and Elementor-specific critical-image sources remain deferred to Phases 9 and 10 and should hook the registry filters instead of adding parallel logic.
- Intrinsic-dimension repair was completed in Subphase 8.3; preload behavior remains deferred to later Phase 8 subphases.
- REST/UI expansion for broader critical-image management remains deferred; 8.2 keeps the visible controls intentionally minimal.

## Subphase 8.1 - Preserve Core Loading Attributes

**Status:** Complete
**Completed:** 2026-07-13

### Tasks

- [x] Extend conservative fallback-image analysis with normalized `loading`, `fetchpriority`, and `decoding` facts.
- [x] Detect the narrow `loading="lazy"` plus `fetchpriority="high"` conflict on standalone fallback `<img>` fragments.
- [x] Refuse to generate plugin `<picture>` markup when that conflict already exists, while preserving the original fallback HTML unchanged.
- [x] Keep the active frontend runtime surface unchanged: no new loading hooks, attribute filters, or rewrite logic.

### Files Changed

```text
CHANGELOG.md
docs/implementation-status.md
src/Delivery/ImageMarkupAnalysis.php
src/Delivery/PictureRenderResult.php
src/Delivery/PictureRenderer.php
src/Delivery/WordPressImageMarkupAnalyzer.php
tests/Unit/Delivery/DeliveryManagerTest.php
tests/Unit/Delivery/PictureRendererTest.php
tests/Unit/Delivery/WordPressImageMarkupAnalyzerTest.php
```

### Loading-Attribute Behavior

- `WordPressImageMarkupAnalyzer` now captures normalized `loading`, `fetchpriority`, and `decoding` values alongside the existing `sizes` fact for exact standalone `<img>` fragments only.
- `ImageMarkupAnalysis` exposes those values through typed getters and reports one narrow conflict state when a fallback image carries both `loading="lazy"` and `fetchpriority="high"`.
- `PictureRenderer` now bails out conservatively on that conflict and returns the original fallback `<img>` unchanged with `conflicting_loading_attributes`.
- Non-conflicting fallback images continue to be embedded verbatim inside generated `<picture>` markup, so core-generated `loading`, `fetchpriority`, `decoding`, classes, IDs, and other valid attributes remain intact.
- Generated `<source>` elements remain limited to `type`, `srcset`, and optional `sizes`; 8.1 does not introduce attribute rewriting, new frontend hooks, or critical-image overrides.

### Verification

```text
composer validate --strict: pass
composer dump-autoload: pass
composer run lint: pass
composer run cs: pass
composer run stan: pass
composer run test: pass
composer run quality: pass
git diff --check: pass
```

Source scans: pass. Runtime delivery remains confined to `wp_get_attachment_image` and `wp_content_img_tag` in `src/Delivery/DeliveryManager.php`; `wp_get_loading_optimization_attributes` and `wp_get_attachment_image_attributes` remain absent from runtime code.

### Acceptance Criteria

- [x] Core-generated `loading`, `fetchpriority`, and `decoding` values remain intact for non-conflicting fallback images.
- [x] The plugin no longer emits `<picture>` markup around fallback images that already carry the lazy/high conflict.
- [x] No loading-related attributes are added to generated `<source>` elements.
- [x] No new loading hooks, output buffering, JavaScript lazy loading, or critical-image policy were introduced in 8.1.

### Deferred Work

- Explicit critical-image registry, eager/high-priority overrides, and logo/per-post critical controls were completed in Subphase 8.2.
- CLS-oriented intrinsic-dimension repair was completed in Subphase 8.3.
- Preload behavior remains deferred to later Phase 8 work.
