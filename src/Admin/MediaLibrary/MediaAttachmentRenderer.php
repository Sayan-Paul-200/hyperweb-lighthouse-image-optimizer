<?php
/**
 * Media attachment renderer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

/**
 * Renders lightweight Media Library status and action markup.
 */
final class MediaAttachmentRenderer {

	/**
	 * Render the list-table column markup.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @return string
	 */
	public function render_column( MediaAttachmentSummary $summary ): string {
		return $this->summary_markup( $summary, 'column' );
	}

	/**
	 * Render the attachment field markup.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @return string
	 */
	public function render_field( MediaAttachmentSummary $summary ): string {
		return $this->summary_markup( $summary, 'field' );
	}

	/**
	 * Render row-action HTML items keyed by action slug.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @return array<string,string>
	 */
	public function render_row_actions( MediaAttachmentSummary $summary ): array {
		$actions = array();

		foreach ( $summary->allowed_actions() as $action ) {
			$actions[ 'hwlio-' . $action ] = $this->action_link( $summary, $action, true );
		}

		return $actions;
	}

	/**
	 * Render one fallback placeholder for unsupported attachments.
	 *
	 * @return string
	 */
	public function render_unavailable(): string {
		return '<span class="hwlio-media-unavailable">' . $this->escape_html( $this->translate( 'Not available' ) ) . '</span>';
	}

	/**
	 * Build the shared summary markup.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @param string                 $context Context label.
	 * @return string
	 */
	private function summary_markup( MediaAttachmentSummary $summary, string $context ): string {
		$attachment_id = $summary->attachment_id();
		$details_id    = 'hwlio-media-details-' . $attachment_id . '-' . $context;
		$actions_id    = 'hwlio-media-actions-' . $attachment_id . '-' . $context;

		$html  = '<div class="hwlio-media-summary" data-hwlio-summary="1" data-attachment-id="' . $this->escape_attr( (string) $attachment_id ) . '" data-state="' . $this->escape_attr( $summary->state() ) . '" data-active="' . $this->escape_attr( $summary->active() ? '1' : '0' ) . '">';
		$html .= '<div class="hwlio-media-summary__status">';
		$html .= $this->status_badge( $summary );
		$html .= $this->ready_format_chips( $summary );
		$html .= '</div>';
		$html .= '<div class="hwlio-media-summary__actions" id="' . $this->escape_attr( $actions_id ) . '">';

		foreach ( $summary->allowed_actions() as $action ) {
			$html .= $this->action_link( $summary, $action );
		}

		$html .= '</div>';
		$html .= '<div class="hwlio-media-summary__details" id="' . $this->escape_attr( $details_id ) . '" hidden></div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the status badge.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @return string
	 */
	private function status_badge( MediaAttachmentSummary $summary ): string {
		return '<span class="hwlio-media-badge hwlio-media-badge--' . $this->escape_attr( $summary->state() ) . '">' . $this->escape_html( $summary->status_label() ) . '</span>';
	}

	/**
	 * Render ready-format chips.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @return string
	 */
	private function ready_format_chips( MediaAttachmentSummary $summary ): string {
		$html = '';

		foreach ( $summary->ready_formats() as $format ) {
			$html .= '<span class="hwlio-media-chip">' . $this->escape_html( strtoupper( $format ) ) . '</span>';
		}

		return $html;
	}

	/**
	 * Render one action link.
	 *
	 * @param MediaAttachmentSummary $summary Attachment summary.
	 * @param string                 $action Action slug.
	 * @param bool                   $row_action Whether this renders inside row actions.
	 * @return string
	 */
	private function action_link( MediaAttachmentSummary $summary, string $action, bool $row_action = false ): string {
		$classes = 'hwlio-media-action';

		if ( $row_action ) {
			$classes .= ' hwlio-media-action--row';
		}

		$attributes = array(
			'href="#"',
			'class="' . $this->escape_attr( $classes ) . '"',
			'data-hwlio-action="' . $this->escape_attr( $action ) . '"',
			'data-attachment-id="' . $this->escape_attr( (string) $summary->attachment_id() ) . '"',
		);

		if ( AttachmentActionAvailability::ACTION_REOPTIMIZE === $action ) {
			$attributes[] = 'data-force="1"';
		}

		return '<a ' . implode( ' ', $attributes ) . '>' . $this->escape_html( $this->action_label( $action ) ) . '</a>';
	}

	/**
	 * Get the translated action label.
	 *
	 * @param string $action Action slug.
	 * @return string
	 */
	private function action_label( string $action ): string {
		$labels = array(
			AttachmentActionAvailability::ACTION_OPTIMIZE  => 'Optimize Now',
			AttachmentActionAvailability::ACTION_RETRY     => 'Retry',
			AttachmentActionAvailability::ACTION_REOPTIMIZE => 'Re-optimize',
			AttachmentActionAvailability::ACTION_RECONCILE => 'Reconcile Files',
			AttachmentActionAvailability::ACTION_EXCLUDE   => 'Exclude from Optimization',
			AttachmentActionAvailability::ACTION_INCLUDE   => 'Include in Optimization',
			AttachmentActionAvailability::ACTION_VIEW_DETAILS => 'View Details',
		);

		return $this->translate( $labels[ $action ] ?? 'View Details' );
	}

	/**
	 * Translate one plugin-owned string.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Wrapper accepts only plugin-owned literals provided by calling code.
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}

	/**
	 * Escape HTML text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function escape_html( string $text ): string {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $text );
		}

		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
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
