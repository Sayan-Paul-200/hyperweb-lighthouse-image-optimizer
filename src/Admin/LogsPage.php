<?php
/**
 * Logs admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Renders the logs admin tab shell.
 */
final class LogsPage extends AbstractAdminPage {

	/**
	 * Current retention days.
	 *
	 * @var int
	 */
	private $retention_days;

	/**
	 * Create the page.
	 *
	 * @param int $retention_days Current retention days.
	 */
	public function __construct( int $retention_days = 30 ) {
		parent::__construct(
			'Logs',
			'Review paginated plugin logs, filter safe fields, adjust retention, and clear plugin-owned log rows without exposing raw context payloads.'
		);
		$this->retention_days = max( 1, $retention_days );
	}

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'logs';
	}

	/**
	 * Render the logs screen shell.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<div class="hwlio-logs" data-hwlio-logs="root">';
		echo '<div class="hwlio-logs__header">';
		echo '<div class="hwlio-logs__intro">';
		echo '<h2>' . $this->escape_html( $this->title() ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->description() ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-logs__actions">';
		echo '<button type="button" class="button button-secondary" data-hwlio-logs-action="refresh">' . $this->escape_html( $this->translate( 'Refresh Logs' ) ) . '</button>';
		echo '</div>';
		echo '</div>';
		echo '<section class="card hwlio-logs__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Filters' ) ) . '</h3>';
		echo '<form class="hwlio-logs__filters" data-hwlio-logs-filters>';
		echo '<label class="hwlio-logs__field">';
		echo '<span class="hwlio-logs__field-label">' . $this->escape_html( $this->translate( 'Level' ) ) . '</span>';
		echo '<select name="level">';
		echo '<option value="all">' . $this->escape_html( $this->translate( 'All levels' ) ) . '</option>';
		echo '<option value="info">Info</option>';
		echo '<option value="warning">' . $this->escape_html( $this->translate( 'Warning' ) ) . '</option>';
		echo '<option value="error">' . $this->escape_html( $this->translate( 'Error' ) ) . '</option>';
		echo '</select>';
		echo '</label>';
		echo '<label class="hwlio-logs__field">';
		echo '<span class="hwlio-logs__field-label">' . $this->escape_html( $this->translate( 'Exact code' ) ) . '</span>';
		echo '<input type="text" name="code" value="" />';
		echo '</label>';
		echo '<label class="hwlio-logs__field">';
		echo '<span class="hwlio-logs__field-label">' . $this->escape_html( $this->translate( 'Attachment ID' ) ) . '</span>';
		echo '<input type="number" min="1" step="1" name="attachment_id" value="" />';
		echo '</label>';
		echo '<div class="hwlio-logs__filter-actions">';
		echo '<button type="submit" class="button button-primary" data-hwlio-logs-action="apply-filters">' . $this->escape_html( $this->translate( 'Apply Filters' ) ) . '</button>';
		echo '<button type="button" class="button" data-hwlio-logs-action="reset-filters">' . $this->escape_html( $this->translate( 'Reset Filters' ) ) . '</button>';
		echo '</div>';
		echo '</form>';
		echo '</section>';
		echo '<section class="card hwlio-logs__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Retention and Cleanup' ) ) . '</h3>';
		echo '<div class="hwlio-logs__maintenance">';
		echo '<label class="hwlio-logs__field hwlio-logs__field--compact">';
		echo '<span class="hwlio-logs__field-label">' . $this->escape_html( $this->translate( 'Retention days' ) ) . '</span>';
		echo '<input type="number" min="1" step="1" name="retention_days" data-hwlio-logs-retention-input value="' . $this->escape_attr( (string) $this->retention_days ) . '" />';
		echo '</label>';
		echo '<button type="button" class="button" data-hwlio-logs-action="save-retention">' . $this->escape_html( $this->translate( 'Save Retention' ) ) . '</button>';
		echo '<button type="button" class="button button-link-delete" data-hwlio-logs-action="clear-all">' . $this->escape_html( $this->translate( 'Clear All Logs' ) ) . '</button>';
		echo '</div>';
		echo '<p class="description" data-hwlio-logs-delete-status>' . $this->escape_html( $this->translate( 'Clear-all deletion runs in bounded batches when requested.' ) ) . '</p>';
		echo '</section>';
		echo '<section class="card hwlio-logs__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Log Entries' ) ) . '</h3>';
		echo '<div class="hwlio-logs__table-wrap" data-hwlio-logs-table-wrap>';
		echo '<table class="widefat striped hwlio-logs__table">';
		echo '<thead><tr>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Date (GMT)' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Level' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Code' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Message' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Attachment' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Job' ) ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody data-hwlio-logs-body>';
		echo '<tr><td colspan="6" class="hwlio-logs__empty">' . $this->escape_html( $this->translate( 'Logs will load shortly.' ) ) . '</td></tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		echo '<div class="hwlio-logs__pagination" data-hwlio-logs-pagination>';
		echo '<button type="button" class="button" data-hwlio-logs-page="previous" disabled>' . $this->escape_html( $this->translate( 'Previous' ) ) . '</button>';
		echo '<span class="description" data-hwlio-logs-page-status>' . $this->escape_html( $this->translate( 'Log pagination metadata will appear after the first load.' ) ) . '</span>';
		echo '<button type="button" class="button" data-hwlio-logs-page="next" disabled>' . $this->escape_html( $this->translate( 'Next' ) ) . '</button>';
		echo '</div>';
		echo '</section>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
