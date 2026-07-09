<?php
/**
 * Environment inspector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Builds canonical environment capability reports.
 */
final class EnvironmentInspector implements FormatSupportProviderInterface {

	/**
	 * Supported output format MIME types.
	 *
	 * @var array<string,string>
	 */
	private const FORMAT_MIME_TYPES = array(
		FormatSupportResult::FORMAT_WEBP => 'image/webp',
		FormatSupportResult::FORMAT_AVIF => 'image/avif',
	);

	/**
	 * Environment probe.
	 *
	 * @var EnvironmentProbeInterface
	 */
	private $probe;

	/**
	 * Minimum PHP version.
	 *
	 * @var string
	 */
	private $minimum_php;

	/**
	 * Minimum WordPress version.
	 *
	 * @var string
	 */
	private $minimum_wordpress;

	/**
	 * Create a WordPress-backed inspector.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		$minimum_php = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_PHP' )
			: '7.4';

		$minimum_wp = defined( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_WP' )
			? (string) constant( 'HYPERWEB_LIGHTHOUSE_IMAGE_OPTIMIZER_MINIMUM_WP' )
			: '6.5';

		return new self( new WordPressEnvironmentProbe(), $minimum_php, $minimum_wp );
	}

	/**
	 * Create the inspector.
	 *
	 * @param EnvironmentProbeInterface $probe Environment probe.
	 * @param string                    $minimum_php Minimum PHP version.
	 * @param string                    $minimum_wordpress Minimum WordPress version.
	 */
	public function __construct( EnvironmentProbeInterface $probe, string $minimum_php, string $minimum_wordpress ) {
		$this->probe             = $probe;
		$this->minimum_php       = $minimum_php;
		$this->minimum_wordpress = $minimum_wordpress;
	}

	/**
	 * Build an aggregate environment report.
	 *
	 * @return EnvironmentReport
	 */
	public function inspect(): EnvironmentReport {
		$php_version       = $this->probe->php_version();
		$wordpress_version = $this->probe->wordpress_version();
		$image_editors     = $this->image_editor_availability();
		$formats           = array();

		foreach ( array_keys( self::FORMAT_MIME_TYPES ) as $format ) {
			$formats[ $format ] = $this->support_for( $format );
		}

		return new EnvironmentReport(
			$php_version,
			$this->minimum_php,
			Requirements::supports_php( $php_version, $this->minimum_php ),
			$wordpress_version,
			$this->minimum_wordpress,
			Requirements::supports_wordpress( $wordpress_version, $this->minimum_wordpress ),
			$image_editors,
			$formats,
			$this->uploads_status(),
			RuntimeConstraints::from_raw( $this->probe->memory_limit(), $this->probe->max_execution_time() ),
			ActionSchedulerStatus::from_state(
				$this->probe->action_scheduler_loaded(),
				$this->probe->action_scheduler_initialized()
			)
		);
	}

	/**
	 * Get support details for a format.
	 *
	 * @param string $format Target format.
	 * @return FormatSupportResult
	 */
	public function support_for( string $format ): FormatSupportResult {
		$format = strtolower( trim( $format ) );

		if ( ! isset( self::FORMAT_MIME_TYPES[ $format ] ) ) {
			return FormatSupportResult::unknown( $format, null, null, null, 'unknown_format' );
		}

		$mime_type          = self::FORMAT_MIME_TYPES[ $format ];
		$mime_recognized    = $this->probe->mime_type_recognized( $mime_type );
		$encoding_supported = $this->probe->image_editor_supports_mime( $mime_type );

		if ( null === $mime_recognized || null === $encoding_supported ) {
			return FormatSupportResult::unknown(
				$format,
				$mime_type,
				$mime_recognized,
				$encoding_supported,
				'support_check_unavailable'
			);
		}

		if ( ! $mime_recognized ) {
			return FormatSupportResult::unsupported(
				$format,
				$mime_type,
				false,
				$encoding_supported,
				'mime_not_recognized'
			);
		}

		if ( ! $encoding_supported && ! in_array( true, $this->image_editor_availability(), true ) ) {
			return FormatSupportResult::misconfigured(
				$format,
				$mime_type,
				true,
				false,
				'no_image_editor_available'
			);
		}

		if ( ! $encoding_supported ) {
			return FormatSupportResult::unsupported(
				$format,
				$mime_type,
				true,
				false,
				'encoding_not_supported'
			);
		}

		return FormatSupportResult::supported( $format, $mime_type );
	}

	/**
	 * Get image editor candidate availability.
	 *
	 * @return array<string,bool>
	 */
	private function image_editor_availability(): array {
		$availability = array();

		foreach ( $this->probe->image_editor_candidates() as $candidate ) {
			$availability[ $candidate ] = $this->probe->class_available( $candidate );
		}

		return $availability;
	}

	/**
	 * Get uploads status.
	 *
	 * @return UploadsStatus
	 */
	private function uploads_status(): UploadsStatus {
		$uploads  = $this->probe->uploads();
		$writable = null;

		if ( is_array( $uploads ) && isset( $uploads['basedir'] ) && is_string( $uploads['basedir'] ) ) {
			$writable = $this->probe->is_writable( $uploads['basedir'] );
		}

		return UploadsStatus::from_uploads( $uploads, $writable );
	}
}
