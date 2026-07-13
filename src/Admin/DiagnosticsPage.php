<?php
/**
 * Diagnostics admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

final class DiagnosticsPage extends AbstractAdminPage {

	/**
	 * Create the page.
	 */
	public function __construct() {
		parent::__construct(
			'Diagnostics',
			'Diagnostics rendering and interactive checks will be added after the shell, assets, and REST foundations are in place.'
		);
	}

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'diagnostics';
	}

	/**
	 * Render the diagnostics screen shell.
	 *
	 * @return void
	 */
	public function render(): void {
		echo '<div class="hwlio-diagnostics" data-hwlio-diagnostics="root">';
		echo '<div class="hwlio-diagnostics__header">';
		echo '<div class="hwlio-diagnostics__intro">';
		echo '<h2>' . $this->escape_html( $this->title() ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->translate( 'Run safe environment checks, inspect structured results, and copy stable diagnostic codes without exposing raw server paths or stack traces.' ) ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__actions">';
		echo '<button type="button" class="button button-secondary" data-hwlio-diagnostics-action="refresh">' . $this->escape_html( $this->translate( 'Refresh Diagnostics' ) ) . '</button>';
		echo '</div>';
		echo '</div>';
		echo '<section class="card hwlio-diagnostics__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Summary' ) ) . '</h3>';
		echo '<div class="hwlio-diagnostics__summary" data-hwlio-diagnostics-summary>';
		$this->render_summary_card( 'total', $this->translate( 'Total Checks' ) );
		$this->render_summary_card( 'pass', $this->translate( 'Pass' ) );
		$this->render_summary_card( 'warning', $this->translate( 'Warnings' ) );
		$this->render_summary_card( 'fail', $this->translate( 'Failures' ) );
		$this->render_summary_card( 'info', $this->translate( 'Info' ) );
		echo '</div>';
		echo '</section>';
		echo '<section class="card hwlio-diagnostics__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Checks' ) ) . '</h3>';
		echo '<div class="hwlio-diagnostics__groups" data-hwlio-diagnostics-groups>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Structured diagnostics will load shortly.' ) ) . '</p>';
		echo '</div>';
		echo '</section>';
		echo '</div>';
	}

	/**
	 * Render one summary card shell.
	 *
	 * @param string $key Summary key.
	 * @param string $label Visible label.
	 * @return void
	 */
	private function render_summary_card( string $key, string $label ): void {
		echo '<div class="hwlio-diagnostics__summary-card" data-hwlio-diagnostics-summary-card="' . $this->escape_attr( $key ) . '">';
		echo '<span class="hwlio-diagnostics__summary-label">' . $this->escape_html( $label ) . '</span>';
		echo '<span class="hwlio-diagnostics__summary-value" data-hwlio-diagnostics-summary-value="' . $this->escape_attr( $key ) . '">0</span>';
		echo '</div>';
	}
}
