<?php
/**
 * Temporary file diagnostic.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Verifies uploads temporary write and rename behavior with cleanup.
 */
final class TemporaryFileDiagnostic {

	private const PREFIX   = 'hwlio-diagnostic-';
	private const CONTENTS = 'HyperWeb Lighthouse Image Optimizer diagnostic file.';

	/**
	 * Filesystem.
	 *
	 * @var DiagnosticFilesystemInterface
	 */
	private $filesystem;

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
		return new self( new WordPressDiagnosticFilesystem() );
	}

	/**
	 * Create diagnostic.
	 *
	 * @param DiagnosticFilesystemInterface $filesystem Filesystem.
	 * @param string                        $token Optional token.
	 */
	public function __construct( DiagnosticFilesystemInterface $filesystem, string $token = '' ) {
		$this->filesystem = $filesystem;
		$this->token      = $token;
	}

	/**
	 * Run the temporary write/rename diagnostic.
	 *
	 * @param string|null $uploads_base_dir Uploads base directory.
	 * @return DiagnosticResult
	 */
	public function run( ?string $uploads_base_dir ): DiagnosticResult {
		$base_realpath = $this->base_realpath( $uploads_base_dir );

		if ( '' === $base_realpath ) {
			return $this->result(
				DiagnosticStatus::FAIL,
				'uploads_base_unavailable',
				'The uploads directory could not be resolved for a temporary file test.',
				array()
			);
		}

		$token       = $this->safe_token();
		$source      = $this->path( $base_realpath, $token, '.tmp' );
		$destination = $this->path( $base_realpath, $token, '.renamed.tmp' );
		$paths       = array( $source, $destination );

		if ( ! $this->is_within_base( $source, $base_realpath ) || ! $this->is_within_base( $destination, $base_realpath ) ) {
			return $this->result(
				DiagnosticStatus::FAIL,
				'invalid_temporary_path',
				'The temporary diagnostic path was rejected before writing.',
				array()
			);
		}

		if ( $this->filesystem->exists( $source ) || $this->filesystem->exists( $destination ) ) {
			$cleanup_ok = $this->cleanup( $paths, $base_realpath );

			if ( ! $cleanup_ok ) {
				return $this->result(
					DiagnosticStatus::FAIL,
					'temporary_file_collision',
					'A previous diagnostic file could not be cleaned before retrying.',
					array( 'cleanup_failed' => true )
				);
			}
		}

		if ( ! $this->filesystem->write( $source, self::CONTENTS ) ) {
			$cleanup_ok = $this->cleanup( $paths, $base_realpath );

			return $this->result(
				DiagnosticStatus::FAIL,
				'temporary_write_failed',
				'The plugin could not write a temporary file in uploads.',
				array( 'cleanup_failed' => ! $cleanup_ok )
			);
		}

		if ( ! $this->filesystem->rename( $source, $destination ) || ! $this->filesystem->is_file( $destination ) ) {
			$cleanup_ok = $this->cleanup( $paths, $base_realpath );

			return $this->result(
				DiagnosticStatus::FAIL,
				'temporary_rename_failed',
				'The plugin could not rename a temporary file in uploads.',
				array( 'cleanup_failed' => ! $cleanup_ok )
			);
		}

		$cleanup_ok = $this->cleanup( $paths, $base_realpath );

		if ( ! $cleanup_ok ) {
			return $this->result(
				DiagnosticStatus::WARNING,
				'temporary_cleanup_failed',
				'The temporary write/rename test succeeded, but cleanup failed.',
				array( 'cleanup_failed' => true )
			);
		}

		return $this->result(
			DiagnosticStatus::PASS,
			'temporary_write_rename_succeeded',
			'Temporary write and rename checks passed.',
			array( 'cleanup_failed' => false )
		);
	}

	/**
	 * Build a result.
	 *
	 * @param string       $status Status.
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Details.
	 * @return DiagnosticResult
	 */
	private function result( string $status, string $code, string $message, array $details ): DiagnosticResult {
		return new DiagnosticResult(
			'temporary_write_rename',
			$status,
			$code,
			'Temporary write and rename',
			$message,
			array_replace(
				array(
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
	 * Build a plugin-owned diagnostic path.
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
}
