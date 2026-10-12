/**
 * The one place the free plugin says a paid add-on exists.
 *
 * Rendered at the foot of the dashboard and nowhere else, above the
 * disclaimer, and not rendered at all once the add-on is installed. It is a
 * paragraph with a link in it, deliberately: no button styled as a call to
 * action, no padlock, no disabled control standing in for a feature somebody
 * does not have. Nothing in this plugin is locked, so there is nothing to put
 * a padlock on.
 *
 * See src/Support/AddOn.php for where the product line sits.
 */

import { __ } from '@wordpress/i18n';

const settings = window.wsakSettings ?? {};
const addOn = settings.addOn ?? {};

export default function AddOn() {
	if ( ! addOn.mention || ! addOn.url ) {
		return null;
	}

	return (
		<aside className="wsak-addon" aria-labelledby="wsak-addon-title">
			<h2 className="wsak-addon__title" id="wsak-addon-title">
				{ __(
					'Scheduled re-checking',
					'wowstudio-accessibility-remediation'
				) }
			</h2>
			<p className="wsak-addon__body">
				{ __(
					'This plugin checks when you ask it to. A separate plugin, Accessibility Remediation Premium, runs the same checks on a schedule and tells you what changed since the last one — which is the part that catches a problem somebody introduced last Tuesday.',
					'wowstudio-accessibility-remediation'
				) }
			</p>
			<p className="wsak-addon__body">
				{ __(
					'It is a paid add-on. Nothing here becomes locked, limited or worse because it exists: every check, every severity and the whole-site scan stay free, with no page limit.',
					'wowstudio-accessibility-remediation'
				) }
			</p>
			<p className="wsak-addon__link">
				<a href={ addOn.url } rel="noopener noreferrer" target="_blank">
					{ __(
						'Read what it does',
						'wowstudio-accessibility-remediation'
					) }{ ' ' }
					<span className="screen-reader-text">
						{ __(
							'(opens in a new tab)',
							'wowstudio-accessibility-remediation'
						) }
					</span>
				</a>
			</p>
		</aside>
	);
}
