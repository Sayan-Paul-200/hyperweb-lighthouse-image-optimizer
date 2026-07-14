# CDN and Offload Adapter Contract

This document defines the product-agnostic extension points available after Subphase 11.2.

These hooks are intended for future CDN and media-offload adapters. Core plugin behavior remains independent of any specific CDN, cache layer, or offload product.

## Scope

Subphase 11.2 formalizes:

- delivery URL rewrite hooks
- derivative cache invalidation request signaling

Subphase 11.2 does not include:

- local source retrieval for offloaded media
- derivative upload or push behavior
- delayed offload timing coordination
- adapter-owned deletion propagation
- product-specific CDN or offload integrations

Those concerns remain deferred to Subphases 11.3 and 11.4.

## Delivery URL Filters

Core delivery exposes two stable filter hooks through `WordPressUploadsRuntime`.

### `hwlio_delivery_uploads_base_url`

Used to rewrite the current uploads base URL before a derivative URL is joined.

Signature:

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

Context payload:

- `relative_path`: uploads-relative derivative path
- `attachment_id`: attachment ID when known
- `size_name`: source size name when known
- `format`: `webp` or `avif` when known
- `request`: `HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest`
- `base_url`: current uploads base URL before filtering

### `hwlio_delivery_derivative_url`

Used to rewrite one final derivative URL after the base URL and relative path have been joined.

Signature:

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

Context payload:

- `relative_path`: uploads-relative derivative path
- `attachment_id`: attachment ID when known
- `size_name`: source size name when known
- `format`: `webp` or `avif` when known
- `request`: `HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest`
- `url`: current resolved derivative URL before filtering

### Filter Rules

- The first five arguments are preserved for backward compatibility.
- The final context array is the preferred adapter-facing payload for future integrations.
- Return values must remain scalar strings.
- Empty or invalid filter returns fail open to the original base URL or derivative URL.
- These hooks must stay URL-only in 11.2. They must not mutate metadata, settings, queue state, or filesystems.

Minimal example:

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

## Cache Invalidation Action

Core derivative create/delete flows emit one stable request action:

```php
do_action( 'hwlio_cache_invalidation_requested', $attachment_id, $payload );
```

This action is emitted only after real derivative state changes:

- successful derivative writes
- successful derivative deletions during cleanup or reconciliation

Payload keys:

- `event`
- `reason`
- `attachment_id`
- `relative_paths`
- `formats`
- `timestamp_gmt`

Supported event values in 11.2:

- `derivatives_saved`
- `derivatives_deleted`

Safety guarantees:

- `relative_paths` are uploads-relative only
- no absolute filesystem paths are included
- no full URLs are included
- no raw manifest data is exposed
- no purge implementation is provided by core

Minimal example:

```php
add_action(
	'hwlio_cache_invalidation_requested',
	static function ( $attachment_id, $payload ) {
		if ( empty( $payload['relative_paths'] ) ) {
			return;
		}

		// Forward this safe payload to a cache or CDN integration layer.
	},
	10,
	2
);
```

## Adapter Expectations

Adapters built on this contract should:

- treat current core payloads as read-only inputs
- fail open when they cannot safely rewrite a URL or forward invalidation
- avoid assuming local writable uploads unless the later offload contract explicitly guarantees it
- keep CDN/offload behavior separate from derivative generation correctness

Future adapter phases may add:

- local-source retrieval contracts
- derivative upload/push contracts
- delayed offload timing rules
- multisite operational hardening

Those additions should extend this contract rather than replacing it.
