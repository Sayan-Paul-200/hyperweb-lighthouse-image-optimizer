# Developer Extension Guide

This document defines the stable developer-facing extension seams that exist after Subphase 13.3.

Use this guide when you need to integrate HyperWeb Lighthouse Image Optimizer with custom delivery rules, cache/CDN adapters, loading behavior, or critical-image selection without editing core plugin files.

For CDN and offload URL-rewrite details, also see [cdn-offload-adapter-contract.md](./cdn-offload-adapter-contract.md).

## Overview and Support Boundary

The plugin exposes a small stable hook surface for integration work. Those hooks are the supported extension path.

The plugin also stores internal attachment meta, options, post meta, and queue/runtime identifiers. Those storage keys and runtime-owned hooks are documented here so you can recognize them, inspect them safely, and avoid conflicting with them. They are not general-purpose write APIs unless a specific contract says otherwise.

Integration rules:

- Prefer filters and actions over direct metadata or option writes.
- Fail open when your integration cannot decide safely.
- Do not edit plugin core files to add exclusions or URL rewrites.
- Do not assume Action Scheduler hook names, internal lifecycle signals, or raw value-object payloads are stable public APIs unless they are listed in this guide as public contracts.

Internal-only examples that are not public extension contracts include:

- `hwlio_attachment_process_started`
- `hwlio_attachment_process_completed`
- `hwlio_attachment_process_failed`
- `hwlio_attachment_status_refresh`
- plugin Action Scheduler action names such as `hwlio_optimize_attachment_format`

## Stable Hooks and Actions

### `hwlio_default_settings`

- Type: filter
- When it runs: before default settings are sanitized and normalized
- Signature:

```php
apply_filters( 'hwlio_default_settings', array $defaults );
```

- Return: associative array of default settings
- Rules:
  - return an array only
  - expect the plugin to sanitize the result before use
  - do not assume every unknown key will be preserved

Example:

```php
add_filter(
	'hwlio_default_settings',
	static function ( array $defaults ): array {
		$defaults['delivery_enabled'] = false;
		return $defaults;
	}
);
```

### `hwlio_max_pixel_count`

- Type: filter
- When it runs: when the resource guard builds the current maximum safe pixel limit
- Signature:

```php
apply_filters( 'hwlio_max_pixel_count', int $max_pixels );
```

- Return: integer pixel limit
- Rules:
  - return a conservative integer
  - do not use this hook to bypass broader memory or processing safeguards

Example:

```php
add_filter(
	'hwlio_max_pixel_count',
	static function ( int $max_pixels ): int {
		return 30000000;
	}
);
```

### `hwlio_delivery_is_enabled`

- Type: filter
- When it runs: before delivery transforms one attachment-backed markup fragment
- Signature:

```php
apply_filters(
	'hwlio_delivery_is_enabled',
	bool $enabled,
	int $attachment_id,
	string $html,
	array $context
);
```

- Return: boolean
- Context keys:
  - `hook`
  - `content_context`
  - `size`
  - `icon`
  - `attr`
  - `request_context`
- Rules:
  - use this to disable delivery conservatively for specific cases
  - the internal emergency delivery switch remains authoritative and cannot be bypassed here
  - do not mutate markup in this filter

Example:

```php
add_filter(
	'hwlio_delivery_is_enabled',
	static function ( bool $enabled, int $attachment_id, string $html, array $context ): bool {
		if ( ! empty( $context['request_context']['is_feed'] ) ) {
			return false;
		}

		return $enabled;
	},
	10,
	4
);
```

### `hwlio_markup_is_eligible`

- Type: filter
- When it runs: after the plugin computes whether one markup fragment is eligible for transformation
- Signature:

```php
apply_filters(
	'hwlio_markup_is_eligible',
	bool $eligible,
	int $attachment_id,
	string $html,
	array $context
);
```

- Return: boolean
- Context keys:
  - `hook`
  - `content_context`
  - `size`
  - `icon`
  - `attr`
  - `request_context`
- Rules:
  - use this for exclusions and compatibility vetoes
  - keep vetoes fail-open by returning `false` when uncertain
  - do not assume the plugin will try to resolve arbitrary external images

Example:

```php
add_filter(
	'hwlio_markup_is_eligible',
	static function ( bool $eligible, int $attachment_id, string $html, array $context ): bool {
		if ( false !== strpos( $html, 'data-no-hwlio' ) ) {
			return false;
		}

		return $eligible;
	},
	10,
	4
);
```

### `hwlio_picture_sources`

- Type: filter
- When it runs: after modern sources are built and before final `<picture>` markup is serialized
- Signature:

```php
apply_filters(
	'hwlio_picture_sources',
	array $payload,
	int $attachment_id,
	string $img_html,
	array $format_preference
);
```

- Return: normalized per-format array
- Payload shape per format:
  - `format`
  - `mime`
  - `sources`
  - `srcset`
- Rules:
  - preserve the existing format keys you keep
  - omit a format entirely to remove it
  - do not inject filesystem paths or unrelated metadata

Example:

```php
add_filter(
	'hwlio_picture_sources',
	static function ( array $payload, int $attachment_id, string $img_html, array $format_preference ): array {
		unset( $payload['avif'] );
		return $payload;
	},
	10,
	4
);
```

### `hwlio_loading_image_role`

- Type: filter
- When it runs: when the plugin classifies one image as `primary`, `secondary`, or `none` for loading-attribute overrides
- Signature:

```php
apply_filters(
	'hwlio_loading_image_role',
	string $role,
	array $context
);
```

- Return: `primary`, `secondary`, or `none`
- Context keys may include:
  - `attachment_id`
  - `src`
  - `attr`
  - `html`
  - `analysis`
  - `request_context`
- Rules:
  - return only `primary`, `secondary`, or `none`
  - keep this classification conservative; it affects eager/lazy and fetchpriority overrides

Example:

```php
add_filter(
	'hwlio_loading_image_role',
	static function ( string $role, array $context ): string {
		if ( ! empty( $context['attr']['class'] ) && false !== strpos( (string) $context['attr']['class'], 'hero-image' ) ) {
			return 'primary';
		}

		return $role;
	},
	10,
	2
);
```

### `hwlio_critical_image_candidates`

- Type: filter
- When it runs: after built-in critical-image candidates are assembled for the current request
- Signature:

```php
apply_filters(
	'hwlio_critical_image_candidates',
	array $payload,
	array $context
);
```

- Return payload keys:
  - `primary_attachment_id`
  - `critical_attachment_ids`
  - `critical_urls`
  - `preload_attachment_id`
- Context keys:
  - `request_context`
  - `post_id`
  - `post_type`
  - `custom_logo_attachment_id`
- Rules:
  - attachment IDs must refer to real image attachments
  - `critical_urls` must contain valid absolute URLs only
  - this filter refines candidates, not final selection certainty

Example:

```php
add_filter(
	'hwlio_critical_image_candidates',
	static function ( array $payload, array $context ): array {
		if ( 'page' === ( $context['post_type'] ?? '' ) ) {
			$payload['critical_attachment_ids'][] = 123;
		}

		return $payload;
	},
	10,
	2
);
```

### `hwlio_critical_image_selection`

- Type: filter
- When it runs: after candidate refinement, before the final request-local critical-image selection is stored
- Signature:

```php
apply_filters(
	'hwlio_critical_image_selection',
	array $payload,
	array $context
);
```

- Return payload keys:
  - `primary_attachment_id`
  - `critical_attachment_ids`
  - `critical_urls`
  - `preload_attachment_id`
- Rules:
  - return a conservative final selection
  - do not assume a selection will be reused across site switches or requests

Example:

```php
add_filter(
	'hwlio_critical_image_selection',
	static function ( array $payload, array $context ): array {
		$payload['preload_attachment_id'] = $payload['primary_attachment_id'] ?? null;
		return $payload;
	},
	10,
	2
);
```

### `hwlio_delivery_uploads_base_url`

- Type: filter
- When it runs: before a derivative URL is joined from the current uploads base URL plus one uploads-relative path
- Signature:

```php
apply_filters(
	'hwlio_delivery_uploads_base_url',
	string $base_url,
	string $relative_path,
	?int $attachment_id,
	?string $size_name,
	?string $format,
	array $context
);
```

- Return: scalar string base URL
- Context keys:
  - `relative_path`
  - `attachment_id`
  - `size_name`
  - `format`
  - `request`
  - `base_url`
- Rules:
  - keep rewrites URL-only
  - empty or invalid returns fail open to the original base URL
  - do not write metadata, files, settings, or queue state here

### `hwlio_delivery_derivative_url`

- Type: filter
- When it runs: after one derivative URL is fully resolved
- Signature:

```php
apply_filters(
	'hwlio_delivery_derivative_url',
	string $url,
	string $relative_path,
	?int $attachment_id,
	?string $size_name,
	?string $format,
	array $context
);
```

- Return: scalar string URL
- Context keys:
  - `relative_path`
  - `attachment_id`
  - `size_name`
  - `format`
  - `request`
  - `url`
- Rules:
  - use this for CDN or offload URL adaptation
  - keep output path-safe and URL-safe
  - fail open to the original URL when unsure

Example:

```php
add_filter(
	'hwlio_delivery_derivative_url',
	static function ( $url, $relative_path, $attachment_id, $size_name, $format, $context ) {
		if ( 'avif' !== $format ) {
			return $url;
		}

		return 'https://cdn.example.test/' . ltrim( $relative_path, '/' );
	},
	10,
	6
);
```

### `hwlio_cache_invalidation_requested`

- Type: action
- When it runs: after real derivative create or delete state changes
- Signature:

```php
do_action( 'hwlio_cache_invalidation_requested', int $attachment_id, array $payload );
```

- Payload keys:
  - `event`
  - `reason`
  - `attachment_id`
  - `relative_paths`
  - `formats`
  - `timestamp_gmt`
- Supported event values:
  - `derivatives_saved`
  - `derivatives_deleted`
- Rules:
  - paths are uploads-relative only
  - no full URLs or absolute filesystem paths are provided
  - listeners should treat the payload as read-only and fail open

Example:

```php
add_action(
	'hwlio_cache_invalidation_requested',
	static function ( int $attachment_id, array $payload ): void {
		if ( empty( $payload['relative_paths'] ) ) {
			return;
		}

		// Forward the safe payload to your cache or CDN integration.
	},
	10,
	2
);
```

## Data Ownership and Sidecar Naming

The plugin preserves originals. It never overwrites uploaded originals.

Plugin sidecars are deterministic sibling files created by appending `.hwlio.{format}` to the source filename. Examples:

- `photo.jpg` -> `photo.jpg.hwlio.webp`
- `photo.jpg` -> `photo.jpg.hwlio.avif`

Plugin-owned attachment meta keys:

- `_hwlio_derivatives`
- `_hwlio_status`
- `_hwlio_excluded`
- `_hwlio_lock`

Plugin-owned cache, option, credentials, report, and log storage:

- `hwlio_settings`
- `hwlio_statistics_cache`
- `hwlio_queue_control_state`
- `hwlio_pagespeed_credentials`
- `_hwlio_pagespeed_reports`
- log table `{prefix}hwlio_logs`

Related plugin-owned post meta also exists for specific features, including:

- `_hwlio_critical_image_id`
- `_hwlio_elementor_hero_background`

Storage rules:

- reading documented plugin-owned values for diagnostics or reporting is acceptable
- direct writes to plugin-owned meta/options are not the preferred integration path
- do not mutate `_hwlio_derivatives` or `_hwlio_status` to invent custom delivery or queue behavior
- do not assume lock payloads, queue state, or log-row internals are public APIs

## Supported Integration and Exclusion Patterns

Supported patterns in 13.3:

- markup-level exclusion through `hwlio_markup_is_eligible`
- delivery-level disable logic through `hwlio_delivery_is_enabled`
- derivative URL adaptation through `hwlio_delivery_uploads_base_url` and `hwlio_delivery_derivative_url`
- cache/CDN signaling through `hwlio_cache_invalidation_requested`
- critical-image and loading-role refinement through the dedicated filters above

Not supported as public integration patterns in 13.3:

- editing core plugin files to add exclusions or URL rewrites
- direct mutation of derivative manifests or status summaries for custom workflows
- guessing attachment IDs from arbitrary URLs
- relying on internal queue hooks, raw process events, or Action Scheduler job names as stable extension contracts

Acceptance-target examples:

### Exclude specific markup without editing plugin files

Use `hwlio_markup_is_eligible` to veto delivery for one known fragment or request context.

### Rewrite derivative URLs for a CDN

Use `hwlio_delivery_derivative_url` or `hwlio_delivery_uploads_base_url` to rewrite runtime URLs. Keep the rewrite URL-only and fail open when your adapter cannot determine a safe result.

### Refine loading behavior

Use `hwlio_loading_image_role` to classify a known hero image as `primary` or to demote uncertain cases to `none`.

## Rollback, Cleanup, and Fail-Open Behavior

The plugin is designed to fail open and preserve originals.

Operational guarantees:

- disabling delivery returns original markup without deleting sidecars
- the internal emergency delivery switch is authoritative
- missing derivatives fail open to original markup instead of emitting broken modern URLs
- cleanup deletes only plugin-owned sidecars and never originals
- cache invalidation signaling fires only after real derivative create/delete state changes
- offload/CDN adapters should remain URL-only or signal-only unless a later contract expands that scope
- Elementor companion stylesheet rollback removes only the plugin-owned companion artifact and leaves Elementor-owned CSS authoritative

If you are writing a CDN or offload adapter, treat [cdn-offload-adapter-contract.md](./cdn-offload-adapter-contract.md) as the specialized appendix for delivery URL rewriting and cache invalidation signaling.
