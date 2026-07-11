<?php
/**
 * Image converter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;

/**
 * Converts a validated source image into one deterministic sidecar derivative.
 */
final class ImageConverter {

	/**
	 * Editor adapter.
	 *
	 * @var ConversionEditorInterface
	 */
	private $editor;

	/**
	 * Filesystem adapter.
	 *
	 * @var ConversionFilesystemInterface
	 */
	private $filesystem;

	/**
	 * Clock.
	 *
	 * @var ConversionClockInterface
	 */
	private $clock;

	/**
	 * Resource guard.
	 *
	 * @var ResourceGuard|null
	 */
	private $resource_guard;

	/**
	 * Create converter.
	 *
	 * @param ConversionEditorInterface     $editor Editor adapter.
	 * @param ConversionFilesystemInterface $filesystem Filesystem adapter.
	 * @param ConversionClockInterface|null $clock Clock.
	 * @param ResourceGuard|null            $resource_guard Resource guard.
	 */
	public function __construct(
		ConversionEditorInterface $editor,
		ConversionFilesystemInterface $filesystem,
		?ConversionClockInterface $clock = null,
		?ResourceGuard $resource_guard = null
	) {
		$this->editor         = $editor;
		$this->filesystem     = $filesystem;
		$this->clock          = $clock instanceof ConversionClockInterface ? $clock : new SystemConversionClock();
		$this->resource_guard = $resource_guard;
	}

	/**
	 * Build a WordPress-backed converter.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressConversionEditor(),
			new WordPressConversionFilesystem(),
			new SystemConversionClock(),
			ResourceGuard::for_wordpress( MemoryLimit::from_raw( (string) ini_get( 'memory_limit' ) ) )
		);
	}

	/**
	 * Convert a source image to the requested destination.
	 *
	 * @param ConversionRequest $request Conversion request.
	 * @return ConversionResult
	 */
	public function convert( ConversionRequest $request ): ConversionResult {
		$source      = $request->source();
		$destination = $request->destination();
		$target_mime = $this->target_mime_for_format( $destination->target_format() );

		if ( null === $target_mime || $target_mime !== $destination->target_mime() ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::INVALID_TARGET_FORMAT,
				'The requested target format is not supported by the converter.'
			);
		}

		$paths = $this->validate_paths( $source, $destination );
		if ( ! $paths['valid'] ) {
			return $this->failed(
				$source,
				$destination,
				(string) $paths['code'],
				(string) $paths['message'],
				null,
				array(
					'reason' => $paths['reason'],
				)
			);
		}

		$source_path      = (string) $paths['source_path'];
		$destination_path = (string) $paths['destination_path'];
		$temporary_path   = (string) $paths['temporary_path'];
		$backup_path      = (string) $paths['backup_path'];
		$uploads_base     = (string) $paths['uploads_base'];

		if ( ! $this->filesystem->exists( $source_path ) ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::SOURCE_MISSING,
				'The source image file is missing.'
			);
		}

		if ( ! $this->filesystem->is_file( $source_path ) || ! $this->filesystem->is_readable( $source_path ) ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::SOURCE_UNREADABLE,
				'The source image file is not readable.'
			);
		}

		$source_bytes = $this->filesystem->file_size( $source_path );
		if ( null === $source_bytes || 0 >= $source_bytes ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::SOURCE_UNREADABLE,
				'The source image file does not have a readable byte size.'
			);
		}

		if ( $request->replace_existing() ) {
			$replaceable_destination = $this->check_replaceable_destination( $destination, $uploads_base );
			if ( null !== $replaceable_destination ) {
				return $this->failed(
					$source,
					$destination,
					$replaceable_destination,
					'The existing destination derivative is not replaceable.',
					null,
					array(
						'replace_existing' => true,
					)
				);
			}
		} else {
			$destination_collision = $this->check_existing_destination( $destination_path, $uploads_base );
			if ( null !== $destination_collision ) {
				$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

				return $this->failed(
					$source,
					$destination,
					$destination_collision,
					ConversionResultCode::DESTINATION_COLLISION === $destination_collision
						? 'The destination derivative already exists and will not be overwritten.'
						: 'The destination derivative resolves outside uploads.',
					null,
					array(
						'cleanup_failed' => $cleanup_failed,
					)
				);
			}
		}

		$temporary_collision = $this->check_existing_temporary( $temporary_path, $uploads_base );
		if ( null !== $temporary_collision ) {
			return $this->failed(
				$source,
				$destination,
				$temporary_collision,
				ConversionResultCode::TEMPORARY_REALPATH_OUTSIDE_UPLOADS === $temporary_collision
					? 'The temporary derivative resolves outside uploads.'
					: 'The temporary derivative path is unsafe.'
			);
		}

		if ( ! $this->cleanup_temporary( $temporary_path, $uploads_base ) ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::TEMPORARY_WRITE_FAILED,
				'The stale temporary derivative could not be removed.',
				null,
				array(
					'cleanup_failed' => true,
				)
			);
		}

		if ( $request->replace_existing() && ! $this->cleanup_backup( $backup_path, $uploads_base ) ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::ATOMIC_MOVE_FAILED,
				'The destination backup derivative could not be prepared safely.',
				null,
				array(
					'cleanup_failed'   => true,
					'replace_existing' => true,
				)
			);
		}

		$resource_result = $this->check_resources( $source );
		if ( null !== $resource_result ) {
			return ConversionResult::skipped(
				$source,
				$destination->target_format(),
				$destination->target_mime(),
				ConversionResultCode::SKIPPED_RESOURCE_LIMIT,
				$resource_result->message(),
				new ConversionSavings( $source_bytes, null, $request->minimum_savings_percent() ),
				$destination,
				$resource_result->to_array()
			);
		}

		$editor_result = $this->editor->save( $source, $destination, $request->quality() );
		if ( ! $editor_result->is_success() ) {
			$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

			return $this->failed(
				$source,
				$destination,
				$editor_result->code(),
				$editor_result->message(),
				null,
				$this->with_cleanup_detail( $editor_result->details(), $cleanup_failed )
			);
		}

		$temporary_output = $this->validate_output_file( $temporary_path, $destination->target_mime(), $source );
		if ( ! $temporary_output['valid'] ) {
			$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::OUTPUT_VALIDATION_FAILED,
				(string) $temporary_output['message'],
				null,
				array(
					'reason'         => $temporary_output['reason'],
					'cleanup_failed' => $cleanup_failed,
				)
			);
		}

		$savings = new ConversionSavings(
			$source_bytes,
			(int) $temporary_output['bytes'],
			$request->minimum_savings_percent()
		);

		if ( true !== $savings->meets_minimum() ) {
			$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

			return ConversionResult::skipped(
				$source,
				$destination->target_format(),
				$destination->target_mime(),
				ConversionResultCode::SKIPPED_NOT_SMALLER,
				'The generated derivative did not meet the configured byte-savings threshold.',
				$savings,
				$destination,
				array(
					'cleanup_failed' => $cleanup_failed,
				)
			);
		}

		$backup_in_use = false;

		if ( $request->replace_existing() ) {
			$replaceable_destination = $this->check_replaceable_destination( $destination, $uploads_base );
			if ( null !== $replaceable_destination ) {
				$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

				return $this->failed(
					$source,
					$destination,
					$replaceable_destination,
					'The existing destination derivative is not replaceable.',
					$savings,
					array(
						'cleanup_failed'   => $cleanup_failed,
						'replace_existing' => true,
					)
				);
			}

			if ( $this->filesystem->exists( $destination_path ) ) {
				if ( ! $this->filesystem->move( $destination_path, $backup_path ) ) {
					$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

					return $this->failed(
						$source,
						$destination,
						ConversionResultCode::ATOMIC_MOVE_FAILED,
						'The existing destination derivative could not be moved aside safely.',
						$savings,
						array(
							'cleanup_failed'   => $cleanup_failed,
							'replace_existing' => true,
						)
					);
				}

				$backup_in_use = true;
			}
		} else {
			$destination_collision = $this->check_existing_destination( $destination_path, $uploads_base );
			if ( null !== $destination_collision ) {
				$cleanup_failed = ! $this->cleanup_temporary( $temporary_path, $uploads_base );

				return $this->failed(
					$source,
					$destination,
					$destination_collision,
					'The destination derivative appeared before the final move.',
					$savings,
					array(
						'cleanup_failed' => $cleanup_failed,
					)
				);
			}
		}

		if ( ! $this->filesystem->move( $temporary_path, $destination_path ) ) {
			$cleanup_failed  = ! $this->cleanup_temporary( $temporary_path, $uploads_base );
			$rollback_failed = $backup_in_use && ! $this->rollback_backup( $destination_path, $backup_path, $uploads_base );

			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::ATOMIC_MOVE_FAILED,
				'The temporary derivative could not be moved into place.',
				$savings,
				array(
					'cleanup_failed'   => $cleanup_failed,
					'rollback_failed'  => $rollback_failed,
					'replace_existing' => $request->replace_existing(),
				)
			);
		}

		$final_output = $this->validate_output_file( $destination_path, $destination->target_mime(), $source );
		if ( ! $final_output['valid'] ) {
			$cleanup_failed  = ! $this->cleanup_temporary( $temporary_path, $uploads_base );
			$cleanup_failed  = ! $this->cleanup_final( $destination_path, $uploads_base ) || $cleanup_failed;
			$rollback_failed = $backup_in_use && ! $this->rollback_backup( $destination_path, $backup_path, $uploads_base );

			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::OUTPUT_VALIDATION_FAILED,
				(string) $final_output['message'],
				$savings,
				array(
					'reason'           => $final_output['reason'],
					'cleanup_failed'   => $cleanup_failed,
					'rollback_failed'  => $rollback_failed,
					'replace_existing' => $request->replace_existing(),
				)
			);
		}

		if ( $backup_in_use && ! $this->cleanup_backup( $backup_path, $uploads_base ) ) {
			return $this->failed(
				$source,
				$destination,
				ConversionResultCode::ATOMIC_MOVE_FAILED,
				'The replaced destination backup could not be cleaned up after a successful conversion.',
				$savings,
				array(
					'cleanup_failed'   => true,
					'replace_existing' => true,
				)
			);
		}

		$output = new ConversionOutput(
			$destination->relative_path(),
			$destination->target_mime(),
			(int) $final_output['width'],
			(int) $final_output['height'],
			(int) $final_output['bytes'],
			$request->quality(),
			$this->clock->now()
		);

		return ConversionResult::success(
			$source,
			$destination,
			$output,
			new ConversionSavings( $source_bytes, $output->bytes(), $request->minimum_savings_percent() )
		);
	}

	/**
	 * Check source resources before editor allocation.
	 *
	 * @param SourceImage $source Source image.
	 * @return ResourceGuardResult|null
	 */
	private function check_resources( SourceImage $source ): ?ResourceGuardResult {
		if ( ! $this->resource_guard instanceof ResourceGuard ) {
			return null;
		}

		$result = $this->resource_guard->check( $source );

		return $result->is_denied() ? $result : null;
	}

	/**
	 * Validate source, final, and temporary path relationships.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @return array<string,mixed>
	 */
	private function validate_paths( SourceImage $source, DestinationPath $destination ): array {
		if ( ! $this->is_safe_relative_path( $source->relative_path() ) ) {
			return $this->invalid_paths( ConversionResultCode::UNSAFE_SOURCE_PATH, 'The source path is unsafe.', 'source_relative_path' );
		}

		if ( ! $this->is_safe_relative_path( $destination->relative_path() ) ) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_OUTSIDE_UPLOADS, 'The destination path is unsafe.', 'destination_relative_path' );
		}

		if ( ! $this->is_safe_relative_path( $destination->temporary_relative_path() ) ) {
			return $this->invalid_paths( ConversionResultCode::TEMPORARY_OUTSIDE_UPLOADS, 'The temporary path is unsafe.', 'temporary_relative_path' );
		}

		$source_path      = $this->normalize_path( $source->absolute_path() );
		$destination_path = $this->normalize_path( $destination->absolute_path() );
		$temporary_path   = $this->normalize_path( $destination->temporary_absolute_path() );
		$backup_path      = $this->normalize_path( $destination->absolute_path() . '.bak' );

		if ( ! $this->is_safe_absolute_path( $source_path ) ) {
			return $this->invalid_paths( ConversionResultCode::UNSAFE_SOURCE_PATH, 'The source path is unsafe.', 'source_absolute_path' );
		}

		if ( ! $this->is_safe_absolute_path( $destination_path ) ) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_OUTSIDE_UPLOADS, 'The destination path is unsafe.', 'destination_absolute_path' );
		}

		if ( ! $this->is_safe_absolute_path( $temporary_path ) ) {
			return $this->invalid_paths( ConversionResultCode::TEMPORARY_OUTSIDE_UPLOADS, 'The temporary path is unsafe.', 'temporary_absolute_path' );
		}

		if ( ! $this->is_safe_absolute_path( $backup_path ) ) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_OUTSIDE_UPLOADS, 'The backup path is unsafe.', 'backup_absolute_path' );
		}

		$source_base      = $this->uploads_base_from_pair( $source_path, $source->relative_path() );
		$destination_base = $this->uploads_base_from_pair( $destination_path, $destination->relative_path() );
		$temporary_base   = $this->uploads_base_from_pair( $temporary_path, $destination->temporary_relative_path() );
		$backup_base      = $this->uploads_base_from_pair( $backup_path, $destination->relative_path() . '.bak' );

		if ( '' === $source_base ) {
			return $this->invalid_paths( ConversionResultCode::UNSAFE_SOURCE_PATH, 'The source path does not match its uploads-relative path.', 'source_base' );
		}

		if ( '' === $destination_base || ! $this->same_path( $source_base, $destination_base ) ) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_OUTSIDE_UPLOADS, 'The destination path is outside uploads.', 'destination_base' );
		}

		if ( '' === $temporary_base || ! $this->same_path( $source_base, $temporary_base ) ) {
			return $this->invalid_paths( ConversionResultCode::TEMPORARY_OUTSIDE_UPLOADS, 'The temporary path is outside uploads.', 'temporary_base' );
		}

		if ( '' === $backup_base || ! $this->same_path( $source_base, $backup_base ) ) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_OUTSIDE_UPLOADS, 'The backup path is outside uploads.', 'backup_base' );
		}

		$source_realpath = $this->filesystem->realpath( $source_path );
		if ( '' !== $source_realpath && ! $this->path_is_inside_base( $source_realpath, $source_base ) ) {
			return $this->invalid_paths( ConversionResultCode::UNSAFE_SOURCE_PATH, 'The source realpath resolves outside uploads.', 'source_realpath' );
		}

		if ( $this->same_path( $source_path, $destination_path ) || $this->same_relative_path( $source->relative_path(), $destination->relative_path() ) ) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_COLLISION, 'The destination collides with the source path.', 'destination_collision' );
		}

		if (
			$this->same_path( $temporary_path, $source_path )
			|| $this->same_path( $temporary_path, $destination_path )
			|| $this->same_relative_path( $destination->temporary_relative_path(), $source->relative_path() )
			|| $this->same_relative_path( $destination->temporary_relative_path(), $destination->relative_path() )
			|| $this->same_path( $temporary_path, $backup_path )
		) {
			return $this->invalid_paths( ConversionResultCode::TEMPORARY_COLLISION, 'The temporary path collides with another conversion path.', 'temporary_collision' );
		}

		if ( ! $this->same_path( dirname( $destination_path ), dirname( $temporary_path ) ) ) {
			return $this->invalid_paths( ConversionResultCode::TEMPORARY_OUTSIDE_UPLOADS, 'The temporary path is not in the destination directory.', 'temporary_directory' );
		}

		if (
			$this->same_path( $backup_path, $source_path )
			|| $this->same_path( $backup_path, $destination_path )
			|| $this->same_relative_path( $destination->relative_path() . '.bak', $source->relative_path() )
			|| ! $this->same_path( dirname( $destination_path ), dirname( $backup_path ) )
		) {
			return $this->invalid_paths( ConversionResultCode::DESTINATION_COLLISION, 'The backup path collides with another conversion path.', 'backup_collision' );
		}

		return array(
			'valid'            => true,
			'uploads_base'     => $source_base,
			'source_path'      => $source_path,
			'destination_path' => $destination_path,
			'temporary_path'   => $temporary_path,
			'backup_path'      => $backup_path,
		);
	}

	/**
	 * Build invalid path result details.
	 *
	 * @param string $code Code.
	 * @param string $message Message.
	 * @param string $reason Reason.
	 * @return array<string,mixed>
	 */
	private function invalid_paths( string $code, string $message, string $reason ): array {
		return array(
			'valid'   => false,
			'code'    => $code,
			'message' => $message,
			'reason'  => $reason,
		);
	}

	/**
	 * Check an existing final destination.
	 *
	 * @param string $path Absolute path.
	 * @param string $uploads_base Uploads base.
	 * @return string|null
	 */
	private function check_existing_destination( string $path, string $uploads_base ): ?string {
		if ( ! $this->filesystem->exists( $path ) ) {
			return null;
		}

		$realpath = $this->filesystem->realpath( $path );
		if ( '' !== $realpath && ! $this->path_is_inside_base( $realpath, $uploads_base ) ) {
			return ConversionResultCode::DESTINATION_REALPATH_OUTSIDE_UPLOADS;
		}

		return ConversionResultCode::DESTINATION_COLLISION;
	}

	/**
	 * Check an existing temporary destination.
	 *
	 * @param string $path Absolute path.
	 * @param string $uploads_base Uploads base.
	 * @return string|null
	 */
	private function check_existing_temporary( string $path, string $uploads_base ): ?string {
		if ( ! $this->filesystem->exists( $path ) ) {
			return null;
		}

		$realpath = $this->filesystem->realpath( $path );
		if ( '' !== $realpath && ! $this->path_is_inside_base( $realpath, $uploads_base ) ) {
			return ConversionResultCode::TEMPORARY_REALPATH_OUTSIDE_UPLOADS;
		}

		if ( ! $this->filesystem->is_file( $path ) ) {
			return ConversionResultCode::TEMPORARY_COLLISION;
		}

		return null;
	}

	/**
	 * Determine whether an existing destination is replaceable in reconcile mode.
	 *
	 * @param DestinationPath $destination Destination path.
	 * @param string          $uploads_base Uploads base.
	 * @return string|null
	 */
	private function check_replaceable_destination( DestinationPath $destination, string $uploads_base ): ?string {
		$path = $destination->absolute_path();

		if ( ! $this->filesystem->exists( $path ) ) {
			return null;
		}

		$realpath = $this->filesystem->realpath( $path );
		if ( '' !== $realpath && ! $this->path_is_inside_base( $realpath, $uploads_base ) ) {
			return ConversionResultCode::DESTINATION_REALPATH_OUTSIDE_UPLOADS;
		}

		if ( ! $this->filesystem->is_file( $path ) || ! $this->is_plugin_owned_destination( $destination ) ) {
			return ConversionResultCode::DESTINATION_COLLISION;
		}

		return null;
	}

	/**
	 * Validate a generated output file.
	 *
	 * @param string      $path Absolute output path.
	 * @param string      $expected_mime Expected MIME.
	 * @param SourceImage $source Source image.
	 * @return array<string,mixed>
	 */
	private function validate_output_file( string $path, string $expected_mime, SourceImage $source ): array {
		if ( ! $this->filesystem->exists( $path ) || ! $this->filesystem->is_file( $path ) ) {
			return $this->invalid_output( 'missing_output', 'The generated derivative file is missing.' );
		}

		$bytes = $this->filesystem->file_size( $path );
		if ( null === $bytes || 0 >= $bytes ) {
			return $this->invalid_output( 'empty_output', 'The generated derivative is empty.' );
		}

		$mime_type = $this->filesystem->mime_type( $path );
		if ( $expected_mime !== $mime_type ) {
			return $this->invalid_output( 'mime_mismatch', 'The generated derivative MIME type is invalid.' );
		}

		$dimensions = $this->filesystem->dimensions( $path );
		if ( null === $dimensions ) {
			return $this->invalid_output( 'missing_dimensions', 'The generated derivative dimensions could not be read.' );
		}

		if ( $source->width() !== $dimensions['width'] || $source->height() !== $dimensions['height'] ) {
			return $this->invalid_output( 'dimension_mismatch', 'The generated derivative dimensions do not match the source.' );
		}

		return array(
			'valid'  => true,
			'bytes'  => $bytes,
			'width'  => $dimensions['width'],
			'height' => $dimensions['height'],
		);
	}

	/**
	 * Build invalid output result details.
	 *
	 * @param string $reason Reason.
	 * @param string $message Message.
	 * @return array<string,mixed>
	 */
	private function invalid_output( string $reason, string $message ): array {
		return array(
			'valid'   => false,
			'reason'  => $reason,
			'message' => $message,
		);
	}

	/**
	 * Delete temporary output if present.
	 *
	 * @param string $path Absolute temporary path.
	 * @param string $uploads_base Uploads base.
	 * @return bool
	 *
	 * @phpstan-impure
	 */
	private function cleanup_temporary( string $path, string $uploads_base ): bool {
		return $this->cleanup_path( $path, $uploads_base );
	}

	/**
	 * Delete final output after failed post-move validation.
	 *
	 * @param string $path Absolute final path.
	 * @param string $uploads_base Uploads base.
	 * @return bool
	 *
	 * @phpstan-impure
	 */
	private function cleanup_final( string $path, string $uploads_base ): bool {
		return $this->cleanup_path( $path, $uploads_base );
	}

	/**
	 * Delete a replacement backup when present.
	 *
	 * @param string $path Absolute backup path.
	 * @param string $uploads_base Uploads base.
	 * @return bool
	 *
	 * @phpstan-impure
	 */
	private function cleanup_backup( string $path, string $uploads_base ): bool {
		return $this->cleanup_path( $path, $uploads_base );
	}

	/**
	 * Delete a validated file path when present.
	 *
	 * @param string $path Absolute path.
	 * @param string $uploads_base Uploads base.
	 * @return bool
	 *
	 * @phpstan-impure
	 */
	private function cleanup_path( string $path, string $uploads_base ): bool {
		if ( ! $this->filesystem->exists( $path ) ) {
			return true;
		}

		$realpath = $this->filesystem->realpath( $path );
		if ( '' !== $realpath && ! $this->path_is_inside_base( $realpath, $uploads_base ) ) {
			return false;
		}

		return $this->filesystem->delete( $path );
	}

	/**
	 * Attempt to restore a moved-aside backup destination.
	 *
	 * @param string $destination_path Absolute final path.
	 * @param string $backup_path Absolute backup path.
	 * @param string $uploads_base Uploads base.
	 * @return bool
	 *
	 * @phpstan-impure
	 */
	private function rollback_backup( string $destination_path, string $backup_path, string $uploads_base ): bool {
		if ( ! $this->filesystem->exists( $backup_path ) ) {
			return false;
		}

		if ( $this->filesystem->exists( $destination_path ) && ! $this->cleanup_final( $destination_path, $uploads_base ) ) {
			return false;
		}

		return $this->filesystem->move( $backup_path, $destination_path );
	}

	/**
	 * Build failed conversion result.
	 *
	 * @param SourceImage            $source Source image.
	 * @param DestinationPath        $destination Destination path.
	 * @param string                 $code Code.
	 * @param string                 $message Message.
	 * @param ConversionSavings|null $savings Savings.
	 * @param array<mixed>           $details Details.
	 * @return ConversionResult
	 */
	private function failed(
		SourceImage $source,
		DestinationPath $destination,
		string $code,
		string $message,
		?ConversionSavings $savings = null,
		array $details = array()
	): ConversionResult {
		return ConversionResult::failed(
			$source,
			$destination->target_format(),
			$destination->target_mime(),
			$code,
			$message,
			$destination,
			$savings,
			null,
			$details
		);
	}

	/**
	 * Add cleanup detail when needed.
	 *
	 * @param array<mixed> $details Details.
	 * @param bool         $cleanup_failed Whether cleanup failed.
	 * @return array<mixed>
	 */
	private function with_cleanup_detail( array $details, bool $cleanup_failed ): array {
		if ( $cleanup_failed ) {
			$details['cleanup_failed'] = true;
		}

		return $details;
	}

	/**
	 * Determine whether a destination path is plugin-owned and replaceable.
	 *
	 * @param DestinationPath $destination Destination path.
	 * @return bool
	 */
	private function is_plugin_owned_destination( DestinationPath $destination ): bool {
		return 1 === preg_match(
			'/\.hwlio\.' . preg_quote( $destination->target_format(), '/' ) . '$/i',
			$destination->relative_path()
		);
	}

	/**
	 * Get MIME for a supported target format.
	 *
	 * @param string $format Format.
	 * @return string|null
	 */
	private function target_mime_for_format( string $format ): ?string {
		$format = strtolower( trim( $format ) );

		if ( 'webp' === $format ) {
			return 'image/webp';
		}

		if ( 'avif' === $format ) {
			return 'image/avif';
		}

		return null;
	}

	/**
	 * Resolve uploads base from an absolute path and uploads-relative path.
	 *
	 * @param string $absolute_path Absolute path.
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function uploads_base_from_pair( string $absolute_path, string $relative_path ): string {
		$absolute_path = rtrim( $this->normalize_path( $absolute_path ), '/' );
		$relative_path = $this->normalize_relative_path( $relative_path );

		if ( '' === $absolute_path || '' === $relative_path || strlen( $absolute_path ) <= strlen( $relative_path ) ) {
			return '';
		}

		if ( strtolower( substr( $absolute_path, -strlen( $relative_path ) ) ) !== strtolower( $relative_path ) ) {
			return '';
		}

		return rtrim( substr( $absolute_path, 0, -strlen( $relative_path ) ), '/' );
	}

	/**
	 * Determine whether a relative path is safe.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_safe_relative_path( string $path ): bool {
		if ( false !== strpos( $path, "\0" ) ) {
			return false;
		}

		$path = str_replace( '\\', '/', trim( $path ) );
		if ( '' === $path || '/' === $path[0] || preg_match( '#^[a-z][a-z0-9+.-]*://#i', $path ) || preg_match( '/^[a-z]:\//i', $path ) ) {
			return false;
		}

		if ( false !== strpos( $path, '//' ) ) {
			return false;
		}

		$segments = explode( '/', $path );
		foreach ( $segments as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine whether an absolute path value is safe to inspect.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_safe_absolute_path( string $path ): bool {
		return '' !== trim( $path )
			&& false === strpos( $path, "\0" )
			&& ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $path );
	}

	/**
	 * Normalize an absolute-ish path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		return str_replace( '\\', '/', trim( $path ) );
	}

	/**
	 * Normalize a relative path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_relative_path( string $path ): string {
		return ltrim( $this->normalize_path( $path ), '/' );
	}

	/**
	 * Determine whether two absolute paths are the same.
	 *
	 * @param string $left Left path.
	 * @param string $right Right path.
	 * @return bool
	 */
	private function same_path( string $left, string $right ): bool {
		return strtolower( rtrim( $this->normalize_path( $left ), '/' ) ) === strtolower( rtrim( $this->normalize_path( $right ), '/' ) );
	}

	/**
	 * Determine whether two uploads-relative paths are the same.
	 *
	 * @param string $left Left path.
	 * @param string $right Right path.
	 * @return bool
	 */
	private function same_relative_path( string $left, string $right ): bool {
		return strtolower( $this->normalize_relative_path( $left ) ) === strtolower( $this->normalize_relative_path( $right ) );
	}

	/**
	 * Determine whether a path stays inside uploads base.
	 *
	 * @param string $path Path.
	 * @param string $uploads_base Uploads base.
	 * @return bool
	 */
	private function path_is_inside_base( string $path, string $uploads_base ): bool {
		$path         = strtolower( rtrim( $this->normalize_path( $path ), '/' ) );
		$uploads_base = strtolower( rtrim( $this->normalize_path( $uploads_base ), '/' ) );

		return $path === $uploads_base || 0 === strpos( $path, $uploads_base . '/' );
	}
}
