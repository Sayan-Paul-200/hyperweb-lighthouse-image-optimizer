<?php
/**
 * Offload-aware source collector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageIssue;

/**
 * Decorates the local source collector and materializes temporary local sources for supported offloaded attachments.
 */
final class OffloadAwareSourceCollector implements AttachmentSourceCollectorInterface {

	/**
	 * Local collector.
	 *
	 * @var AttachmentSourceCollectorInterface
	 */
	private $local;

	/**
	 * Runtime.
	 *
	 * @var WpOffloadMediaRuntimeInterface
	 */
	private $runtime;

	/**
	 * Resolver.
	 *
	 * @var LocalSourceResolverInterface
	 */
	private $resolver;

	/**
	 * Support service.
	 *
	 * @var OffloadSupportService
	 */
	private $support;

	/**
	 * Path sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Create collector.
	 *
	 * @param AttachmentSourceCollectorInterface $local Local collector.
	 * @param WpOffloadMediaRuntimeInterface     $runtime Runtime.
	 * @param LocalSourceResolverInterface       $resolver Resolver.
	 * @param OffloadSupportService              $support Support service.
	 * @param DerivativeManifestSanitizer        $sanitizer Path sanitizer.
	 */
	public function __construct(
		AttachmentSourceCollectorInterface $local,
		WpOffloadMediaRuntimeInterface $runtime,
		LocalSourceResolverInterface $resolver,
		OffloadSupportService $support,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->local     = $local;
		$this->runtime   = $runtime;
		$this->resolver  = $resolver;
		$this->support   = $support;
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Collect one authoritative source set.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return CollectedSourceSet
	 */
	public function collect( int $attachment_id ): CollectedSourceSet {
		$local   = $this->local->collect( $attachment_id );
		$support = $this->support->attachment_support( $attachment_id );

		if ( ! $support->is_supported() || ! $support->is_offloaded() ) {
			return $local;
		}

		$metadata = $this->runtime->attachment_metadata( $attachment_id );

		if ( ! is_array( $metadata ) ) {
			return $local;
		}

		$local_sources = array();
		foreach ( $local->collection()->sources() as $source ) {
			$local_sources[ $source->relative_path() ] = $source;
		}

		$sources = array();
		$issues  = array_values(
			array_filter(
				$local->collection()->issues(),
				static function ( SourceImageIssue $issue ): bool {
					return ! in_array(
						$issue->code(),
						array(
							SourceImageIssue::CODE_SOURCE_MISSING,
							SourceImageIssue::CODE_SOURCE_UNREADABLE,
						),
						true
					);
				}
			)
		);
		$leases  = array();

		foreach ( $this->candidate_records( $attachment_id, $metadata ) as $candidate ) {
			if ( isset( $local_sources[ $candidate['relative_path'] ] ) ) {
				$sources[] = $local_sources[ $candidate['relative_path'] ];
				continue;
			}

			$remote_url = $this->remote_url_for_candidate( $attachment_id, $candidate, $support );

			$resolution = $this->resolver->resolve(
				new LocalSourceResolutionRequest(
					$attachment_id,
					$candidate['size_name'],
					$candidate['role'],
					$candidate['relative_path'],
					$remote_url,
					$candidate['width'],
					$candidate['height'],
					$support
				)
			);

			if ( ! $resolution->is_successful() || ! $resolution->source() instanceof SourceImage || ! $resolution->lease() instanceof TemporarySourceLease ) {
				$issues[] = new SourceImageIssue(
					$attachment_id,
					$candidate['size_name'],
					$candidate['role'],
					$resolution->code(),
					$resolution->message(),
					array(
						'relative_path' => $candidate['relative_path'],
					)
				);
				continue;
			}

			$sources[] = $resolution->source();
			$leases[]  = $resolution->lease();
		}

		return new CollectedSourceSet( new SourceImageCollection( $sources, $issues ), $leases );
	}

	/**
	 * Build authoritative candidate records from attachment metadata.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return array<int,array<string,mixed>>
	 */
	private function candidate_records( int $attachment_id, array $metadata ): array {
		$candidates    = array();
		$metadata_file = isset( $metadata['file'] ) && is_scalar( $metadata['file'] )
			? $this->sanitizer->safe_relative_path( (string) $metadata['file'] )
			: '';
		$metadata_dir  = '' === $metadata_file ? '' : dirname( $metadata_file );
		$metadata_dir  = '.' === $metadata_dir ? '' : trim( str_replace( '\\', '/', $metadata_dir ), '/' );

		if ( '' !== $metadata_file ) {
			$candidates[] = array(
				'size_name'     => 'full',
				'role'          => SourceImage::ROLE_FULL,
				'relative_path' => $metadata_file,
				'width'         => isset( $metadata['width'] ) && is_numeric( $metadata['width'] ) ? (int) $metadata['width'] : null,
				'height'        => isset( $metadata['height'] ) && is_numeric( $metadata['height'] ) ? (int) $metadata['height'] : null,
			);
		}

		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size_name => $size ) {
				if ( ! is_array( $size ) || ! isset( $size['file'] ) || ! is_scalar( $size['file'] ) ) {
					continue;
				}

				$relative = $this->metadata_relative_path(
					$this->sanitizer->safe_relative_path( (string) $size['file'] ),
					$metadata_dir
				);

				if ( '' === $relative ) {
					continue;
				}

				$candidates[] = array(
					'size_name'     => is_string( $size_name ) ? $size_name : 'unknown',
					'role'          => SourceImage::ROLE_SUBSIZE,
					'relative_path' => $relative,
					'width'         => isset( $size['width'] ) && is_numeric( $size['width'] ) ? (int) $size['width'] : null,
					'height'        => isset( $size['height'] ) && is_numeric( $size['height'] ) ? (int) $size['height'] : null,
				);
			}
		}

		if ( isset( $metadata['original_image'] ) && is_scalar( $metadata['original_image'] ) ) {
			$relative = $this->metadata_relative_path(
				$this->sanitizer->safe_relative_path( (string) $metadata['original_image'] ),
				$metadata_dir
			);

			if ( '' !== $relative ) {
				$candidates[] = array(
					'size_name'     => 'original',
					'role'          => SourceImage::ROLE_ORIGINAL,
					'relative_path' => $relative,
					'width'         => null,
					'height'        => null,
				);
			}
		}

		return $candidates;
	}

	/**
	 * Resolve a candidate remote URL.
	 *
	 * @param int                      $attachment_id Attachment ID.
	 * @param array<string,mixed>      $candidate Candidate record.
	 * @param OffloadAttachmentSupport $support Attachment support facts.
	 * @return string
	 */
	private function remote_url_for_candidate( int $attachment_id, array $candidate, OffloadAttachmentSupport $support ): string {
		if ( SourceImage::ROLE_FULL === $candidate['role'] && null !== $support->attachment_url() ) {
			return $support->attachment_url();
		}

		if ( SourceImage::ROLE_SUBSIZE === $candidate['role'] ) {
			$url = $this->runtime->attachment_image_url( $attachment_id, (string) $candidate['size_name'] );

			if ( is_string( $url ) && $this->url_suffix_matches_relative_file( $url, (string) $candidate['relative_path'] ) ) {
				return $url;
			}
		}

		$base = $support->remote_base_url();

		return null === $base ? '' : rtrim( $base, '/' ) . '/' . ltrim( (string) $candidate['relative_path'], '/' );
	}

	/**
	 * Join one basename-only metadata path against the metadata directory.
	 *
	 * @param string $relative_path Relative path.
	 * @param string $metadata_dir Metadata directory.
	 * @return string
	 */
	private function metadata_relative_path( string $relative_path, string $metadata_dir ): string {
		if ( '' === $relative_path ) {
			return '';
		}

		if ( false !== strpos( $relative_path, '/' ) || '' === $metadata_dir ) {
			return $relative_path;
		}

		return trim( $metadata_dir, '/' ) . '/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Determine whether a URL path suffix matches the metadata-relative file.
	 *
	 * @param string $url URL.
	 * @param string $relative_file Relative file.
	 * @return bool
	 */
	private function url_suffix_matches_relative_file( string $url, string $relative_file ): bool {
		$path = function_exists( 'wp_parse_url' )
			? \wp_parse_url( $url, PHP_URL_PATH )
			: parse_url( $url, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Safe fallback outside WordPress bootstrap.

		if ( ! is_string( $path ) || '' === $path ) {
			return false;
		}

		$path          = ltrim( str_replace( '\\', '/', rawurldecode( $path ) ), '/' );
		$relative_file = ltrim( str_replace( '\\', '/', $relative_file ), '/' );

		return '' !== $relative_file
			&& strlen( $path ) >= strlen( $relative_file )
			&& substr( $path, -strlen( $relative_file ) ) === $relative_file;
	}
}
