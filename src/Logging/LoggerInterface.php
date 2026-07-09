<?php
/**
 * Logger contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Records bounded, sanitized plugin events.
 */
interface LoggerInterface {

	/**
	 * Record a log entry.
	 *
	 * @param string       $level Log level.
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function log(
		string $level,
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool;

	/**
	 * Record a debug entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function debug(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool;

	/**
	 * Record an informational entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function info(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool;

	/**
	 * Record a warning entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function warning(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool;

	/**
	 * Record an error entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function error(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool;
}
