<?php
/**
 * Elementor baseline fixture manifest.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

return array(
	array(
		'context'                       => 'image_widget_attachment',
		'label'                         => 'Image widget attachment',
		'widget_type'                   => 'Image',
		'fixture_file'                  => 'image-widget-attachment.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'supported',
		'must_preserve_classes'         => array( 'wp-image-321', 'attachment-full', 'size-full', 'elementor-animation-grow' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox', 'data-elementor-lightbox-slideshow' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Attachment-backed frontend image widget baseline.',
			'10.1 should allow picture delivery while preserving fallback image attributes verbatim.',
		),
	),
	array(
		'context'                       => 'image_box_widget_attachment',
		'label'                         => 'Image Box widget attachment',
		'widget_type'                   => 'Image Box',
		'fixture_file'                  => 'image-box-widget-attachment.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'supported',
		'must_preserve_classes'         => array( 'wp-image-322', 'attachment-medium', 'size-medium', 'elementor-animation-grow' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Attachment-backed frontend image-box baseline.',
			'Wrapper-level hover treatment remains outside 10.1 scope.',
		),
	),
	array(
		'context'                       => 'cta_widget_attachment',
		'label'                         => 'CTA widget attachment',
		'widget_type'                   => 'CTA',
		'fixture_file'                  => 'cta-widget-attachment.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'supported',
		'must_preserve_classes'         => array( 'wp-image-323', 'attachment-large', 'size-large', 'elementor-animation-shrink' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'CTA attachment-backed fallback image baseline.',
			'Overlay/wrapper behavior remains untouched in 10.1.',
		),
	),
	array(
		'context'                       => 'image_widget_attachment_full_small_slot',
		'label'                         => 'Image widget full selection small slot',
		'widget_type'                   => 'Image',
		'fixture_file'                  => 'image-widget-full-small-slot.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'supported',
		'must_preserve_classes'         => array( 'wp-image-321', 'attachment-full', 'size-full', 'elementor-animation-grow' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox', 'data-elementor-lightbox-slideshow' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Diagnostic baseline for a supported widget selecting full while rendering much smaller.',
			'10.2 should report this as advisory oversized full-image selection when slot width evidence is reliable.',
		),
	),
	array(
		'context'                       => 'image_widget_attachment_full_near_full',
		'label'                         => 'Image widget full selection near full size',
		'widget_type'                   => 'Image',
		'fixture_file'                  => 'image-widget-full-near-full.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'supported',
		'must_preserve_classes'         => array( 'wp-image-321', 'attachment-full', 'size-full', 'elementor-animation-grow' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox', 'data-elementor-lightbox-slideshow' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Diagnostic baseline for a supported widget selecting full while rendering close to full width.',
			'10.2 should not report an oversized advisory finding at the 1.5x threshold.',
		),
	),
	array(
		'context'                       => 'image_widget_attachment_full_uncertain',
		'label'                         => 'Image widget full selection uncertain slot',
		'widget_type'                   => 'Image',
		'fixture_file'                  => 'image-widget-full-uncertain.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'supported',
		'must_preserve_classes'         => array( 'wp-image-321', 'attachment-full', 'size-full', 'elementor-animation-grow' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox', 'data-elementor-lightbox-slideshow' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Diagnostic baseline for a supported widget selecting full without reliable slot dimensions.',
			'10.2 should report uncertainty rather than guessing rendered slot size.',
		),
	),
	array(
		'context'                       => 'gallery_widget_attachment',
		'label'                         => 'Gallery widget attachment',
		'widget_type'                   => 'Gallery',
		'fixture_file'                  => 'gallery-widget-attachment.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'fail_open',
		'must_preserve_classes'         => array( 'wp-image-324', 'e-gallery-image', 'elementor-gallery-item__image' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox', 'data-elementor-lightbox-slideshow' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Gallery/lightbox context is explicitly excluded in 10.1.',
			'Later phases must verify gallery JS compatibility first.',
		),
	),
	array(
		'context'                       => 'carousel_widget_attachment',
		'label'                         => 'Carousel widget attachment',
		'widget_type'                   => 'Carousel',
		'fixture_file'                  => 'carousel-widget-attachment.html',
		'attachment_expected'           => true,
		'delivery_intent'               => 'fail_open',
		'must_preserve_classes'         => array( 'wp-image-325', 'swiper-slide-image' ),
		'must_preserve_data_attributes' => array( 'data-elementor-open-lightbox', 'data-swiper-slide-index' ),
		'editor_preview_fail_open'      => true,
		'notes'                         => array(
			'Carousel/swiper context is explicitly excluded in 10.1.',
			'Later phases must validate slide lifecycle and duplication behavior.',
		),
	),
);
