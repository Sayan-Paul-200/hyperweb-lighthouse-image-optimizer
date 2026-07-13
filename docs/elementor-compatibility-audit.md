# Elementor Compatibility Audit

This document captures the repository-owned baseline for Subphase 10.1. It is intentionally fragment-oriented because the active delivery pipeline transforms standalone fallback `<img>` markup rather than full Elementor widget trees.

## Context: image_widget_attachment

- Label: Image widget attachment
- Widget type: Image
- Attachment-backed: yes
- 10.1 delivery intent: supported
- Preserved classes/data attributes:
  - `wp-image-*`
  - `attachment-full`
  - `size-full`
  - `elementor-animation-*`
  - `data-elementor-open-lightbox`
  - `data-elementor-lightbox-slideshow`
- Editor/preview fail-open required: yes
- Later risks:
  - lightbox slideshow coordination
  - future preload/critical-image interplay

## Context: image_box_widget_attachment

- Label: Image Box widget attachment
- Widget type: Image Box
- Attachment-backed: yes
- 10.1 delivery intent: supported
- Preserved classes/data attributes:
  - `wp-image-*`
  - `attachment-medium`
  - `size-medium`
  - `elementor-animation-*`
  - `data-elementor-open-lightbox`
- Editor/preview fail-open required: yes
- Later risks:
  - responsive breakpoint variants
  - wrapper-level hover effects outside the `<img>` node

## Context: cta_widget_attachment

- Label: CTA widget attachment
- Widget type: Call To Action
- Attachment-backed: yes
- 10.1 delivery intent: supported
- Preserved classes/data attributes:
  - `wp-image-*`
  - `attachment-large`
  - `size-large`
  - `elementor-animation-*`
  - `data-elementor-open-lightbox`
- Editor/preview fail-open required: yes
- Later risks:
  - CTA-specific hover and reveal behavior
  - widget wrappers that combine image and overlay content

## Context: image_widget_attachment_full_small_slot

- Label: Image widget full selection small slot
- Widget type: Image
- Attachment-backed: yes
- 10.1 delivery intent: supported
- 10.2 oversized advisory applicability: yes
- Reliable evidence in 10.2:
  - exact standalone `<img>` fragment
  - supported Elementor attachment-widget classification
  - selected `src` uniquely resolving to the metadata full file
  - positive intrinsic `width` and `height` attributes representing a much smaller slot
- Later risks:
  - breakpoints may change actual rendered slot width outside the committed fragment
  - advisory output must remain report-only and must not rewrite Elementor data

## Context: image_widget_attachment_full_near_full

- Label: Image widget full selection near full size
- Widget type: Image
- Attachment-backed: yes
- 10.1 delivery intent: supported
- 10.2 oversized advisory applicability: yes
- Reliable evidence in 10.2:
  - exact standalone `<img>` fragment
  - supported Elementor attachment-widget classification
  - selected `src` uniquely resolving to the metadata full file
  - positive intrinsic `width` and `height` attributes showing the slot remains near full width
- Later risks:
  - thresholds must stay conservative so moderate width differences do not produce noisy advisories

## Context: image_widget_attachment_full_uncertain

- Label: Image widget full selection uncertain slot
- Widget type: Image
- Attachment-backed: yes
- 10.1 delivery intent: supported
- 10.2 oversized advisory applicability: yes
- Reliable evidence in 10.2:
  - supported Elementor attachment-widget classification
  - selected `src` uniquely resolving to the metadata full file
- Known uncertainty boundaries:
  - missing intrinsic width/height means the plugin must not guess slot dimensions from Elementor controls, CSS, or breakpoints
  - this case should remain advisory-uncertain instead of being treated as a definite oversized finding

## Context: gallery_widget_attachment

- Label: Gallery widget attachment
- Widget type: Gallery
- Attachment-backed: yes
- 10.1 delivery intent: fail_open
- Preserved classes/data attributes:
  - `e-gallery-image`
  - `elementor-gallery-item__image`
  - `data-elementor-open-lightbox`
  - `data-elementor-lightbox-slideshow`
- Editor/preview fail-open required: yes
- Later risks:
  - gallery JS expectations
  - slideshow/lightbox synchronization
  - breakpoint-sensitive gallery layouts

## Context: carousel_widget_attachment

- Label: Carousel widget attachment
- Widget type: Carousel
- Attachment-backed: yes
- 10.1 delivery intent: fail_open
- Preserved classes/data attributes:
  - `swiper-slide-image`
  - `data-swiper-slide-index`
  - `data-elementor-open-lightbox`
- Editor/preview fail-open required: yes
- Later risks:
  - Swiper lifecycle and cloned slides
  - lazy/eager coordination across active/inactive slides
  - breakpoint-sensitive carousel behavior

## Later Adapter Touchpoints

Subphase 10.1 intentionally uses only the current eligibility seam:

- `hwlio_markup_is_eligible`

Later Elementor subphases should continue to extend the existing delivery system rather than creating a parallel rendering stack. Critical-image registration, preload behavior, and page-level reporting remain deferred to later Phase 10 work.

## Background Discovery Baseline

Subphase 10.3 established the read-only discovery boundary that Subphase 10.4 now builds on. The supported evidence boundary remains limited to structured Elementor document/settings data stored for the current document; arbitrary inline styles, theme CSS, and unrelated page CSS remain outside scope.

### supported structured control keys

- `background_background = classic`
- `background_image`
- `background_image_tablet`
- `background_image_mobile`
- `background_overlay_background = classic`
- `background_overlay_image`
- `background_overlay_image_tablet`
- `background_overlay_image_mobile`

### Structured background discovery contexts

#### Context: background_classic_desktop

- Label: Classic desktop background image
- Attachment-backed expectation: yes
- 10.3 discovery intent: supported
- Supported evidence:
  - structured element `settings`
  - `background_background = classic`
  - positive attachment ID in `background_image`
- Device mapping behavior:
  - desktop only
- Later risks:
  - CSS delivery relies on a plugin-owned companion stylesheet layered after Elementor CSS

#### Context: background_classic_responsive

- Label: Classic responsive background image
- Attachment-backed expectation: yes
- 10.3 discovery intent: supported
- Supported evidence:
  - structured element `settings`
  - explicit `background_image`, `background_image_tablet`, and `background_image_mobile`
- Device mapping behavior:
  - desktop/tablet/mobile values are recorded separately
  - responsive inheritance is not synthesized in 10.3
- Later risks:
  - responsive companion CSS must avoid forcing hidden breakpoint images into every viewport unnecessarily

#### Context: background_overlay_classic

- Label: Classic background overlay image
- Attachment-backed expectation: yes
- 10.3 discovery intent: supported
- Supported evidence:
  - structured element `settings`
  - `background_overlay_background = classic`
  - positive attachment ID in `background_overlay_image`
- Device mapping behavior:
  - explicit values only; no inheritance guessing
- Later risks:
  - overlay CSS generation must stay limited to the canonical overlay selector

#### Context: background_url_only

- Label: URL-only background value
- Attachment-backed expectation: uncertain
- 10.3 discovery intent: unsupported_value
- Known unsupported boundary:
  - classic media control value without a usable positive attachment ID
- Later risks:
  - local unregistered URLs may need a separate supported strategy later, but 10.3 must not guess

#### Context: background_custom_css_url

- Label: Custom CSS `url()` value
- Attachment-backed expectation: unknown
- 10.3 discovery intent: unsupported_css_url
- Known unsupported boundary:
  - record `url(...)` only from known Elementor-owned CSS text settings such as `custom_css`
  - do not inspect generated CSS files or unrelated page CSS
- Later risks:
  - structured custom CSS `url(...)` values remain unsupported in 10.4

#### Context: background_unsupported_modes

- Label: Unsupported background modes
- Attachment-backed expectation: no
- 10.3 discovery intent: unsupported_mode
- Known unsupported boundary:
  - gradient, video, slideshow, and other non-classic modes are recorded as unsupported
- Later risks:
  - later phases may broaden support, but 10.3 must not reinterpret unsupported modes as safe attachment backgrounds

#### Context: background_invalid_document

- Label: Invalid structured document data
- Attachment-backed expectation: unknown
- 10.3 discovery intent: invalid
- Known unsupported boundary:
  - malformed or undecodable `_elementor_data` must produce a stable invalid-document result
- Later risks:
  - no recovery or rewrite behavior belongs in 10.3

## Background Delivery Strategy

Subphase 10.4 adds a plugin-owned companion stylesheet strategy for supported structured Elementor backgrounds.

- Companion CSS is generated only from 10.3-supported structured classic background and classic background-overlay mappings.
- Elementor's own generated CSS remains the fallback source of truth and is never rewritten in place.
- The runtime supports only the current singular Elementor document on eligible frontend requests.
- Editor mode, preview mode, global kit/theme-builder/template cases, unsupported CSS URLs, and unsupported background modes remain fail-open exclusions.
- Canonical selectors are limited to:
  - `.elementor-element.elementor-element-{element_id}`
  - `.elementor-element.elementor-element-{element_id} > .elementor-background-overlay`
- Responsive companion CSS is emitted only when a reliable Elementor breakpoint map is available; otherwise responsive background delivery is skipped rather than guessed.
- Regeneration and rollback affect only the plugin-owned companion artifact under uploads.

## Critical Background Preload Strategy

Subphase 10.5 builds on the 10.4 companion-stylesheet strategy without rewriting Elementor's own generated CSS or overloading the attachment-image critical/preload registry.

- Critical background preload is explicit and opt-in through the new `critical_background_preload_enabled` setting.
- One per-post/page stored hero-background target is selected from currently discoverable supported Elementor background sources and is stored as normalized `{ element_id, setting_group }`.
- The shared background delivery-plan builder now acts as the source of truth for both companion CSS generation and background preload generation, so both paths reuse the same validated structured targets, derivative URLs, and breakpoint media queries.
- The runtime provider hooks only `wp_head`, excludes admin/feed/ajax/rest/editor/preview, and supports only the current singular Elementor document.
- Preload remains conservative:
  - desktop-only selected target: one modern preload tag
  - responsive selected target with explicit device mappings: mutually exclusive `media`-scoped preload tags only
  - no global desktop preload when explicit tablet/mobile variants exist
- Only the highest-preference ready modern derivative is preloaded for each explicit device variant. Original fallback URLs and lower-preference modern formats are not preloaded.
- Attachment-image preload and Elementor background preload now share one request-local dedupe seam keyed by final emitted-link identity.

### Explicit hero-background selection boundary

- Post/page editor controls are limited to `post` and `page`.
- The selector lists only 10.3-discovered supported structured classic `background` and `background_overlay` targets for the current document.
- Invalid, empty, stale, or no-longer-discoverable selections are deleted instead of being preserved optimistically.
- Selection remains separate from `CriticalImageRegistry`, `preload_attachment_id`, and attachment critical-image post meta.

### Still deferred after 10.5

- automatic hero-background inference
- theme-builder, global-kit, popup, and non-singular Elementor document support
- editor/preview preload behavior
- unsupported CSS `url(...)`, unsupported background modes, and broader CSS scanning
- REST/admin diagnostics surfacing beyond the minimal selector control
