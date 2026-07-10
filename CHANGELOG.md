# Changelog

All notable changes to HyperWeb Lighthouse Image Optimizer will be documented in this file.

The format is based on Keep a Changelog, and this project will use semantic versioning once release packaging begins.

## [Unreleased]

### Added

- Established the development baseline for the initial WordPress Plugin Boilerplate scaffold.
- Added project editor conventions, ignore rules, changelog tracking, and implementation status documentation.
- Added Composer PSR-4 autoloading, quality tooling configuration, and a minimal autoload proof.
- Hardened the plugin bootstrap with platform constants, requirement guards, Composer loading, and bundled Action Scheduler 3.9.3.
- Added a namespaced application composition root with a shared hook registrar and i18n provider.
- Added lifecycle installer routines for default settings, version state, activation diagnostics, and the initial log table schema.
- Added non-destructive deactivation cleanup and opt-in uninstall policy services with safe derivative path validation.
- Added a bounded logging foundation with sanitization, database-backed writes, and Action Scheduler log-retention maintenance.
- Added a schema-driven settings repository with sanitization, default merging, typed getters, and filtered defaults.
- Added WordPress Settings API registration with schema-backed sanitization, admin validation feedback, and minimal format-support guarding.
- Added a canonical environment capability layer for PHP/WordPress versions, image editor candidates, WebP/AVIF support, uploads status, runtime limits, and Action Scheduler readiness.
- Added a structured diagnostics framework with user-safe results, environment checks, temporary write/rename checks, and bounded sample conversion diagnostics.
- Added read-only source image value objects and a collector for normalized attachment full, subsize, and original-image sources.
- Added source MIME and animation validation for read-only image eligibility checks.
- Added deterministic uploads-safe destination resolution for future derivative sidecars.
- Added a conversion result model and stable conversion error/skip taxonomy for future converter and worker phases.
- Added a callable WordPress image-editor converter with deterministic temp output, output validation, minimum-savings enforcement, cleanup, and atomic sidecar moves.
- Added a pre-allocation resource guard that skips oversized converter requests before editor allocation.
- Added service-only attachment fingerprinting for cheap source/metadata staleness checks and future queue payload validation.
- Added the derivative repository for schema-versioned `_hwlio_derivatives` manifests and `_hwlio_status` summaries.

### Removed

- Removed global placeholder admin/frontend asset loading, scaffold jQuery wrappers, and unused boilerplate display partials.
- Removed the obsolete legacy runtime core, hook loader, and i18n classes.
- Replaced the settings-local format support checker with the canonical environment format support provider.

### Notes

- No runtime image optimization hooks, queueing, delivery, or admin workflow behavior has been implemented yet.
