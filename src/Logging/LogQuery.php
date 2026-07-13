<?php
/**
 * Log query value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Carries one bounded logs-screen query.
 */
final class LogQuery {

	public const LEVEL_ALL       = 'all';
	public const DEFAULT_PAGE    = 1;
	public const DEFAULT_PER_PAGE = 20;
	public const MAX_PER_PAGE    = 100;

	/**
	 * Filtered level.
	 *
	 * @var string
	 */
	private $level;

	/**
	 * Exact code filter.
	 *
	 * @var string|null
	 */
	private $code;

	/**
	 * Attachment filter.
	 *
	 * @var int|null
	 */
	private $attachment_id;

	/**
	 * Page number.
	 *
	 * @var int
	 */
	private $page;

	/**
	 * Page size.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Create the query.
	 *
	 * @param string      $level Filtered level.
	 * @param string|null $code Exact code filter.
	 * @param int|null    $attachment_id Attachment filter.
	 * @param int         $page Page number.
	 * @param int         $per_page Page size.
	 */
	public function __construct(
		string $level = self::LEVEL_ALL,
		?string $code = null,
		?int $attachment_id = null,
		int $page = self::DEFAULT_PAGE,
		int $per_page = self::DEFAULT_PER_PAGE
	) {
		$this->level         = $this->normalize_level( $level );
		$this->code          = $this->normalize_code( $code );
		$this->attachment_id = ( null !== $attachment_id && $attachment_id > 0 ) ? $attachment_id : null;
		$this->page          = max( self::DEFAULT_PAGE, $page );
		$this->per_page      = min( self::MAX_PER_PAGE, max( 1, $per_page ) );
	}

	/**
	 * Get allowed filter levels.
	 *
	 * @return string[]
	 */
	public static function levels(): array {
		return array(
			self::LEVEL_ALL,
			LogLevel::INFO,
			LogLevel::WARNING,
			LogLevel::ERROR,
		);
	}

	/**
	 * Get filtered level.
	 *
	 * @return string
	 */
	public function level(): string {
		return $this->level;
	}

	/**
	 * Get exact code filter.
	 *
	 * @return string|null
	 */
	public function code(): ?string {
		return $this->code;
	}

	/**
	 * Get attachment filter.
	 *
	 * @return int|null
	 */
	public function attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Get page number.
	 *
	 * @return int
	 */
	public function page(): int {
		return $this->page;
	}

	/**
	 * Get page size.
	 *
	 * @return int
	 */
	public function per_page(): int {
		return $this->per_page;
	}

	/**
	 * Get SQL offset.
	 *
	 * @return int
	 */
	public function offset(): int {
		return max( 0, ( $this->page - 1 ) * $this->per_page );
	}

	/**
	 * Serialize the query for clients.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'level'         => $this->level,
			'code'          => $this->code,
			'attachment_id' => $this->attachment_id,
			'page'          => $this->page,
			'per_page'      => $this->per_page,
		);
	}

	/**
	 * Normalize a level filter.
	 *
	 * @param string $level Raw level.
	 * @return string
	 */
	private function normalize_level( string $level ): string {
		$level = strtolower( trim( $level ) );

		return in_array( $level, self::levels(), true ) ? $level : self::LEVEL_ALL;
	}

	/**
	 * Normalize an optional exact code filter.
	 *
	 * @param string|null $code Raw code.
	 * @return string|null
	 */
	private function normalize_code( ?string $code ): ?string {
		if ( null === $code ) {
			return null;
		}

		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return '' === $code ? null : substr( $code, 0, 64 );
	}
}
