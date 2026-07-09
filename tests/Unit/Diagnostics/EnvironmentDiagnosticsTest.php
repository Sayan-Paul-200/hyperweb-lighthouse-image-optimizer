<?php
/**
 * Tests for environment diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticReport;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticSanitizer;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\EnvironmentDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionDiagnostic;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\SampleConversionResult;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\TemporaryFileDiagnostic;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportResult;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsSchema;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeEnvironmentProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies environment diagnostic orchestration.
 */
final class EnvironmentDiagnosticsTest extends TestCase {

	/**
	 * Test a healthy environment produces callable structured diagnostics.
	 *
	 * @return void
	 */
	public function test_run_returns_structured_pass_report_for_healthy_environment(): void {
		$report = $this->run_diagnostics();

		self::assertSame( 13, $report->summary()['total'] );
		self::assertSame( 13, $report->summary()['pass'] );
		self::assertSame( 0, $report->summary()['warning'] );
		self::assertSame( 0, $report->summary()['fail'] );
		self::assertSame( 'php_version', $report->to_array()['results'][0]['id'] );
		self::assertSame( DiagnosticStatus::PASS, $this->result( $report, 'sample_conversion_webp' )->status() );
	}

	/**
	 * Test version and scheduler failures.
	 *
	 * @return void
	 */
	public function test_run_maps_version_and_action_scheduler_failures(): void {
		$probe                               = new FakeEnvironmentProbe();
		$probe->php_version                  = '7.3.33';
		$probe->wordpress_version            = '6.4.3';
		$probe->action_scheduler_loaded      = false;
		$probe->action_scheduler_initialized = null;
		$report                              = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'php_version' )->status() );
		self::assertSame( 'php_version_unsupported', $this->result( $report, 'php_version' )->code() );
		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'wordpress_version' )->status() );
		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'action_scheduler' )->status() );
	}

	/**
	 * Test image editor availability failure.
	 *
	 * @return void
	 */
	public function test_run_maps_missing_image_editors_to_failure(): void {
		$probe                  = new FakeEnvironmentProbe();
		$probe->class_available = array(
			'WP_Image_Editor_Imagick' => false,
			'WP_Image_Editor_GD'      => false,
		);

		$report = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'image_editors' )->status() );
		self::assertSame( 'image_editor_unavailable', $this->result( $report, 'image_editors' )->code() );
	}

	/**
	 * Test enabled unsupported formats are failures while skipped sample conversion is informational.
	 *
	 * @return void
	 */
	public function test_run_marks_enabled_unsupported_formats_as_failures(): void {
		$probe                             = new FakeEnvironmentProbe();
		$probe->mime_support['image/avif'] = false;
		$settings                          = array_replace(
			SettingsSchema::defaults(),
			array(
				'enabled_formats' => array( 'webp', 'avif' ),
			)
		);
		$report                            = $this->run_diagnostics( $probe, $settings );

		self::assertSame( DiagnosticStatus::PASS, $this->result( $report, 'format_support_webp' )->status() );
		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'format_support_avif' )->status() );
		self::assertSame( DiagnosticStatus::INFO, $this->result( $report, 'sample_conversion_avif' )->status() );
		self::assertSame( 'sample_conversion_skipped', $this->result( $report, 'sample_conversion_avif' )->code() );
	}

	/**
	 * Test disabled unsupported formats are warnings.
	 *
	 * @return void
	 */
	public function test_run_marks_disabled_unsupported_formats_as_warnings(): void {
		$probe                             = new FakeEnvironmentProbe();
		$probe->mime_support['image/avif'] = false;
		$report                            = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::WARNING, $this->result( $report, 'format_support_avif' )->status() );
	}

	/**
	 * Test upload status mappings.
	 *
	 * @return void
	 */
	public function test_run_maps_upload_states(): void {
		$probe           = new FakeEnvironmentProbe();
		$probe->writable = array( '/tmp/uploads' => false );
		$report          = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::PASS, $this->result( $report, 'upload_base_path' )->status() );
		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'upload_path_writable' )->status() );

		$probe          = new FakeEnvironmentProbe();
		$probe->uploads = null;
		$report         = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::WARNING, $this->result( $report, 'upload_base_path' )->status() );
		self::assertSame( DiagnosticStatus::WARNING, $this->result( $report, 'upload_path_writable' )->status() );
		self::assertSame( DiagnosticStatus::FAIL, $this->result( $report, 'temporary_write_rename' )->status() );
	}

	/**
	 * Test runtime constraint mappings.
	 *
	 * @return void
	 */
	public function test_run_maps_memory_and_execution_time_states(): void {
		$probe                     = new FakeEnvironmentProbe();
		$probe->memory_limit       = '64M';
		$probe->max_execution_time = 'not-a-number';
		$report                    = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::WARNING, $this->result( $report, 'memory_limit' )->status() );
		self::assertSame( 'memory_limit_low', $this->result( $report, 'memory_limit' )->code() );
		self::assertSame( DiagnosticStatus::WARNING, $this->result( $report, 'max_execution_time' )->status() );

		$probe               = new FakeEnvironmentProbe();
		$probe->memory_limit = '-1';
		$report              = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::PASS, $this->result( $report, 'memory_limit' )->status() );
		self::assertSame( 'memory_limit_unlimited', $this->result( $report, 'memory_limit' )->code() );
	}

	/**
	 * Test Action Scheduler not initialized is a warning.
	 *
	 * @return void
	 */
	public function test_run_maps_action_scheduler_not_initialized_to_warning(): void {
		$probe                               = new FakeEnvironmentProbe();
		$probe->action_scheduler_initialized = false;
		$report                              = $this->run_diagnostics( $probe );

		self::assertSame( DiagnosticStatus::WARNING, $this->result( $report, 'action_scheduler' )->status() );
		self::assertSame( 'action_scheduler_not_initialized', $this->result( $report, 'action_scheduler' )->code() );
	}

	/**
	 * Run diagnostics with fakes.
	 *
	 * @param FakeEnvironmentProbe|null $probe Environment probe.
	 * @param array<string,mixed>|null  $settings Settings.
	 * @return DiagnosticReport
	 */
	private function run_diagnostics( ?FakeEnvironmentProbe $probe = null, ?array $settings = null ): DiagnosticReport {
		$probe      = $probe ?? new FakeEnvironmentProbe();
		$settings   = $settings ?? SettingsSchema::defaults();
		$filesystem = new FakeDiagnosticFilesystem();
		$sample     = new SampleConversionDiagnostic(
			$filesystem,
			new FakeSampleConversionProbe( SampleConversionResult::success(), $filesystem ),
			'abc123'
		);

		$diagnostics = new EnvironmentDiagnostics(
			new EnvironmentInspector( $probe, '7.4', '6.5' ),
			SettingsRepository::for_options( new FakeOptionStore( array( SettingsRepository::OPTION_NAME => $settings ) ) ),
			new DiagnosticSanitizer(),
			new TemporaryFileDiagnostic( $filesystem, 'abc123' ),
			$sample
		);

		return $diagnostics->run();
	}

	/**
	 * Find a result by ID.
	 *
	 * @param DiagnosticReport $report Report.
	 * @param string           $id Result ID.
	 * @return DiagnosticResult
	 */
	private function result( DiagnosticReport $report, string $id ): DiagnosticResult {
		foreach ( $report->results() as $result ) {
			if ( $id === $result->id() ) {
				return $result;
			}
		}

		self::fail( sprintf( 'Diagnostic result %s was not found.', $id ) );
	}
}
