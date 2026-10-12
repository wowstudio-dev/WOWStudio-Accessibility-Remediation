<?php
/**
 * What the free plugin knows about the paid add-on.
 *
 * @package WOWStudio\AccessibilityKit
 */

declare( strict_types=1 );

namespace WOWStudio\AccessibilityKit\Support;

/**
 * The one place that knows a separate paid plugin exists.
 *
 * The plugin says so in exactly one position, at the foot of its own
 * dashboard, and says nothing at all once the add-on is installed. That is
 * the whole of it: no locked control, no disabled button with a padlock on
 * it, no second mention on the other screens, no banner that follows anybody
 * around the admin.
 *
 * The product rule in CLAUDE.md is that finding is never gated. It is not
 * gated here. Every check, every severity and the whole-site scan stay free,
 * and the add-on adds re-running them on a schedule, which is automation
 * rather than a finding withheld. Telling somebody that exists is not the
 * same as charging them for what they already have, and leaving them to
 * discover it by accident is not integrity, it is just quieter.
 *
 * Where the line actually is: the moment this grows a second placement, a
 * countdown, a disabled control, or the word "upgrade" in a button, it has
 * become the thing the rule was written against.
 *
 * @since 1.0.5
 */
final class AddOn {

	/**
	 * The constant the add-on defines when it loads.
	 *
	 * Checked rather than the folder name, which somebody can rename, or the
	 * active-plugins option, which needs an include of wp-admin to read
	 * safely this early.
	 *
	 * @since 1.0.5
	 * @var string
	 */
	private const CONSTANT = 'WSAK_PREMIUM_VERSION';

	/**
	 * Where somebody reads about it. Not a checkout, a description.
	 *
	 * @since 1.0.5
	 * @var string
	 */
	private const URL = 'https://wowstudio.dev/products/accessibility-remediation';

	/**
	 * Whether the add-on is installed and running on this site.
	 *
	 * @since 1.0.5
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( self::CONSTANT );
	}

	/**
	 * Whether to mention it at all.
	 *
	 * False once the add-on is here, because advertising something to the
	 * person who already has it is noise, and because that is the single
	 * commonest way a mention turns into a nag.
	 *
	 * @since 1.0.5
	 *
	 * @return bool
	 */
	public static function should_mention(): bool {
		/**
		 * Filters whether the dashboard mentions the paid add-on.
		 *
		 * Returning false removes the mention entirely. Provided for people
		 * who run this for a client and would rather the client never saw it.
		 *
		 * @since 1.0.5
		 *
		 * @param bool $mention Whether to mention the add-on.
		 */
		return (bool) apply_filters( 'wsak_mention_add_on', ! self::is_active() );
	}

	/**
	 * Where the mention points.
	 *
	 * @since 1.0.5
	 *
	 * @return string
	 */
	public static function url(): string {
		return self::URL;
	}
}
