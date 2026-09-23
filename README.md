# WOWStudio Accessibility Remediation

A free WordPress accessibility plugin that finds, fixes and documents WCAG 2.2
issues **in your own code**. It scans every page twice, shows each finding on a
live preview of the real page, and proposes changes to your markup and your own
CSS that you review before anything is applied.

It is not an overlay. No widget or toolbar is ever added to your front end for
your visitors — a site-wide fix enqueues only the CSS or the small script that
fix needs, and nothing at all is enqueued until you turn one on. It makes no
outbound requests of any kind — no account, no API, no telemetry, no
AI. It never tells anybody their site is compliant, because no tool can.

**Install it from the WordPress Plugin Directory:**
<https://wordpress.org/plugins/wowstudio-accessibility-remediation/>

See [`CLAUDE.md`](CLAUDE.md) for the non-negotiable product rules and
[`SPEC.md`](SPEC.md) for the full feature specification and build order.

## Status

**Published.** Version 1.0.4 is live in the WordPress Plugin Directory:
<https://wordpress.org/plugins/wowstudio-accessibility-remediation/>

This repository is the source for what ships there, including the JavaScript
and CSS that are distributed compiled. Keeping it public and current is a
condition of staying in the directory, not a courtesy.

One plugin, free, nothing gated. There is no licensing SDK, no paid tier and no
AI; see [`CHANGELOG.md`](CHANGELOG.md) for what went and why.

What works: the two-pass scanner (41 checks — 36 on the server, 5 in the
browser), the inspector with a live page preview, the block-editor panel,
deterministic CSS fixes with preview and undo, fourteen site-wide fixes,
site-wide scanning, the bulk alt-text editor, theme triage, the overview
dashboard, the accessibility-statement generator, reading-level checking with a
plain-language summary, WP-CLI, and the admin columns.

What is next: three checks the current passes cannot reach — text too small to
read, carousels, and animated GIFs — testing against the page builders beyond
Elementor, and a screen-reader pass over the admin with disabled users, which
is the largest gap and the one no tooling closes. [`CLAUDE.md`](CLAUDE.md) has
the list.

## How it compares

Honest version, because you should pick the right tool rather than this one.

**[Equalize Digital Accessibility Checker](https://wordpress.org/plugins/accessibility-checker/)**
(10,000 installs, 98/100 from 78 ratings) is the mature choice and checks a page
as you edit it. Use it if you want a long track record and a company behind it
with paid support. Its full-site scanning, its reports and its centralised
results are part of the paid tier; ours are not, which is the main practical
difference.

**[WP Accessibility](https://wordpress.org/plugins/wp-accessibility/)** by Joe
Dolson (60,000 installs, 96/100 from 68 ratings) patches common theme faults
automatically and is written by a W3C contributor. It is not a scanner and does
not try to be. If all you want is the automatic markup patches and no findings
UI, it is lighter than this and you should use it. Several of our fourteen
site-wide fixes cover the same ground.

**Overlay widgets** — accessiBe, UserWay, AudioEye and the rest — inject a
script that layers controls over your site and change nothing underneath. They
are rejected by the disabled people they claim to serve, and one of them has
drawn an FTC action over its marketing. Do not use one. The
[Overlay Fact Sheet](https://overlayfactsheet.com/), signed by several hundred
accessibility practitioners, sets out why at length.

**Use this one if** you want the whole site scanned without paying, fixes that
are written into your own code and survive the plugin being uninstalled, and a
tool that reports what it cannot detect as plainly as what it can.

**Nothing here replaces a person.** Automated testing reaches part of WCAG and
no more. Meaningful sequence, focus order and whether your alt text is actually
accurate all need a human being, and testing with disabled users is not
optional.

## Requirements

| Dependency | Version |
|---|---|
| WordPress | 6.8+ |
| PHP | 8.1+ |
| Node | 20+ |
| Docker | required for `wp-env` and for running the PHP test suite locally |

## Setup

```bash
composer install
npm install
npx wp-env start      # WordPress at http://localhost:8888 (admin/password)
```

## Quality gate

Run the whole gate before every commit:

```bash
composer lint
```

That runs, in order:

| Command | Checks |
|---|---|
| `composer phpcs` | WordPress-Core, -Extra, -Docs, PHPCompatibilityWP |
| `composer phpstan` | Static analysis at level 6 |
| `composer test` | PHPUnit unit suite |
| `composer check-claims` | No unqualified compliance claims anywhere |
| `composer check-disclosures` | Every honesty caveat is still on the screen that needs it |
| `npm run plugin-check` | The official WordPress Plugin Check |

`composer lint` is the PHP half. The full 10-step gate — which also covers the
JavaScript, the stylesheet, the colour contrast of our own interface, and the
staleness of the translation template — is:

```bash
bash bin/gate.sh
```

`npm run plugin-check` and `npm run makepot` both build the plugin into `dist/`
first and run against that, not the working tree — otherwise they see
`node_modules`, dev Composer dependencies, and tooling config as shipped files.
See [`docs/RELEASE-CHECKLIST.md`](docs/RELEASE-CHECKLIST.md) for what a release
run involves.

Build the release artifact with:

```bash
npm run dist:zip
```

## One build

There is one build and no flavours. `bin/build.sh` produces exactly what ships,
so what the gate tests is what a user installs — which was never quite true
while a server-side stripper generated the real zip from a premium tree.

### Running tests locally

Brain Monkey depends on Patchwork, which does not support PHP 8.5. If your host
PHP is newer than 8.4, run the suite in a container:

```bash
docker run --rm -v "$PWD":/app -w /app php:8.1-cli php vendor/bin/phpunit
```

CI runs the suite on PHP 8.1, 8.2, 8.3, and 8.4.

## Two guards worth knowing about

Both are wired into CI and both fail the build.

**`bin/check-claims.php`** enforces product rule #1 — assist, never guarantee.
It fails on any unqualified use of "compliant", "certified", "guaranteed",
"lawsuit", and similar, in any file that ships. A line is allowed only if it
negates the claim, carries a `wsak:claim-reviewed` annotation, or appears
verbatim in `bin/claims-allowlist.txt`.

**`bin/check-disclosures.php`** is the other half of the same idea. The claim
guard stops the plugin saying something it must not; this stops it quietly
dropping something it must say. Product rules 1, 4 and 5 all rest on ordinary
strings — the caveat under the score, the coverage lede, the draft banner on an
unsigned statement — and any of those can be deleted in a routine refactor
without breaking a test. Each surface it names must keep carrying its
disclosure; reword them freely, but update the pattern when you do.

## Repository layout

```
wowstudio-accessibility-remediation.php   Bootstrap: headers, constants, requirement
                                  checks, lifecycle hooks including uninstall
src/Core/                         Orchestrator, activation, installation
src/Db/                           Schema, repositories, typed records
src/Scanner/                      Engine, rules, registry, page fetching
src/AltText/                      Finding images that have never been described
src/Jobs/                         Action Scheduler queue and the bulk-scan worker
src/Remediation/                  The override layer, diffing, and fix review
src/SiteFixes/                    Site-wide fixes and the registry they hang off
assets/front/                     The one front-end script, shipped unbundled
src/Conformance/                  The accessibility statement and its sign-off
src/Rest/                         REST controllers
assets/src/                       React admin app (source)
build/                            Compiled admin app (generated, not tracked)
src/Admin/                        Admin menu
src/Support/                      Shared helpers (capabilities)
bin/build.sh                      Release build (honours .distignore)
bin/gate.sh                       The 10-step quality gate
bin/                              Product guards
docs/RELEASE-CHECKLIST.md         What a human must verify before release
```

## Scanning

```
POST /wp-json/wsak/v1/scan       { "post_id": 12 }  requires wsak_run_scan
GET  /wp-json/wsak/v1/scans/<id>                    requires wsak_view_reports
GET  /wp-json/wsak/v1/scannable                     requires wsak_view_reports
GET  /wp-json/wsak/v1/coverage                      requires wsak_view_reports
GET  /wp-json/wsak/v1/overview                      requires wsak_view_reports
GET  /wp-json/wsak/v1/media                         requires wsak_apply_fix
POST /wp-json/wsak/v1/media/alt                     requires wsak_apply_fix
GET  /wp-json/wsak/v1/site-fixes                    requires wsak_view_reports
POST /wp-json/wsak/v1/site-fixes/<id>               requires wsak_manage_settings
GET  /wp-json/wsak/v1/fixes/css                     requires wsak_apply_fix
POST /wp-json/wsak/v1/fixes/css                     requires wsak_apply_fix
DEL  /wp-json/wsak/v1/fixes/css/<issue_id>          requires wsak_apply_fix
POST /wp-json/wsak/v1/runs                          requires wsak_run_scan
GET  /wp-json/wsak/v1/runs/<id>                     requires wsak_view_reports
GET  /wp-json/wsak/v1/statement                     requires wsak_view_reports
POST /wp-json/wsak/v1/statement                     requires wsak_manage_settings
POST /wp-json/wsak/v1/statement/attest              requires wsak_manage_settings
POST /wp-json/wsak/v1/statement/withdraw            requires wsak_manage_settings
```

## The accessibility statement

Published with the `wowstudio/accessibility-statement` block or the
`[wsak_accessibility_statement]` shortcode. Both render on the server from
current settings, so what visitors see is always the statement as it now stands.

The plugin never claims conformance on anybody's behalf: every conformance
sentence is attributed to the organisation by name. An unsigned statement
publishes as a draft saying nobody has checked it, sign-off is refused while the
statement is unfinished, and **editing it withdraws the sign-off** so an approved
statement cannot come to say something its approver never read.

## The override layer

Applying a fix never edits post content. The correction is stored as a pair of
"this element" and "this element instead", and substituted as the page renders.
Undo is a flag, not a restore, and deactivating the plugin returns every page to
its original markup by ceasing to filter.

Matching happens in the DOM, not on the string, because a scan reads the
rendered page — where WordPress has already added attributes like
`decoding="async"` — while the override runs over post content, which has
fewer. See `Remediation\Substitution` for the direction that tolerance runs in
and why.

No route returns an API key. Reading the AI settings tells you whether a key is
stored and shows a masked hint; that is the most any caller can learn.

The scanner fetches the whole rendered page over a loopback request, because the
page language, title, landmarks, and most of the theme's markup live outside
post content. If loopback requests are blocked — many hosts block them, and
`wp-env` cannot reach its own mapped port — the scan falls back to post content
alone and labels the result: `full_page` is `false` and `coverage_notice`
explains what was skipped. The document-level rules recognise a fragment and
stay quiet, so a reduced scan under-reports rather than inventing failures.

To supply markup yourself, or to test the full-page path locally, filter
`wsak_page_html`. To make a loopback failure a hard error instead, return false
from `wsak_allow_content_fallback`.

## The admin app

Source lives in `assets/src`, compiled output in `build/`. `build/` is generated
and not tracked, so `bin/build.sh` compiles it and refuses to produce a release
without it.

```bash
npm run start     # watch
npm run build     # one-off
```

Translatable strings live in the JavaScript sources, not the minified bundle, so
`npm run makepot` copies `assets/src` into the build for the duration of the
extraction. Running `wp i18n make-pot` against the build alone silently drops
every string in the app.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
