<?php
/**
 * Fake logger.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Logging\LoggerInterface;

/**
 * Captures worker log calls.
 */
final class FakeLogger implements LoggerInterface {

	/**
	 * Log calls.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $entries = array();

	/**
	 * Record a log entry.
	 *
	 * @param string       $level Log level.
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $context Context.
	 * @param int|null     $attachment_id Attachment ID.
	 * @param string|null  $job_id Job ID.
	 * @return bool
	 */
	public function log(
		string $level,
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		$this->entries[] = array(
			'level'         => $level,
			'code'          => $code,
			'message'       => $message,
			'context'       => $context,
			'attachment_id' => $attachment_id,
			'job_id'        => $job_id,
		);

		return true;
	}

	/**
	 * Record a debug log.
	 *
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $context Context.
	 * @param int|null     $attachment_id Attachment ID.
	 * @param string|null  $job_id Job ID.
	 * @return bool
	 */
	public function debug(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( 'debug', $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Record an info log.
	 *
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $context Context.
	 * @param int|null     $attachment_id Attachment ID.
	 * @param string|null  $job_id Job ID.
	 * @return bool
	 */
	public function info(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( 'info', $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Record a warning log.
	 *
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $context Context.
	 * @param int|null     $attachment_id Attachment ID.
	 * @param string|null  $job_id Job ID.
	 * @return bool
	 */
	public function warning(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( 'warning', $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Record an error log.
	 *
	 * @param string       $code Code.
	 * @param string       $message Message.
	 * @param array<mixed> $context Context.
	 * @param int|null     $attachment_id Attachment ID.
	 * @param string|null  $job_id Job ID.
	 * @return bool
	 */
	public function error(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( 'error', $code, $message, $context, $attachment_id, $job_id );
	}
}
