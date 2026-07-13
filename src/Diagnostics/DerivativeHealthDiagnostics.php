<?php
/**
 * Derivative health diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\UploadsRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressUploadsRuntime;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Image\WordPressImageFileProbe;

/**
 * Checks whether sanitized ready derivative manifest entries still exist on disk.
 */
final class DerivativeHealthDiagnostics {

	private const PAGE_SIZE           = 100;
	private const MAX_ATTACHMENTS     = 1000;
	private const SAMPLE_LIMIT        = 20;
	private const ID_DELIVERY_FILES   = 'delivery_derivative_files';
	private const CODE_OK             = 'delivery_derivatives_ok';
	private const CODE_MISSING        = 'delivery_derivatives_missing';
	private const CODE_NONE           = 'delivery_derivatives_none';
	private const CODE_SCAN_TRUNCATED = 'delivery_derivatives_scan_truncated';

	/**
	 * Runtime.
	 *
	 * @var DerivativeHealthRuntimeInterface
	 */
	private $runtime;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Uploads runtime.
	 *
	 * @var UploadsRuntimeInterface
	 */
	private $uploads;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Manifest sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Build WordPress-backed diagnostics.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressDerivativeHealthRuntime(),
			DerivativeRepository::for_wordpress(),
			new WordPressUploadsRuntime(),
			new WordPressImageFileProbe(),
			new DerivativeManifestSanitizer()
		);
	}

	/**
	 * Create diagnostics.
	 *
	 * @param DerivativeHealthRuntimeInterface $runtime Runtime.
	 * @param DerivativeRepository             $repository Repository.
	 * @param UploadsRuntimeInterface          $uploads Uploads runtime.
	 * @param ImageFileProbeInterface          $files File probe.
	 * @param DerivativeManifestSanitizer      $sanitizer Manifest sanitizer.
	 */
	public function __construct(
		DerivativeHealthRuntimeInterface $runtime,
		DerivativeRepository $repository,
		UploadsRuntimeInterface $uploads,
		ImageFileProbeInterface $files,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->runtime    = $runtime;
		$this->repository = $repository;
		$this->uploads    = $uploads;
		$this->files      = $files;
		$this->sanitizer  = $sanitizer;
	}

	/**
	 * Run the bounded diagnostic.
	 *
	 * @return DiagnosticResult
	 */
	public function run(): DiagnosticResult {
		$base_dir      = $this->uploads->uploads_base_dir();
		$base_realpath = is_string( $base_dir ) ? $this->normalize_path( $this->files->realpath( $base_dir ) ) : '';
		$cursor        = 0;
		$scanned       = 0;
		$checked       = 0;
		$missing       = 0;
		$sample_ids    = array();
		$sample_paths  = array();
		$scan_complete = true;

		while ( $scanned < self::MAX_ATTACHMENTS ) {
			$limit = min( self::PAGE_SIZE, self::MAX_ATTACHMENTS - $scanned );
			$ids   = $this->runtime->attachment_ids_after( $cursor, $limit );

			if ( array() === $ids ) {
				break;
			}

			foreach ( $ids as $attachment_id ) {
				$cursor = max( $cursor, $attachment_id );
				++$scanned;

				$read = $this->repository->read( $attachment_id );

				foreach ( $this->ready_derivative_paths( $read->manifest()->sizes() ) as $relative_path ) {
					++$checked;

					if ( '' === $base_realpath || ! $this->derivative_exists_inside_uploads( $base_dir, $base_realpath, $relative_path ) ) {
						++$missing;
						$sample_ids[ $attachment_id ] = $attachment_id;

						if ( count( $sample_paths ) < self::SAMPLE_LIMIT ) {
							$sample_paths[] = $relative_path;
						}
					}
				}
			}

			if ( count( $ids ) < $limit ) {
				break;
			}
		}

		if ( $scanned >= self::MAX_ATTACHMENTS && array() !== $this->runtime->attachment_ids_after( $cursor, 1 ) ) {
			$scan_complete = false;
		}

		$details = array(
			'scanned_attachments'   => $scanned,
			'checked_derivatives'   => $checked,
			'missing_derivatives'   => $missing,
			'scan_complete'         => $scan_complete,
			'sample_attachment_ids' => array_slice( array_values( $sample_ids ), 0, self::SAMPLE_LIMIT ),
			'sample_relative_paths' => array_slice( array_values( array_unique( $sample_paths ) ), 0, self::SAMPLE_LIMIT ),
		);

		if ( 0 < $missing ) {
			return new DiagnosticResult(
				self::ID_DELIVERY_FILES,
				DiagnosticStatus::WARNING,
				self::CODE_MISSING,
				'Delivery derivative files',
				'Some ready derivative files recorded in metadata are missing from uploads.',
				$details
			);
		}

		if ( ! $scan_complete ) {
			return new DiagnosticResult(
				self::ID_DELIVERY_FILES,
				DiagnosticStatus::WARNING,
				self::CODE_SCAN_TRUNCATED,
				'Delivery derivative files',
				'Derivative file diagnostics reached the bounded scan limit.',
				$details
			);
		}

		if ( 0 === $checked ) {
			return new DiagnosticResult(
				self::ID_DELIVERY_FILES,
				DiagnosticStatus::INFO,
				self::CODE_NONE,
				'Delivery derivative files',
				'No ready derivative files were available to verify.',
				$details
			);
		}

		return new DiagnosticResult(
			self::ID_DELIVERY_FILES,
			DiagnosticStatus::PASS,
			self::CODE_OK,
			'Delivery derivative files',
			'Ready derivative files referenced by metadata are present.',
			$details
		);
	}

	/**
	 * Extract sanitized ready derivative paths from manifest sizes.
	 *
	 * @param array<string,array<string,mixed>> $sizes Manifest sizes.
	 * @return string[]
	 */
	private function ready_derivative_paths( array $sizes ): array {
		$paths = array();

		foreach ( $sizes as $size ) {
			$formats = isset( $size['formats'] ) && is_array( $size['formats'] ) ? $size['formats'] : array();

			foreach ( $formats as $entry ) {
				if ( ! is_array( $entry ) || ! isset( $entry['file'] ) || ! is_scalar( $entry['file'] ) ) {
					continue;
				}

				$path = $this->sanitizer->safe_relative_path( (string) $entry['file'] );

				if ( '' !== $path ) {
					$paths[] = $path;
				}
			}
		}

		return array_values( array_unique( $paths ) );
	}

	/**
	 * Determine whether a derivative file exists inside uploads.
	 *
	 * @param string|null $base_dir Uploads base directory.
	 * @param string      $base_realpath Uploads base realpath.
	 * @param string      $relative_path Relative path.
	 * @return bool
	 */
	private function derivative_exists_inside_uploads( ?string $base_dir, string $base_realpath, string $relative_path ): bool {
		if ( ! is_string( $base_dir ) || '' === trim( $base_dir ) ) {
			return false;
		}

		$candidate = $this->normalize_path( rtrim( $base_dir, '\\/' ) . '/' . $relative_path );

		if ( ! $this->files->exists( $candidate ) || ! $this->files->is_file( $candidate ) ) {
			return false;
		}

		$realpath = $this->normalize_path( $this->files->realpath( $candidate ) );

		return '' !== $realpath && $this->is_within_base( $realpath, $base_realpath );
	}

	/**
	 * Normalize path separators.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		return rtrim( str_replace( '\\', '/', trim( $path ) ), '/' );
	}

	/**
	 * Whether path is within uploads.
	 *
	 * @param string $path Path.
	 * @param string $base Base realpath.
	 * @return bool
	 */
	private function is_within_base( string $path, string $base ): bool {
		return '' !== $base && ( $path === $base || 0 === strpos( $path, $base . '/' ) );
	}
}
