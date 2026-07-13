<?php
/**
 * Bulk optimize admin tab.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin;

/**
 * Renders the bulk optimization admin tab shell.
 */
final class BulkPage extends AbstractAdminPage {

	/**
	 * Create the page.
	 */
	public function __construct() {
		parent::__construct(
			'Bulk Optimize',
			'Run bounded dry-run scans across the Media Library, review eligible candidates, and prepare for queue controls that arrive in the next subphase.'
		);
	}

	/**
	 * Get the stable tab slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'bulk-optimize';
	}

	/**
	 * Render the bulk scan shell.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<div class="hwlio-bulk" data-hwlio-bulk="root">';
		echo '<div class="hwlio-bulk__header">';
		echo '<div class="hwlio-bulk__intro">';
		echo '<h2>' . $this->escape_html( $this->title() ) . '</h2>';
		echo '<p>' . $this->escape_html( $this->description() ) . '</p>';
		echo '</div>';
		echo '<div class="hwlio-bulk__actions">';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Dry-run scans never generate sidecars or queue conversions in Subphase 6.6.' ) ) . '</p>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Queue controls operate only on completed dry-run scan sessions and respect the global pause state.' ) ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '<section class="card hwlio-bulk__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Dry-Run Scan Filters' ) ) . '</h3>';
		echo '<form class="hwlio-bulk__form" data-hwlio-bulk-form>';
		echo '<div class="hwlio-bulk__field-grid">';
		$this->render_select_field(
			'scan_scope',
			$this->translate( 'Scope' ),
			array(
				'all_eligible' => $this->translate( 'All eligible images' ),
				'missing_only' => $this->translate( 'Missing derivatives only' ),
				'failed_only'  => $this->translate( 'Failed attachments only' ),
				'stale_only'   => $this->translate( 'Stale attachments only' ),
			)
		);
		$this->render_select_field(
			'target_format',
			$this->translate( 'Target format' ),
			array(
				'all_enabled' => $this->translate( 'All enabled formats' ),
				'webp'        => 'WebP',
				'avif'        => 'AVIF',
			)
		);
		$this->render_date_field( 'date_from', $this->translate( 'Uploaded after' ) );
		$this->render_date_field( 'date_to', $this->translate( 'Uploaded before' ) );
		$this->render_text_field(
			'attachment_ids',
			$this->translate( 'Attachment IDs' ),
			$this->translate( 'Optional comma-separated attachment IDs.' )
		);
		echo '</div>';
		echo '<div class="hwlio-bulk__form-actions">';
		echo '<button type="submit" class="button button-primary" data-hwlio-bulk-action="scan">' . $this->escape_html( $this->translate( 'Run Dry-Run Scan' ) ) . '</button>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Excluded attachments are skipped in this subphase and can be included manually from the Media Library first.' ) ) . '</p>';
		echo '</div>';
		echo '</form>';
		echo '</section>';
		echo '<section class="card hwlio-bulk__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Queue Controls' ) ) . '</h3>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Pause prevents new plugin work from being queued or started. Running actions may finish safely.' ) ) . '</p>';
		echo '<div class="hwlio-bulk__control-actions">';
		echo '<button type="button" class="button button-primary" data-hwlio-bulk-action="queue">' . $this->escape_html( $this->translate( 'Queue Current Scan Results' ) ) . '</button>';
		echo '<button type="button" class="button" data-hwlio-bulk-action="retry">' . $this->escape_html( $this->translate( 'Retry Failed Current Scan Results' ) ) . '</button>';
		echo '<button type="button" class="button" data-hwlio-bulk-action="pause">' . $this->escape_html( $this->translate( 'Pause Queue' ) ) . '</button>';
		echo '<button type="button" class="button" data-hwlio-bulk-action="resume">' . $this->escape_html( $this->translate( 'Resume Queue' ) ) . '</button>';
		echo '<button type="button" class="button" data-hwlio-bulk-action="cancel">' . $this->escape_html( $this->translate( 'Cancel Pending Jobs' ) ) . '</button>';
		echo '</div>';
		echo '<p class="description" data-hwlio-bulk-queue-status>' . $this->escape_html( $this->translate( 'Queue controls are idle until a dry-run scan completes.' ) ) . '</p>';
		echo '<div class="hwlio-bulk__summary hwlio-bulk__summary--queue" data-hwlio-bulk-queue-summary>';
		$this->render_summary_card( 'queued', $this->translate( 'Queued' ), 'queue' );
		$this->render_summary_card( 'already_queued', $this->translate( 'Already Queued' ), 'queue' );
		$this->render_summary_card( 'already_optimized', $this->translate( 'Already Optimized' ), 'queue' );
		$this->render_summary_card( 'skipped', $this->translate( 'Skipped' ), 'queue' );
		$this->render_summary_card( 'failed_to_queue', $this->translate( 'Failed to Queue' ), 'queue' );
		echo '</div>';
		echo '<div class="hwlio-bulk__control-meta" data-hwlio-bulk-control-meta>';
		echo '<span data-hwlio-bulk-control-pending>' . $this->escape_html( $this->translate( 'Pending jobs: 0' ) ) . '</span>';
		echo '<span data-hwlio-bulk-control-running>' . $this->escape_html( $this->translate( 'Running jobs: 0' ) ) . '</span>';
		echo '</div>';
		echo '</section>';
		echo '<section class="card hwlio-bulk__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Scan Progress' ) ) . '</h3>';
		echo '<p class="description" data-hwlio-bulk-status>' . $this->escape_html( $this->translate( 'No dry-run scan has started yet.' ) ) . '</p>';
		echo '<div class="hwlio-bulk__summary" data-hwlio-bulk-summary>';
		$this->render_summary_card( 'scanned', $this->translate( 'Scanned' ) );
		$this->render_summary_card( 'eligible', $this->translate( 'Eligible' ) );
		$this->render_summary_card( 'excluded', $this->translate( 'Excluded' ) );
		$this->render_summary_card( 'active', $this->translate( 'Active' ) );
		$this->render_summary_card( 'already_optimized', $this->translate( 'Already Optimized' ) );
		$this->render_summary_card( 'skipped', $this->translate( 'Skipped' ) );
		echo '</div>';
		echo '</section>';
		echo '<section class="card hwlio-bulk__panel">';
		echo '<h3>' . $this->escape_html( $this->translate( 'Eligible Candidate Preview' ) ) . '</h3>';
		echo '<p class="description">' . $this->escape_html( $this->translate( 'Candidate previews stay lightweight and use `_hwlio_status` only in this subphase.' ) ) . '</p>';
		echo '<div class="hwlio-bulk__preview" data-hwlio-bulk-preview>';
		echo '<table class="widefat striped hwlio-bulk__table">';
		echo '<thead><tr>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Attachment' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Uploaded' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Status' ) ) . '</th>';
		echo '<th scope="col">' . $this->escape_html( $this->translate( 'Ready Formats' ) ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody data-hwlio-bulk-preview-body>';
		echo '<tr><td colspan="4" class="hwlio-bulk__empty">' . $this->escape_html( $this->translate( 'Run a dry-run scan to load eligible candidates.' ) ) . '</td></tr>';
		echo '</tbody>';
		echo '</table>';
		echo '<div class="hwlio-bulk__pagination" data-hwlio-bulk-pagination>';
		echo '<button type="button" class="button" data-hwlio-bulk-page="previous" disabled>' . $this->escape_html( $this->translate( 'Previous' ) ) . '</button>';
		echo '<span class="description" data-hwlio-bulk-page-status>' . $this->escape_html( $this->translate( 'Preview pagination will appear after a scan completes.' ) ) . '</span>';
		echo '<button type="button" class="button" data-hwlio-bulk-page="next" disabled>' . $this->escape_html( $this->translate( 'Next' ) ) . '</button>';
		echo '</div>';
		echo '</div>';
		echo '</section>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render one select field.
	 *
	 * @param string               $name Field name.
	 * @param string               $label Visible label.
	 * @param array<string,string> $options Select options.
	 * @return void
	 */
	private function render_select_field( string $name, string $label, array $options ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<label class="hwlio-bulk__field">';
		echo '<span class="hwlio-bulk__field-label">' . $this->escape_html( $label ) . '</span>';
		echo '<select name="' . $this->escape_attr( $name ) . '">';
		foreach ( $options as $value => $text ) {
			echo '<option value="' . $this->escape_attr( $value ) . '">' . $this->escape_html( $text ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render one date field.
	 *
	 * @param string $name Field name.
	 * @param string $label Visible label.
	 * @return void
	 */
	private function render_date_field( string $name, string $label ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<label class="hwlio-bulk__field">';
		echo '<span class="hwlio-bulk__field-label">' . $this->escape_html( $label ) . '</span>';
		echo '<input type="date" name="' . $this->escape_attr( $name ) . '" />';
		echo '</label>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render one text field.
	 *
	 * @param string $name Field name.
	 * @param string $label Visible label.
	 * @param string $description Small help text.
	 * @return void
	 */
	private function render_text_field( string $name, string $label, string $description ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<label class="hwlio-bulk__field hwlio-bulk__field--wide">';
		echo '<span class="hwlio-bulk__field-label">' . $this->escape_html( $label ) . '</span>';
		echo '<input type="text" name="' . $this->escape_attr( $name ) . '" placeholder="' . $this->escape_attr( $description ) . '" />';
		echo '<span class="description">' . $this->escape_html( $description ) . '</span>';
		echo '</label>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render one summary card shell.
	 *
	 * @param string $key Summary key.
	 * @param string $label Visible label.
	 * @param string $group Summary group.
	 * @return void
	 */
	private function render_summary_card( string $key, string $label, string $group = 'scan' ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through inherited wrapper methods.
		echo '<div class="hwlio-bulk__summary-card" data-hwlio-bulk-summary-card="' . $this->escape_attr( $group . ':' . $key ) . '">';
		echo '<span class="hwlio-bulk__summary-label">' . $this->escape_html( $label ) . '</span>';
		echo '<span class="hwlio-bulk__summary-value" data-hwlio-bulk-summary-value="' . $this->escape_attr( $group . ':' . $key ) . '">0</span>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
