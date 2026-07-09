<?php
/**
 * Uninstall orchestration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Runs uninstall cleanup according to explicit user settings.
 */
final class Uninstaller {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings_repository;

	/**
	 * Derivative cleanup.
	 *
	 * @var DerivativeCleanupInterface
	 */
	private $derivatives;

	/**
	 * Data cleaner.
	 *
	 * @var PluginDataCleanerInterface
	 */
	private $data_cleaner;

	/**
	 * Build a WordPress-backed uninstaller.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		$options = new WordPressOptionStore();

		return new self(
			$options,
			new DerivativeCleanup(
				self::uploads_base_dir(),
				new WordPressFilesystem(),
				new WordPressDerivativeManifestProvider()
			),
			new WordPressPluginDataCleaner( $options ),
			SettingsRepository::for_options( $options )
		);
	}

	/**
	 * Create the uninstaller.
	 *
	 * @param OptionStoreInterface             $options Option store.
	 * @param DerivativeCleanupInterface       $derivatives Derivative cleanup.
	 * @param PluginDataCleanerInterface       $data_cleaner Data cleaner.
	 * @param SettingsRepositoryInterface|null $settings_repository Optional settings repository.
	 */
	public function __construct(
		OptionStoreInterface $options,
		DerivativeCleanupInterface $derivatives,
		PluginDataCleanerInterface $data_cleaner,
		?SettingsRepositoryInterface $settings_repository = null
	) {
		$this->settings_repository = $settings_repository ?? SettingsRepository::for_options( $options );
		$this->derivatives         = $derivatives;
		$this->data_cleaner        = $data_cleaner;
	}

	/**
	 * Run uninstall cleanup.
	 *
	 * @return LifecycleResult
	 */
	public function uninstall(): LifecycleResult {
		$settings        = $this->settings_repository->read();
		$settings_result = $settings->is_valid()
			? LifecycleResult::success( array( LifecycleResult::CODE_UNINSTALL_COMPLETE ) )
			: LifecycleResult::warning(
				array( LifecycleResult::CODE_INVALID_SETTINGS_PRESERVED ),
				array( 'Uninstall settings were invalid; plugin data and derivatives were preserved by default.' )
			);

		$derivative_result = $settings->is_valid() && $this->settings_repository->delete_derivatives_on_uninstall()
			? $this->derivatives->cleanup()
			: LifecycleResult::success( array( LifecycleResult::CODE_DERIVATIVES_PRESERVED ) );

		$data_result = $settings->is_valid() && $this->settings_repository->delete_data_on_uninstall()
			? $this->data_cleaner->cleanup()
			: LifecycleResult::success( array( LifecycleResult::CODE_UNINSTALL_DATA_PRESERVED ) );

		return LifecycleResult::combine(
			$settings_result,
			$derivative_result,
			$data_result,
			LifecycleResult::success( array( LifecycleResult::CODE_UNINSTALL_COMPLETE ) )
		);
	}

	/**
	 * Resolve the uploads base directory.
	 *
	 * @return string
	 */
	private static function uploads_base_dir(): string {
		if ( ! function_exists( 'wp_get_upload_dir' ) ) {
			return '';
		}

		$uploads = \wp_get_upload_dir();

		if ( ! is_array( $uploads ) || empty( $uploads['basedir'] ) || ! is_string( $uploads['basedir'] ) ) {
			return '';
		}

		return $uploads['basedir'];
	}
}
