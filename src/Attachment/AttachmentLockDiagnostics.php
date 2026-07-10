<?php
/**
 * Attachment lock diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticReport;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticSanitizer;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;

/**
 * Builds structured diagnostics for attachment locks.
 */
final class AttachmentLockDiagnostics {

	/**
	 * Scanner.
	 *
	 * @var AttachmentLockScannerInterface
	 */
	private $scanner;

	/**
	 * Meta store.
	 *
	 * @var AttachmentMetaStoreInterface
	 */
	private $meta;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface
	 */
	private $clock;

	/**
	 * Sanitizer.
	 *
	 * @var DiagnosticSanitizer
	 */
	private $sanitizer;

	/**
	 * Build WordPress-backed diagnostics.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressAttachmentLockScanner(),
			new WordPressAttachmentMetaStore(),
			new SystemAttachmentClock(),
			new DiagnosticSanitizer()
		);
	}

	/**
	 * Create diagnostics.
	 *
	 * @param AttachmentLockScannerInterface $scanner Scanner.
	 * @param AttachmentMetaStoreInterface   $meta Meta store.
	 * @param AttachmentClockInterface       $clock Clock.
	 * @param DiagnosticSanitizer            $sanitizer Sanitizer.
	 */
	public function __construct(
		AttachmentLockScannerInterface $scanner,
		AttachmentMetaStoreInterface $meta,
		AttachmentClockInterface $clock,
		DiagnosticSanitizer $sanitizer
	) {
		$this->scanner   = $scanner;
		$this->meta      = $meta;
		$this->clock     = $clock;
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Run diagnostics.
	 *
	 * @param int $limit Maximum locks to scan.
	 * @return DiagnosticReport
	 */
	public function run( int $limit = AttachmentLockManager::RECOVERY_LIMIT ): DiagnosticReport {
		$limit          = max( 1, min( AttachmentLockManager::RECOVERY_LIMIT, $limit ) );
		$attachment_ids = $this->scanner->locked_attachment_ids( $limit );
		$active         = 0;
		$stale          = 0;
		$invalid        = 0;
		$missing        = 0;
		$sample_ids     = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			$raw_lock      = $this->meta->get( $attachment_id, LifecyclePolicy::META_LOCK, null );

			if ( null === $raw_lock ) {
				++$missing;
				continue;
			}

			$sample_ids[] = $attachment_id;
			$lock         = AttachmentLock::from_stored( $raw_lock );

			if ( ! $lock instanceof AttachmentLock ) {
				++$invalid;
				continue;
			}

			if ( $lock->is_expired( $this->clock->now() ) ) {
				++$stale;
				continue;
			}

			++$active;
		}

		return new DiagnosticReport(
			array(
				$this->sanitizer->sanitize_result(
					$this->result(
						count( $attachment_ids ),
						$active,
						$stale,
						$invalid,
						$missing,
						$sample_ids
					)
				),
			)
		);
	}

	/**
	 * Build diagnostic result.
	 *
	 * @param int   $scanned Scanned count.
	 * @param int   $active Active count.
	 * @param int   $stale Stale count.
	 * @param int   $invalid Invalid count.
	 * @param int   $missing Missing count.
	 * @param int[] $sample_ids Sample attachment IDs.
	 * @return DiagnosticResult
	 */
	private function result(
		int $scanned,
		int $active,
		int $stale,
		int $invalid,
		int $missing,
		array $sample_ids
	): DiagnosticResult {
		$status  = DiagnosticStatus::PASS;
		$code    = 'attachment_locks_clear';
		$message = 'No attachment locks were found.';

		if ( 0 < $stale || 0 < $invalid ) {
			$status  = DiagnosticStatus::WARNING;
			$code    = 'attachment_locks_need_recovery';
			$message = 'Some attachment locks are stale or invalid and can be recovered.';
		} elseif ( 0 < $active ) {
			$status  = DiagnosticStatus::INFO;
			$code    = 'attachment_locks_active';
			$message = 'Active attachment locks are present.';
		}

		return new DiagnosticResult(
			'attachment_locks',
			$status,
			$code,
			'Attachment locks',
			$message,
			array(
				'scanned'               => $scanned,
				'active'                => $active,
				'stale'                 => $stale,
				'invalid'               => $invalid,
				'missing'               => $missing,
				'sample_attachment_ids' => array_slice( array_values( array_unique( $sample_ids ) ), 0, 10 ),
			)
		);
	}
}
