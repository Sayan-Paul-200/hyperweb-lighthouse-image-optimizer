<?php
/**
 * Media attachment summary value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

/**
 * Carries lightweight Media Library status and action data for one attachment.
 */
final class MediaAttachmentSummary {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Summary state.
	 *
	 * @var string
	 */
	private $state;

	/**
	 * Human-readable status label.
	 *
	 * @var string
	 */
	private $status_label;

	/**
	 * Ready formats.
	 *
	 * @var string[]
	 */
	private $ready_formats;

	/**
	 * Whether excluded.
	 *
	 * @var bool
	 */
	private $excluded;

	/**
	 * Allowed action slugs.
	 *
	 * @var string[]
	 */
	private $allowed_actions;

	/**
	 * Whether the summary should be actively polled.
	 *
	 * @var bool
	 */
	private $active;

	/**
	 * Create the summary.
	 *
	 * @param int      $attachment_id Attachment ID.
	 * @param string   $state State.
	 * @param string   $status_label Status label.
	 * @param string[] $ready_formats Ready formats.
	 * @param bool     $excluded Whether excluded.
	 * @param string[] $allowed_actions Allowed action slugs.
	 * @param bool     $active Whether the summary is active.
	 */
	public function __construct(
		int $attachment_id,
		string $state,
		string $status_label,
		array $ready_formats,
		bool $excluded,
		array $allowed_actions,
		bool $active
	) {
		$this->attachment_id   = max( 0, $attachment_id );
		$this->state           = $state;
		$this->status_label    = $status_label;
		$this->ready_formats   = array_values( $ready_formats );
		$this->excluded        = $excluded;
		$this->allowed_actions = array_values( $allowed_actions );
		$this->active          = $active;
	}

	/**
	 * Get the attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get the state.
	 *
	 * @return string
	 */
	public function state(): string {
		return $this->state;
	}

	/**
	 * Get the status label.
	 *
	 * @return string
	 */
	public function status_label(): string {
		return $this->status_label;
	}

	/**
	 * Get ready formats.
	 *
	 * @return string[]
	 */
	public function ready_formats(): array {
		return $this->ready_formats;
	}

	/**
	 * Whether excluded.
	 *
	 * @return bool
	 */
	public function excluded(): bool {
		return $this->excluded;
	}

	/**
	 * Get allowed action slugs.
	 *
	 * @return string[]
	 */
	public function allowed_actions(): array {
		return $this->allowed_actions;
	}

	/**
	 * Whether polling should continue.
	 *
	 * @return bool
	 */
	public function active(): bool {
		return $this->active;
	}

	/**
	 * Serialize the summary for HTML/bootstrap payloads.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		$attachment_id = $this->attachment_id();

		return array(
			'attachmentId'   => $attachment_id,
			'state'          => $this->state(),
			'statusLabel'    => $this->status_label(),
			'readyFormats'   => $this->ready_formats(),
			'excluded'       => $this->excluded(),
			'allowedActions' => $this->allowed_actions(),
			'active'         => $this->active(),
			'routes'         => array(
				'details'   => '/attachments/' . $attachment_id,
				'optimize'  => '/attachments/' . $attachment_id . '/optimize',
				'retry'     => '/attachments/' . $attachment_id . '/retry',
				'reconcile' => '/attachments/' . $attachment_id . '/reconcile',
				'exclude'   => '/attachments/' . $attachment_id . '/exclude',
				'include'   => '/attachments/' . $attachment_id . '/include',
			),
		);
	}
}
