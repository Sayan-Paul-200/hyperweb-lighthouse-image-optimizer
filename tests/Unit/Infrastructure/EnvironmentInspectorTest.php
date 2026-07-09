<?php
/**
 * Tests for environment inspection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\ActionSchedulerStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\RuntimeConstraints;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\UploadsStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies environment capability reporting.
 */
final class EnvironmentInspectorTest extends TestCase {

	/**
	 * Test PHP and WordPress version reporting.
	 *
	 * @return void
	 */
	public function test_reports_php_and_wordpress_versions(): void {
		$probe                    = new FakeEnvironmentProbe();
		$probe->php_version       = '8.2.1';
		$probe->wordpress_version = '6.4.3';
		$report                   = ( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect();

		self::assertSame( '8.2.1', $report->php_version() );
		self::assertSame( '7.4', $report->minimum_php() );
		self::assertTrue( $report->php_supported() );
		self::assertSame( '6.4.3', $report->wordpress_version() );
		self::assertSame( '6.5', $report->minimum_wordpress() );
		self::assertFalse( $report->wordpress_supported() );
	}

	/**
	 * Test image editor candidate discovery.
	 *
	 * @return void
	 */
	public function test_reports_image_editor_candidate_availability(): void {
		$probe                          = new FakeEnvironmentProbe();
		$probe->image_editor_candidates = array( 'Editor_A', 'Editor_B' );
		$probe->class_available         = array(
			'Editor_A' => true,
			'Editor_B' => false,
		);

		$report = ( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect();

		self::assertSame(
			array(
				'Editor_A' => true,
				'Editor_B' => false,
			),
			$report->image_editors()
		);
	}

	/**
	 * Test WebP and AVIF support are independent.
	 *
	 * @return void
	 */
	public function test_reports_webp_and_avif_support_independently(): void {
		$probe                             = new FakeEnvironmentProbe();
		$probe->mime_support['image/webp'] = true;
		$probe->mime_support['image/avif'] = false;
		$report                            = ( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect();
		$webp                              = $report->support_for( FormatSupportResult::FORMAT_WEBP );
		$avif                              = $report->support_for( FormatSupportResult::FORMAT_AVIF );

		self::assertSame( FormatSupportResult::STATUS_SUPPORTED, $webp->status() );
		self::assertSame( FormatSupportResult::STATUS_UNSUPPORTED, $avif->status() );
		self::assertTrue( $webp->is_supported() );
		self::assertFalse( $avif->is_supported() );
		self::assertTrue( $avif->mime_recognized() );
		self::assertFalse( $avif->encoding_supported() );
	}

	/**
	 * Test unavailable support functions produce unknown status.
	 *
	 * @return void
	 */
	public function test_missing_support_functions_return_unknown_status(): void {
		$probe                  = new FakeEnvironmentProbe();
		$probe->mime_recognized = array();
		$probe->mime_support    = array();
		$result                 = ( new EnvironmentInspector( $probe, '7.4', '6.5' ) )
			->support_for( FormatSupportResult::FORMAT_WEBP );

		self::assertSame( FormatSupportResult::STATUS_UNKNOWN, $result->status() );
		self::assertNull( $result->mime_recognized() );
		self::assertNull( $result->encoding_supported() );
	}

	/**
	 * Test no image editors reports a misconfigured format when MIME is recognized.
	 *
	 * @return void
	 */
	public function test_no_available_image_editors_reports_misconfigured_support(): void {
		$probe                             = new FakeEnvironmentProbe();
		$probe->class_available            = array(
			'WP_Image_Editor_Imagick' => false,
			'WP_Image_Editor_GD'      => false,
		);
		$probe->mime_support['image/webp'] = false;

		$result = ( new EnvironmentInspector( $probe, '7.4', '6.5' ) )
			->support_for( FormatSupportResult::FORMAT_WEBP );

		self::assertSame( FormatSupportResult::STATUS_MISCONFIGURED, $result->status() );
		self::assertSame( 'no_image_editor_available', $result->reason() );
		self::assertTrue( $result->blocks_enablement() );
	}

	/**
	 * Test uploads statuses.
	 *
	 * @return void
	 */
	public function test_reports_uploads_statuses(): void {
		$probe = new FakeEnvironmentProbe();
		self::assertSame(
			UploadsStatus::STATUS_AVAILABLE,
			( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect()->uploads()->status()
		);

		$probe->uploads = array(
			'basedir' => '/tmp/uploads',
			'error'   => 'Upload path unavailable.',
		);
		self::assertSame(
			UploadsStatus::STATUS_ERROR,
			( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect()->uploads()->status()
		);

		$probe->uploads = array(
			'basedir' => '',
			'error'   => false,
		);
		self::assertSame(
			UploadsStatus::STATUS_MISSING,
			( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect()->uploads()->status()
		);

		$probe->uploads  = array(
			'basedir' => '/tmp/uploads',
			'error'   => false,
		);
		$probe->writable = array( '/tmp/uploads' => false );
		self::assertSame(
			UploadsStatus::STATUS_NOT_WRITABLE,
			( new EnvironmentInspector( $probe, '7.4', '6.5' ) )->inspect()->uploads()->status()
		);
	}

	/**
	 * Test memory limit parsing.
	 *
	 * @return void
	 */
	public function test_memory_limit_parsing(): void {
		self::assertSame( 134217728, MemoryLimit::from_raw( '128M' )->bytes() );
		self::assertSame( 1073741824, MemoryLimit::from_raw( '1G' )->bytes() );
		self::assertSame( 262144, MemoryLimit::from_raw( '256K' )->bytes() );
		self::assertTrue( MemoryLimit::from_raw( '-1' )->is_unlimited() );
		self::assertTrue( MemoryLimit::from_raw( 'not-a-limit' )->is_unknown() );
	}

	/**
	 * Test max execution time parsing.
	 *
	 * @return void
	 */
	public function test_runtime_constraints_report_max_execution_time(): void {
		$constraints = RuntimeConstraints::from_raw( '256M', '45' );

		self::assertSame( 45, $constraints->max_execution_time() );
		self::assertSame( '45', $constraints->max_execution_time_raw() );
		self::assertNull( RuntimeConstraints::from_raw( '256M', 'not-a-number' )->max_execution_time() );
	}

	/**
	 * Test Action Scheduler readiness states.
	 *
	 * @return void
	 */
	public function test_action_scheduler_readiness_states(): void {
		self::assertSame(
			ActionSchedulerStatus::STATUS_MISSING,
			ActionSchedulerStatus::from_state( false, null )->status()
		);
		self::assertSame(
			ActionSchedulerStatus::STATUS_NOT_INITIALIZED,
			ActionSchedulerStatus::from_state( true, false )->status()
		);
		self::assertSame(
			ActionSchedulerStatus::STATUS_READY,
			ActionSchedulerStatus::from_state( true, true )->status()
		);
	}
}
