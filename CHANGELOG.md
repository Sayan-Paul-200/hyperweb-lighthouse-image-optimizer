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

### Removed

- Removed global placeholder admin/frontend asset loading, scaffold jQuery wrappers, and unused boilerplate display partials.
- Removed the obsolete legacy runtime core, hook loader, and i18n classes.

### Notes

- No image optimization, conversion, queueing, delivery, or admin workflow behavior has been implemented yet.
