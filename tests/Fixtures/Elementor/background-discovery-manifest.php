<?php
/**
 * Elementor background-discovery fixture manifest.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

return array(
	array(
		'context'                    => 'background_classic_desktop',
		'label'                      => 'Classic desktop background image',
		'fixture_file'               => 'background-classic-desktop.php',
		'document_shape'             => 'structured_elements',
		'discovery_intent'           => 'supported',
		'desktop_tablet_mobile_case' => false,
		'notes'                      => array(
			'Baseline section background image with an attachment ID on the desktop control only.',
		),
	),
	array(
		'context'                    => 'background_classic_responsive',
		'label'                      => 'Classic responsive background image',
		'fixture_file'               => 'background-classic-responsive.php',
		'document_shape'             => 'structured_elements',
		'discovery_intent'           => 'supported',
		'desktop_tablet_mobile_case' => true,
		'notes'                      => array(
			'Explicit desktop, tablet, and mobile attachment-backed background values.',
		),
	),
	array(
		'context'                    => 'background_overlay_classic',
		'label'                      => 'Classic background overlay image',
		'fixture_file'               => 'background-overlay-classic.php',
		'document_shape'             => 'structured_elements',
		'discovery_intent'           => 'supported',
		'desktop_tablet_mobile_case' => false,
		'notes'                      => array(
			'Attachment-backed overlay image control using the supported background_overlay keys.',
		),
	),
	array(
		'context'                    => 'background_url_only',
		'label'                      => 'URL-only background value',
		'fixture_file'               => 'background-url-only.php',
		'document_shape'             => 'structured_elements',
		'discovery_intent'           => 'unsupported_value',
		'desktop_tablet_mobile_case' => false,
		'notes'                      => array(
			'Structured background image value without a usable attachment ID.',
		),
	),
	array(
		'context'                    => 'background_custom_css_url',
		'label'                      => 'Custom CSS url() value',
		'fixture_file'               => 'background-custom-css-url.php',
		'document_shape'             => 'structured_elements',
		'discovery_intent'           => 'unsupported_css_url',
		'desktop_tablet_mobile_case' => false,
		'notes'                      => array(
			'Known Elementor-owned custom CSS field containing a background url() token.',
		),
	),
	array(
		'context'                    => 'background_unsupported_modes',
		'label'                      => 'Unsupported background modes',
		'fixture_file'               => 'background-unsupported-modes.php',
		'document_shape'             => 'structured_elements',
		'discovery_intent'           => 'unsupported_mode',
		'desktop_tablet_mobile_case' => false,
		'notes'                      => array(
			'Video/slideshow/gradient-style background modes remain outside 10.3 supported discovery.',
		),
	),
	array(
		'context'                    => 'background_invalid_document',
		'label'                      => 'Invalid structured document data',
		'fixture_file'               => 'background-invalid-document.php',
		'document_shape'             => 'invalid_document',
		'discovery_intent'           => 'invalid',
		'desktop_tablet_mobile_case' => false,
		'notes'                      => array(
			'Malformed document-data fixture for invalid-document handling.',
		),
	),
);
