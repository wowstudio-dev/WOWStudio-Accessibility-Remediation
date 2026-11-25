<?php
/**
 * A skip link, for themes that have none.
 *
 * @package WOWStudio\AccessibilityKit
 */

namespace WOWStudio\AccessibilityKit\SiteFixes\Fixes;

use WOWStudio\AccessibilityKit\SiteFixes\ProvidesCss;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Skip to content" link as the first thing in the page.
 *
 * The single highest-value fix on the list for keyboard users, and the reason is
 * arithmetic: without one, reaching the article on a site with a forty-item menu
 * costs forty Tab presses, on every page, every time. With one it costs two.
 *
 * The link is visible only while focused, which is both the convention and what
 * makes it safe to add to a theme not designed around it — it occupies no space
 * and changes nothing until somebody tabs to it. At that point it has to be
 * plainly visible, so it is styled as a solid panel rather than inheriting
 * whatever the theme does to links.
 *
 * **Where it points, and why that is the hard part.** A skip link whose target
 * does not exist is worse than no skip link: it looks like the problem has been
 * dealt with, and it silently does nothing. This plugin's own
 * `link-anchor-broken` check reports exactly that fault on other people's sites,
 * so shipping it here would be indefensible.
 *
 * Themes disagree about what to call their content element and there is no way
 * to read the theme's markup from `wp_body_open`, which fires before any of it
 * exists. The way out is to supply the target as well as the link: an empty,
 * focusable anchor element is prepended to the post content through
 * `the_content`, which every theme runs and which is a filter for exactly that
 * content rather than a rewrite of the page. The two are printed as a pair or
 * not at all — `wp_body_open` prints the link only in the contexts where a loop
 * is going to run, and the target marks the first piece of content in it.
 *
 * The target carries `tabindex="-1"` because without it browsers move the
 * viewport but not the focus, and the next Tab press continues from the
 * navigation the reader was trying to escape.
 *
 * **The pair came apart once, and it is worth knowing how.** The target was
 * filtered onto `the_content` at priority 1, which is before a page builder
 * gets to it. Elementor renders from its own stored data and replaces the
 * content string wholesale at priority 9, so on a page built with it the link
 * was printed and the target was thrown away: precisely the broken skip link
 * this plugin reports on other people's sites, shipped by the fix meant to
 * prevent it. Filtering last instead of first fixes it for any builder that
 * works this way, not only that one.
 *
 * **"Printed as a pair or not at all" is not something this class can
 * guarantee, and it used to say that it could.** The two hooks do not fire in
 * a fixed order. Under a classic template `wp_body_open` comes first and the
 * content follows; under a block theme the template is assembled before the
 * body is emitted, so `the_content` runs *first* — measured, not assumed, with
 * a probe on a stock Twenty Twenty-Five install:
 *
 *     the_content  len=137  via render_block<do_blocks
 *     wp_body_open fired
 *
 * So neither half can wait to see whether the other happened. An attempt to
 * make the target conditional on the link having been printed removed the
 * target from every block-theme page, which is how the ordering came to light.
 *
 * What that leaves is one benign asymmetry: a template that renders content
 * without ever opening the body — Elementor's header-footer template on a
 * block theme is one — gets a target with no link pointing at it. That is an
 * empty span of zero height, outside the tab order, and no checker reports it.
 * The harmful asymmetry, a link with no target, is the one that is fixed.
 *
 * @since 0.19.0
 */
final class SkipLink implements ProvidesCss {

	/**
	 * The id shared by the link and the target it points at.
	 *
	 * @since 0.19.0
	 * @var string
	 */
	public const TARGET_ID = 'wsak-content';

	/**
	 * Whether the target has already been placed in this request.
	 *
	 * An archive runs the_content once per post. The target belongs on the
	 * first, and a page carrying nine copies of the same id would break every
	 * other thing that resolves one.
	 *
	 * @since 0.19.0
	 * @var bool
	 */
	private bool $target_placed = false;


	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return string
	 */
	public function id(): string {
		return 'skip-link';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'Add a skip link', 'wowstudio-accessibility-remediation' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return string
	 */
	public function description(): string {
		return __( 'Puts a "Skip to content" link at the very start of every page, visible only while it has keyboard focus, and places the target it jumps to at the start of your content. Without one, reaching the article past a large menu can take dozens of key presses on every page.', 'wowstudio-accessibility-remediation' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return string[]
	 */
	public function rule_ids(): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return string
	 */
	public function caveat(): string {
		return __( 'Needs your theme to call wp_body_open(), which themes have done since WordPress 5.2; a much older theme, or a page builder supplying its own page template, may not, and then nothing is printed at all. The link lands at the start of your post content rather than at your theme\'s own main element, so on a page built entirely out of widgets it may skip less than you expect. If your theme already has a skip link, you will now have two.', 'wowstudio-accessibility-remediation' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_body_open', array( $this, 'print_link' ), 1 );

		/*
		 * Last, not first.
		 *
		 * This ran at priority 1 until 1.0.6, which put the target into the
		 * raw post content before anything else saw it. A page builder that
		 * renders from its own storage then replaces that string outright and
		 * the target goes with it, leaving the link pointing at an id that is
		 * not on the page. Elementor does exactly this, at priority 9.
		 *
		 * Running last means the target is prepended to whatever the content
		 * finally became, builder output included, so it cannot be discarded
		 * by anything downstream. It also puts the span after wpautop, which
		 * at priority 1 was free to wrap it in a paragraph of its own.
		 */
		add_filter( 'the_content', array( $this, 'place_target' ), PHP_INT_MAX );
	}

	/**
	 * Prints the link, where there is going to be content to skip to.
	 *
	 * @since 0.19.0
	 *
	 * @return void
	 */
	public function print_link(): void {
		if ( is_admin() || ! $this->context_has_content() ) {
			return;
		}

		printf(
			'<a class="wsak-skip-link" href="#%s">%s</a>',
			esc_attr( self::TARGET_ID ),
			esc_html__( 'Skip to content', 'wowstudio-accessibility-remediation' )
		);
	}

	/**
	 * Prepends the target to the first piece of content rendered.
	 *
	 * @since 0.19.0
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function place_target( string $content ): string {
		if ( $this->target_placed || is_admin() || is_feed() || ! $this->context_has_content() ) {
			return $content;
		}

		$this->target_placed = true;

		// tabindex="-1" so that activating the link moves focus and not only
		// the viewport. Without it the next Tab press carries on from the
		// navigation the reader was trying to skip.
		return sprintf(
			'<span id="%s" tabindex="-1" class="wsak-skip-target"></span>',
			esc_attr( self::TARGET_ID )
		) . $content;
	}

	/**
	 * Reports whether this request is going to render post content.
	 *
	 * @since 0.19.0
	 *
	 * @return bool
	 */
	private function context_has_content(): bool {
		$has_content = is_singular() || is_home() || is_archive() || is_search();

		/**
		 * Filters whether the skip link and its target are printed.
		 *
		 * The pair is printed together or not at all, so this governs both. A
		 * theme with an unusual template can widen or narrow it.
		 *
		 * @since 0.19.0
		 *
		 * @param bool $has_content Whether a loop is expected to run.
		 */
		return (bool) apply_filters( 'wsak_skip_link_applies', $has_content );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.19.0
	 *
	 * @return string
	 */
	public function css(): string {
		return '/* Skip link: off-screen until focused, then a plain visible panel. */
.wsak-skip-link {
	position: absolute;
	left: -9999px;
	top: 0;
	z-index: 100000;
	padding: 12px 20px;
	background: #fff;
	color: #111;
	font-size: 16px;
	font-weight: 600;
	text-decoration: underline;
	border: 2px solid #111;
	border-radius: 0 0 4px 0;
}

.wsak-skip-link:focus {
	left: 0;
	outline: 3px solid #111;
	outline-offset: 2px;
}

/* The target takes no space and shows nothing; it exists to receive focus. */
.wsak-skip-target {
	display: block;
	height: 0;
	overflow: hidden;
}

.wsak-skip-target:focus {
	outline: none;
}';
	}
}
