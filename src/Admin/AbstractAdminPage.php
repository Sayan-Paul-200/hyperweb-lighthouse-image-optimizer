<?php
/**
 * Base admin page.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Provides common title and description storage for shell pages.
 */
abstract class AbstractAdminPage implements AdminPageInterface {

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	abstract public function slug(): string;

	/**
	 * Raw page title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Raw page description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Create the page.
	 *
	 * @param string $title Visible title.
	 * @param string $description Placeholder description.
	 */
	public function __construct( string $title, string $description ) {
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Get the visible page title.
	 *
	 * @return string
	 */
	public function title(): string {
		return $this->translate( $this->title );
	}

	/**
	 * Get the placeholder body copy.
	 *
	 * @return string
	 */
	public function description(): string {
		return $this->translate( $this->description );
	}

	/**
	 * Render the default placeholder card body.
	 *
	 * @return void
	 */
	public function render(): void {
		echo '<div class="card">';
		echo '<h2>' . $this->escape_html( $this->title() ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->description() ) . '</p>';
		echo '</div>';
	}

	/**
	 * Translate one plugin-owned string.
	 *
	 * @param string $text Raw English string.
	 * @return string
	 */
	protected function translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}

	/**
	 * Escape HTML text for admin output.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	protected function escape_html( string $text ): string {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $text );
		}

		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape one HTML attribute for admin output.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	protected function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
