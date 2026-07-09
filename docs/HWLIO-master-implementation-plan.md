# HyperWeb Lighthouse Image Optimizer
## Agentic AI Project Context, Requirements, Architecture, and Master Implementation Plan

**Plugin name:** HyperWeb Lighthouse Image Optimizer  
**Plugin slug:** `hyperweb-lighthouse-image-optimizer`  
**Internal short prefix:** `hwlio`  
**PHP package name:** `Hyperweb_Lighthouse_Image_Optimizer`  
**Proposed PHP namespace:** `HyperWeb\LighthouseImageOptimizer`  
**Document status:** Final master implementation specification  
**Prepared for:** An intelligent coding agent working inside the plugin repository  
**Document date:** 2026-07-09

---

## 1. Instructions to the Implementing Agent

This document is the source of truth for the implementation of **HyperWeb Lighthouse Image Optimizer**. Read it completely before changing code.

The existing repository is a scaffold, not a partially completed optimizer. Do not infer that placeholder classes, methods, assets, or comments represent final architectural decisions. Preserve the useful WordPress Plugin Boilerplate bootstrap pattern where it helps, but refactor the codebase into clear domain services before implementing image optimization logic.

### Mandatory operating rules

1. **Never delete, overwrite, or destructively recompress an original uploaded image.** Version 1 must always preserve originals.
2. **Do not add global frontend CSS, JavaScript, jQuery, or output buffering.** The plugin is intended to improve frontend performance and must not add avoidable render or network overhead.
3. **Use WordPress image APIs.** Use `wp_get_image_editor()`, `wp_image_editor_supports()`, attachment metadata APIs, responsive-image APIs, and WordPress filesystem/path helpers. Do not directly couple core conversion logic to GD or Imagick.
4. **Treat frontend delivery as a separate feature from conversion.** Conversion may be enabled while delivery is disabled, and vice versa only when valid derivatives exist.
5. **All expensive work must run asynchronously.** Upload requests and normal frontend requests must not perform a full attachment conversion synchronously.
6. **Every operation must be idempotent and resumable.** Re-running a task must not create duplicate files, duplicate jobs, corrupt metadata, or change a correct result.
7. **Every destructive operation requires path validation and explicit authorization.** Never construct a deletion path from untrusted input alone.
8. **Do not modify `.htaccess`, Nginx configuration, Elementor serialized data, theme files, or plugin files in version 1.** Later adapters may provide guidance or supported integration paths.
9. **Do not promise or encode a guaranteed Lighthouse score.** The plugin improves image-related causes of LCP, CLS, transfer size, and image-delivery findings; it cannot solve unrelated JavaScript, CSS, font, server, or third-party issues.
10. **Complete one subphase at a time.** At the end of each subphase, run its specified checks and update the implementation status section of this document or a dedicated progress document.

### Required completion report after each subphase

The agent should report:

- Subphase completed
- Files added, changed, or removed
- Architectural decisions made
- Automated checks run and their results
- Manual checks performed
- Known limitations or deferred items
- Whether the subphase acceptance criteria are satisfied

Do not start a later phase to hide incomplete work in an earlier phase.

---

## 2. Product Vision

HyperWeb Lighthouse Image Optimizer is an image-performance system for WordPress. Its purpose is not merely to convert JPEG and PNG files to WebP or AVIF. Its purpose is to reduce image-related performance costs and improve the underlying metrics and audits that influence Lighthouse Performance scores.

The plugin should eventually:

- Generate modern-format alternatives for eligible Media Library images.
- Automatically queue optimization for new uploads when enabled, while also providing per-attachment post-upload controls in the Media Library.
- Generate alternatives for the original display file and all relevant WordPress sub-sizes.
- Serve responsive AVIF/WebP sources with safe original-format fallback.
- Reduce unnecessary image transfer bytes.
- Avoid delivering full-size images into small layout slots.
- Preserve or repair intrinsic image dimensions to reduce image-related CLS.
- Avoid lazy-loading likely LCP images.
- Defer below-the-fold images without adding a JavaScript lazy-loading framework.
- Prioritize explicitly configured critical images.
- Support Elementor and WooCommerce through isolated adapters.
- Provide diagnostics and measurable before/after byte savings.
- Preserve originals and provide safe rollback and cleanup.

### Product promise

The product may state:

> Optimize WordPress image generation, responsive delivery, and loading behavior to reduce image payloads and address common image-related Lighthouse findings.

The product must not state:

> Guaranteed 100 Lighthouse score.

---

## 3. Goals, Success Criteria, and Non-Goals

## 3.1 Primary goals

1. **Reduce transferred image bytes** through modern formats and right-sized responsive candidates.
2. **Improve LCP opportunities** by preventing lazy loading of explicitly critical images and by making critical image resources discoverable and correctly prioritized.
3. **Reduce image-related CLS** by retaining or repairing valid intrinsic dimensions and aspect ratios.
4. **Avoid main-thread and network overhead** by implementing server-side delivery and avoiding global frontend scripts.
5. **Make optimization safe at scale** through queueing, locking, retries, progress reporting, and resumability.
6. **Provide operational visibility** through environment checks, job status, logs, and per-attachment results.
7. **Remain compatible with WordPress conventions** and degrade safely when a server does not support WebP or AVIF encoding.
8. **Provide immediate post-upload control and visibility** so administrators can see optimization status, start optimization manually, retry failures, re-optimize, inspect savings, or exclude an individual attachment.

## 3.2 MVP success criteria

The first production-capable milestone is successful when:

- A supported JPEG or PNG attachment can be queued and converted to a valid WebP sidecar.
- A supported server can also generate AVIF sidecars.
- The attachment's full display file and registered sub-sizes are handled.
- Conversion happens outside the upload request.
- Existing Media Library attachments can be bulk queued.
- A global `Automatically optimize new uploads` setting controls whether newly created image attachments are queued automatically.
- Newly uploaded attachments expose their optimization state and actions in supported Media Library interfaces after WordPress has created the attachment and generated its metadata.
- When automatic optimization is disabled, an authorized user can trigger `Optimize Now` for an individual attachment without using the bulk screen.
- A failed conversion does not break the upload, attachment, admin page, or frontend.
- Original files remain unchanged.
- Generated files have collision-safe names.
- Derivative metadata is saved separately from core attachment metadata.
- Deleting an attachment safely deletes only the derivatives recorded for that attachment.
- The plugin adds no global frontend CSS or JavaScript.
- The admin can see supported formats, job status, failures, and byte savings.

## 3.3 Full version 1 success criteria

Version 1 is successful when, in addition to the MVP:

- Valid derivatives can be served using attachment-aware `<picture>` markup.
- Responsive AVIF and WebP `srcset` candidates map correctly to WordPress image sizes.
- Original image markup and attributes are preserved.
- WordPress loading-optimization attributes are respected.
- Delivery can be enabled or disabled independently and rolled back instantly.
- WooCommerce main and gallery images pass compatibility tests.
- Standard Elementor image widgets pass compatibility tests.
- Image-related diagnostics identify common problems without claiming to be a complete Lighthouse replacement.
- Media Library list view, grid view, and Attachment Details integrations expose per-attachment status, format savings, optimize, retry, re-optimize, exclusion, and details actions where technically supported.
- Security, coding standards, unit tests, integration tests, and release checks pass.

## 3.4 Explicit non-goals for version 1

Do not implement the following as part of the core version 1 scope:

- Full-page caching
- CSS or JavaScript minification
- JavaScript delay or defer management
- Critical CSS generation
- Font hosting, subsetting, or preloading
- Database cleanup unrelated to this plugin
- General CDN provisioning
- Server TTFB optimization
- Video transcoding
- Automatic downloading and republishing of third-party images
- Destructive replacement of JPEG or PNG originals
- Automatic rewriting of theme or plugin asset files
- Generic whole-page HTML output buffering
- Automatic editing of server configuration
- AI-generated alt text
- A guaranteed Lighthouse score

---

## 4. Existing Scaffold: Verified Repository State

The supplied archive contains a single plugin directory:

```text
hyperweb-lighthouse-image-optimizer/
```

The archive currently contains **22 files**, including **15 PHP files**. Every PHP file passes `php -l` syntax validation at the time of this document.

### 4.1 Existing tree

```text
hyperweb-lighthouse-image-optimizer/
├── LICENSE.txt
├── README.txt
├── hyperweb-lighthouse-image-optimizer.php
├── uninstall.php
├── index.php
│
├── includes/
│   ├── class-hyperweb-lighthouse-image-optimizer.php
│   ├── class-hyperweb-lighthouse-image-optimizer-loader.php
│   ├── class-hyperweb-lighthouse-image-optimizer-i18n.php
│   ├── class-hyperweb-lighthouse-image-optimizer-activator.php
│   ├── class-hyperweb-lighthouse-image-optimizer-deactivator.php
│   └── index.php
│
├── admin/
│   ├── class-hyperweb-lighthouse-image-optimizer-admin.php
│   ├── css/
│   │   └── hyperweb-lighthouse-image-optimizer-admin.css
│   ├── js/
│   │   └── hyperweb-lighthouse-image-optimizer-admin.js
│   ├── partials/
│   │   └── hyperweb-lighthouse-image-optimizer-admin-display.php
│   └── index.php
│
├── public/
│   ├── class-hyperweb-lighthouse-image-optimizer-public.php
│   ├── css/
│   │   └── hyperweb-lighthouse-image-optimizer-public.css
│   ├── js/
│   │   └── hyperweb-lighthouse-image-optimizer-public.js
│   ├── partials/
│   │   └── hyperweb-lighthouse-image-optimizer-public-display.php
│   └── index.php
│
└── languages/
    └── hyperweb-lighthouse-image-optimizer.pot
```

### 4.2 File-by-file role and required treatment

#### `hyperweb-lighthouse-image-optimizer.php`

Current role:

- Defines the WordPress plugin header.
- Defines `HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_VERSION`.
- Registers activation and deactivation callbacks.
- Loads the core class.
- Instantiates and runs the plugin.

Required treatment:

- Keep this file as the stable WordPress entry point.
- Add file, path, URL, basename, minimum PHP, minimum WordPress, and schema-version constants.
- Load the project autoloader and the bundled Action Scheduler library early enough.
- Keep activation/deactivation callbacks small and deterministic.
- Add `Requires at least` and `Requires PHP` headers.
- Avoid business logic in this file.

#### `includes/class-hyperweb-lighthouse-image-optimizer.php`

Current role:

- Acts as the composition root.
- Loads dependencies manually.
- Creates the loader.
- Registers i18n, admin, and public hooks.

Required treatment:

- Keep it as a thin compatibility/composition layer or replace its internals with a namespaced application bootstrap.
- Do not add conversion logic directly to it.
- Instantiate domain services once and inject dependencies.
- Register hooks by domain module.

#### `includes/class-hyperweb-lighthouse-image-optimizer-loader.php`

Current role:

- Stores action/filter definitions and registers them in `run()`.

Required treatment:

- It may remain as a hook registrar.
- Do not misuse it as a service container, queue, logger, or repository.
- Add type/documentation improvements only if they do not complicate compatibility.

#### `includes/class-hyperweb-lighthouse-image-optimizer-i18n.php`

Current role:

- Loads the plugin text domain on `plugins_loaded`.

Required treatment:

- Retain translation support.
- Ensure all user-facing strings use the plugin text domain.
- Regenerate the POT file before release.

#### Activator and deactivator classes

Current role:

- Empty placeholders.

Required treatment:

Activation may:

- Check minimum requirements.
- Initialize default options.
- Store installed version and schema version.
- Schedule recurring maintenance after Action Scheduler is initialized.
- Set a one-time activation/setup notice.

Activation must not:

- Scan or convert the Media Library.
- make remote requests.
- alter frontend delivery.
- delete data.

Deactivation may:

- Unschedule plugin-owned pending maintenance actions.
- Release stale locks.
- preserve settings, metadata, originals, and derivatives.

Deactivation must not behave like uninstall.

#### Admin class

Current role:

- Globally enqueues demonstration CSS and jQuery-dependent JavaScript on every admin page.

Required treatment:

- Replace it with a thin admin coordinator.
- Load assets only on plugin-owned screens.
- Load JavaScript in the footer.
- Remove the jQuery dependency unless a proven admin-only need exists.
- Register menus, screens, settings, notices, REST controllers, and Media Library integrations through dedicated classes.
- Ensure post-upload controls are attachment-aware and appear only after WordPress has created a valid attachment ID and attachment metadata.

#### Public class

Current role:

- Globally enqueues empty CSS and jQuery-dependent JavaScript on every frontend page, with the script currently requested in the document head.

Required treatment:

- Remove both global enqueue hooks immediately.
- Do not enqueue frontend assets in the MVP.
- Convert this class into a thin frontend delivery coordinator or replace its internals with namespaced delivery services.
- All primary frontend work should occur through WordPress markup and responsive-image filters.

#### `uninstall.php`

Current role:

- Contains only the standard uninstall guard.

Required treatment:

- Default to preserving generated files and settings unless the administrator explicitly enabled destructive cleanup before uninstall.
- Validate every derivative path against stored attachment-owned metadata and the uploads directory.
- Never delete originals.
- Support multisite carefully and avoid loading every site into memory.

#### `README.txt`

Current role:

- Unmodified boilerplate with incorrect tags and obsolete compatibility values.

Required treatment:

- Replace before release with accurate requirements, description, installation, FAQ, privacy, third-party libraries, changelog, and upgrade notices.

### 4.3 Strengths of the scaffold

- Valid WordPress plugin entry point.
- Activation and deactivation wiring exists.
- Central hook loader exists.
- Admin/public separation exists.
- Translation class and text domain exist.
- GPL-compatible license metadata exists.
- No legacy optimizer logic constrains the architecture.

### 4.4 Current architectural risks

- Global frontend CSS and JavaScript contradict the plugin's performance goal.
- Global admin assets add unnecessary requests throughout wp-admin.
- jQuery is included without a functional need.
- Manual dependency loading will become unmaintainable as classes grow.
- Empty activator, deactivator, and uninstall logic provide no lifecycle safety.
- No settings, data model, queue, locking, logging, or tests exist.
- The placeholder README could misrepresent compatibility and functionality.

---

## 5. Required Architectural Decisions

These decisions are mandatory unless a documented technical blocker is discovered.

## 5.1 Bootstrap and class loading

Use a hybrid migration approach:

- Keep the existing entry file and legacy activation/deactivation callback names for stable WordPress lifecycle registration.
- Add Composer PSR-4 autoloading for all new domain code under:

```text
src/
```

- Use namespace:

```php
HyperWeb\LighthouseImageOptimizer
```

- The distributed plugin must include generated autoload files. Production must not require the site owner to run Composer.
- Composer development dependencies must not be shipped unless required at runtime.

Recommended `composer.json` responsibilities:

- PSR-4 runtime autoloading.
- PHPUnit.
- WordPress Coding Standards.
- PHPCompatibilityWP.
- Static analysis tooling.
- Test scripts.

## 5.2 Minimum platform

Target:

- WordPress 6.5 or later.
- PHP 7.4 or later.
- MySQL/MariaDB versions supported by the targeted WordPress release.

Reasoning:

- WordPress 6.5 introduced core AVIF handling, while actual AVIF encoding remains dependent on the server's GD or Imagick capabilities.
- PHP 7.4 provides a reasonable baseline for modern structure while retaining broad hosting compatibility.

Do not assume AVIF support merely because WordPress is new enough. Test the active image editor at runtime.

## 5.3 Background processing

Use **Action Scheduler** as the primary background queue.

Implementation rules:

- Bundle a pinned stable Action Scheduler release under `libraries/action-scheduler/` using the library's supported distribution approach.
- Include its loader before `plugins_loaded` priority `0`.
- Do not call scheduling APIs before Action Scheduler is initialized; use `action_scheduler_init` or a later safe hook.
- Use a dedicated group:

```text
hwlio
```

- Hide or filter plugin actions appropriately only if the Action Scheduler API supports it without forking the library.
- Queue one attachment-format operation with resumable cursor data rather than one unbounded request for an entire library.

A queue abstraction must wrap Action Scheduler so the conversion domain does not depend directly on global `as_*` functions.

## 5.4 File strategy

Use sidecar files in the same uploads subdirectory as the source by default.

Collision-safe examples:

```text
hero.jpg
hero.jpg.hwlio.webp
hero.jpg.hwlio.avif

hero-768x512.jpg
hero-768x512.jpg.hwlio.webp
hero-768x512.jpg.hwlio.avif

logo.png
logo.png.hwlio.webp
```

Why the original extension remains in the sidecar name:

- `logo.jpg` and `logo.png` may coexist.
- Mapping is deterministic.
- Cleanup is traceable.
- File ownership is clearer.

Never create `logo.webp` from both `logo.jpg` and `logo.png`.

## 5.5 Original preservation

Version 1 must not offer a setting that deletes originals after conversion. Original preservation is a hard invariant.

## 5.6 Core attachment metadata

Do not insert plugin-specific derivative structures into `_wp_attachment_metadata` in the initial implementation.

Store plugin-owned data in separate attachment meta so:

- Core metadata remains compatible.
- Regeneration plugins are less likely to remove plugin data unexpectedly.
- Rollback is straightforward.
- Schema changes are under plugin control.

## 5.7 Frontend delivery

Use attachment-aware, server-side rendering.

Primary delivery mode:

- Build `<picture>` markup for images with reliable attachment IDs.
- Prefer AVIF, then WebP, then the existing original `<img>` fallback.
- Preserve the original `<img>` node and all valid attributes.

Do not:

- Buffer and rewrite the full HTML response.
- parse arbitrary frontend HTML with fragile regular expressions.
- rewrite external URLs.
- add a frontend JavaScript polyfill.

Delivery must be disabled by default until the conversion pipeline and environment are validated. Activation must not change existing frontend markup.

## 5.8 WordPress loading attributes

WordPress core already calculates `loading`, `fetchpriority`, and `decoding` attributes. Preserve and cooperate with those calculations.

- Never add `loading="lazy"` to an image carrying `fetchpriority="high"`.
- Do not mark multiple unrelated images as high priority.
- Use explicit/manual critical-image configuration before attempting aggressive heuristics.

## 5.9 Integration isolation

Elementor, WooCommerce, CDN, offload, and multisite behavior must live in adapters. Core conversion classes must not import Elementor or WooCommerce classes.

## 5.10 No automatic server configuration edits

Version 1 must not write Apache or Nginx rules. Server negotiation may be documented as an advanced option later, but the default delivery path is WordPress markup.

---

## 6. Target Code Structure

The agent may adjust names modestly while retaining the domains and responsibilities below.

```text
hyperweb-lighthouse-image-optimizer/
├── hyperweb-lighthouse-image-optimizer.php
├── uninstall.php
├── composer.json
├── phpcs.xml.dist
├── phpunit.xml.dist
├── README.txt
├── CHANGELOG.md
├── LICENSE.txt
│
├── vendor/                         # Runtime Composer autoloader in release build.
├── libraries/
│   └── action-scheduler/           # Pinned upstream library, unmodified.
│
├── src/
│   ├── Plugin.php                  # Application composition root.
│   ├── Infrastructure/
│   │   ├── HookRegistrar.php
│   │   ├── Requirements.php
│   │   ├── Environment.php
│   │   ├── Clock.php
│   │   └── Installer.php
│   │
│   ├── Settings/
│   │   ├── SettingsRepository.php
│   │   ├── SettingsSchema.php
│   │   └── SettingsSanitizer.php
│   │
│   ├── Image/
│   │   ├── FormatSupport.php
│   │   ├── SourceImage.php
│   │   ├── SourceCollector.php
│   │   ├── DestinationResolver.php
│   │   ├── Converter.php
│   │   ├── ConversionPolicy.php
│   │   ├── ConversionResult.php
│   │   ├── ImageValidator.php
│   │   └── ResourceGuard.php
│   │
│   ├── Attachment/
│   │   ├── AttachmentProcessor.php
│   │   ├── DerivativeRepository.php
│   │   ├── AttachmentFingerprint.php
│   │   ├── AttachmentLock.php
│   │   ├── AttachmentCleanup.php
│   │   └── AttachmentStatus.php
│   │
│   ├── Queue/
│   │   ├── QueueInterface.php
│   │   ├── ActionSchedulerQueue.php
│   │   ├── OptimizationJob.php
│   │   ├── OptimizationWorker.php
│   │   ├── QueueStatus.php
│   │   └── QueueMaintenance.php
│   │
│   ├── Delivery/
│   │   ├── DeliveryManager.php
│   │   ├── PictureRenderer.php
│   │   ├── SourceSetBuilder.php
│   │   ├── DerivativeUrlResolver.php
│   │   ├── MarkupEligibility.php
│   │   ├── LoadingAttributeManager.php
│   │   └── CriticalImageRegistry.php
│   │
│   ├── Admin/
│   │   ├── AdminController.php
│   │   ├── Menu.php
│   │   ├── Assets.php
│   │   ├── NoticeManager.php
│   │   ├── MediaLibraryIntegration.php
│   │   ├── DashboardPage.php
│   │   ├── BulkPage.php
│   │   ├── SettingsPage.php
│   │   ├── DiagnosticsPage.php
│   │   ├── LogsPage.php
│   │   └── Rest/
│   │       ├── StatusController.php
│   │       ├── JobsController.php
│   │       ├── DiagnosticsController.php
│   │       └── AttachmentsController.php
│   │
│   ├── Diagnostics/
│   │   ├── DiagnosticCheck.php
│   │   ├── DiagnosticResult.php
│   │   ├── EnvironmentDiagnostics.php
│   │   ├── ConflictDetector.php
│   │   └── Statistics.php
│   │
│   ├── Logging/
│   │   ├── LoggerInterface.php
│   │   ├── DatabaseLogger.php
│   │   └── LogRetention.php
│   │
│   ├── Integration/
│   │   ├── IntegrationInterface.php
│   │   ├── WooCommerceIntegration.php
│   │   ├── ElementorIntegration.php
│   │   ├── OffloadIntegration.php
│   │   └── MultisiteIntegration.php
│   │
│   └── Cli/
│       └── Commands.php
│
├── admin/
│   ├── css/
│   ├── js/
│   └── partials/
│
├── public/                         # Thin compatibility layer; no global assets.
├── languages/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Fixtures/
│   └── bootstrap.php
└── docs/
    ├── architecture.md
    ├── data-model.md
    ├── hooks.md
    ├── testing.md
    └── release-process.md
```

### Structural constraints

- Classes should have one primary responsibility.
- Repositories read/write data; they do not render UI.
- Controllers validate requests and call services; they do not contain conversion algorithms.
- Conversion classes accept paths/data and return result objects; they do not print output or call admin functions.
- Integrations may depend on core services; core services must not depend on integrations.
- Static global state should be minimized.
- Do not instantiate the same major service repeatedly per hook registration.

---

## 7. Data Model

## 7.1 Options

Use consistently prefixed option names.

### `hwlio_settings`

A single structured option containing sanitized plugin settings.

Proposed schema:

```php
array(
    'schema_version'            => 1,
    'setup_completed'           => false,
    'automatic_optimization'    => false,
    'media_library_controls'    => true,
    'allow_attachment_exclusion' => true,
    'delivery_enabled'          => false,
    'enabled_formats'           => array( 'webp' ),
    'format_preference'         => array( 'avif', 'webp' ),
    'webp_quality'              => 82,
    'avif_quality'              => 60,
    'minimum_savings_percent'   => 5,
    'optimize_full_size'        => true,
    'optimize_subsizes'         => true,
    'skip_animated_gif'         => true,
    'max_retries'               => 3,
    'worker_time_budget'        => 20,
    'queue_concurrency'         => 1,
    'log_retention_days'        => 30,
    'delete_data_on_uninstall'  => false,
    'delete_derivatives_on_uninstall' => false,
);
```

Defaults must be filtered through one method and must not be duplicated across UI, activation, and repositories.

### Other options

```text
hwlio_version
hwlio_db_version
hwlio_activation_state
hwlio_statistics_cache
```

Avoid autoloading large statistics or log data. Explicitly control autoload behavior.

## 7.2 Attachment meta

### `_hwlio_derivatives`

Authoritative map of generated sidecars owned by an attachment.

Example:

```php
array(
    'schema_version' => 1,
    'fingerprint'    => array(
        'relative_file' => '2026/07/hero.jpg',
        'file_size'     => 920000,
        'modified_time' => 1783526400,
        'metadata_hash' => '...',
    ),
    'updated_at'     => 1783526500,
    'sizes'          => array(
        'full' => array(
            'source' => array(
                'file'   => '2026/07/hero.jpg',
                'width'  => 2400,
                'height' => 1600,
                'mime'   => 'image/jpeg',
                'bytes'  => 920000,
            ),
            'formats' => array(
                'webp' => array(
                    'file'            => '2026/07/hero.jpg.hwlio.webp',
                    'mime'            => 'image/webp',
                    'bytes'           => 310000,
                    'quality'         => 82,
                    'savings_bytes'   => 610000,
                    'savings_percent' => 66.30,
                    'status'          => 'ready',
                    'generated_at'    => 1783526500,
                ),
                'avif' => array(
                    'file'            => '2026/07/hero.jpg.hwlio.avif',
                    'mime'            => 'image/avif',
                    'bytes'           => 218000,
                    'quality'         => 60,
                    'savings_bytes'   => 702000,
                    'savings_percent' => 76.30,
                    'status'          => 'ready',
                    'generated_at'    => 1783526510,
                ),
            ),
        ),
    ),
);
```

Rules:

- Store paths relative to the uploads base directory.
- Do not store absolute filesystem paths or hard-coded site URLs.
- Treat this meta as the only authoritative deletion manifest.
- Keep skipped and failed statuses only when operationally useful; prune old verbose failure data into logs.

### `_hwlio_status`

Small summary status for list-table display:

```php
array(
    'state'       => 'optimized', // unprocessed|queued|processing|partial|optimized|failed|stale|excluded|skipped
    'formats'     => array( 'webp', 'avif' ),
    'updated_at'  => 1783526510,
    'error_code'  => null,
    'excluded'    => false,
);
```

### `_hwlio_excluded`

Optional boolean attachment meta used only when per-attachment exclusion is enabled.

```php
true
```

Rules:

- Exclusion prevents automatic and bulk queueing for the attachment.
- An explicitly authorized `Optimize Now` request may either require removing the exclusion first or include an explicit override confirmation; do not silently ignore exclusion state.
- Exclusion does not delete existing derivatives unless the administrator separately requests cleanup.
- The Media Library UI must clearly display the excluded state and provide an `Include in Optimization` action.

### `_hwlio_lock`

Use unique post meta as an attachment-level lock:

```php
array(
    'token'      => 'uuid-or-random-token',
    'created_at' => 1783526400,
    'expires_at' => 1783527000,
);
```

Acquire with `add_post_meta(..., true)` so concurrent workers do not process the same attachment. Stale locks must be recoverable.

## 7.3 Logs

For the first conversion milestone, a bounded custom database log table is acceptable if implemented carefully. Do not store an unbounded log in one option.

Proposed table:

```text
{$wpdb->prefix}hwlio_logs
```

Minimum columns:

```text
id
created_at_gmt
level
code
message
attachment_id nullable
job_id nullable
context_json nullable
```

Rules:

- Use `dbDelta()` for creation/upgrades.
- Index `created_at_gmt`, `level`, and `attachment_id`.
- Never store secrets, API keys, cookies, nonces, or absolute paths.
- Enforce retention.
- Successful per-file events should not be logged at normal verbosity.

A custom audit-results table may be added in a later diagnostics phase, not before it is needed.

---

## 8. Image Processing Requirements

## 8.1 Eligible source formats

Initial eligible raster sources:

- JPEG/JPG
- PNG
- non-animated WebP only when generating AVIF and when explicitly enabled

Initial exclusions:

- SVG
- animated GIF
- animated WebP
- AVIF-to-AVIF
- WebP-to-WebP
- unsupported or corrupt images
- images outside the WordPress uploads directory
- remote URLs

The source MIME must be detected from file content using WordPress APIs, not trusted solely from the filename or attachment post MIME.

## 8.2 Source collection

For an image attachment, collect:

- The current attached display file from `get_attached_file()`.
- The `full` dimensions and file details from attachment metadata.
- All current entries under attachment metadata `sizes`.
- The WordPress large-image `original_image` relationship when present.
- Missing, stale, and orphaned sub-size records.

Do not assume that the originally uploaded file and the current full display file are the same. WordPress may create a scaled full-size image.

Each normalized source record must include:

```text
attachment ID
WordPress size name
relative source path
absolute source path internally
source MIME
width
height
bytes
modified time
```

## 8.3 Format support

The environment service must evaluate WebP and AVIF separately using WordPress image-editor support APIs.

A format may be:

```text
supported
unsupported
misconfigured
unknown
```

The UI must distinguish “WordPress recognizes AVIF” from “this server can encode AVIF.”

## 8.4 Conversion policy

The policy decides whether a normalized source should be converted.

It must consider:

- Setting enabled for target format.
- Server encoding support.
- Source MIME.
- animation status.
- source existence/readability.
- destination writability.
- source pixel count/resource guard.
- existing valid derivative with matching fingerprint.
- force/re-optimize request.
- attachment-level exclusion state and path exclusion filters.

## 8.5 Conversion workflow

For each source and target format:

1. Validate source ownership and MIME.
2. Resolve a deterministic destination.
3. Check for a valid reusable derivative.
4. Create a temporary file in the destination directory.
5. Load via `wp_get_image_editor()`.
6. Set target quality.
7. Save with an explicit destination path and MIME.
8. Validate that the generated file exists and has the expected MIME and dimensions.
9. Compare bytes against the source.
10. If savings are below the configured threshold, delete the temporary output and record `skipped_not_smaller`.
11. Atomically rename the valid file into the destination.
12. Return a typed result object.
13. Update attachment metadata only after the file is successfully in place.

If the platform cannot atomically rename across filesystems, ensure the temporary file is created in the same directory as the destination.

## 8.6 Resource guard

Before loading large images:

- Inspect dimensions.
- Estimate memory conservatively.
- Compare against the effective PHP memory limit.
- Enforce a filterable maximum pixel count.
- Abort gracefully with an actionable error code rather than risking a fatal process termination.

Do not call `ini_set()` to raise global limits automatically.

## 8.7 Quality and size policy

Initial defaults:

- WebP quality: 82
- AVIF quality: 60
- Minimum required byte saving: 5%

These are configuration defaults, not claims of equal visual quality.

Rules:

- A modern derivative that is not meaningfully smaller must not be delivered.
- Preserve alpha transparency.
- PNG graphics may legitimately remain PNG if sidecars do not save bytes.
- Store the quality used in derivative metadata.
- Reoptimization with changed quality must be an explicit operation.

## 8.8 Fingerprinting and staleness

A derivative is reusable only when:

- The source path matches.
- Source bytes and modified time match.
- Relevant attachment metadata signature matches.
- The derivative exists.
- Its MIME and dimensions validate.
- Its metadata status is `ready`.

When these conditions fail, mark the attachment or size stale and queue regeneration.

Do not calculate a full cryptographic hash of every large image on every request. Use a cheap fingerprint for normal operations and allow deeper integrity checks in diagnostics.

---

## 9. Queue and Worker Requirements

## 9.1 Job types

Use named, prefixed hooks, for example:

```text
hwlio_optimize_attachment_format
hwlio_cleanup_attachment
hwlio_reconcile_attachment
hwlio_prune_logs
hwlio_recalculate_statistics
```

## 9.2 Optimization job payload

Keep payloads small and serializable:

```php
array(
    'attachment_id' => 123,
    'format'        => 'webp',
    'cursor'        => 0,
    'force'         => false,
    'reason'        => 'new_upload',
    'fingerprint'   => 'short-signature',
);
```

Do not pass absolute paths or entire attachment metadata arrays through the queue.

## 9.3 Job granularity

One logical job represents one attachment and one target format. The worker may process multiple sizes but must respect a time budget.

If the budget is approached:

- Save completed derivative metadata.
- Release the lock safely.
- Enqueue a continuation with the next cursor.
- Mark the attachment `partial` or `processing`.

This avoids creating one action for every thumbnail while keeping large attachments resumable.

## 9.4 Uniqueness and locking

Before enqueueing, check whether an equivalent pending/in-progress action exists.

At execution:

- Acquire the attachment lock.
- Confirm the queued fingerprint still matches current source state.
- If the source changed, stop and enqueue a fresh job.
- Release the lock in a `finally`-equivalent control path.
- Recover locks older than their TTL.

## 9.5 Retry policy

Retry only errors likely to be transient:

- Temporary filesystem write failure
- Resource contention
- Temporary offload/CDN adapter failure
- Recoverable lock collision

Do not repeatedly retry permanent errors such as:

- Unsupported format
- Missing source
- Corrupt image
- Permission denied that remains unchanged
- invalid MIME

Use exponential or bounded backoff. Default maximum retries: 3.

## 9.6 Bulk queueing

The bulk scanner must paginate attachment IDs and enqueue in bounded chunks. It must not load the entire Media Library into memory.

Supported initial filters:

- All eligible images
- Date range
- Attachment IDs selected by the user
- Missing derivatives only
- Failed attachments
- Stale attachments
- Specific target format

Bulk queueing must be resumable and report:

```text
scanned
eligible
queued
already optimized
skipped
failed to queue
```

## 9.7 Pause, resume, cancel

- **Pause** prevents the plugin from enqueueing or starting new plugin work while preserving pending actions.
- **Resume** restarts processing.
- **Cancel pending** removes only pending plugin-owned actions, not completed logs or valid derivatives.
- A currently running action may finish safely.

---

## 10. Frontend Delivery Requirements

## 10.1 Eligibility

Only transform markup when:

- Frontend delivery is enabled.
- A valid attachment ID is known.
- The attachment has valid ready derivatives.
- The request is an eligible HTML frontend context.
- The image is not already inside a `<picture>` element.
- A compatibility adapter has not excluded the image.
- The original markup can be preserved safely.

Skip by default in:

- wp-admin
- REST responses unless a specific endpoint opts in
- feeds
- emails
- XML sitemaps
- oEmbed responses
- AJAX fragments unless explicitly supported
- block editor canvas where markup compatibility is uncertain

## 10.2 Picture structure

Expected form:

```html
<picture class="hwlio-picture">
    <source type="image/avif" srcset="..." sizes="...">
    <source type="image/webp" srcset="..." sizes="...">
    <img src="original.jpg" srcset="..." sizes="..." width="..." height="..." alt="...">
</picture>
```

Rules:

- Source order follows configured preference, normally AVIF then WebP.
- Include a format only when at least one valid candidate exists.
- Preserve the original `<img>` exactly except for narrowly required repairs.
- Do not remove original `src` or original-format `srcset` fallback.
- Preserve classes, IDs, alt, title, dimensions, loading attributes, fetch priority, decoding, data attributes, event attributes, ARIA attributes, and plugin-specific attributes.
- Do not add a wrapper class when it breaks CSS selectors; make wrapper class filterable and allow no class.

## 10.3 Responsive mapping

Build modern `srcset` values by mapping original candidate URLs/widths back to attachment size records and derivative metadata.

The builder must:

- Retain the same candidate widths as the original WordPress `srcset` when matching derivatives exist.
- Exclude missing or invalid derivatives.
- Preserve the original `sizes` value.
- Avoid inventing dimensions that were not generated.
- Avoid using the full image for every candidate.
- Return no source element when mapping confidence is insufficient.

## 10.4 WordPress hooks

Likely hooks include:

- `wp_get_attachment_image`
- `wp_content_img_tag`
- `wp_get_attachment_image_attributes`
- `wp_calculate_image_srcset`
- `wp_get_loading_optimization_attributes`

Use the narrowest hook for each responsibility. Avoid applying the same transformation twice.

Implement a request-local registry of already transformed image signatures to prevent nested or duplicate wrapping.

## 10.5 Fallback and fail-open behavior

Any delivery error must return the original markup unchanged.

Delivery failures must never:

- remove an image.
- emit malformed HTML.
- cause a PHP warning in production output.
- make the page depend on a derivative that does not exist.

## 10.6 Caching

Derivative URLs must be deterministic so page caches and CDNs can cache generated markup.

When derivatives are regenerated or removed:

- Fire plugin hooks for cache integrations.
- Do not attempt to purge every known cache plugin in core code.
- Implement isolated adapters for supported cache/CDN products.

---

## 11. LCP, Lazy Loading, and CLS Requirements

## 11.1 Core-first loading behavior

WordPress core determines loading optimization attributes. The plugin should preserve those values and apply only explicit, validated overrides.

## 11.2 Critical image registry

Provide a registry allowing a page/template context to identify critical image attachment IDs or URLs.

Initial UI/API may support:

- Global site logo exclusion.
- Per-post/page critical attachment ID.
- WooCommerce primary product image.
- Explicit developer filter.

For a registered critical image:

- Remove `loading="lazy"` if present.
- Preserve or set `loading="eager"` only when appropriate.
- Permit `fetchpriority="high"` for a narrowly limited primary image.
- Do not assign high priority to multiple images by default.

## 11.3 Automatic heuristics

Do not claim reliable server-side LCP detection. Heuristics may be added later, but they must be conservative and overridable.

## 11.4 Preloading

Responsive preload is a later feature and must be opt-in.

Only preload when:

- The critical image is explicitly identified.
- The resource would otherwise be discovered late.
- `imagesrcset` and `imagesizes` match the delivered source candidates.
- Duplicate downloads have been tested against supported browsers.

Do not preload normal inline images already discoverable at the top of initial HTML without evidence of benefit.

## 11.5 CLS protection

The plugin should preserve valid `width` and `height` attributes.

Where attachment metadata provides reliable dimensions and markup lacks them, a repair module may add intrinsic dimensions to the `<img>` element.

Rules:

- Do not add fixed inline layout CSS.
- Do not change the intended responsive CSS behavior.
- Do not guess dimensions when the selected crop/size is unknown.
- Report uncertain cases instead of modifying them.

---

## 12. Admin Experience Requirements

## 12.1 Menu structure

Initial menu location:

```text
Media → Lighthouse Image Optimizer
```

Suggested tabs/screens:

- Dashboard
- Bulk Optimize
- Settings
- Diagnostics
- Logs

## 12.2 Dashboard

Display:

- WebP encoding status
- AVIF encoding status
- Active WordPress image editor
- Upload directory status
- Automatic optimization status
- Newly uploaded attachment activity and recent per-attachment results
- Frontend delivery status
- Queue state
- Attachments optimized/partial/failed/stale
- Source bytes represented
- Generated bytes
- Estimated bytes saved
- Recent failures
- Detected conflicts

Statistics may be cached but must be recalculable.

## 12.3 Bulk Optimize screen

Controls:

- Dry-run scan
- Queue all eligible images
- Queue missing only
- Requeue failures
- Re-optimize with current quality
- Date range
- Format selection
- Pause/resume
- Cancel pending

The UI must distinguish scanning from conversion.

## 12.4 Settings

Groups:

1. General
2. Formats and quality
3. Processing
4. Delivery
5. Logging and cleanup
6. Advanced exclusions

Use the WordPress Settings API for persistent settings and sanitization. Each setting must have:

- Type
- Default
- Sanitizer
- Validation rule
- Capability requirement
- Description

Do not silently enable unsupported formats.

## 12.5 Diagnostics

Checks should return a structured status:

```text
pass
warning
fail
info
```

Initial checks:

- WordPress version
- PHP version
- GD/Imagick availability
- WebP encode support
- AVIF encode support
- Upload base path
- Upload path writable
- Temporary file write/rename test
- Memory limit
- Action Scheduler initialized
- Queue health
- Stale locks
- Conflicting image optimizers
- Persistent object cache presence
- Multisite state
- Offload/CDN indicators
- Sample conversion test

A sample conversion must use a plugin-owned fixture or temporary generated image and remove it immediately.

## 12.6 Admin assets

- Enqueue only on plugin screens.
- Prefer small vanilla JavaScript or `wp-api-fetch`.
- Enqueue in the footer.
- Localize only necessary non-secret bootstrap data.
- Use accessible controls and live regions for progress.
- Poll only while a progress screen is visible; use a reasonable interval.

## 12.7 Media Library and post-upload optimization controls

Optimization controls must become available only after WordPress has:

1. Finished creating the attachment post.
2. Assigned a valid attachment ID.
3. Generated the attachment metadata and registered image sub-sizes.

The plugin must not attempt full synchronous conversion inside the binary upload request.

### Required interfaces

Where supported by WordPress, integrate with:

- Media Library list view.
- Media Library grid view.
- Attachment Details modal.
- Post/page media-selection modal after upload.
- The attachment edit screen.
- A success/status notice after uploading an image on plugin-supported admin screens.

### Required per-attachment status display

The UI should expose a concise status:

```text
Unprocessed
Queued
Processing
Optimized
Partially optimized
Failed
Stale
Skipped
Excluded
```

When results exist, show:

```text
WebP: Ready — 64% smaller
AVIF: Ready — 72% smaller
Last optimized: 2026-07-09 12:15
```

Do not expose absolute filesystem paths.

### Required per-attachment actions

Show only actions valid for the current state and current user's capabilities:

```text
Optimize Now
Retry
Re-optimize
View Details
Exclude from Optimization
Include in Optimization
Reconcile Files
```

Behavior requirements:

- `Optimize Now` queues the attachment asynchronously; it does not perform the entire conversion in the REST request.
- `Retry` queues only eligible failed or incomplete work.
- `Re-optimize` queues enabled formats using current quality settings and requires a clear confirmation because existing derivatives will be replaced after successful validation.
- `View Details` displays source sizes, generated formats, byte savings, skips, failures, and timestamps.
- `Exclude from Optimization` prevents new-upload automation, bulk queueing, and maintenance reconciliation from queueing the attachment.
- Existing derivatives are preserved when exclusion is enabled unless a separate cleanup action is requested.
- Controls update without a full page refresh where feasible.
- Upload success and image availability must not depend on optimization completion.

### Automatic-new-upload behavior

When `automatic_optimization` is enabled:

1. `wp_generate_attachment_metadata` returns the original WordPress metadata unchanged.
2. The plugin queues eligible enabled formats.
3. The attachment status changes to `queued`.
4. The Media Library shows non-blocking progress.
5. Completion changes the status to `optimized`, `partial`, `skipped`, or `failed`.

When `automatic_optimization` is disabled:

1. No automatic conversion job is queued.
2. The attachment remains `unprocessed`.
3. The Media Library exposes `Optimize Now`.
4. Bulk optimization remains available unless the attachment is excluded.

### Media Library performance requirements

- Do not run filesystem validation or aggregate conversion calculations for every row during normal Media Library rendering.
- Read the small `_hwlio_status` summary meta for list/grid indicators.
- Fetch detailed results only when the user opens details.
- Avoid unbounded attachment-meta queries.
- Add sortable/filterable columns only after query-cost testing.
- Any custom attachment query filters must be opt-in and indexed where appropriate.

### Authorization requirements

- Viewing status requires the ability to read/edit the attachment context.
- Starting, retrying, re-optimizing, reconciling, including, or excluding requires `upload_files` plus attachment-specific edit capability.
- Destructive derivative cleanup requires `manage_options` or a dedicated high-privilege capability.
- Every browser-triggered state change requires REST nonce authentication and capability checks.

### Acceptance requirements

- A newly uploaded image appears immediately in the Media Library even while optimization is pending.
- The user can determine whether optimization is queued, processing, complete, partial, failed, skipped, stale, or excluded.
- Automatic optimization can be enabled or disabled globally.
- Individual optimization works even when global automatic optimization is disabled.
- Duplicate clicks do not create equivalent duplicate jobs.
- Errors do not break the Media Library modal or the upload flow.
- The feature works without loading plugin assets on unrelated admin screens.

## 12.8 REST endpoints

Suggested namespace:

```text
hwlio/v1
```

Suggested endpoints:

```text
GET    /status
GET    /diagnostics
GET    /attachments
GET    /attachments/{id}
POST   /jobs/scan
POST   /jobs/queue
POST   /jobs/pause
POST   /jobs/resume
POST   /jobs/retry
DELETE /jobs/pending
POST   /attachments/{id}/optimize
POST   /attachments/{id}/reconcile
POST   /attachments/{id}/retry
POST   /attachments/{id}/exclude
POST   /attachments/{id}/include
```

Every route must:

- Register on `rest_api_init`.
- Include a `permission_callback`.
- Validate and sanitize arguments.
- Return `WP_REST_Response` or `WP_Error` consistently.
- Avoid exposing absolute paths.
- Avoid returning raw exception traces.

Settings and destructive operations require `manage_options`. Media operations must require `upload_files` and an attachment-specific capability such as `current_user_can( 'edit_post', $attachment_id )`.

---

## 13. Security Requirements

## 13.1 Authorization

- Use capability checks for every administrative action.
- Nonces protect browser-initiated state changes but do not replace capability checks.
- REST requests use WordPress REST nonce authentication for logged-in admin UI.

## 13.2 Input handling

- Sanitize settings based on declared type.
- Validate enum values against allowlists.
- Cast attachment IDs to positive integers.
- Never accept arbitrary source or destination filesystem paths from HTTP requests.
- Never accept arbitrary MIME values outside the plugin allowlist.

## 13.3 Filesystem safety

Before read/write/delete:

- Normalize paths.
- Resolve the uploads base directory.
- Confirm the candidate path is within the allowed base.
- Reject traversal segments and null bytes.
- Handle symlinks conservatively.
- Check that a deletion target is listed in attachment-owned derivative metadata.
- Never recursively delete an uploads directory.

## 13.4 SQL safety

- Use `$wpdb->prepare()` for dynamic SQL values.
- Use `dbDelta()` only for controlled schema strings.
- Paginate queries.
- Do not use unbounded `SELECT *` queries for the entire library.

## 13.5 Output safety

- Escape HTML, attributes, URLs, and JSON for their context.
- Translation strings containing placeholders must use safe formatting.
- Log viewer content must be escaped.

## 13.6 Privacy

Core conversion must not send images, filenames, page content, or site data to external services.

An optional PageSpeed Insights integration, if later implemented, must:

- Be disabled by default.
- clearly disclose the external request.
- send only the public URL and required API parameters.
- store API keys securely in WordPress options with autoload disabled.

---

## 14. Compatibility Requirements

## 14.1 Conflict detection

Detect, but do not automatically deactivate, plugins/features that may overlap in:

- WebP/AVIF generation
- frontend URL rewriting
- `<picture>` generation
- lazy loading
- CDN image transformation
- Media Library offload

The UI should explain the overlapping capability and recommend which module to disable.

## 14.2 WooCommerce

Initial WooCommerce adapter requirements:

- Primary product image remains eligible for critical-image treatment.
- Secondary gallery images preserve zoom/lightbox data attributes.
- Thumbnail markup remains compatible.
- Cart and checkout image output remains valid.
- Delivery must fail open to original markup.

Do not download the full zoom source early merely to serve the visible product image.

## 14.3 Elementor

Initial Elementor adapter requirements:

- Standard Image widget using attachment IDs should benefit from core attachment delivery.
- Image Box and other attachment-based widgets should be tested.
- Existing lightbox and data attributes must be preserved.
- Do not modify Elementor post meta in initial phases.

CSS background images are a separate advanced phase because they may be emitted through generated CSS, inline styles, responsive controls, or template data.

## 14.4 CDN and offload

Core file generation assumes local writable uploads.

An offload adapter must define:

- How a local source is obtained.
- Whether sidecars are uploaded.
- How derivative URLs are resolved.
- How deletion propagates.
- How asynchronous offload timing interacts with conversion.

If no supported adapter exists, the diagnostics UI must warn and disable unsafe operations rather than guessing.

## 14.5 Multisite

- Settings and attachment metadata are per site by default.
- Network activation must not synchronously iterate a large network and scan media.
- Initialize new sites through supported multisite lifecycle hooks.
- Network-wide controls are a later feature.
- Uninstall must handle network scope in bounded batches.

---

## 15. Extensibility Contract

Provide documented filters and actions after the core behavior stabilizes.

Suggested filters:

```text
hwlio_default_settings
hwlio_supported_source_mimes
hwlio_supported_target_formats
hwlio_should_optimize_attachment
hwlio_should_optimize_source
hwlio_conversion_quality
hwlio_minimum_savings_percent
hwlio_destination_relative_path
hwlio_delivery_is_enabled
hwlio_markup_is_eligible
hwlio_picture_sources
hwlio_critical_image_ids
hwlio_log_retention_days
```

Suggested actions:

```text
hwlio_before_attachment_queued
hwlio_after_attachment_queued
hwlio_before_attachment_optimization
hwlio_after_attachment_optimization
hwlio_conversion_failed
hwlio_derivative_created
hwlio_derivative_deleted
hwlio_attachment_became_stale
hwlio_cache_invalidation_requested
```

Rules:

- Prefix every hook.
- Document parameters and return types.
- Do not expose mutable internal objects without reason.
- Do not create hooks merely speculatively; add them around stable extension points.

---

# 16. Master Implementation Plan

The phases below are ordered. Each subphase should be completed and accepted before moving forward unless an explicit dependency requires parallel work.

---

## Phase 0 — Repository Baseline and Scaffold Hardening

### Subphase 0.1 — Create the development baseline

**Tasks**

- Record the current file tree and plugin metadata.
- Create a clean Git baseline commit.
- Add `.gitignore`, `.editorconfig`, and project coding conventions.
- Add `CHANGELOG.md` and a `docs/` directory.
- Copy this master plan into `docs/` if it is not already stored there.
- Add a lightweight implementation-status document or checklist.

**Acceptance criteria**

- The original scaffold can be restored from version control.
- All existing PHP files pass syntax checks.
- The plugin activates without fatal errors on the minimum target environment.

### Subphase 0.2 — Add Composer and quality tooling

**Tasks**

- Add Composer PSR-4 autoloading for `src/`.
- Add development dependencies for PHPCS with WordPress Coding Standards, PHPCompatibilityWP, PHPUnit, and a chosen static analyzer.
- Add scripts for linting, coding standards, unit tests, and static analysis.
- Include generated runtime autoload files in release packaging.

**Acceptance criteria**

- A trivial namespaced class autoloads from the plugin.
- Quality commands run locally.
- No dev-only vendor packages are required by the production plugin.

### Subphase 0.3 — Harden the bootstrap

**Tasks**

- Add constants for plugin file, path, URL, basename, version, DB version, minimum WordPress, and minimum PHP.
- Add plugin header requirements.
- Add graceful minimum-requirement handling.
- Load the Composer autoloader.
- Load Action Scheduler at the required early point.
- Keep activation/deactivation registration in the entry file.

**Acceptance criteria**

- Unsupported environments receive a clear admin-facing activation failure or safe disabled state.
- Supported environments activate normally.
- No business logic executes in the entry file.

### Subphase 0.4 — Remove performance-negative placeholder behavior

**Tasks**

- Remove public CSS/JS enqueue hook registration.
- Restrict admin assets to plugin-owned screens, initially none until screens exist.
- Remove jQuery dependency from placeholder assets.
- Remove or rewrite boilerplate comments that misdescribe final behavior.

**Acceptance criteria**

- Activating the plugin adds no frontend stylesheet or script requests.
- Activating the plugin adds no plugin assets to unrelated admin screens.

---

## Phase 1 — Application Foundation and Lifecycle

### Subphase 1.1 — Build the composition root

**Tasks**

- Create `src/Plugin.php`.
- Define service construction and hook registration boundaries.
- Retain the existing loader only as a hook registrar or replace it with an equivalent namespaced registrar.
- Ensure one shared instance per major repository/service.

**Acceptance criteria**

- Core modules can be registered without circular dependencies.
- Admin-only classes are not unnecessarily instantiated on frontend requests.
- Delivery classes are not active while delivery is disabled.

### Subphase 1.2 — Implement installation and upgrade routines

**Tasks**

- Implement default option initialization.
- Store plugin and database schema versions.
- Implement idempotent upgrades.
- Create the bounded log table with `dbDelta()`.
- Add upgrade execution on activation and version mismatch.

**Acceptance criteria**

- Re-running installation does not duplicate or corrupt data.
- Upgrade routines can run from an older simulated schema.
- The plugin remains usable if table creation fails; it reports diagnostics and falls back to minimal logging.

### Subphase 1.3 — Implement activation, deactivation, and uninstall policy

**Tasks**

- Activation initializes settings and setup state only.
- Deactivation unschedules plugin-owned recurring maintenance and leaves user data intact.
- Uninstall honors explicit deletion settings.
- Add safe derivative cleanup routines for uninstall without deleting originals.
- Add multisite guards even if network UI is deferred.

**Acceptance criteria**

- Activation performs no Media Library scan.
- Deactivation preserves all files and metadata.
- Default uninstall preserves derivatives and settings unless configured otherwise.
- Destructive uninstall tests confirm originals survive.

### Subphase 1.4 — Implement logging foundation

**Tasks**

- Add logger interface and database implementation.
- Define levels and stable machine-readable error codes.
- Redact sensitive/path data.
- Add retention cleanup scheduled through Action Scheduler.

**Acceptance criteria**

- Errors can be recorded without breaking the main operation.
- Retention removes old rows in bounded batches.
- Logs never expose absolute server paths in admin REST output.

---

## Phase 2 — Settings, Environment, and Diagnostics Foundation

### Subphase 2.1 — Settings schema and repository

**Tasks**

- Define all initial settings, types, defaults, and validation rules in one schema.
- Implement repository getters and immutable/default merging behavior.
- Set large/rare options to autoload false.
- Add filters for defaults where appropriate.

**Acceptance criteria**

- Invalid values cannot enter persisted settings through the repository.
- Missing keys receive defaults after upgrades.
- Feature modules read settings through the repository, not direct scattered `get_option()` calls.

### Subphase 2.2 — Settings API registration

**Tasks**

- Register settings and sanitizers.
- Create basic settings sections.
- Add validation feedback.
- Do not allow unsupported AVIF/WebP formats to be enabled without a warning/guard.

**Acceptance criteria**

- Only authorized administrators can save settings.
- Malformed arrays, quality values, and enums are rejected or normalized.
- Settings save successfully on single-site WordPress.

### Subphase 2.3 — Environment and format support

**Tasks**

- Detect PHP/WordPress versions.
- Detect active image editor candidates.
- Detect WebP and AVIF encoding support through WordPress APIs.
- Inspect uploads directory location and writability.
- Determine memory and execution constraints.
- Detect Action Scheduler readiness.

**Acceptance criteria**

- WebP and AVIF statuses are independent.
- A server without AVIF remains fully usable for WebP.
- Diagnostics do not generate warnings on missing extensions.

### Subphase 2.4 — Diagnostics framework

**Tasks**

- Create structured diagnostic result objects.
- Add pass/warning/fail/info states.
- Implement environment checks.
- Add a temporary sample conversion test with cleanup.

**Acceptance criteria**

- Diagnostics are callable from PHP and REST without rendering HTML.
- Temporary files are removed on success and failure.
- Results contain user-safe messages and developer details without secrets.

---

## Phase 3 — Core Image Domain

### Subphase 3.1 — Source image value objects and collector

**Tasks**

- Create normalized source image objects.
- Read current full file, sub-sizes, and `original_image` relationships.
- Normalize relative and absolute paths.
- Record dimensions, MIME, bytes, and modified time.
- Handle missing files and malformed metadata.

**Acceptance criteria**

- Fixture attachments with multiple sizes produce the expected normalized list.
- A missing thumbnail does not invalidate the whole attachment.
- Files outside uploads are rejected.

### Subphase 3.2 — MIME and animation validation

**Tasks**

- Detect real MIME through WordPress APIs.
- Implement supported source allowlist.
- Add animated GIF and animated WebP detection sufficient to skip safely.
- Reject corrupt or mismatched images.

**Acceptance criteria**

- Renamed non-image files are rejected.
- Animated sources are skipped with a clear status, not flattened.
- SVG is never passed to raster conversion.

### Subphase 3.3 — Destination resolver

**Tasks**

- Generate deterministic sidecar names.
- Preserve uploads subdirectories.
- Normalize and validate destination paths.
- Create temporary paths in the destination directory.
- Ensure source/destination collisions cannot occur.

**Acceptance criteria**

- `logo.jpg` and `logo.png` produce different sidecars.
- Every destination remains inside uploads.
- Repeated resolution returns the same result.

### Subphase 3.4 — Conversion result model and error taxonomy

**Tasks**

- Define success, skipped, and failed result states.
- Define stable error/skip codes.
- Include source/destination metadata and byte savings.
- Keep raw `WP_Error` details internal where sensitive.

**Acceptance criteria**

- Callers do not need to parse log strings to understand outcomes.
- Results serialize safely for job status and tests.

### Subphase 3.5 — Converter implementation

**Tasks**

- Use `wp_get_image_editor()`.
- Apply target quality.
- Save explicit target MIME.
- Validate output MIME and dimensions.
- Compare bytes and enforce minimum savings.
- Write temporarily and rename atomically.
- Clean up temporary files on every exit path.

**Acceptance criteria**

- JPEG and PNG fixtures produce valid WebP on supported environments.
- AVIF fixtures are generated only on supported environments.
- A larger derivative is removed and marked skipped.
- Originals are byte-for-byte unchanged.

### Subphase 3.6 — Resource guard

**Tasks**

- Calculate pixel count.
- Estimate memory requirements conservatively.
- Enforce configurable/filterable limits.
- Return graceful resource-limit results.

**Acceptance criteria**

- Oversized fixtures are skipped before editor allocation.
- No test requires changing global PHP limits.

---

## Phase 4 — Attachment State, Metadata, and Cleanup

### Subphase 4.1 — Attachment fingerprint

**Tasks**

- Build cheap source/metadata fingerprints.
- Compare queued fingerprints with current state.
- Detect staleness after image edits or regenerated sizes.

**Acceptance criteria**

- Replacing an attached file invalidates old derivatives.
- Unchanged attachments are idempotently skipped.

### Subphase 4.2 — Derivative repository

**Tasks**

- Implement schema-versioned `_hwlio_derivatives` reads/writes.
- Merge partial format/size results without losing completed work.
- Validate stored paths before returning them.
- Implement status summary meta.

**Acceptance criteria**

- Partial continuation jobs can safely add results.
- Invalid metadata is ignored and diagnosed rather than trusted.
- Core attachment metadata remains unchanged by plugin-specific data.

### Subphase 4.3 — Attachment locking

**Tasks**

- Acquire unique attachment locks.
- Add expiration and stale-lock recovery.
- Guarantee release after failures.
- Expose lock diagnostics.

**Acceptance criteria**

- Two simulated workers cannot convert the same attachment concurrently.
- Stale locks recover without manual database edits.

### Subphase 4.4 — Attachment processor

**Tasks**

- Orchestrate collection, policy, conversion, repository updates, and statistics.
- Process one format with cursor/time budget.
- Return a job-level summary.
- Fire stable lifecycle actions.

**Acceptance criteria**

- One attachment with multiple sizes processes predictably.
- Partial work is saved and resumable.
- A failure in one size does not discard successful sizes.

### Subphase 4.5 — Attachment cleanup

**Tasks**

- Register deletion cleanup.
- Delete only derivative paths present in authoritative metadata.
- Cancel pending attachment jobs.
- Remove plugin meta.
- Add orphan reconciliation in dry-run mode.

**Acceptance criteria**

- Deleting an attachment deletes its recorded sidecars.
- Original attachment files are never deleted by plugin cleanup code.
- Tampered paths outside uploads are rejected.

---

## Phase 5 — Action Scheduler Queue and Automatic Processing

### Subphase 5.1 — Queue abstraction

**Tasks**

- Define queue interface.
- Implement Action Scheduler adapter.
- Implement group and hook constants.
- Add availability guards.

**Acceptance criteria**

- Domain services can be tested with a fake queue.
- No conversion class directly calls global Action Scheduler functions.

### Subphase 5.2 — Optimization worker

**Tasks**

- Register worker hooks.
- Validate payloads.
- Acquire lock.
- Verify current fingerprint.
- Call attachment processor.
- Enqueue continuation or retry as required.
- Release lock and log outcome.

**Acceptance criteria**

- Invalid job payloads fail safely.
- Permanent errors do not retry indefinitely.
- Continuations resume at the correct cursor.

### Subphase 5.3 — New-upload integration

**Tasks**

- Listen to `wp_generate_attachment_metadata` with all available arguments.
- Return the original metadata unmodified.
- Read the global `automatic_optimization` setting.
- Respect attachment-level exclusion before queueing.
- Queue enabled target formats only after metadata generation when automatic optimization is enabled.
- Set the lightweight attachment summary state to `queued`, `unprocessed`, or `excluded` as appropriate.
- Deduplicate equivalent jobs.
- Handle create and update contexts.
- Fire an internal action that Media Library integrations may use to refresh status.

**Acceptance criteria**

- Upload completion is not blocked by full conversion.
- Normal WordPress thumbnails exist before the optimization job runs.
- A queue failure does not fail the upload.
- Disabling automatic optimization leaves new attachments available and marked `unprocessed`.
- Excluded attachments are not automatically queued.
- The hook always returns the WordPress metadata array unchanged except for unrelated core changes already present.

### Subphase 5.4 — Regeneration and edit reconciliation

**Tasks**

- Detect updated attachment metadata.
- Mark affected derivatives stale.
- Queue reconciliation.
- Remove obsolete sidecars only after replacement state is safe.

**Acceptance criteria**

- Regenerating thumbnails produces matching modern sidecars.
- Obsolete derivative metadata is not served.

### Subphase 5.5 — Maintenance actions

**Tasks**

- Schedule log pruning.
- Schedule stale-lock recovery.
- Schedule statistics reconciliation.
- Ensure important recurring actions remain scheduled.

**Acceptance criteria**

- Maintenance actions are uniquely scheduled.
- Deactivation unschedules only plugin-owned recurring actions.

---

## Phase 6 — Admin Screens, REST API, and Bulk Processing

### Subphase 6.1 — Menu and screen shell

**Tasks**

- Add the Media submenu.
- Build tab routing.
- Add capability checks.
- Add screen IDs for asset scoping.

**Acceptance criteria**

- Unauthorized users cannot access screens.
- Plugin assets load only on plugin screens.

### Subphase 6.2 — Admin assets and REST client

**Tasks**

- Implement minimal CSS.
- Use vanilla JS or `wp-api-fetch`.
- Add nonce/root configuration.
- Add accessible progress states and error notices.

**Acceptance criteria**

- No jQuery dependency unless documented.
- No plugin asset appears on unrelated admin pages.
- Admin actions work with JavaScript errors handled visibly.

### Subphase 6.3 — REST controllers

**Tasks**

- Add status, diagnostics, jobs, and attachment controllers.
- Implement attachment detail, optimize, retry, re-optimize, exclude, include, and reconcile operations.
- Add schemas, validation, sanitization, and permission callbacks.
- Normalize errors.

**Acceptance criteria**

- Every route rejects unauthenticated/unauthorized access.
- Invalid IDs and enums return 4xx errors.
- Responses do not expose absolute paths.

### Subphase 6.4 — Media Library and new-upload optimization controls

**Tasks**

- Create the `MediaLibraryIntegration` admin service.
- Add a lightweight optimization-status indicator to supported Media Library list and grid contexts.
- Integrate status and actions into the Attachment Details modal and attachment edit screen where supported.
- Add `Optimize Now`, `Retry`, `Re-optimize`, `View Details`, `Exclude from Optimization`, `Include in Optimization`, and `Reconcile Files` actions.
- Use `_hwlio_status` for lightweight list/grid rendering.
- Fetch detailed derivative data only on demand.
- Add REST-backed non-blocking status refresh after upload and while processing.
- Ensure controls remain available when automatic optimization is disabled.
- Ensure exclusion state is respected by new-upload automation and bulk scanning.
- Add capability and nonce protection for every action.
- Add accessible live-region updates for queued, processing, completed, and failed states.
- Avoid loading Media Library integration assets on unrelated admin screens.

**Acceptance criteria**

- A newly uploaded image is immediately usable and visible before optimization finishes.
- When automatic optimization is enabled, the new attachment displays `Queued` or `Processing` without requiring a bulk operation.
- When automatic optimization is disabled, the new attachment displays `Unprocessed` and exposes `Optimize Now`.
- The attachment details UI displays per-format readiness and byte savings after completion.
- Retry and re-optimize actions queue asynchronous jobs rather than converting inside the REST request.
- Excluded attachments are visibly marked and are skipped by automatic and bulk queueing.
- Equivalent duplicate jobs cannot be created by repeated clicks.
- List/grid rendering does not perform per-file filesystem validation.
- Unauthorized users cannot invoke optimization actions.
- Media upload and selection flows continue working when queueing or status requests fail.

### Subphase 6.5 — Dashboard

**Tasks**

- Display environment status.
- Display queue and attachment status counts.
- Display byte savings.
- Display recent failures and conflict warnings.

**Acceptance criteria**

- Dashboard remains fast on a large library by using cached/aggregate data.
- A recalculate action is available.

### Subphase 6.6 — Bulk scanner

**Tasks**

- Implement paginated attachment scanning.
- Implement dry-run summary.
- Support initial filters.
- Exclude attachment-level exclusions unless an explicit privileged override is requested.
- Persist scan progress if necessary.

**Acceptance criteria**

- A large fixture library is processed in bounded pages.
- Dry-run creates no sidecars and queues no conversions.

### Subphase 6.7 — Bulk queue controls

**Tasks**

- Queue scan results.
- Pause/resume.
- Cancel pending.
- Retry failed.
- Poll/display progress.

**Acceptance criteria**

- Duplicate clicks do not duplicate equivalent jobs.
- Pausing and resuming preserves progress.
- Cancel affects only plugin pending work.

### Subphase 6.8 — Logs and diagnostics screens

**Tasks**

- Render structured diagnostics.
- Paginate and filter logs.
- Add copyable error codes, not raw stack traces.
- Add safe log deletion/retention controls.

**Acceptance criteria**

- Log output is escaped.
- Large log tables remain paginated.

---

## Phase 7 — Frontend Modern-Format Delivery

### Subphase 7.1 — Derivative URL resolver

**Tasks**

- Convert validated relative paths to URLs using current uploads configuration.
- Support domain migrations and HTTPS changes.
- Add filter points for CDN adapters.

**Acceptance criteria**

- Stored metadata contains no fixed domain.
- URL changes follow `wp_upload_dir()` configuration.

### Subphase 7.2 — Responsive source-set builder

**Tasks**

- Map original WordPress candidates to derivative records.
- Build valid AVIF and WebP `srcset` strings.
- Preserve width descriptors.
- Omit missing candidates.

**Acceptance criteria**

- Generated sources match fixture candidate widths.
- No source references a nonexistent file.

### Subphase 7.3 — Picture renderer

**Tasks**

- Render safe `<picture>` markup.
- Preserve original `<img>` markup.
- Preserve all attributes.
- Prevent nested pictures and duplicate transformations.
- Fail open.

**Acceptance criteria**

- Snapshot tests cover common WordPress image markup.
- Malformed/unknown markup returns unchanged.
- No frontend JavaScript is required.

### Subphase 7.4 — Attachment image integration

**Tasks**

- Integrate with `wp_get_attachment_image` first.
- Add request-local deduplication.
- Exclude admin/editor/feed contexts.
- Add developer opt-out filters.

**Acceptance criteria**

- Standard theme attachment images render with picture sources when enabled.
- Delivery-disabled output is byte-equivalent to pre-plugin output wherever practical.

### Subphase 7.5 — Post-content image integration

**Tasks**

- Integrate with `wp_content_img_tag` only when WordPress identifies an attachment.
- Avoid wrapping images already in picture markup.
- Preserve block attributes/classes.

**Acceptance criteria**

- Gutenberg Image blocks with attachment IDs work.
- External and unresolvable images remain unchanged.

### Subphase 7.6 — Delivery rollback and cache hooks

**Tasks**

- Add a single emergency delivery toggle.
- Fire cache invalidation request hooks after derivative state changes.
- Add diagnostics for missing derivative files.

**Acceptance criteria**

- Disabling delivery immediately restores original markup without deleting sidecars.
- Missing sidecars never produce broken source URLs.

---

## Phase 8 — Loading Optimization and Layout Stability

### Subphase 8.1 — Preserve core loading attributes

**Tasks**

- Verify picture rendering preserves `loading`, `fetchpriority`, and `decoding` on the fallback image.
- Avoid conflicting combinations.
- Add tests across WordPress contexts.

**Acceptance criteria**

- Core-generated attributes remain intact.
- No image receives both lazy loading and high fetch priority through plugin code.

### Subphase 8.2 — Critical image registry

**Tasks**

- Add per-request developer API.
- Add global logo and per-post critical attachment settings.
- Allow integrations to register a critical image.
- Limit automatic high-priority application.

**Acceptance criteria**

- A configured critical image is not lazy-loaded.
- Unconfigured images retain core behavior.

### Subphase 8.3 — Intrinsic dimension repair

**Tasks**

- Detect missing width/height on known attachment-size markup.
- Add only when dimensions are certain.
- Add report-only mode first.
- Enable repair after compatibility tests.

**Acceptance criteria**

- Correct aspect ratio is preserved.
- Uncertain images remain unchanged and are reported.

### Subphase 8.4 — Optional responsive preload

**Tasks**

- Add an opt-in preload service for explicitly registered late-discovered critical images.
- Generate `imagesrcset`/`imagesizes` consistently.
- Deduplicate preloads.

**Acceptance criteria**

- No duplicate preload tags.
- Browser/network tests show no duplicate image download for supported scenarios.

---

## Phase 9 — WooCommerce Integration

### Subphase 9.1 — Compatibility audit and fixtures

**Tasks**

- Create product gallery fixture pages.
- Document WooCommerce image hooks and data attributes.
- Identify primary, secondary, cart, and checkout image contexts.

**Acceptance criteria**

- Baseline snapshots exist before integration-specific changes.

### Subphase 9.2 — Primary product image optimization

**Tasks**

- Register the primary visible product image as critical where appropriate.
- Preserve zoom/lightbox attributes.
- Ensure responsive modern sources map correctly.

**Acceptance criteria**

- Product zoom and lightbox continue working.
- The primary image is not accidentally lazy-loaded.

### Subphase 9.3 — Gallery and commerce surfaces

**Tasks**

- Test secondary gallery images.
- Test loop thumbnails, cart, checkout, related products, upsells, and variations.
- Add exclusions for incompatible contexts.

**Acceptance criteria**

- No broken images or lost variation switching.
- Fail-open behavior is verified.

---

## Phase 10 — Elementor and CSS Background Integration

### Subphase 10.1 — Elementor attachment-widget compatibility

**Tasks**

- Test Image, Image Box, Gallery, Carousel, CTA, and other attachment-based widgets.
- Preserve Elementor data attributes and lightbox behavior.
- Add adapter exclusions where generic wrapping breaks a widget.

**Acceptance criteria**

- Standard Elementor image widgets render correctly on responsive breakpoints.
- Editor mode is not disrupted.

### Subphase 10.2 — Oversized selection diagnostics

**Tasks**

- Detect Elementor widgets selecting `full` while rendering much smaller.
- Report selected source dimensions and likely rendered slot dimensions where reliable.
- Provide recommendations; do not alter Elementor data automatically.

**Acceptance criteria**

- Reports are advisory and do not change page data.

### Subphase 10.3 — Background image discovery

**Tasks**

- Identify supported Elementor background settings and attachment IDs.
- Distinguish desktop/tablet/mobile sources.
- Record unsupported CSS URL cases.

**Acceptance criteria**

- Discovery is read-only and does not parse unrelated CSS indiscriminately.

### Subphase 10.4 — Background delivery strategy

**Tasks**

- Design supported generated CSS integration using Elementor APIs.
- Preserve original fallback URLs.
- Generate modern-format declarations only for validated local attachment backgrounds.
- Regenerate Elementor CSS through supported mechanisms.

**Acceptance criteria**

- Elementor CSS can be regenerated and rolled back.
- Hidden breakpoint images are not unnecessarily forced into every viewport where the supported strategy can avoid it.

### Subphase 10.5 — Critical background preload

**Tasks**

- Allow explicit identification of a hero background.
- Generate safe responsive preload only for supported background mappings.
- Add duplicate-download testing.

**Acceptance criteria**

- Feature remains opt-in.
- No unsupported background is silently rewritten.

---

## Phase 11 — CDN, Offload, Multisite, and Conflict Adapters

### Subphase 11.1 — Conflict detector

**Tasks**

- Detect overlapping generation, delivery, lazy-loading, and CDN transformations.
- Show actionable warnings.
- Allow individual plugin modules to be disabled.

**Acceptance criteria**

- Detection does not deactivate or modify other plugins.
- The warning identifies the overlapping feature, not merely the plugin name.

### Subphase 11.2 — Generic CDN URL filter contract

**Tasks**

- Formalize URL resolver filters.
- Add cache-invalidation action contract.
- Document adapter requirements.

**Acceptance criteria**

- Core delivery remains independent of any specific CDN.

### Subphase 11.3 — Offload adapter framework

**Tasks**

- Define local-source retrieval and derivative-push interfaces.
- Implement one selected offload integration only after research/testing.
- Handle delayed offload and deletion.

**Acceptance criteria**

- Unsupported offload environments are detected and safely disabled.

### Subphase 11.4 — Multisite hardening

**Tasks**

- Test per-site settings and uploads.
- Add new-site initialization.
- Add bounded network uninstall routines.
- Document network activation behavior.

**Acceptance criteria**

- No cross-site metadata or file deletion occurs.

---

## Phase 12 — Page-Level Diagnostics and Lighthouse-Oriented Reporting

### Subphase 12.1 — Attachment and page inventory

**Tasks**

- Build a read-only inventory of image attachments referenced by supported page content/builders.
- Classify local attachment, local unregistered URL, background, external, and unknown images.

**Acceptance criteria**

- Inventory does not alter content.
- Unsupported cases are clearly labeled.

### Subphase 12.2 — Image issue rules

**Tasks**

Add report rules for:

- Missing modern derivative
- Oversized source selection
- Missing responsive candidates
- Missing intrinsic dimensions
- Critical image lazy-loaded
- Below-the-fold eager loading where measurable
- External image
- Animated GIF
- Broken image URL
- CSS background image
- Duplicate source downloads where measurable

**Acceptance criteria**

- Each rule includes evidence, severity, and remediation.
- Rules do not claim measured LCP without browser lab data.

### Subphase 12.3 — Before/after byte reporting

**Tasks**

- Calculate source and derivative byte totals for known candidates.
- Record actual conversion savings.
- Distinguish theoretical page savings from measured transfer savings.

**Acceptance criteria**

- Reports label estimates clearly.
- No score projection is presented as guaranteed.

### Subphase 12.4 — Optional PageSpeed Insights integration

**Tasks**

- Add opt-in API configuration.
- Use WordPress HTTP APIs.
- Store results with timestamp, strategy, URL, and relevant metrics.
- Handle quotas and API errors.
- Disclose external service use.

**Acceptance criteria**

- Core plugin works with no API key.
- API failures do not affect image delivery.
- Results are labeled as lab data and may fluctuate.

---

## Phase 13 — WP-CLI and Developer Operations

### Subphase 13.1 — WP-CLI status and diagnostics

**Tasks**

Add commands such as:

```text
wp hwlio status
wp hwlio diagnostics
wp hwlio attachment <id> --format=webp
```

**Acceptance criteria**

- Commands return meaningful exit codes.
- Human and machine-readable output options are considered.

### Subphase 13.2 — WP-CLI bulk operations

**Tasks**

- Scan/queue media in pages.
- Reconcile stale attachments.
- Retry failures.
- Prune logs.
- Dry-run cleanup.

**Acceptance criteria**

- Large operations stream progress and do not load all attachments at once.

### Subphase 13.3 — Public developer documentation

**Tasks**

- Document filters/actions.
- Document data ownership and sidecar names.
- Document integrations and exclusions.
- Document rollback.

**Acceptance criteria**

- A third-party developer can add an exclusion or URL adapter without editing core plugin files.

---

## Phase 14 — Testing, Performance, Security, and Release

### Subphase 14.1 — Unit test coverage

Prioritize tests for:

- Settings sanitization
- Destination resolution
- Path validation
- Fingerprinting
- Conversion policy
- Result calculations
- Source-set mapping
- Picture rendering
- Lock behavior
- Retry classification

**Acceptance criteria**

- Critical pure-domain logic has meaningful coverage.
- Tests include failure paths, not only successful conversion.

### Subphase 14.2 — WordPress integration tests

Test:

- Activation/upgrades
- Attachment upload metadata hook
- Queue creation
- Attachment deletion
- REST authorization
- Media Library individual optimize/retry/exclude/include actions
- Automatic-new-upload enabled and disabled behavior
- Settings persistence
- WordPress markup filters
- Multisite basics

**Acceptance criteria**

- Tests run against the minimum target WordPress version and a current stable version.

### Subphase 14.3 — Image fixture matrix

Fixtures should include:

- Small JPEG photograph
- Large JPEG photograph
- Opaque PNG
- Transparent PNG
- PNG graphic with poor modern-format savings
- Animated GIF
- Static WebP
- Animated WebP if fixture licensing permits
- AVIF
- Corrupt file
- Filename collision pair (`logo.jpg` and `logo.png`)
- Missing thumbnail metadata case
- WordPress scaled-large-image case

**Acceptance criteria**

- Expected process/skip/fail outcomes are documented and automated where possible.

### Subphase 14.4 — Compatibility test matrix

Test at minimum:

- A default WordPress theme
- Gutenberg content images
- WooCommerce product/gallery flows
- Elementor frontend and editor
- A representative page-cache plugin
- A representative image-optimization conflict
- No persistent object cache and a persistent object cache
- GD and Imagick environments where available
- WebP-only and WebP+AVIF environments

**Acceptance criteria**

- Known incompatibilities have safe exclusions or documented warnings.

### Subphase 14.5 — Performance regression testing

Measure:

- Frontend queries added
- Frontend PHP time added with delivery disabled
- Frontend PHP time added with delivery enabled
- Admin screen query/time behavior
- Upload response impact
- Media Library list/grid query and rendering overhead
- Queue worker memory/time
- Media Library scan memory
- Added network requests

**Acceptance criteria**

- Delivery disabled has negligible frontend overhead.
- No global frontend network requests are added.
- Upload requests do not perform full conversion.

### Subphase 14.6 — Security review

Review:

- REST permissions
- Nonces and capabilities
- Path traversal
- Arbitrary file deletion
- MIME spoofing
- Stored and reflected XSS
- SQL injection
- Information disclosure
- Race conditions
- Uninstall safety
- Third-party library provenance

**Acceptance criteria**

- No high-severity unresolved finding remains.
- Security-sensitive code has dedicated tests.

### Subphase 14.7 — Accessibility and i18n review

**Tasks**

- Check keyboard navigation and focus states.
- Use accessible tables, notices, progress, and controls.
- Ensure all user-facing strings are translatable.
- Regenerate POT.
- Preserve image alt text and ARIA attributes.

**Acceptance criteria**

- Admin workflows are usable by keyboard.
- Frontend transformations do not alter alt text.

### Subphase 14.8 — Packaging and release readiness

**Tasks**

- Replace boilerplate README.
- Document Action Scheduler license/source.
- Update changelog.
- Build production artifact without tests/dev dependencies.
- Verify no source maps, secrets, test fixtures, or local configuration leak unintentionally.
- Run a clean-install smoke test from the release ZIP.

**Acceptance criteria**

- Release ZIP activates on a clean supported WordPress install.
- The ZIP contains required runtime dependencies.
- The plugin remains inert on the frontend until configured.

---

## 17. Definition of Done for Every Subphase

A subphase is not done merely because code exists. It is done only when:

- The intended behavior is implemented.
- Error paths are handled.
- Security checks are present.
- Relevant automated tests pass.
- PHP syntax, coding standards, and static checks pass for changed files.
- No unrelated frontend/admin assets are introduced.
- Documentation is updated.
- Acceptance criteria are demonstrated.
- Deferred work is recorded explicitly.

---

## 18. Release Milestones

### Milestone A — Internal foundation (`0.1.0`)

Phases 0–2 complete.

Deliverable:

- Hardened scaffold
- Autoloading
- lifecycle
- settings
- environment diagnostics
- no conversion yet

### Milestone B — Conversion MVP (`0.2.0`)

Phases 3–5 complete.

Deliverable:

- New uploads and selected existing attachments generate safe sidecars asynchronously.
- Cleanup and metadata ownership work.

### Milestone C — Operational admin (`0.3.0`)

Phase 6 complete.

Deliverable:

- Dashboard, Media Library post-upload controls, individual attachment actions, bulk queueing, diagnostics, settings, and logs.

### Milestone D — Frontend delivery beta (`0.4.0`)

Phases 7–8 complete.

Deliverable:

- Opt-in picture delivery, responsive sources, loading-attribute preservation, and critical-image controls.

### Milestone E — Integration beta (`0.5.0`)

Phases 9–11 complete for selected supported integrations.

Deliverable:

- WooCommerce and Elementor compatibility plus adapter framework.

### Milestone F — Release candidate (`0.9.0`)

Phases 12–14 substantially complete.

Deliverable:

- Diagnostics/reporting, CLI, test matrix, security review, and release packaging.

### Milestone G — Stable (`1.0.0`)

All version 1 acceptance criteria pass with no unresolved release blocker.

---

## 19. Implementation Status Checklist

The implementing agent should maintain this section or migrate it to `docs/implementation-status.md`.

- [ ] Phase 0 — Repository Baseline and Scaffold Hardening
- [ ] Phase 1 — Application Foundation and Lifecycle
- [ ] Phase 2 — Settings, Environment, and Diagnostics Foundation
- [ ] Phase 3 — Core Image Domain
- [ ] Phase 4 — Attachment State, Metadata, and Cleanup
- [ ] Phase 5 — Action Scheduler Queue and Automatic Processing
- [ ] Phase 6 — Admin Screens, REST API, and Bulk Processing
- [ ] Phase 7 — Frontend Modern-Format Delivery
- [ ] Phase 8 — Loading Optimization and Layout Stability
- [ ] Phase 9 — WooCommerce Integration
- [ ] Phase 10 — Elementor and CSS Background Integration
- [ ] Phase 11 — CDN, Offload, Multisite, and Conflict Adapters
- [ ] Phase 12 — Page-Level Diagnostics and Lighthouse-Oriented Reporting
- [ ] Phase 13 — WP-CLI and Developer Operations
- [ ] Phase 14 — Testing, Performance, Security, and Release

---

## 20. Agent Decision Log Template

For decisions that deviate from this plan, add an entry:

```markdown
### ADR-XXX: Decision title

**Date:** YYYY-MM-DD  
**Status:** Proposed | Accepted | Superseded  
**Context:** What problem required a decision?  
**Decision:** What was selected?  
**Alternatives considered:** What else was evaluated?  
**Consequences:** Benefits, costs, risks, and migration impact.  
**Affected phases/files:** ...
```

Do not silently change foundational decisions such as metadata ownership, original preservation, queue technology, path strategy, or frontend delivery method.

---

## 21. Initial Error and Status Codes

Use stable codes rather than relying only on messages.

### Success/status examples

```text
optimized
partial
already_current
queued
stale
unprocessed
excluded
```

### Skip examples

```text
skipped_unsupported_source_mime
skipped_target_not_enabled
skipped_target_not_supported
skipped_animated_image
skipped_not_smaller
skipped_resource_limit
skipped_excluded
skipped_outside_uploads
```

### Failure examples

```text
source_missing
source_unreadable
source_invalid_mime
source_corrupt
editor_unavailable
editor_load_failed
conversion_failed
temporary_write_failed
output_validation_failed
atomic_move_failed
metadata_write_failed
lock_unavailable
queue_unavailable
permission_denied
invalid_job_payload
```

Messages may change or be translated; codes should remain stable unless versioned.

---

## 22. Technical Reference Links

The implementing agent should consult official primary documentation rather than relying on remembered signatures.

### WordPress media and images

- `wp_generate_attachment_metadata` filter:  
  https://developer.wordpress.org/reference/hooks/wp_generate_attachment_metadata/
- `wp_generate_attachment_metadata()` function:  
  https://developer.wordpress.org/reference/functions/wp_generate_attachment_metadata/
- `wp_get_image_editor()`:  
  https://developer.wordpress.org/reference/functions/wp_get_image_editor/
- `wp_image_editor_supports()`:  
  https://developer.wordpress.org/reference/functions/wp_image_editor_supports/
- `WP_Image_Editor`:  
  https://developer.wordpress.org/reference/classes/wp_image_editor/
- `WP_Image_Editor::save()`:  
  https://developer.wordpress.org/reference/classes/wp_image_editor/save/
- Responsive images API:  
  https://developer.wordpress.org/apis/responsive-images/
- `wp_get_loading_optimization_attributes()`:  
  https://developer.wordpress.org/reference/functions/wp_get_loading_optimization_attributes/
- `wp_get_attachment_image` filter:  
  https://developer.wordpress.org/reference/hooks/wp_get_attachment_image/
- `wp_content_img_tag` filter:  
  https://developer.wordpress.org/reference/hooks/wp_content_img_tag/
- `wp_calculate_image_srcset` filter:  
  https://developer.wordpress.org/reference/hooks/wp_calculate_image_srcset/

### WordPress administration and data

- Settings API:  
  https://developer.wordpress.org/plugins/settings/settings-api/
- Custom settings page:  
  https://developer.wordpress.org/plugins/settings/custom-settings-page/
- Custom database tables and `dbDelta()`:  
  https://developer.wordpress.org/plugins/creating-tables-with-plugins/
- REST custom endpoints:  
  https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
- REST routes and permission callbacks:  
  https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/
- Plugin best practices:  
  https://developer.wordpress.org/plugins/plugin-basics/best-practices/

### Action Scheduler

- Usage and library loading:  
  https://actionscheduler.org/usage/
- API reference:  
  https://actionscheduler.org/api/

### Lighthouse and web performance

- Lighthouse performance scoring:  
  https://developer.chrome.com/docs/lighthouse/performance/performance-scoring
- Improve image delivery insight:  
  https://developer.chrome.com/docs/performance/insights/image-delivery
- Optimize Largest Contentful Paint:  
  https://web.dev/articles/optimize-lcp
- Browser-level image lazy loading:  
  https://web.dev/articles/browser-level-image-lazy-loading

---

## 23. Final Implementation Principle

The plugin should be safe before it is clever.

A smaller, fully validated system that generates correct sidecars, processes work asynchronously, preserves originals, records ownership, cleans up safely, and adds no frontend overhead is more valuable than an ambitious optimizer that rewrites every image source but creates compatibility and rollback risks.

Implement in this order:

1. Establish architecture and safety.
2. Generate correct files.
3. Track and clean them correctly.
4. Process at scale.
5. Expose post-upload and operational controls.
6. Deliver modern formats safely.
7. Optimize critical loading behavior.
8. Add integrations and diagnostics.

Do not reverse that order.
