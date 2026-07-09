<?php
/**
 * Environment probe contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Wraps WordPress/PHP runtime reads for environment inspection.
 */
interface EnvironmentProbeInterface {

	/**
	 * Get the current PHP version.
	 *
	 * @return string
	 */
	public function php_version(): string;

	/**
	 * Get the current WordPress version.
	 *
	 * @return string|null
	 */
	public function wordpress_version(): ?string;

	/**
	 * Get active image editor candidate class names.
	 *
	 * @return string[]
	 */
	public function image_editor_candidates(): array;

	/**
	 * Determine whether a class is available.
	 *
	 * @param string $class_name Class name.
	 * @return bool
	 */
	public function class_available( string $class_name ): bool;

	/**
	 * Determine whether WordPress recognizes a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool|null
	 */
	public function mime_type_recognized( string $mime_type ): ?bool;

	/**
	 * Determine whether WordPress image editors can encode a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool|null
	 */
	public function image_editor_supports_mime( string $mime_type ): ?bool;

	/**
	 * Get WordPress uploads data.
	 *
	 * @return array<string,mixed>|null
	 */
	public function uploads(): ?array;

	/**
	 * Determine whether a path is writable.
	 *
	 * @param string $path Filesystem path.
	 * @return bool|null
	 */
	public function is_writable( string $path ): ?bool;

	/**
	 * Get raw PHP memory limit.
	 *
	 * @return string
	 */
	public function memory_limit(): string;

	/**
	 * Get raw PHP max execution time.
	 *
	 * @return string
	 */
	public function max_execution_time(): string;

	/**
	 * Determine whether Action Scheduler is loaded.
	 *
	 * @return bool
	 */
	public function action_scheduler_loaded(): bool;

	/**
	 * Determine whether Action Scheduler is initialized.
	 *
	 * @return bool|null
	 */
	public function action_scheduler_initialized(): ?bool;
}
