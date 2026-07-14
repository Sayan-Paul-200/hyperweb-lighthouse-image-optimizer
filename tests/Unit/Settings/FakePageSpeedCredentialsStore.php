<?php
/**
 * Fake PageSpeed credentials store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

use HyperWeb\LighthouseImageOptimizer\Settings\PageSpeedCredentialsStoreInterface;

/**
 * Provides deterministic in-memory credentials behavior for tests.
 */
final class FakePageSpeedCredentialsStore implements PageSpeedCredentialsStoreInterface {

	/**
	 * Stored payload.
	 *
	 * @var array<string,string>
	 */
	public $payload = array(
		'api_key' => '',
	);

	/**
	 * Stable option name.
	 *
	 * @var string
	 */
	public $option_name = 'hwlio_pagespeed_credentials';

	/**
	 * Create the fake store.
	 *
	 * @param string $api_key Initial API key.
	 */
	public function __construct( string $api_key = '' ) {
		$this->payload['api_key'] = trim( $api_key );
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
	 * Read all credentials.
	 *
	 * @return array<string,string>
	 */
	public function all(): array {
		return $this->payload;
	}

	/**
	 * Get the saved API key.
	 *
	 * @return string
	 */
	public function api_key(): string {
		return $this->payload['api_key'];
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
	 * Save one submission.
	 *
	 * @param mixed $input Raw payload.
	 * @return array<string,string>
	 */
	public function save_submission( $input ): array {
		$input = is_array( $input ) ? $input : array();

		if ( ! empty( $input['clear_api_key'] ) ) {
			$this->payload = array( 'api_key' => '' );
			return $this->payload;
		}

		if ( array_key_exists( 'api_key', $input ) && is_scalar( $input['api_key'] ) && '' !== trim( (string) $input['api_key'] ) ) {
			$this->payload = array(
				'api_key' => trim( (string) $input['api_key'] ),
			);
		}

		return $this->payload;
	}
}
