<?php
/**
 * Diagnostics admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Renders the diagnostics admin tab shell.
 */
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
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
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
		echo '<section class="card hwlio-diagnostics__panel hwlio-diagnostics__inventory" data-hwlio-inventory="root">';
		echo '<div class="hwlio-diagnostics__inventory-header">';
		echo '<div class="hwlio-diagnostics__inventory-intro">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Page Inventory' ) ) . '</h3>';
		echo '<p>' . $this->escape_html( $this->translate( 'Inspect one content record at a time to inventory trusted attachment-backed images, unregistered local URLs, external references, and unsupported structured background cases without altering content.' ) ) . '</p>';
		echo '</div>';
		echo '<form class="hwlio-diagnostics__inventory-form" data-hwlio-inventory-form>';
		echo '<label class="hwlio-diagnostics__inventory-field">';
		echo '<span class="hwlio-diagnostics__field-label">' . $this->escape_html( $this->translate( 'Content ID' ) ) . '</span>';
		echo '<input type="number" min="1" step="1" class="small-text" data-hwlio-inventory-input>';
		echo '</label>';
		echo '<button type="submit" class="button button-secondary" data-hwlio-inventory-action="load">' . $this->escape_html( $this->translate( 'Load Inventory' ) ) . '</button>';
		echo '</form>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-content" data-hwlio-inventory-content>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Enter a content ID to load page-level inventory details.' ) ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-summary" data-hwlio-inventory-summary>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Inventory summary counts will appear here.' ) ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-issue-summary" data-hwlio-inventory-issue-summary>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Issue summary counts will appear here after analysis.' ) ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-byte-summary" data-hwlio-inventory-byte-summary>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Measured conversion totals and conservative page-transfer estimates will appear here after analysis.' ) ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-pagespeed" data-hwlio-pagespeed="root">';
		echo '<div class="hwlio-diagnostics__inventory-pagespeed-header">';
		echo '<div class="hwlio-diagnostics__inventory-pagespeed-intro">';
		echo '<h4>' . $this->escape_html( $this->translate( 'PageSpeed Insights' ) ) . '</h4>';
		echo '<p>' . $this->escape_html( $this->translate( 'Load cached PageSpeed Insights results for the selected content record, or run a live request explicitly. Live requests send only the safe public URL and required API parameters to Google and return lab data that may fluctuate.' ) ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-pagespeed-controls">';
		echo '<label class="hwlio-diagnostics__inventory-field">';
		echo '<span class="hwlio-diagnostics__field-label">' . $this->escape_html( $this->translate( 'Strategy' ) ) . '</span>';
		echo '<select data-hwlio-pagespeed-strategy>';
		echo '<option value="mobile">' . $this->escape_html( $this->translate( 'Mobile' ) ) . '</option>';
		echo '<option value="desktop">' . $this->escape_html( $this->translate( 'Desktop' ) ) . '</option>';
		echo '</select>';
		echo '</label>';
		echo '<button type="button" class="button button-secondary" data-hwlio-pagespeed-action="run">' . $this->escape_html( $this->translate( 'Run PageSpeed Insights' ) ) . '</button>';
		echo '</div>';
		echo '</div>';
		echo '<p class="description" data-hwlio-pagespeed-status>' . $this->escape_html( $this->translate( 'Load inventory first to inspect cached PageSpeed Insights data for one content record.' ) ) . '</p>';
		echo '<div data-hwlio-pagespeed-summary>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Normalized lab-data metrics and public URL details will appear here when available.' ) ) . '</p>';
		echo '</div>';
		echo '<div data-hwlio-pagespeed-audits>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Normalized image audit summaries will appear here when available.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-results">';
		echo '<div class="hwlio-diagnostics__inventory-bytes">';
		echo '<h4>' . $this->escape_html( $this->translate( 'Byte Occurrences' ) ) . '</h4>';
		echo '<div data-hwlio-inventory-byte-occurrences>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Per-occurrence page-transfer byte estimates will appear here after a successful lookup.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-issues">';
		echo '<h4>' . $this->escape_html( $this->translate( 'Findings' ) ) . '</h4>';
		echo '<div data-hwlio-inventory-issues>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Conservative image issue findings will appear here after a successful lookup.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-items">';
		echo '<h4>' . $this->escape_html( $this->translate( 'Occurrences' ) ) . '</h4>';
		echo '<div data-hwlio-inventory-items>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Ordered inventory occurrences will appear here after a successful lookup.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '<div class="hwlio-diagnostics__inventory-unsupported">';
		echo '<h4>' . $this->escape_html( $this->translate( 'Unsupported Cases' ) ) . '</h4>';
		echo '<div data-hwlio-inventory-unsupported>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Unsupported or uncertain references will be listed separately.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</section>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render one summary card shell.
	 *
	 * @param string $key Summary key.
	 * @param string $label Visible label.
	 * @return void
	 */
	private function render_summary_card( string $key, string $label ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<div class="hwlio-diagnostics__summary-card" data-hwlio-diagnostics-summary-card="' . $this->escape_attr( $key ) . '">';
		echo '<span class="hwlio-diagnostics__summary-label">' . $this->escape_html( $label ) . '</span>';
		echo '<span class="hwlio-diagnostics__summary-value" data-hwlio-diagnostics-summary-value="' . $this->escape_attr( $key ) . '">0</span>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
