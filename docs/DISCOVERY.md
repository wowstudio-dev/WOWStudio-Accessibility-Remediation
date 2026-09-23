# Being found

How people and machines find this plugin, what has been done about it, and
what is left that only a person with the passwords can do.

Written 2026-09-24, a couple of days after 1.0.4 went live with fewer than ten
installs and no reviews.

## Three channels, three different games

**The plugin directory's own search** is where most installs come from, and it
is not Google. It weights the plugin title, the short description, the tags,
the active install count, the rating, how fast support threads get resolved,
and how recently `Tested up to` was touched. Most of those are earned rather
than written.

**Google** mostly does not send people to plugin pages. It sends them to
listicles — "7 best WordPress accessibility plugins" — and the listicle sends
them on.

**Assistants** work the same way, and the evidence is easy to reproduce. Ask
one for the best WordPress accessibility plugin and read where the answer came
from: blog roundups, essentially never the directory. Two of the roundups
quoted back at us on 2026-09-24 were written by vendors recommending their own
plugin, and one by an overlay company. The assistant repeated both as neutral.

So the directory page is where somebody lands once they already have the name.
It is not where they get the name.

## Done

- **The title now carries the words people type.** It was
  "WOWStudio Accessibility Remediation"; nobody searches for "remediation". The
  displayed title comes from the first line of `readme.txt` and is free to
  change — the slug is what is permanent. The approved name stays at the head
  because the naming round of review is not worth reopening.
- **Tag `accessibility scanner` traded for `accessibility checker`**, which is
  the term the category is actually known by. Five is the maximum and all five
  are used. "Scanner" now lives in the title instead.
- **Four questions added to the readme FAQ** — what it checks, how it differs
  from the others, page builders, and whether it slows a site down. These are
  real queries, and an FAQ block is one of the few things on a directory page
  that gets quoted wholesale by an assistant.
- **The GitHub repository had no description, no homepage and no topics.** All
  three are indexed, both by GitHub's own search and by the crawlers behind
  assistant answers. Set on 2026-09-24.
- **An honest comparison section in `README.md`**, naming Equalize Digital and
  Joe Dolson's WP Accessibility with their real install counts, and saying
  plainly when to use them instead. This is not modesty. A comparison that
  concedes something is the kind that gets cited; a page that claims to win on
  every axis gets ignored by readers and models alike.
- **`.github/workflows/plugin-release.yml`**, which did not exist. The website
  reads every version number it shows from `src/data/plugin.json` in the
  website repository, and that file says in its own comment that this workflow
  maintains it. It sat at 1.0.0 through four releases.

## The claims rule still applies to all of it

`bin/check-claims.php` blocks "compliant", "certified", "guaranteed" and the
rest in shipped strings. It does not police a blog post, a pitch email, or a
title someone is tempted to pad with "ADA, EAA and Section 508". Equalize
Digital does exactly that and it plainly helps them rank. It is still not worth
doing here: the whole argument for this plugin is that it tells the truth about
what automation reaches, and the first person to notice a contradiction will be
the accessibility practitioner whose opinion decides everything else.

## Only a person can do these

**Verify the domains.** [Google Search Console](https://search.google.com/search-console)
and [Bing Webmaster Tools](https://www.bing.com/webmasters) for `wowstudio.dev`,
by DNS TXT record so it survives a rebuild. Bing is not optional here — ChatGPT's
web search runs on Bing's index. Neither can be pointed at the directory page;
that domain is Automattic's. Both report, neither improves anything by itself.

**Add the `WEBSITE_REPO_TOKEN` secret** to this repository: a fine-grained PAT
with Contents read and write on `wowstudio-dev/wowstudio-website`. Without it
the sync workflow logs a warning and skips.

**Answer every support thread, fast.** The resolution rate is a visible, ranked
number on the directory. At this install count each thread also moves the
average measurably.

**Keep `Tested up to` current** with each WordPress major. Three versions
behind and the directory flags the plugin and pushes it down its own search.

**Ask for reviews once, in the plugin, dismissable forever.** Zero reviews is
the single biggest ranking handicap and the one copy cannot touch. Anything
more insistent than once violates the same guideline the review team already
raised over the activation redirect.

**Pitch the roundups.** Their authors take submissions and most of them are
looking for a non-overlay pick that is not the same three plugins. Lead with
what nothing else in the category offers: no outbound requests, no account, no
telemetry, no AI, no paid tier, whole-site scanning free.

**Turn up where the argument already happens.** WordPress Accessibility Day,
the `#accessibility` channel in WordPress Slack, the practitioners around the
[Overlay Fact Sheet](https://overlayfactsheet.com/). They agree with this
product's entire thesis already.

## Not this

No seeded Reddit threads, no reviews we did not earn, no paid placement dressed
as a review, no keyword padding. Beyond being dishonest, the accessibility
community is small and unusually good at spotting it, and it is the one
audience this product cannot afford to lose. The fact sheet has several hundred
signatories who all know each other.

## Numbers as of 2026-09-24

| | Installs | Rating |
| --- | --- | --- |
| WP Accessibility | 60,000 | 96/100, 68 ratings |
| Equalize Digital Accessibility Checker | 10,000 | 98/100, 78 ratings |
| This | fewer than 10 | none |

From the directory's own API. Worth re-reading before deciding anything here
is working.
