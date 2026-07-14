<?php
/**
 * Content inventory REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminBootstrapConfig;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentByteReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentIssueReportService;
use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryService;

/**
 * Registers the page inventory route for one selected content record.
 */
final class ContentInventoryController implements RestControllerInterface {

	/**
	 * REST runtime.
	 *
	 * @var RestRuntimeInterface
	 */
	private $runtime;

	/**
	 * Error factory.
	 *
	 * @var RestErrorFactory
	 */
	private $errors;

	/**
	 * Inventory service.
	 *
	 * @var ContentInventoryService
	 */
	private $inventory;

	/**
	 * Issue reporting service.
	 *
	 * @var ContentIssueReportService
	 */
	private $issues;

	/**
	 * Byte reporting service.
	 *
	 * @var ContentByteReportService
	 */
	private $bytes;

	/**
	 * Create controller.
	 *
	 * @param RestRuntimeInterface   $runtime REST runtime.
	 * @param RestErrorFactory       $errors Error factory.
	 * @param ContentInventoryService   $inventory Inventory service.
	 * @param ContentIssueReportService $issues Issue service.
	 * @param ContentByteReportService  $bytes Byte reporting service.
	 */
	public function __construct(
		RestRuntimeInterface $runtime,
		RestErrorFactory $errors,
		ContentInventoryService $inventory,
		ContentIssueReportService $issues,
		ContentByteReportService $bytes
	) {
		$this->runtime   = $runtime;
		$this->errors    = $errors;
		$this->inventory = $inventory;
		$this->issues    = $issues;
		$this->bytes     = $bytes;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->runtime->register_route(
			rtrim( AdminBootstrapConfig::REST_NAMESPACE, '/' ),
			'/content/(?P<content_id>[\d]+)/inventory',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_inventory' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
					'args'                => array(
						'content_id' => array(
							'required'          => true,
							'sanitize_callback' => array( $this, 'sanitize_content_id' ),
							'validate_callback' => array( $this, 'validate_content_id' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool|mixed
	 */
	public function can_manage_options() {
		if ( $this->runtime->current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $this->errors->forbidden();
	}

	/**
	 * Route callback.
	 *
	 * @param mixed $request Request object.
	 * @return mixed
	 */
	public function get_inventory( $request ) {
		$content_id = RequestData::positive_int( $request, 'content_id' );

		if ( 0 === $content_id ) {
			return $this->errors->invalid_content_id();
		}

		if ( ! $this->inventory->content_exists( $content_id ) ) {
			return $this->errors->content_not_found( $content_id );
		}

		try {
			$snapshot = $this->inventory->inspect( $content_id );
			$report   = $snapshot->to_page_inventory_report()->to_array();
			$issues   = $this->issues->report( $snapshot );
			$bytes    = $this->bytes->report( $snapshot );

			$report['issue_summary']   = $issues->summary();
			$report['issues']          = $issues->to_array();
			$report['byte_summary']    = $bytes->summary();
			$report['byte_occurrences'] = $bytes->occurrences();

			return $this->runtime->response( $report, 200 );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return $this->errors->content_inventory_unavailable();
		}
	}

	/**
	 * Sanitize content ID.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_content_id( $value ): int {
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * Validate content ID.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_content_id( $value ): bool {
		return is_numeric( $value ) && 0 < (int) $value;
	}
}
