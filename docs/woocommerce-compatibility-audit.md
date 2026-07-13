# WooCommerce Compatibility Audit

This document establishes the repository-owned WooCommerce compatibility baseline and is updated as later WooCommerce subphases widen support.

The current plugin runtime includes the isolated primary-image adapter from Subphase 9.2. These notes continue to document the preservation requirements and supported versus fail-open boundaries for later WooCommerce subphases.

## Current plugin touchpoints for later WooCommerce adapters

These existing seams are the intended extension points for later WooCommerce-specific behavior:

- `hwlio_critical_image_candidates`
- `hwlio_critical_image_selection`
- `hwlio_markup_is_eligible`
- `hwlio_picture_sources`
- Core runtime delivery hooks already active in the plugin:
  - `wp_get_attachment_image`
  - `wp_content_img_tag`
  - `wp_get_loading_optimization_attributes`
  - `wp_head`

WooCommerce-specific adapters should prefer these seams before adding any narrower compatibility behavior in later subphases.

## Baseline contexts

### `single_product_primary`

- **Label:** Single product primary image
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_before_single_product_summary`
  - `woocommerce_show_product_images`
  - `single-product/product-image.php`
- **Wrapper/container expectations:**
  - Gallery wrapper remains intact
  - Primary image container remains compatible with gallery JS, zoom, and lightbox behavior
  - Product-gallery wrapper data such as `data-thumb` remains available to WooCommerce scripts
- **Must preserve image classes/data:**
  - `wp-post-image`
  - `attachment-woocommerce_single`
  - `size-woocommerce_single`
  - `data-caption`
  - `data-src`
  - `data-large_image`
  - `data-large_image_width`
  - `data-large_image_height`
- **Later critical role:** `primary`
- **Fail-open required:** Yes
- **Known later risks:**
  - Losing zoom/lightbox data attributes
  - Loading/preload behavior causing the zoom source to download too early
  - Product-gallery JS expecting the original wrapper shape

### `single_product_gallery_secondary`

- **Label:** Single product secondary gallery image
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_product_thumbnails`
  - `woocommerce_single_product_image_thumbnail_html`
  - `single-product/product-thumbnails.php`
- **Wrapper/container expectations:**
  - Secondary gallery item wrapper remains intact
  - Gallery thumbnail/lightbox navigation data remains untouched
  - The plugin must not break active/selected gallery state classes
- **Must preserve image classes/data:**
  - `attachment-woocommerce_thumbnail`
  - `size-woocommerce_thumbnail`
  - `wp-post-image`
  - `data-caption`
  - `data-src`
  - `data-large_image`
  - `data-large_image_width`
  - `data-large_image_height`
- **Later critical role:** `secondary`
- **Fail-open required:** Yes
- **Known later risks:**
  - Variation/gallery scripts depending on untouched markup
  - Accidental lazy/eager changes for non-primary gallery items
  - Partial wrapping that breaks thumbnail navigation or lightbox targeting

### `cart_item_thumbnail`

- **Label:** Cart item thumbnail
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_cart_item_thumbnail`
  - `cart/cart.php`
- **Wrapper/container expectations:**
  - Cart thumbnail link and row structure remain intact
  - No new wrapper should break table layout or thumbnail links
- **Must preserve image classes/data:**
  - `attachment-woocommerce_thumbnail`
  - `size-woocommerce_thumbnail`
  - `wp-post-image`
- **Later critical role:** `none`
- **Fail-open required:** Yes
- **Known later risks:**
  - Small-table/cart markup being more brittle than product-gallery markup
  - Non-critical commerce images should not inherit primary-image loading behavior

### `checkout_review_thumbnail`

- **Label:** Checkout/review item thumbnail
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_cart_item_thumbnail`
  - `checkout/review-order.php`
- **Wrapper/container expectations:**
  - Review-order line-item structure remains intact
  - Thumbnail markup should remain valid in compact checkout layouts
- **Must preserve image classes/data:**
  - `attachment-woocommerce_thumbnail`
  - `size-woocommerce_thumbnail`
  - `wp-post-image`
- **Later critical role:** `none`
- **Fail-open required:** Yes
- **Known later risks:**
  - Checkout templates are sensitive to broken or extra wrappers
  - Checkout should not receive product-primary loading overrides

### `product_loop_thumbnail`

- **Label:** Product loop thumbnail
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_before_shop_loop_item_title`
  - `content-product.php`
- **Wrapper/container expectations:**
  - Loop product link and card structure remain intact
  - No additional wrapper assumptions should leak from single-product image handling
- **Must preserve image classes/data:**
  - `attachment-woocommerce_thumbnail`
  - `size-woocommerce_thumbnail`
  - `wp-post-image`
- **Later critical role:** `none`
- **Fail-open required:** Yes
- **Known later risks:**
  - Shared loop markup may be reused by related and upsell surfaces
  - Non-critical loop thumbnails must not inherit primary or gallery loading behavior

### `related_product_thumbnail`

- **Label:** Related product thumbnail
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_after_single_product_summary`
  - `content-product.php`
- **Wrapper/container expectations:**
  - Related-product card markup remains intact
  - Wrapper links and thumbnail layout remain untouched
- **Must preserve image classes/data:**
  - `attachment-woocommerce_thumbnail`
  - `size-woocommerce_thumbnail`
  - `wp-post-image`
- **Later critical role:** `none`
- **Fail-open required:** Yes
- **Known later risks:**
  - Related-product cards should not be promoted to critical behavior on single-product requests
  - Shared loop markup can make context guessing fragile

### `upsell_product_thumbnail`

- **Label:** Upsell product thumbnail
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_after_single_product_summary`
  - `content-product.php`
- **Wrapper/container expectations:**
  - Upsell card markup remains intact
  - No single-product primary/gallery assumptions should apply
- **Must preserve image classes/data:**
  - `attachment-woocommerce_thumbnail`
  - `size-woocommerce_thumbnail`
  - `wp-post-image`
- **Later critical role:** `none`
- **Fail-open required:** Yes
- **Known later risks:**
  - Upsell cards share loop-like markup and should stay conservative
  - Non-critical commerce images must not inherit primary-image loading treatment

### `single_product_variation_image`

- **Label:** Single product variation image
- **Attachment-backed expected:** Yes
- **Likely WooCommerce entrypoints:**
  - `woocommerce_available_variation`
  - variation JS image-swap payloads/templates
- **Wrapper/container expectations:**
  - Variation image swaps must remain compatible with WooCommerce variation scripts
  - Product-gallery state and selected image behavior must remain intact
- **Must preserve image classes/data:**
  - `attachment-woocommerce_single`
  - `size-woocommerce_single`
  - `wp-post-image`
  - `data-caption`
  - `data-src`
  - `data-large_image`
  - `data-large_image_width`
  - `data-large_image_height`
- **Later critical role:** `none`
- **Fail-open required:** Yes
- **Known later risks:**
  - Broken variation switching is a release blocker
  - Ambiguous same-request image swaps should be excluded rather than guessed

## Fixture inventory

The repository-owned baseline fixture fragments for these contexts live in:

- `tests/Fixtures/WooCommerce/single-product-primary.html`
- `tests/Fixtures/WooCommerce/single-product-gallery-secondary.html`
- `tests/Fixtures/WooCommerce/cart-item-thumbnail.html`
- `tests/Fixtures/WooCommerce/checkout-review-thumbnail.html`
- `tests/Fixtures/WooCommerce/product-loop-thumbnail.html`
- `tests/Fixtures/WooCommerce/related-product-thumbnail.html`
- `tests/Fixtures/WooCommerce/upsell-product-thumbnail.html`
- `tests/Fixtures/WooCommerce/single-product-variation-image.html`
- `tests/Fixtures/WooCommerce/baseline-manifest.php`

These fragments are intentionally focused snapshots rather than whole-page exports because the current delivery pipeline operates on image fragments and adjacent wrappers, not full-document parsing.

## Current status

- The isolated WooCommerce integration provider from Subphase 9.2 is composed in `Plugin::create()`.
- Primary product images are the only Woo fragments currently eligible for critical-image treatment.
- Subphase 9.3 widens picture-delivery eligibility only for confirmed secondary gallery images; broader commerce surfaces and variation-sensitive fragments remain fail-open.
- Live WooCommerce capture/smoke verification remains pending until a supported WordPress + WooCommerce runtime is available.
