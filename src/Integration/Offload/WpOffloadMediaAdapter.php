<?php
/**
 * WP Offload Media adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;

/**
 * Implements the first supported offload adapter with core-API-first attachment classification.
 */
final class WpOffloadMediaAdapter implements LocalSourceResolverInterface, DerivativePushInterface, DerivativeDeleteInterface {

	public const PLUGIN_BASENAME = 'amazon-s3-and-cloudfront/wordpress-s3.php';
	public const PLUGIN_NAME     = 'WP Offload Media';

	/**
	 * Runtime.
	 *
	 * @var WpOffloadMediaRuntimeInterface
	 */
	private $runtime;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Create adapter.
	 *
	 * @param WpOffloadMediaRuntimeInterface $runtime Runtime.
	 * @param ImageFileProbeInterface        $files File probe.
	 * @param DerivativeManifestSanitizer    $sanitizer Path sanitizer.
	 */
	public function __construct(
		WpOffloadMediaRuntimeInterface $runtime,
		ImageFileProbeInterface $files,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->runtime   = $runtime;
		$this->files     = $files;
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Get site support.
	 *
	 * @return OffloadSiteSupport
	 */
	public function site_support(): OffloadSiteSupport {
		if ( ! $this->plugin_active() ) {
			return OffloadSiteSupport::inactive();
		}

		if ( ! $this->runtime->remote_operations_available() ) {
			return OffloadSiteSupport::unsupported();
		}

		return OffloadSiteSupport::supported();
	}

	/**
	 * Get attachment support facts.
	 *
	 * @param int                     $attachment_id Attachment ID.
	 * @param OffloadSiteSupport|null $site_support Optional cached site support.
	 * @return OffloadAttachmentSupport
	 */
	public function attachment_support( int $attachment_id, ?OffloadSiteSupport $site_support = null ): OffloadAttachmentSupport {
		$attachment_id = max( 0, $attachment_id );
		$site_support  = $site_support ?? $this->site_support();
		$attachment_url = $this->runtime->attachment_url( $attachment_id );
		$metadata       = $this->runtime->attachment_metadata( $attachment_id );
		$relative_file  = $this->metadata_file( $metadata );

		if ( ! $this->plugin_active() ) {
			return OffloadAttachmentSupport::local_native( $attachment_id, $attachment_url );
		}

		if ( '' === $relative_file || null === $attachment_url ) {
			return OffloadAttachmentSupport::unsupported(
				$attachment_id,
				OffloadSiteSupport::CODE_UNSUPPORTED,
				'The attachment could not be classified safely for media offload compatibility.',
				$attachment_url
			);
		}

		$uploads_base_url = $this->normalize_base_url( $this->runtime->uploads_base_url() );

		if ( null !== $uploads_base_url && $this->is_local_upload_url( $attachment_url, $uploads_base_url, $relative_file ) ) {
			return OffloadAttachmentSupport::local_native( $attachment_id, $attachment_url );
		}

		if ( ! $this->url_suffix_matches_relative_file( $attachment_url, $relative_file ) ) {
			return OffloadAttachmentSupport::unsupported(
				$attachment_id,
				'offload_remote_base_unresolved',
				'The offloaded attachment URL did not match the authoritative metadata-relative file path.',
				$attachment_url
			);
		}

		$remote_base_url = $this->remote_base_url( $attachment_url, $relative_file );

		if ( null === $remote_base_url ) {
			return OffloadAttachmentSupport::unsupported(
				$attachment_id,
				'offload_remote_base_unresolved',
				'The remote base URL could not be inferred safely for this offloaded attachment.',
				$attachment_url
			);
		}

		if ( ! $site_support->supported() ) {
			return OffloadAttachmentSupport::unsupported(
				$attachment_id,
				$site_support->code(),
				$site_support->message(),
				$attachment_url,
				$remote_base_url,
				$site_support->blocked_operations()
			);
		}

		$attached_file = $this->runtime->attached_file( $attachment_id );
		$mode          = is_string( $attached_file ) && '' !== trim( $attached_file ) && $this->runtime->file_exists( $attached_file ) && $this->runtime->is_readable( $attached_file )
			? OffloadAttachmentSupport::MODE_OFFLOADED_KEEP_LOCAL
			: OffloadAttachmentSupport::MODE_OFFLOADED_REMOTE_ONLY;

		return OffloadAttachmentSupport::offloaded_supported( $attachment_id, $mode, $attachment_url, $remote_base_url );
	}

	/**
	 * Resolve a temporary local source.
	 *
	 * @param LocalSourceResolutionRequest $request Request.
	 * @return LocalSourceResolutionResult
	 */
	public function resolve( LocalSourceResolutionRequest $request ): LocalSourceResolutionResult {
		if ( ! $request->support()->is_supported() || ! $request->support()->is_offloaded() ) {
			return LocalSourceResolutionResult::failure( 'offload_unsupported', 'The attachment is not in a supported offloaded state.' );
		}

		if ( '' === trim( $request->remote_url() ) ) {
			return LocalSourceResolutionResult::failure( 'offload_remote_base_unresolved', 'A safe remote source URL could not be resolved for this attachment source.' );
		}

		$temporary = $this->runtime->download_remote_file( $request->remote_url() );

		if ( ! is_string( $temporary ) || '' === trim( $temporary ) ) {
			return LocalSourceResolutionResult::failure( 'offload_source_download_failed', 'The remote source could not be downloaded to a temporary local file.' );
		}

		$absolute_path = str_replace( '\\', '/', trim( $this->files->realpath( $temporary ) ) );

		if (
			'' === $absolute_path
			|| ! $this->files->exists( $absolute_path )
			|| ! $this->files->is_file( $absolute_path )
			|| ! $this->files->is_readable( $absolute_path )
		) {
			$this->runtime->delete_local_temp_file( $temporary );

			return LocalSourceResolutionResult::failure( 'offload_source_download_failed', 'The downloaded temporary source file could not be validated safely.' );
		}

		$bytes         = $this->files->file_size( $absolute_path );
		$modified_time = $this->files->modified_time( $absolute_path );
		$dimensions    = $this->files->dimensions( $absolute_path );
		$mime_type     = $this->files->mime_type( $absolute_path );

		if ( null === $bytes || null === $modified_time ) {
			$this->runtime->delete_local_temp_file( $absolute_path );

			return LocalSourceResolutionResult::failure( 'offload_source_download_failed', 'The downloaded temporary source file facts could not be read safely.' );
		}

		if ( null === $dimensions ) {
			if ( null === $request->width() || null === $request->height() ) {
				$this->runtime->delete_local_temp_file( $absolute_path );

				return LocalSourceResolutionResult::failure( 'offload_source_download_failed', 'The downloaded temporary source dimensions could not be determined safely.' );
			}

			$dimensions = array(
				'width'  => $request->width(),
				'height' => $request->height(),
			);
		}

		$source = new SourceImage(
			$request->attachment_id(),
			$request->size_name(),
			$request->role(),
			$request->relative_path(),
			$absolute_path,
			$mime_type,
			(int) $dimensions['width'],
			(int) $dimensions['height'],
			(int) $bytes,
			(int) $modified_time
		);

		return LocalSourceResolutionResult::success(
			$source,
			new TemporarySourceLease(
				$absolute_path,
				array( $this->runtime, 'delete_local_temp_file' )
			)
		);
	}

	/**
	 * Publish derivative results to remote storage.
	 *
	 * @param DerivativePushRequest $request Request.
	 * @return DerivativePushResult
	 */
	public function publish( DerivativePushRequest $request ): DerivativePushResult {
		$support = $request->support();

		if ( ! $support->is_offloaded() ) {
			return new DerivativePushResult( $request->results(), array( OffloadSiteSupport::CODE_SUPPORTED ) );
		}

		$results  = array();
		$codes    = array();
		$messages = array();

		foreach ( $request->results()->results() as $result ) {
			if ( ! $result->is_success() || null === $result->destination() || null === $result->output() ) {
				$results[] = $result;
				continue;
			}

			$published = $this->runtime->push_derivative(
				$request->attachment_id(),
				$result->output()->relative_path(),
				$result->destination()->absolute_path(),
				$result->output()->mime_type(),
				array(
					'mode'              => $support->mode(),
					'remote_base_url'   => $support->remote_base_url(),
					'attachment_url'    => $support->attachment_url(),
					'target_format'     => $result->target_format(),
				)
			);

			if ( ! $published ) {
				$codes[]    = 'offload_push_failed';
				$messages[] = sprintf( 'Remote publication failed for %s.', $result->output()->relative_path() );
				$results[]  = ConversionResult::failed(
					$result->source(),
					$result->target_format(),
					$result->target_mime(),
					'offload_push_failed',
					'The derivative could not be published to remote storage.',
					$result->destination(),
					$result->savings(),
					$result->output()
				);
				continue;
			}

			if ( $support->is_remote_only() ) {
				$this->runtime->delete_local_temp_file( $result->destination()->absolute_path() );
			}

			$results[] = $result;
		}

		return new DerivativePushResult( new ConversionResultCollection( $results ), $codes, $messages );
	}

	/**
	 * Delete derivatives from remote storage.
	 *
	 * @param DerivativeDeleteRequest $request Request.
	 * @return DerivativeDeleteResult
	 */
	public function delete( DerivativeDeleteRequest $request ): DerivativeDeleteResult {
		if ( array() === $request->relative_paths() || ! $request->support()->is_offloaded() ) {
			return DerivativeDeleteResult::success();
		}

		$deleted  = array();
		$messages = array();

		foreach ( $request->relative_paths() as $relative_path ) {
			if ( $this->runtime->delete_derivative(
				$request->attachment_id(),
				$relative_path,
				array(
					'mode'            => $request->support()->mode(),
					'reason'          => $request->reason(),
					'remote_base_url' => $request->support()->remote_base_url(),
				)
			) ) {
				$deleted[] = $relative_path;
				continue;
			}

			$messages[] = sprintf( 'Remote deletion failed for %s.', $relative_path );
		}

		if ( array() === $messages ) {
			return DerivativeDeleteResult::success( $deleted );
		}

		return DerivativeDeleteResult::failure( array( 'offload_delete_failed' ), $messages, $deleted );
	}

	/**
	 * Determine whether the plugin is active.
	 *
	 * @return bool
	 */
	private function plugin_active(): bool {
		return in_array( self::PLUGIN_BASENAME, $this->runtime->active_plugin_basenames(), true );
	}

	/**
	 * Read the authoritative metadata file.
	 *
	 * @param array<string,mixed>|null $metadata Metadata.
	 * @return string
	 */
	private function metadata_file( ?array $metadata ): string {
		if ( ! is_array( $metadata ) || ! isset( $metadata['file'] ) || ! is_scalar( $metadata['file'] ) ) {
			return '';
		}

		return $this->sanitizer->safe_relative_path( (string) $metadata['file'] );
	}

	/**
	 * Determine whether the attachment URL still points to local uploads.
	 *
	 * @param string $attachment_url Attachment URL.
	 * @param string $uploads_base_url Local uploads base URL.
	 * @param string $relative_file Relative file.
	 * @return bool
	 */
	private function is_local_upload_url( string $attachment_url, string $uploads_base_url, string $relative_file ): bool {
		$uploads_base_url = $this->normalize_base_url( $uploads_base_url );

		if ( null === $uploads_base_url ) {
			return false;
		}

		return 0 === strpos( $attachment_url, $uploads_base_url . '/' )
			&& $this->url_suffix_matches_relative_file( $attachment_url, $relative_file );
	}

	/**
	 * Determine whether a URL path suffix matches the metadata-relative file exactly.
	 *
	 * @param string $url URL.
	 * @param string $relative_file Relative file.
	 * @return bool
	 */
	private function url_suffix_matches_relative_file( string $url, string $relative_file ): bool {
		$path = parse_url( $url, PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === $path ) {
			return false;
		}

		$path          = ltrim( str_replace( '\\', '/', rawurldecode( $path ) ), '/' );
		$relative_file = ltrim( str_replace( '\\', '/', $relative_file ), '/' );

		if ( '' === $relative_file || strlen( $path ) < strlen( $relative_file ) ) {
			return false;
		}

		if ( substr( $path, -strlen( $relative_file ) ) !== $relative_file ) {
			return false;
		}

		$prefix = substr( $path, 0, -strlen( $relative_file ) );

		return '' === $prefix || '/' === substr( $prefix, -1 );
	}

	/**
	 * Infer the remote base URL by stripping the authoritative metadata file suffix.
	 *
	 * @param string $attachment_url Attachment URL.
	 * @param string $relative_file Relative file.
	 * @return string|null
	 */
	private function remote_base_url( string $attachment_url, string $relative_file ): ?string {
		if ( ! $this->url_suffix_matches_relative_file( $attachment_url, $relative_file ) ) {
			return null;
		}

		$base = substr( $attachment_url, 0, -strlen( $relative_file ) );
		$base = rtrim( $base, '/' );

		return '' === $base ? null : $base;
	}

	/**
	 * Normalize a base URL.
	 *
	 * @param string|null $url URL.
	 * @return string|null
	 */
	private function normalize_base_url( ?string $url ): ?string {
		return null === $url || '' === trim( $url ) ? null : rtrim( trim( $url ), '/' );
	}
}
