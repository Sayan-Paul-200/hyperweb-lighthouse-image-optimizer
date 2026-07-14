<?php
/**
 * WordPress-backed PageSpeed Insights credentials store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;

/**
 * Stores the optional PageSpeed Insights API key with autoload disabled.
 */
final class WordPressPageSpeedCredentialsStore implements PageSpeedCredentialsStoreInterface {

	public const OPTION_NAME = 'hwlio_pagespeed_credentials';

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Build a WordPress-backed store.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return self::for_options( new WordPressOptionStore() );
	}

	/**
	 * Build a store around an existing option adapter.
	 *
	 * @param OptionStoreInterface $options Option store.
	 * @return self
	 */
	public static function for_options( OptionStoreInterface $options ): self {
		return new self( $options );
	}

	/**
	 * Create the store.
	 *
	 * @param OptionStoreInterface $options Option store.
	 * @param string               $option_name Option name.
	 */
	public function __construct( OptionStoreInterface $options, string $option_name = self::OPTION_NAME ) {
		$this->options     = $options;
		$this->option_name = $option_name;
	}

	/**
	 * Get the stable option name.
	 *
	 * @return string
	 */
	public function option_name(): string {
		return $this->option_name;
	}

	/**
	 * Read the normalized credentials payload.
	 *
	 * @return array<string,string>
	 */
	public function all(): array {
		$stored = $this->options->get( $this->option_name, null );

		if ( ! is_array( $stored ) ) {
			return $this->default_payload();
		}

		return array(
			'api_key' => $this->sanitize_api_key( $stored['api_key'] ?? '' ),
		);
	}

	/**
	 * Get the saved API key.
	 *
	 * @return string
	 */
	public function api_key(): string {
		return $this->all()['api_key'];
	}

	/**
	 * Whether a non-empty API key is stored.
	 *
	 * @return bool
	 */
	public function has_api_key(): bool {
		return '' !== $this->api_key();
	}

	/**
	 * Normalize and persist one submitted credentials payload.
	 *
	 * @param mixed $input Raw settings payload.
	 * @return array<string,string>
	 */
	public function save_submission( $input ): array {
		$current   = $this->all();
		$submitted = is_array( $input ) ? $input : array();
		$clear     = ! empty( $submitted['clear_api_key'] );
		$api_key   = array_key_exists( 'api_key', $submitted ) ? $this->sanitize_api_key( $submitted['api_key'] ) : '';

		if ( $clear ) {
			$payload = $this->default_payload();
		} elseif ( '' === $api_key ) {
			$payload = $current;
		} else {
			$payload = array(
				'api_key' => $api_key,
			);
		}

		$this->persist( $payload );

		return $payload;
	}

	/**
	 * Persist the payload with autoload disabled.
	 *
	 * @param array<string,string> $payload Normalized payload.
	 * @return void
	 */
	private function persist( array $payload ): void {
		if ( null === $this->options->get( $this->option_name, null ) ) {
			if ( $this->options->add( $this->option_name, $payload, false ) ) {
				return;
			}
		}

		$this->options->update( $this->option_name, $payload, false );
	}

	/**
	 * Get the default payload.
	 *
	 * @return array<string,string>
	 */
	private function default_payload(): array {
		return array(
			'api_key' => '',
		);
	}

	/**
	 * Sanitize one API key.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_api_key( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		$value = (string) preg_replace( '/[\x00-\x1F\x7F]/', '', $value );

		return substr( $value, 0, 191 );
	}
}
