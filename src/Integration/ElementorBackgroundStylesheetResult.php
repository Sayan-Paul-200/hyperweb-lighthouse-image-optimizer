<?php
/**
 * Elementor background stylesheet result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Carries safe stylesheet generation, storage, and enqueue outcomes.
 */
final class ElementorBackgroundStylesheetResult {

	public const STATUS_READY   = 'ready';
	public const STATUS_NOOP    = 'noop';
	public const STATUS_FAILURE = 'failure';

	public const CODE_RULES_GENERATED          = 'rules_generated';
	public const CODE_NO_SUPPORTED_SOURCES     = 'no_supported_sources';
	public const CODE_NO_SAFE_RULES            = 'no_safe_rules';
	public const CODE_BREAKPOINT_MAP_MISSING   = 'breakpoint_map_missing';
	public const CODE_STYLESHEET_CURRENT       = 'stylesheet_current';
	public const CODE_STYLESHEET_WRITTEN       = 'stylesheet_written';
	public const CODE_STYLESHEET_DELETED       = 'stylesheet_deleted';
	public const CODE_STYLESHEET_WRITE_FAILED  = 'stylesheet_write_failed';
	public const CODE_STYLESHEET_DELETE_FAILED = 'stylesheet_delete_failed';
	public const CODE_REQUEST_INELIGIBLE       = 'request_ineligible';
	public const CODE_DOCUMENT_UNAVAILABLE     = 'document_unavailable';

	/**
	 * Result status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Primary result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private $document_id;

	/**
	 * Whether safe rules exist.
	 *
	 * @var bool
	 */
	private $has_rules;

	/**
	 * Rule count.
	 *
	 * @var int
	 */
	private $rule_count;

	/**
	 * Stylesheet signature.
	 *
	 * @var string
	 */
	private $signature;

	/**
	 * Relative artifact path.
	 *
	 * @var string|null
	 */
	private $relative_path;

	/**
	 * Public artifact URL.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * CSS payload.
	 *
	 * @var string
	 */
	private $css;

	/**
	 * Additional codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Additional messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Create result.
	 *
	 * @param string      $status Result status.
	 * @param string      $code Primary code.
	 * @param int         $document_id Document ID.
	 * @param bool        $has_rules Whether safe rules exist.
	 * @param int         $rule_count Rule count.
	 * @param string      $signature Stylesheet signature.
	 * @param string|null $relative_path Relative artifact path.
	 * @param string|null $url Public artifact URL.
	 * @param string      $css CSS payload.
	 * @param string[]    $codes Additional codes.
	 * @param string[]    $messages Messages.
	 */
	public function __construct(
		string $status,
		string $code,
		int $document_id,
		bool $has_rules = false,
		int $rule_count = 0,
		string $signature = '',
		?string $relative_path = null,
		?string $url = null,
		string $css = '',
		array $codes = array(),
		array $messages = array()
	) {
		$this->status        = $this->normalize_status( $status );
		$this->code          = $this->normalize_code( $code );
		$this->document_id   = max( 0, $document_id );
		$this->has_rules     = $has_rules;
		$this->rule_count    = max( 0, $rule_count );
		$this->signature     = strtolower( trim( $signature ) );
		$this->relative_path = $this->normalize_optional_string( $relative_path );
		$this->url           = $this->normalize_optional_string( $url );
		$this->css           = $css;
		$this->codes         = $this->normalize_codes( array_merge( array( $this->code ), $codes ) );
		$this->messages      = $this->sanitize_messages( $messages );
	}

	/**
	 * Build a ready result.
	 *
	 * @param int      $document_id Document ID.
	 * @param string   $code Primary code.
	 * @param bool     $has_rules Whether safe rules exist.
	 * @param int      $rule_count Rule count.
	 * @param string   $signature Stylesheet signature.
	 * @param string   $css CSS payload.
	 * @param string[] $codes Additional codes.
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function ready(
		int $document_id,
		string $code,
		bool $has_rules,
		int $rule_count,
		string $signature = '',
		string $css = '',
		array $codes = array(),
		array $messages = array()
	): self {
		return new self(
			self::STATUS_READY,
			$code,
			$document_id,
			$has_rules,
			$rule_count,
			$signature,
			null,
			null,
			$css,
			$codes,
			$messages
		);
	}

	/**
	 * Build a noop result.
	 *
	 * @param int      $document_id Document ID.
	 * @param string   $code Primary code.
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function noop( int $document_id, string $code, array $messages = array() ): self {
		return new self( self::STATUS_NOOP, $code, $document_id, false, 0, '', null, null, '', array(), $messages );
	}

	/**
	 * Build a failure result.
	 *
	 * @param int      $document_id Document ID.
	 * @param string   $code Primary code.
	 * @param string[] $messages Messages.
	 * @return self
	 */
	public static function failure( int $document_id, string $code, array $messages = array() ): self {
		return new self( self::STATUS_FAILURE, $code, $document_id, false, 0, '', null, null, '', array(), $messages );
	}

	/**
	 * Clone with artifact details.
	 *
	 * @param string|null $relative_path Relative artifact path.
	 * @param string|null $url Public artifact URL.
	 * @return self
	 */
	public function with_artifact( ?string $relative_path, ?string $url ): self {
		return new self(
			$this->status,
			$this->code,
			$this->document_id,
			$this->has_rules,
			$this->rule_count,
			$this->signature,
			$relative_path,
			$url,
			$this->css,
			$this->codes,
			$this->messages
		);
	}

	/**
	 * Result status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Primary result code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Document ID.
	 *
	 * @return int
	 */
	public function document_id(): int {
		return $this->document_id;
	}

	/**
	 * Whether result is ready.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return self::STATUS_READY === $this->status;
	}

	/**
	 * Whether result is noop.
	 *
	 * @return bool
	 */
	public function is_noop(): bool {
		return self::STATUS_NOOP === $this->status;
	}

	/**
	 * Whether result is a failure.
	 *
	 * @return bool
	 */
	public function is_failure(): bool {
		return self::STATUS_FAILURE === $this->status;
	}

	/**
	 * Whether the document has safe modern rules.
	 *
	 * @return bool
	 */
	public function has_rules(): bool {
		return $this->has_rules;
	}

	/**
	 * Rule count.
	 *
	 * @return int
	 */
	public function rule_count(): int {
		return $this->rule_count;
	}

	/**
	 * Stylesheet signature.
	 *
	 * @return string
	 */
	public function signature(): string {
		return $this->signature;
	}

	/**
	 * Relative artifact path.
	 *
	 * @return string|null
	 */
	public function relative_path(): ?string {
		return $this->relative_path;
	}

	/**
	 * Public artifact URL.
	 *
	 * @return string|null
	 */
	public function url(): ?string {
		return $this->url;
	}

	/**
	 * CSS payload.
	 *
	 * @return string
	 */
	public function css(): string {
		return $this->css;
	}

	/**
	 * All result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Safe messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Serialize safely.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'status'        => $this->status,
			'code'          => $this->code,
			'document_id'   => $this->document_id,
			'has_rules'     => $this->has_rules,
			'rule_count'    => $this->rule_count,
			'signature'     => $this->signature,
			'relative_path' => $this->relative_path,
			'url'           => $this->url,
			'codes'         => $this->codes,
			'messages'      => $this->messages,
		);
	}

	/**
	 * Normalize status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private function normalize_status( string $status ): string {
		$status = strtolower( trim( $status ) );

		if ( ! in_array( $status, array( self::STATUS_READY, self::STATUS_NOOP, self::STATUS_FAILURE ), true ) ) {
			return self::STATUS_FAILURE;
		}

		return $status;
	}

	/**
	 * Normalize one code.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );

		return trim( $code, '_' );
	}

	/**
	 * Normalize one optional string.
	 *
	 * @param string|null $value Raw value.
	 * @return string|null
	 */
	private function normalize_optional_string( ?string $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		return '' === $value ? null : $value;
	}

	/**
	 * Normalize codes.
	 *
	 * @param string[] $codes Codes.
	 * @return string[]
	 */
	private function normalize_codes( array $codes ): array {
		$normalized = array();

		foreach ( $codes as $code ) {
			if ( ! is_scalar( $code ) ) {
				continue;
			}

			$code = $this->normalize_code( (string) $code );

			if ( '' !== $code ) {
				$normalized[] = $code;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Sanitize messages.
	 *
	 * @param string[] $messages Messages.
	 * @return string[]
	 */
	private function sanitize_messages( array $messages ): array {
		$sanitized = array();

		foreach ( $messages as $message ) {
			if ( ! is_scalar( $message ) ) {
				continue;
			}

			$message = trim( (string) $message );

			if ( '' !== $message ) {
				$sanitized[] = $message;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}
}
