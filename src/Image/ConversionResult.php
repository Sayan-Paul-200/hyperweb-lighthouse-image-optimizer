<?php
/**
 * Conversion result model.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents one per-source, per-target conversion outcome.
 */
final class ConversionResult {

	public const STATUS_SUCCESS = 'success';
	public const STATUS_SKIPPED = 'skipped';
	public const STATUS_FAILED  = 'failed';

	/**
	 * Source image.
	 *
	 * @var SourceImage
	 */
	private $source;

	/**
	 * Destination path.
	 *
	 * @var DestinationPath|null
	 */
	private $destination;

	/**
	 * Output metadata.
	 *
	 * @var ConversionOutput|null
	 */
	private $output;

	/**
	 * Savings.
	 *
	 * @var ConversionSavings
	 */
	private $savings;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Target format.
	 *
	 * @var string
	 */
	private $target_format;

	/**
	 * Target MIME type.
	 *
	 * @var string|null
	 */
	private $target_mime;

	/**
	 * Sanitized details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create result.
	 *
	 * @param SourceImage           $source Source image.
	 * @param string                $status Status.
	 * @param string                $code Code.
	 * @param string                $message Message.
	 * @param string                $target_format Target format.
	 * @param string|null           $target_mime Target MIME type.
	 * @param DestinationPath|null  $destination Destination path.
	 * @param ConversionOutput|null $output Output metadata.
	 * @param ConversionSavings     $savings Savings.
	 * @param array<mixed>          $details Details.
	 */
	private function __construct(
		SourceImage $source,
		string $status,
		string $code,
		string $message,
		string $target_format,
		?string $target_mime,
		?DestinationPath $destination,
		?ConversionOutput $output,
		ConversionSavings $savings,
		array $details = array()
	) {
		$sanitizer = new ConversionResultSanitizer();
		$status    = self::normalize_status( $status );

		$this->source        = $source;
		$this->status        = $status;
		$this->code          = ConversionResultCode::normalize_for_status( $status, $code );
		$this->message       = '' === trim( $message ) ? 'Conversion result.' : $sanitizer->sanitize_message( $message );
		$this->target_format = $this->normalize_target_format( $target_format );
		$this->target_mime   = $this->normalize_nullable_string( $target_mime );
		$this->destination   = $destination;
		$this->output        = $output;
		$this->savings       = $savings;
		$this->details       = $sanitizer->sanitize_details( $details );
	}

	/**
	 * Build successful conversion result.
	 *
	 * @param SourceImage       $source Source image.
	 * @param DestinationPath   $destination Destination path.
	 * @param ConversionOutput  $output Output metadata.
	 * @param ConversionSavings $savings Savings.
	 * @param array<mixed>      $details Details.
	 * @return self
	 */
	public static function success(
		SourceImage $source,
		DestinationPath $destination,
		ConversionOutput $output,
		ConversionSavings $savings,
		array $details = array()
	): self {
		return new self(
			$source,
			self::STATUS_SUCCESS,
			ConversionResultCode::OPTIMIZED,
			'The derivative was generated successfully.',
			$destination->target_format(),
			$destination->target_mime(),
			$destination,
			$output,
			$savings,
			$details
		);
	}

	/**
	 * Build already-current success result.
	 *
	 * @param SourceImage       $source Source image.
	 * @param DestinationPath   $destination Destination path.
	 * @param ConversionOutput  $output Output metadata.
	 * @param ConversionSavings $savings Savings.
	 * @param array<mixed>      $details Details.
	 * @return self
	 */
	public static function already_current(
		SourceImage $source,
		DestinationPath $destination,
		ConversionOutput $output,
		ConversionSavings $savings,
		array $details = array()
	): self {
		return new self(
			$source,
			self::STATUS_SUCCESS,
			ConversionResultCode::ALREADY_CURRENT,
			'The existing derivative is already current.',
			$destination->target_format(),
			$destination->target_mime(),
			$destination,
			$output,
			$savings,
			$details
		);
	}

	/**
	 * Build skipped result.
	 *
	 * @param SourceImage          $source Source image.
	 * @param string               $target_format Target format.
	 * @param string|null          $target_mime Target MIME type.
	 * @param string               $code Code.
	 * @param string               $message Message.
	 * @param ConversionSavings    $savings Savings.
	 * @param DestinationPath|null $destination Destination path.
	 * @param array<mixed>         $details Details.
	 * @return self
	 */
	public static function skipped(
		SourceImage $source,
		string $target_format,
		?string $target_mime,
		string $code,
		string $message,
		ConversionSavings $savings,
		?DestinationPath $destination = null,
		array $details = array()
	): self {
		return new self(
			$source,
			self::STATUS_SKIPPED,
			$code,
			$message,
			$target_format,
			$target_mime,
			$destination,
			null,
			$savings,
			$details
		);
	}

	/**
	 * Build failed result.
	 *
	 * @param SourceImage            $source Source image.
	 * @param string                 $target_format Target format.
	 * @param string|null            $target_mime Target MIME type.
	 * @param string                 $code Code.
	 * @param string                 $message Message.
	 * @param DestinationPath|null   $destination Destination path.
	 * @param ConversionSavings|null $savings Savings.
	 * @param ConversionOutput|null  $output Output metadata.
	 * @param array<mixed>           $details Details.
	 * @return self
	 */
	public static function failed(
		SourceImage $source,
		string $target_format,
		?string $target_mime,
		string $code,
		string $message,
		?DestinationPath $destination = null,
		?ConversionSavings $savings = null,
		?ConversionOutput $output = null,
		array $details = array()
	): self {
		return new self(
			$source,
			self::STATUS_FAILED,
			$code,
			$message,
			$target_format,
			$target_mime,
			$destination,
			$output,
			null === $savings ? new ConversionSavings( $source->bytes(), null ) : $savings,
			$details
		);
	}

	/**
	 * Normalize a result status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function normalize_status( string $status ): string {
		$status = strtolower( trim( $status ) );

		return in_array( $status, self::statuses(), true ) ? $status : self::STATUS_FAILED;
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_SUCCESS,
			self::STATUS_SKIPPED,
			self::STATUS_FAILED,
		);
	}

	/**
	 * Get source.
	 *
	 * @return SourceImage
	 */
	public function source(): SourceImage {
		return $this->source;
	}

	/**
	 * Get destination.
	 *
	 * @return DestinationPath|null
	 */
	public function destination(): ?DestinationPath {
		return $this->destination;
	}

	/**
	 * Get output metadata.
	 *
	 * @return ConversionOutput|null
	 */
	public function output(): ?ConversionOutput {
		return $this->output;
	}

	/**
	 * Get savings.
	 *
	 * @return ConversionSavings
	 */
	public function savings(): ConversionSavings {
		return $this->savings;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get target format.
	 *
	 * @return string
	 */
	public function target_format(): string {
		return $this->target_format;
	}

	/**
	 * Get target MIME type.
	 *
	 * @return string|null
	 */
	public function target_mime(): ?string {
		return $this->target_mime;
	}

	/**
	 * Get details.
	 *
	 * @return array<mixed>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Whether the result is successful.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return self::STATUS_SUCCESS === $this->status;
	}

	/**
	 * Whether the result is skipped.
	 *
	 * @return bool
	 */
	public function is_skipped(): bool {
		return self::STATUS_SKIPPED === $this->status;
	}

	/**
	 * Whether the result failed.
	 *
	 * @return bool
	 */
	public function is_failed(): bool {
		return self::STATUS_FAILED === $this->status;
	}

	/**
	 * Serialize result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'source'        => $this->source->to_array(),
			'destination'   => $this->destination instanceof DestinationPath ? $this->destination->to_array() : null,
			'output'        => $this->output instanceof ConversionOutput ? $this->output->to_array() : null,
			'savings'       => $this->savings->to_array(),
			'status'        => $this->status,
			'code'          => $this->code,
			'message'       => $this->message,
			'target_format' => $this->target_format,
			'target_mime'   => $this->target_mime,
			'details'       => $this->details,
		);
	}

	/**
	 * Normalize target format.
	 *
	 * @param string $target_format Target format.
	 * @return string
	 */
	private function normalize_target_format( string $target_format ): string {
		$target_format = strtolower( trim( $target_format ) );
		$target_format = (string) preg_replace( '/[^a-z0-9_]/', '_', $target_format );
		$target_format = trim( $target_format, '_' );

		return '' === $target_format ? 'unknown' : substr( $target_format, 0, 32 );
	}

	/**
	 * Normalize nullable string.
	 *
	 * @param string|null $value Value.
	 * @return string|null
	 */
	private function normalize_nullable_string( ?string $value ): ?string {
		return null === $value || '' === trim( $value ) ? null : strtolower( trim( $value ) );
	}
}
