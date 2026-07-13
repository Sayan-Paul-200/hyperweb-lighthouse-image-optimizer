<?php
/**
 * Admin notice and live-region helper.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Owns the shared PHP/JS identifiers for admin notices and announcements.
 */
final class NoticeManager {

	/**
	 * Stable app mount ID.
	 *
	 * @var string
	 */
	public const APP_ID = 'hwlio-admin-app';

	/**
	 * Notice stack container ID.
	 *
	 * @var string
	 */
	public const NOTICE_CONTAINER_ID = 'hwlio-admin-notices';

	/**
	 * Polite live-region ID.
	 *
	 * @var string
	 */
	public const POLITE_REGION_ID = 'hwlio-admin-live-polite';

	/**
	 * Assertive live-region ID.
	 *
	 * @var string
	 */
	public const ASSERTIVE_REGION_ID = 'hwlio-admin-live-assertive';

	/**
	 * Get the mount ID.
	 *
	 * @return string
	 */
	public function app_id(): string {
		return self::APP_ID;
	}

	/**
	 * Get the notice container ID.
	 *
	 * @return string
	 */
	public function notice_container_id(): string {
		return self::NOTICE_CONTAINER_ID;
	}

	/**
	 * Get the polite live-region ID.
	 *
	 * @return string
	 */
	public function polite_region_id(): string {
		return self::POLITE_REGION_ID;
	}

	/**
	 * Get the assertive live-region ID.
	 *
	 * @return string
	 */
	public function assertive_region_id(): string {
		return self::ASSERTIVE_REGION_ID;
	}

	/**
	 * Get shared selectors for the JS bootstrap payload.
	 *
	 * @return array<string,string>
	 */
	public function selectors(): array {
		return array(
			'app'       => '#' . $this->app_id(),
			'notices'   => '#' . $this->notice_container_id(),
			'polite'    => '#' . $this->polite_region_id(),
			'assertive' => '#' . $this->assertive_region_id(),
		);
	}

	/**
	 * Render the notice and live-region containers.
	 *
	 * @return void
	 */
	public function render_containers(): void {
		echo '<div id="' . $this->escape_attr( $this->notice_container_id() ) . '" class="hwlio-admin-notices" aria-live="polite"></div>';
		echo '<div id="' . $this->escape_attr( $this->polite_region_id() ) . '" class="hwlio-admin-live-region" aria-live="polite" aria-atomic="true"></div>';
		echo '<div id="' . $this->escape_attr( $this->assertive_region_id() ) . '" class="hwlio-admin-live-region" aria-live="assertive" aria-atomic="true"></div>';
	}

	/**
	 * Escape one HTML attribute.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	private function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
