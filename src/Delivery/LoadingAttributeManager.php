<?php
/**
 * Critical-image loading attribute manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Applies explicit loading-attribute overrides for configured critical images.
 */
final class LoadingAttributeManager implements HookProviderInterface {

	/**
	 * Priority used for the loading optimization filter.
	 *
	 * @var int
	 */
	public const PRIORITY = 10;

	/**
	 * Critical-image registry.
	 *
	 * @var CriticalImageRegistry
	 */
	private $registry;

	/**
	 * Attachment image runtime.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

	/**
	 * Markup analyzer.
	 *
	 * @var ImageMarkupAnalyzerInterface
	 */
	private $analyzer;

	/**
	 * Whether plugin-managed high priority has already been claimed in this request.
	 *
	 * @var bool
	 */
	private $high_priority_claimed = false;

	/**
	 * Create provider.
	 *
	 * @param CriticalImageRegistry           $registry Critical-image registry.
	 * @param AttachmentImageRuntimeInterface $runtime Attachment image runtime.
	 * @param ImageMarkupAnalyzerInterface    $analyzer Markup analyzer.
	 */
	public function __construct(
		CriticalImageRegistry $registry,
		AttachmentImageRuntimeInterface $runtime,
		ImageMarkupAnalyzerInterface $analyzer
	) {
		$this->registry = $registry;
		$this->runtime  = $runtime;
		$this->analyzer = $analyzer;
	}

	/**
	 * Register the narrow loading-optimization filter.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter(
			'wp_get_loading_optimization_attributes',
			array( $this, 'filter_loading_optimization_attributes' ),
			self::PRIORITY,
			4
		);
	}

	/**
	 * Apply explicit overrides to core loading optimization attributes.
	 *
	 * @param array<string,string> $loading_attrs Core loading attributes.
	 * @param string               $tag_name Tag name.
	 * @param array<string,mixed>  $attr Original element attributes.
	 * @param string               $context Core loading optimization context.
	 * @return array<string,string>
	 */
	public function filter_loading_optimization_attributes(
		array $loading_attrs,
		string $tag_name,
		array $attr,
		string $context
	): array {
		unset( $context );

		if ( 'img' !== strtolower( $tag_name ) ) {
			return $loading_attrs;
		}

		$current_fetchpriority = $this->string_value( $loading_attrs['fetchpriority'] ?? $attr['fetchpriority'] ?? null );
		$current_loading       = $this->string_value( $loading_attrs['loading'] ?? $attr['loading'] ?? null );
		$this->claim_existing_high_priority( $current_fetchpriority );

		$selection      = $this->registry->resolve();
		$attachment_id  = $this->attachment_id_from_attributes( $attr );
		$classification = $this->classification_for_attributes( $selection, $attachment_id, $attr );
		$classification = $this->refine_role(
			$classification,
			array(
				'attachment_id'   => $attachment_id,
				'src'             => isset( $attr['src'] ) && is_string( $attr['src'] ) ? trim( $attr['src'] ) : null,
				'attr'            => $attr,
				'html'            => null,
				'analysis'        => array(
					'src'           => isset( $attr['src'] ) && is_string( $attr['src'] ) ? trim( $attr['src'] ) : null,
					'loading'       => $current_loading,
					'fetchpriority' => $current_fetchpriority,
					'decoding'      => $this->string_value( $loading_attrs['decoding'] ?? $attr['decoding'] ?? null ),
				),
				'request_context' => $this->runtime->request_context(),
			)
		);

		if ( 'none' === $classification ) {
			return $loading_attrs;
		}

		if ( 'lazy' === $current_loading ) {
			unset( $loading_attrs['loading'] );
		}

		if ( 'primary' === $classification ) {
			if ( null === $current_loading || 'lazy' === $current_loading ) {
				$loading_attrs['loading'] = 'eager';
			}

			if ( ! $this->high_priority_claimed && null === $current_fetchpriority ) {
				$loading_attrs['fetchpriority'] = 'high';
				$this->high_priority_claimed    = true;
			}
		}

		return $loading_attrs;
	}

	/**
	 * Apply the same explicit override to fallback markup before picture rendering.
	 *
	 * @param string $html Standalone fallback image HTML.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 */
	public function apply_to_fallback_markup( string $html, int $attachment_id ): string {
		if ( $attachment_id < 1 ) {
			return $html;
		}

		$analysis = $this->analyzer->analyze( $html );

		if ( ! $analysis->is_renderable_img() ) {
			return $html;
		}

		$this->claim_existing_high_priority( $analysis->fetchpriority() );

		$selection = $this->registry->resolve();
		$role      = $this->refine_role(
			$this->classification_for_attachment( $selection, $attachment_id ),
			array(
				'attachment_id'   => $attachment_id,
				'src'             => $analysis->src(),
				'attr'            => array(
					'class'         => $this->extract_attribute_value( $html, 'class' ),
					'loading'       => $analysis->loading(),
					'fetchpriority' => $analysis->fetchpriority(),
					'decoding'      => $analysis->decoding(),
				),
				'html'            => $html,
				'analysis'        => $analysis->to_array(),
				'request_context' => $this->runtime->request_context(),
			)
		);

		if ( 'primary' === $role ) {
			return $this->rewrite_markup( $html, true, $analysis->loading(), $analysis->fetchpriority() );
		}

		if ( 'secondary' === $role ) {
			return $this->rewrite_markup( $html, false, $analysis->loading(), $analysis->fetchpriority() );
		}

		return $html;
	}

	/**
	 * Determine one critical classification from current attributes.
	 *
	 * @param CriticalImageSelection $selection Current selection.
	 * @param int                    $attachment_id Resolved attachment ID.
	 * @param array<string,mixed>    $attr Original element attributes.
	 * @return string
	 */
	private function classification_for_attributes( CriticalImageSelection $selection, int $attachment_id, array $attr ): string {
		$classification = $this->classification_for_attachment( $selection, $attachment_id );

		if ( 'none' !== $classification ) {
			return $classification;
		}

		if (
			$attachment_id < 1
			&& isset( $attr['src'] )
			&& is_string( $attr['src'] )
			&& $selection->matches_url( $attr['src'] )
		) {
			return 'secondary';
		}

		return 'none';
	}

	/**
	 * Determine one critical classification from the attachment selection only.
	 *
	 * @param CriticalImageSelection $selection Current selection.
	 * @param int                    $attachment_id Resolved attachment ID.
	 * @return string
	 */
	private function classification_for_attachment( CriticalImageSelection $selection, int $attachment_id ): string {
		if ( $selection->is_primary_attachment( $attachment_id ) ) {
			return 'primary';
		}

		if ( $selection->is_critical_attachment( $attachment_id ) ) {
			return 'secondary';
		}

		return 'none';
	}

	/**
	 * Allow integrations to refine one computed image role.
	 *
	 * @param string              $role Current role.
	 * @param array<string,mixed> $context Markup context.
	 * @return string
	 */
	private function refine_role( string $role, array $context ): string {
		if ( function_exists( 'apply_filters' ) ) {
			$role = (string) \apply_filters( 'hwlio_loading_image_role', $role, $context );
		}

		return in_array( $role, array( 'primary', 'secondary', 'none' ), true ) ? $role : 'none';
	}

	/**
	 * Rewrite one standalone IMG fragment conservatively.
	 *
	 * @param string      $html Original IMG fragment.
	 * @param bool        $primary Whether the image is the primary critical image.
	 * @param string|null $loading Current loading value.
	 * @param string|null $fetchpriority Current fetchpriority value.
	 * @return string
	 */
	private function rewrite_markup( string $html, bool $primary, ?string $loading, ?string $fetchpriority ): string {
		$updated = $html;

		if ( 'lazy' === $loading ) {
			$updated = $this->remove_attribute( $updated, 'loading' );
		}

		if ( $primary && ( null === $loading || 'lazy' === $loading ) ) {
			$updated = $this->set_attribute( $updated, 'loading', 'eager' );
		}

		if ( 'high' === $fetchpriority ) {
			$this->high_priority_claimed = true;
		}

		if ( $primary && ! $this->high_priority_claimed && null === $fetchpriority ) {
			$updated                     = $this->set_attribute( $updated, 'fetchpriority', 'high' );
			$this->high_priority_claimed = true;
		}

		return $updated;
	}

	/**
	 * Remove one IMG attribute conservatively.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return string
	 */
	private function remove_attribute( string $html, string $attribute ): string {
		$updated = preg_replace(
			sprintf(
				'/\s+%s\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i',
				preg_quote( $attribute, '/' )
			),
			'',
			$html
		);

		return is_string( $updated ) ? $updated : $html;
	}

	/**
	 * Set or add one IMG attribute conservatively.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @param string $value Attribute value.
	 * @return string
	 */
	private function set_attribute( string $html, string $attribute, string $value ): string {
		$replacement = ' ' . $attribute . '="' . $this->escape_attr( $value ) . '"';
		$pattern     = sprintf(
			'/(\s+)%s\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i',
			preg_quote( $attribute, '/' )
		);
		$updated     = preg_replace( $pattern, $replacement, $html, 1, $count );

		if ( ! is_string( $updated ) ) {
			return $html;
		}

		if ( $count > 0 ) {
			return $updated;
		}

		$inserted = preg_replace( '/\s*\/?>$/', $replacement . '$0', $html, 1 );

		return is_string( $inserted ) ? $inserted : $html;
	}

	/**
	 * Resolve one attachment ID from element attributes when possible.
	 *
	 * @param array<string,mixed> $attr Original element attributes.
	 * @return int
	 */
	private function attachment_id_from_attributes( array $attr ): int {
		foreach ( array( 'data-id', 'data-attachment-id', 'attachment_id' ) as $key ) {
			if ( isset( $attr[ $key ] ) && is_numeric( $attr[ $key ] ) ) {
				return max( 0, (int) $attr[ $key ] );
			}
		}

		if ( isset( $attr['class'] ) && is_string( $attr['class'] ) ) {
			if ( 1 === preg_match( '/\bwp-image-(\d+)\b/', $attr['class'], $matches ) ) {
				return max( 0, (int) $matches[1] );
			}
		}

		return 0;
	}

	/**
	 * Normalize one optional string value.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function string_value( $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = strtolower( trim( $value ) );

		return '' === $value ? null : $value;
	}

	/**
	 * Extract one raw attribute from standalone image markup.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function extract_attribute_value( string $html, string $attribute ): ?string {
		$pattern = sprintf(
			'/\b%s\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i',
			preg_quote( $attribute, '/' )
		);

		if ( 1 !== preg_match( $pattern, $html, $matches ) ) {
			return null;
		}

		foreach ( array( 1, 2, 3 ) as $index ) {
			if ( array_key_exists( $index, $matches ) ) {
				return $matches[ $index ];
			}
		}

		return null;
	}

	/**
	 * Mark request-local high priority as claimed when one image already has it.
	 *
	 * @param string|null $fetchpriority Current fetchpriority.
	 * @return void
	 */
	private function claim_existing_high_priority( ?string $fetchpriority ): void {
		if ( 'high' === $fetchpriority ) {
			$this->high_priority_claimed = true;
		}
	}

	/**
	 * Escape one generated attribute value.
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
