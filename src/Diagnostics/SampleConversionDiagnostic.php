<?php
/**
 * Sample conversion diagnostic.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;

/**
 * Runs bounded sample image conversion checks with cleanup.
 */
final class SampleConversionDiagnostic {

	private const PREFIX = 'hwlio-diagnostic-sample-';

	/**
	 * Target format MIME types.
	 *
	 * @var array<string,string>
	 */
	private const FORMAT_MIME_TYPES = array(
		FormatSupportResult::FORMAT_WEBP => 'image/webp',
		FormatSupportResult::FORMAT_AVIF => 'image/avif',
	);

	/**
	 * Tiny plugin-owned PNG fixture.
	 *
	 * @var string
	 */
	private const PNG_FIXTURE_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

	/**
	 * Filesystem.
	 *
	 * @var DiagnosticFilesystemInterface
	 */
	private $filesystem;

	/**
	 * Conversion probe.
	 *
	 * @var SampleConversionProbeInterface
	 */
	private $probe;

	/**
	 * Optional deterministic token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Create a WordPress-backed diagnostic.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressDiagnosticFilesystem(),
			new WordPressSampleConversionProbe()
		);
	}

	/**
	 * Create diagnostic.
	 *
	 * @param DiagnosticFilesystemInterface  $filesystem Filesystem.
	 * @param SampleConversionProbeInterface $probe Conversion probe.
	 * @param string                         $token Optional token.
	 */
	public function __construct(
		DiagnosticFilesystemInterface $filesystem,
		SampleConversionProbeInterface $probe,
		string $token = ''
	) {
		$this->filesystem = $filesystem;
		$this->probe      = $probe;
		$this->token      = $token;
	}

	/**
	 * Run the sample conversion diagnostic.
	 *
	 * @param string      $format Target format.
	 * @param string|null $uploads_base_dir Uploads base directory.
	 * @return DiagnosticResult
	 */
	public function run( string $format, ?string $uploads_base_dir ): DiagnosticResult {
		$format = strtolower( trim( $format ) );

		if ( ! isset( self::FORMAT_MIME_TYPES[ $format ] ) ) {
			return $this->result(
				$format,
				DiagnosticStatus::INFO,
				'sample_conversion_skipped',
				'Sample conversion was skipped for an unknown format.',
				array()
			);
		}

		$base_realpath = $this->base_realpath( $uploads_base_dir );

		if ( '' === $base_realpath ) {
			return $this->result(
				$format,
				DiagnosticStatus::FAIL,
				'uploads_base_unavailable',
				'The uploads directory could not be resolved for a sample conversion.',
				array()
			);
		}

		$token       = $this->safe_token();
		$source      = $this->path( $base_realpath, $token, '.png' );
		$destination = $this->path( $base_realpath, $token, '.' . $format );
		$paths       = array( $source, $destination );

		if ( ! $this->is_within_base( $source, $base_realpath ) || ! $this->is_within_base( $destination, $base_realpath ) ) {
			return $this->result(
				$format,
				DiagnosticStatus::FAIL,
				'invalid_sample_path',
				'The sample conversion path was rejected before writing.',
				array()
			);
		}

		$fixture = $this->fixture();

		if ( '' === $fixture || ! $this->filesystem->write( $source, $fixture ) ) {
			$cleanup_ok = $this->cleanup( $paths, $base_realpath );

			return $this->result(
				$format,
				DiagnosticStatus::FAIL,
				'sample_fixture_write_failed',
				'The plugin could not write the sample image fixture.',
				array( 'cleanup_failed' => ! $cleanup_ok )
			);
		}

		$conversion = $this->probe->convert( $source, $destination, self::FORMAT_MIME_TYPES[ $format ] );

		if ( ! $conversion->is_success() ) {
			$cleanup_ok = $this->cleanup( $paths, $base_realpath );

			return $this->result(
				$format,
				DiagnosticStatus::FAIL,
				$conversion->code(),
				$conversion->message(),
				array_replace(
					$conversion->details(),
					array( 'cleanup_failed' => ! $cleanup_ok )
				)
			);
		}

		if ( ! $this->filesystem->is_file( $destination ) || null === $this->filesystem->file_size( $destination ) || 0 >= (int) $this->filesystem->file_size( $destination ) ) {
			$cleanup_ok = $this->cleanup( $paths, $base_realpath );

			return $this->result(
				$format,
				DiagnosticStatus::FAIL,
				'output_validation_failed',
				'The sample conversion output could not be validated.',
				array( 'cleanup_failed' => ! $cleanup_ok )
			);
		}

		$cleanup_ok = $this->cleanup( $paths, $base_realpath );

		if ( ! $cleanup_ok ) {
			return $this->result(
				$format,
				DiagnosticStatus::WARNING,
				'sample_conversion_cleanup_failed',
				'The sample conversion succeeded, but cleanup failed.',
				array( 'cleanup_failed' => true )
			);
		}

		return $this->result(
			$format,
			DiagnosticStatus::PASS,
			'sample_conversion_succeeded',
			'Sample conversion succeeded and temporary files were removed.',
			array( 'cleanup_failed' => false )
		);
	}

	/**
	 * Build a result.
	 *
	 * @param string       $format Format.
	 * @param string       $status Status.
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Details.
	 * @return DiagnosticResult
	 */
	private function result( string $format, string $status, string $code, string $message, array $details ): DiagnosticResult {
		$format = strtolower( trim( $format ) );

		return new DiagnosticResult(
			'sample_conversion_' . ( '' === $format ? 'unknown' : $format ),
			$status,
			$code,
			sprintf( 'Sample %s conversion', strtoupper( '' === $format ? 'unknown' : $format ) ),
			$message,
			array_replace(
				array(
					'format'      => $format,
					'file_prefix' => self::PREFIX,
				),
				$details
			)
		);
	}

	/**
	 * Resolve uploads base real path.
	 *
	 * @param string|null $uploads_base_dir Uploads base directory.
	 * @return string
	 */
	private function base_realpath( ?string $uploads_base_dir ): string {
		if ( null === $uploads_base_dir || '' === trim( $uploads_base_dir ) ) {
			return '';
		}

		return $this->normalize_path( $this->filesystem->realpath( $uploads_base_dir ) );
	}

	/**
	 * Build a sample file path.
	 *
	 * @param string $base_realpath Base path.
	 * @param string $token Token.
	 * @param string $suffix Suffix.
	 * @return string
	 */
	private function path( string $base_realpath, string $token, string $suffix ): string {
		return rtrim( $base_realpath, '/' ) . '/' . self::PREFIX . $token . $suffix;
	}

	/**
	 * Cleanup generated files.
	 *
	 * @param string[] $paths Paths.
	 * @param string   $base_realpath Base path.
	 * @return bool
	 */
	private function cleanup( array $paths, string $base_realpath ): bool {
		$ok = true;

		foreach ( array_unique( $paths ) as $path ) {
			$path = $this->normalize_path( $path );

			if ( ! $this->is_within_base( $path, $base_realpath ) ) {
				$ok = false;
				continue;
			}

			if ( $this->filesystem->exists( $path ) && ! $this->filesystem->delete( $path ) ) {
				$ok = false;
			}
		}

		return $ok;
	}

	/**
	 * Determine whether a path is inside uploads.
	 *
	 * @param string $path Path.
	 * @param string $base_realpath Base path.
	 * @return bool
	 */
	private function is_within_base( string $path, string $base_realpath ): bool {
		$path          = $this->normalize_path( $path );
		$base_realpath = rtrim( $this->normalize_path( $base_realpath ), '/' );

		return '' !== $path && '' !== $base_realpath && 0 === strpos( $path, $base_realpath . '/' );
	}

	/**
	 * Normalize a path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_path( string $path ): string {
		return str_replace( '\\', '/', $path );
	}

	/**
	 * Get a safe filename token.
	 *
	 * @return string
	 */
	private function safe_token(): string {
		$token = '' === $this->token ? $this->generate_token() : $this->token;
		$token = strtolower( trim( $token ) );
		$token = (string) preg_replace( '/[^a-z0-9_-]/', '', $token );

		return '' === $token ? 'diagnostic' : substr( $token, 0, 40 );
	}

	/**
	 * Generate a non-secret filename token.
	 *
	 * @return string
	 */
	private function generate_token(): string {
		try {
			return bin2hex( random_bytes( 8 ) );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return str_replace( '.', '', (string) microtime( true ) );
		}
	}

	/**
	 * Get the PNG fixture.
	 *
	 * @return string
	 */
	private function fixture(): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decodes a fixed 1x1 PNG fixture for a bounded sample conversion diagnostic.
		$fixture = base64_decode( self::PNG_FIXTURE_BASE64, true );

		return false === $fixture ? '' : $fixture;
	}
}
