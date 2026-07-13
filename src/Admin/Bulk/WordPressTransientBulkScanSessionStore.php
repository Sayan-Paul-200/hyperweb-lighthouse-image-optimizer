<?php
/**
 * Transient-backed bulk scan session store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\TransientStoreInterface;

/**
 * Persists resumable bulk scan sessions in transient-backed metadata and chunks.
 */
final class WordPressTransientBulkScanSessionStore implements BulkScanSessionStoreInterface {

	public const TTL_SECONDS = 21600;
	public const CHUNK_SIZE  = 50;

	/**
	 * Transient store.
	 *
	 * @var TransientStoreInterface
	 */
	private $transients;

	/**
	 * Create the session store.
	 *
	 * @param TransientStoreInterface $transients Transient store.
	 */
	public function __construct( TransientStoreInterface $transients ) {
		$this->transients = $transients;
	}

	/**
	 * Load one stored session.
	 *
	 * @param string $token Scan token.
	 * @return BulkScanSession|null
	 */
	public function load( string $token ): ?BulkScanSession {
		$token   = BulkScanSession::normalize_token( $token );
		$session = BulkScanSession::from_array( $this->transients->get( $this->meta_key( $token ) ) );

		if ( $session instanceof BulkScanSession ) {
			return $session;
		}

		$this->delete( $token );

		return null;
	}

	/**
	 * Save one session metadata snapshot.
	 *
	 * @param BulkScanSession $session Session.
	 * @return bool
	 */
	public function save( BulkScanSession $session ): bool {
		return $this->transients->set(
			$this->meta_key( $session->token() ),
			$session->to_array(),
			self::TTL_SECONDS
		);
	}

	/**
	 * Delete one stored session and its candidate chunks.
	 *
	 * @param string $token Scan token.
	 * @return void
	 */
	public function delete( string $token ): void {
		$token        = BulkScanSession::normalize_token( $token );
		$chunk_count  = 0;
		$stored       = BulkScanSession::from_array( $this->transients->get( $this->meta_key( $token ) ) );

		if ( $stored instanceof BulkScanSession ) {
			$chunk_count = $stored->progress()->candidate_chunk_count();
		}

		$this->transients->delete( $this->meta_key( $token ) );

		for ( $index = 0; $index < $chunk_count; ++$index ) {
			$this->transients->delete( $this->chunk_key( $token, $index ) );
		}
	}

	/**
	 * Append one candidate-ID batch and return updated session progress.
	 *
	 * @param BulkScanSession $session Session.
	 * @param int[]           $attachment_ids Candidate attachment IDs.
	 * @return BulkScanSession
	 */
	public function append_candidate_ids( BulkScanSession $session, array $attachment_ids ): BulkScanSession {
		$ids = array_values(
			array_filter(
				array_map( 'intval', $attachment_ids ),
				static function ( int $attachment_id ): bool {
					return 0 < $attachment_id;
				}
			)
		);

		if ( array() === $ids ) {
			return $session;
		}

		$progress            = $session->progress();
		$chunk_count         = $progress->candidate_chunk_count();
		$total               = $progress->candidate_total();
		$chunk_index         = max( 0, $chunk_count - 1 );
		$buffer              = 0 < $chunk_count ? $this->read_chunk( $session->token(), $chunk_index ) : array();
		$writing_partial     = 0 < $chunk_count && count( $buffer ) < self::CHUNK_SIZE;

		if ( ! $writing_partial ) {
			$buffer      = array();
			$chunk_index = $chunk_count;
		}

		foreach ( $ids as $attachment_id ) {
			$buffer[] = $attachment_id;
			++$total;

			if ( count( $buffer ) < self::CHUNK_SIZE ) {
				continue;
			}

			$this->write_chunk( $session->token(), $chunk_index, $buffer );

			if ( ! $writing_partial ) {
				++$chunk_count;
			}

			$buffer          = array();
			$writing_partial = false;
			$chunk_index     = $chunk_count;
		}

		if ( array() !== $buffer ) {
			$this->write_chunk( $session->token(), $chunk_index, $buffer );

			if ( $chunk_count === $chunk_index ) {
				++$chunk_count;
			}
		}

		return $session->with_progress(
			$progress->with_candidates( $chunk_count, $total ),
			$session->updated_at_gmt()
		);
	}

	/**
	 * Read one bounded candidate preview page.
	 *
	 * @param BulkScanSession $session Session.
	 * @param int             $page Page number.
	 * @param int             $per_page Page size.
	 * @return int[]
	 */
	public function read_candidate_page( BulkScanSession $session, int $page, int $per_page ): array {
		$page         = max( 1, $page );
		$per_page     = max( 1, min( self::CHUNK_SIZE, $per_page ) );
		$offset       = ( $page - 1 ) * $per_page;
		$chunk_index  = (int) floor( $offset / self::CHUNK_SIZE );
		$chunk_offset = $offset % self::CHUNK_SIZE;
		$remaining    = $per_page;
		$ids          = array();

		while ( 0 < $remaining && $chunk_index < $session->progress()->candidate_chunk_count() ) {
			$chunk = $this->read_chunk( $session->token(), $chunk_index );

			if ( array() === $chunk ) {
				break;
			}

			$slice = array_slice( $chunk, $chunk_offset, $remaining );

			foreach ( $slice as $attachment_id ) {
				if ( is_numeric( $attachment_id ) && 0 < (int) $attachment_id ) {
					$ids[] = (int) $attachment_id;
					--$remaining;
				}
			}

			$chunk_offset = 0;
			++$chunk_index;
		}

		return $ids;
	}

	/**
	 * Build one session metadata key.
	 *
	 * @param string $token Scan token.
	 * @return string
	 */
	private function meta_key( string $token ): string {
		return 'hwlio_scan_session_' . $token;
	}

	/**
	 * Build one session chunk key.
	 *
	 * @param string $token Scan token.
	 * @param int    $index Chunk index.
	 * @return string
	 */
	private function chunk_key( string $token, int $index ): string {
		return 'hwlio_scan_session_' . $token . '_chunk_' . max( 0, $index );
	}

	/**
	 * Read one candidate chunk.
	 *
	 * @param string $token Scan token.
	 * @param int    $index Chunk index.
	 * @return int[]
	 */
	private function read_chunk( string $token, int $index ): array {
		$value = $this->transients->get( $this->chunk_key( $token, $index ) );

		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'intval', $value ),
				static function ( int $attachment_id ): bool {
					return 0 < $attachment_id;
				}
			)
		);
	}

	/**
	 * Persist one candidate chunk.
	 *
	 * @param string $token Scan token.
	 * @param int    $index Chunk index.
	 * @param int[]  $chunk Candidate IDs.
	 * @return void
	 */
	private function write_chunk( string $token, int $index, array $chunk ): void {
		if ( ! $this->transients->set(
			$this->chunk_key( $token, $index ),
			array_values(
				array_slice(
					array_map( 'intval', $chunk ),
					0,
					self::CHUNK_SIZE
				)
			),
			self::TTL_SECONDS
		) ) {
			throw new \RuntimeException( 'Bulk scan candidate chunk could not be persisted.' );
		}
	}
}
