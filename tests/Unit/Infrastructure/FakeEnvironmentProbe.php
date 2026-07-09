<?php
/**
 * Fake environment probe.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentProbeInterface;

/**
 * Provides deterministic environment facts for unit tests.
 */
final class FakeEnvironmentProbe implements EnvironmentProbeInterface {

	/**
	 * PHP version.
	 *
	 * @var string
	 */
	public $php_version = '8.1.25';

	/**
	 * WordPress version.
	 *
	 * @var string|null
	 */
	public $wordpress_version = '6.5';

	/**
	 * Image editor candidates.
	 *
	 * @var string[]
	 */
	public $image_editor_candidates = array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );

	/**
	 * Class availability map.
	 *
	 * @var array<string,bool>
	 */
	public $class_available = array(
		'WP_Image_Editor_Imagick' => true,
		'WP_Image_Editor_GD'      => true,
	);

	/**
	 * MIME recognition map.
	 *
	 * @var array<string,bool|null>
	 */
	public $mime_recognized = array(
		'image/webp' => true,
		'image/avif' => true,
	);

	/**
	 * MIME encoding support map.
	 *
	 * @var array<string,bool|null>
	 */
	public $mime_support = array(
		'image/webp' => true,
		'image/avif' => true,
	);

	/**
	 * Uploads data.
	 *
	 * @var array<string,mixed>|null
	 */
	public $uploads = array(
		'basedir' => '/tmp/uploads',
		'error'   => false,
	);

	/**
	 * Writability map.
	 *
	 * @var array<string,bool|null>
	 */
	public $writable = array(
		'/tmp/uploads' => true,
	);

	/**
	 * Memory limit.
	 *
	 * @var string
	 */
	public $memory_limit = '256M';

	/**
	 * Max execution time.
	 *
	 * @var string
	 */
	public $max_execution_time = '30';

	/**
	 * Whether Action Scheduler is loaded.
	 *
	 * @var bool
	 */
	public $action_scheduler_loaded = true;

	/**
	 * Whether Action Scheduler is initialized.
	 *
	 * @var bool|null
	 */
	public $action_scheduler_initialized = true;

	/**
	 * Get the current PHP version.
	 *
	 * @return string
	 */
	public function php_version(): string {
		return $this->php_version;
	}

	/**
	 * Get the current WordPress version.
	 *
	 * @return string|null
	 */
	public function wordpress_version(): ?string {
		return $this->wordpress_version;
	}

	/**
	 * Get active image editor candidate class names.
	 *
	 * @return string[]
	 */
	public function image_editor_candidates(): array {
		return $this->image_editor_candidates;
	}

	/**
	 * Determine whether a class is available.
	 *
	 * @param string $class_name Class name.
	 * @return bool
	 */
	public function class_available( string $class_name ): bool {
		return $this->class_available[ $class_name ] ?? false;
	}

	/**
	 * Determine whether WordPress recognizes a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool|null
	 */
	public function mime_type_recognized( string $mime_type ): ?bool {
		return array_key_exists( $mime_type, $this->mime_recognized )
			? $this->mime_recognized[ $mime_type ]
			: null;
	}

	/**
	 * Determine whether WordPress image editors can encode a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool|null
	 */
	public function image_editor_supports_mime( string $mime_type ): ?bool {
		return array_key_exists( $mime_type, $this->mime_support )
			? $this->mime_support[ $mime_type ]
			: null;
	}

	/**
	 * Get WordPress uploads data.
	 *
	 * @return array<string,mixed>|null
	 */
	public function uploads(): ?array {
		return $this->uploads;
	}

	/**
	 * Determine whether a path is writable.
	 *
	 * @param string $path Filesystem path.
	 * @return bool|null
	 */
	public function is_writable( string $path ): ?bool {
		return $this->writable[ $path ] ?? null;
	}

	/**
	 * Get raw PHP memory limit.
	 *
	 * @return string
	 */
	public function memory_limit(): string {
		return $this->memory_limit;
	}

	/**
	 * Get raw PHP max execution time.
	 *
	 * @return string
	 */
	public function max_execution_time(): string {
		return $this->max_execution_time;
	}

	/**
	 * Determine whether Action Scheduler is loaded.
	 *
	 * @return bool
	 */
	public function action_scheduler_loaded(): bool {
		return $this->action_scheduler_loaded;
	}

	/**
	 * Determine whether Action Scheduler is initialized.
	 *
	 * @return bool|null
	 */
	public function action_scheduler_initialized(): ?bool {
		return $this->action_scheduler_initialized;
	}
}
