<?php
/**
 * Dashboard admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Renders the dashboard admin tab shell.
 */
final class DashboardPage extends AbstractAdminPage {

	/**
	 * Create the page.
	 */
	public function __construct() {
		parent::__construct(
			'Dashboard',
			'View queue health, optimization totals, byte savings, and the latest warnings without scanning the whole Media Library on every load.'
		);
	}

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'dashboard';
	}

	/**
	 * Render the dashboard shell.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<div class="hwlio-dashboard" data-hwlio-dashboard="root">';
		echo '<div class="hwlio-dashboard__header">';
		echo '<div class="hwlio-dashboard__intro">';
		echo '<h2>' . $this->escape_html( $this->title() ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->description() ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-dashboard__actions">';
		echo '<button type="button" class="button button-secondary" data-hwlio-dashboard-action="recalculate">';
		echo $this->escape_html( $this->translate( 'Recalculate Statistics' ) );
		echo '</button>';
		echo '<p class="description" data-hwlio-dashboard-refresh-state>' . $this->escape_html( $this->translate( 'Statistics cache status will load shortly.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '<div class="hwlio-dashboard__grid">';
		$this->render_panel(
			'environment',
			$this->translate( 'Environment Status' ),
			$this->translate( 'Checking encoder support, uploads status, and Action Scheduler readiness.' )
		);
		$this->render_panel(
			'queue',
			$this->translate( 'Queue and Attachment Status' ),
			$this->translate( 'Loading cached attachment states and queue availability.' )
		);
		$this->render_panel(
			'savings',
			$this->translate( 'Byte Savings' ),
			$this->translate( 'Loading cached source, generated, and estimated savings totals.' )
		);
		$this->render_panel(
			'failures',
			$this->translate( 'Recent Failures' ),
			$this->translate( 'Loading the most recent warning and error entries.' )
		);
		$this->render_panel(
			'conflicts',
			$this->translate( 'Conflict Warnings' ),
			$this->translate( 'Loading conservative environment and compatibility warnings that may affect optimization reliability.' )
		);
		echo '</div>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render one dashboard panel shell.
	 *
	 * @param string $panel Panel key.
	 * @param string $title Panel title.
	 * @param string $message Placeholder message.
	 * @return void
	 */
	private function render_panel( string $panel, string $title, string $message ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<section class="card hwlio-dashboard__panel" data-hwlio-dashboard-panel="' . $this->escape_attr( $panel ) . '">';
		echo '<h3>' . $this->escape_html( $title ) . '</h3>';
		echo '<div class="hwlio-dashboard__panel-body" data-hwlio-dashboard-body="' . $this->escape_attr( $panel ) . '">';
		echo '<p class="description">' . $this->escape_html( $message ) . '</p>';
		echo '</div>';
		echo '</section>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
