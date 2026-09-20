<?php
/**
 * What each REST route asks for before it answers.
 *
 * @package WOWStudio\AccessibilityKit
 */

declare( strict_types = 1 );

namespace WOWStudio\AccessibilityKit\Tests\Unit;

use WOWStudio\AccessibilityKit\Tests\TestCase;

/**
 * Guards the capability behind every REST route.
 *
 * There was already a check that every route had a permission callback, and it
 * passed for a year while two of them asked for the wrong capability. Counting
 * the locks is not the same as checking they fit: `IssueController::can_read()`
 * and `OverviewController::can_read()` both asked for RUN_SCAN, which gates
 * starting a scan, before handing back the site's entire findings report.
 *
 * Nothing misbehaved, because the roles this plugin grants hold both
 * capabilities — which is precisely why it survived until somebody read it.
 *
 * Source-reading, like the other wiring tests here. What is being guarded is
 * that a reading route asks to read and a writing route asks to write, and the
 * failure is silent in both directions.
 *
 * @coversNothing
 */
final class RestPermissionTest extends TestCase {

	/**
	 * Reads a file from the plugin root.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	private function source( string $relative ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local source file in a unit test; WordPress is not loaded.
		return (string) file_get_contents( __DIR__ . '/../../' . $relative );
	}

	/**
	 * Every gate asks for the capability its name promises.
	 *
	 * @dataProvider gates
	 *
	 * @param string $file       Controller, relative to the plugin root.
	 * @param string $method     The permission callback.
	 * @param string $capability The constant it must check.
	 * @return void
	 */
	public function test_each_gate_asks_for_the_right_capability( string $file, string $method, string $capability ): void {
		$source = $this->source( $file );

		$at = strpos( $source, 'function ' . $method . '(' );

		$this->assertNotFalse( $at, $file . ' has no ' . $method . '().' );

		// The body, up to the next method. Long enough to hold the check and
		// short enough that a neighbouring method's check cannot satisfy it.
		$body = substr( $source, $at, 700 );

		$this->assertStringContainsString(
			'Capabilities::' . $capability,
			$body,
			$file . '::' . $method . '() should ask for ' . $capability . '.'
		);
	}

	/**
	 * Every permission callback in the plugin, and what it must require.
	 *
	 * Reading a report requires the reports capability, starting a scan
	 * requires the scan capability, changing content requires the fix
	 * capability, and changing how the whole site behaves requires the
	 * settings capability. Where a route is about somebody else's content the
	 * route checks that too, on top of this.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function gates(): array {
		return array(
			'findings list'      => array( 'src/Rest/IssueController.php', 'can_read', 'VIEW_REPORTS' ),
			'report figures'     => array( 'src/Rest/OverviewController.php', 'can_read', 'VIEW_REPORTS' ),
			'dismissal log'      => array( 'src/Rest/ReviewController.php', 'can_read', 'VIEW_REPORTS' ),
			'scan results'       => array( 'src/Rest/ScanController.php', 'can_view', 'VIEW_REPORTS' ),
			'run results'        => array( 'src/Rest/RunController.php', 'can_view', 'VIEW_REPORTS' ),
			'site fixes listing' => array( 'src/Rest/SiteFixController.php', 'can_view', 'VIEW_REPORTS' ),
			'statement reading'  => array( 'src/Rest/StatementController.php', 'can_view', 'VIEW_REPORTS' ),

			'starting a scan'    => array( 'src/Rest/ScanController.php', 'can_scan', 'RUN_SCAN' ),
			'starting a run'     => array( 'src/Rest/RunController.php', 'can_scan', 'RUN_SCAN' ),
			'checking a block'   => array( 'src/Rest/BlockCheckController.php', 'can_scan', 'RUN_SCAN' ),

			'deciding a finding' => array( 'src/Rest/ReviewController.php', 'can_decide', 'APPLY_FIX' ),
			'writing a style'    => array( 'src/Rest/CssFixController.php', 'can_edit_css', 'APPLY_FIX' ),
			'describing images'  => array( 'src/Rest/AltTextController.php', 'can_edit_media', 'APPLY_FIX' ),

			'switching a fix on' => array( 'src/Rest/SiteFixController.php', 'can_change', 'MANAGE_SETTINGS' ),
			'editing the note'   => array( 'src/Rest/StatementController.php', 'can_manage', 'MANAGE_SETTINGS' ),
			'finishing setup'    => array( 'src/Rest/OnboardingController.php', 'can_change', 'MANAGE_SETTINGS' ),
		);
	}

	/**
	 * No declared method is left without a gate.
	 *
	 * Counted per method rather than per route, which is the distinction that
	 * made the first version of this wrong: one `register_rest_route()` call
	 * can declare a readable and a creatable method, and each needs its own
	 * `permission_callback`. Thirty-three gates across twenty-nine routes is
	 * correct, not a surplus.
	 *
	 * The weaker half of this test, kept because it is still worth knowing: a
	 * method registered without a callback is public, and WordPress warns about
	 * it and then serves it anyway.
	 *
	 * @return void
	 */
	public function test_every_declared_method_is_gated(): void {
		$methods = 0;
		$gates   = 0;

		foreach ( (array) glob( __DIR__ . '/../../src/Rest/*.php' ) as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local source file in a unit test; WordPress is not loaded.
			$source = (string) file_get_contents( (string) $file );

			$methods += substr_count( $source, "'methods'" );
			$gates   += substr_count( $source, "'permission_callback'" );
		}

		$this->assertGreaterThan( 0, $methods, 'No routes found; this test is looking in the wrong place.' );
		$this->assertSame( $methods, $gates, 'Every declared REST method needs its own permission callback.' );
	}
}
