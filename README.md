# UstaCRM — customer website

The public site for UstaCRM: what the product does, what it costs, and a form that turns a
visitor into a phone call. It is aimed at furniture workshop owners, not at users who already
have an account — there is no login here and it never talks to the CRM.

**One page: `index.html`.** No build step, no framework, no backend. HTML, CSS and JavaScript
in a single document, the way the CRM itself is written. Open it in a browser and it works.
`dist/` holds the downloadable builds, and `tools/` the one script that refreshes them.

## Hosting

Copy `index.html` **and the `dist/` folder** anywhere that serves static files and the site is
live:

- shared hosting / cPanel — upload it as `index.html` in `public_html`
- GitHub Pages, Netlify, Cloudflare Pages — drop the folder in
- a VPS with nginx or Apache — put it in the web root

It needs no PHP, no database and no configuration. The only thing loaded from outside is the
Google Fonts stylesheet (Bitter, IBM Plex Sans, IBM Plex Mono, Source Serif 4). If the site
must work without any external request, download those four families, save them next to the
file and swap the `<link>` for local `@font-face` rules — the CRM already self-hosts Source
Serif 4 in `prototype/fonts/`, so that copy can be reused.

To preview a change locally, copy the file into `E:\XAMPP\htdocs\` and open
`http://localhost/<folder>/`. Opening it from disk works too, but a real server is closer to
what a customer sees.

## What to edit

Everything a person normally wants to change sits in one of three places.

**Prices** — in the `STR` table at the bottom, under `'plans'`, once per language. Each plan is
`[name, amount, unit, badge, [included], [coming soon], button]`. An item in the *coming soon*
list is rendered greyed with a "soon" flag, which is how features that are planned but not yet
built are shown without promising them. The badge string is what marks a plan as the
recommended one — give it text and the card gains the border and the tag; leave it empty (`''`)
and it is an ordinary card.

**Phone number** — appears four times: the hero, the contact block, the footer, and the
Telegram button. Search for `998905012611`.

**The Telegram button** currently points at `https://t.me/+998905012611`, which resolves only
if that number is reachable that way. If there is a public username — `@ustacrm`, say — use
`https://t.me/ustacrm` instead; it is more reliable and it looks like a business.

**Copy** — all of it lives in `STR`, with one block per language (`uz`, `ru`, `en`). The markup
carries `data-i18n` keys, never sentences, so a wording change is made in one place. Repeated
blocks (the 17 stages, the features, the roles, the plans, the roadmap) are arrays: add an item
to all three languages and it appears on the page. **The 17 stage names are copied from the
CRM's own `prototype/js/i18n.js`** — if a stage is ever renamed there, rename it here too, or
the site will describe a pipeline the software does not run.

## The downloads

The Android and Windows builds are offered on their own platform cards, each with the version,
the size and the date it was built, so a visitor can tell whether they already have that one.
There is nothing to download for the browser — that is the point of it — and nothing for iPhone
until there is a build.

**`#yuklab` links straight to them** — send a customer `https://…/#yuklab` and the page opens at
the downloads rather than at the top. The script re-applies the jump after it has built the
page, because the browser acts on the `#` before the sections exist and would otherwise land in
the wrong place.

Publish a new build with:

```bash
E:/XAMPP/php/php.exe tools/publish_builds.php
```

It copies `ustacrm.apk` and `UstaCRM.exe` out of `E:\Projects\MEBEL_CRM\prototype\dist` (where
the Android and Desktop projects' own publish scripts leave them), keeps their build times, and
writes the version, size and date into the `BUILDS` block in `index.html`. **Do not edit that
block by hand** — it is regenerated, and a figure typed in by hand is a figure that will be
wrong after the next build. Then upload `index.html` and `dist/` together.

If a build is missing from the source folder the script writes `null` for it and its button
disappears from the page. The page checks as well: each button asks the server whether its file
is really there and removes itself if the answer is no, so a site uploaded without `dist/`
offers no dead links. A build served from a different place — object storage, a release page —
just needs its `file` value pointing at that URL.

Two things worth knowing before pointing customers at these. An APK downloaded outside the Play
Store makes the phone ask for permission to install from an unknown source, which the page warns
about. And the licence binds **four devices per user** (raised from two on 2026-08-25, because a
phone, a browser and the desktop app is already three - the desktop app keeps its own WebView2
profile and does not share the browser's slot). A fifth is refused, and the refusal now names
the devices holding the slots; one is freed with `revoke_device.php`.

## The trial form

The site is static, so by default there is nowhere to send a request. Instead the form assembles
the answers into one message and offers three ways to deliver it: copy, Telegram, or a phone
call. Nothing is stored and nothing is sent behind the visitor's back.

If the site is later hosted somewhere with PHP, set `ENDPOINT` (near the bottom of the script)
to a URL and the form will also POST the fields as JSON — `{name, phone, shop, city, size, note}` —
while still showing the message panel, so a failed request never costs a lead. A script of a
dozen lines that appends to a table or emails the row is enough on the receiving end.

## Design notes

The design plan is written in a comment at the top of `index.html` — palette, typefaces and the
layout idea, with the reasoning for each. Read it before making visual changes; it explains why
the logo orange (`#C75B22`) and the interface orange (`#C2561C`) are deliberately different
values, which is the one thing here that looks like a mistake and is not.

Light and dark are both supported through CSS custom properties. Colours are only ever defined
as tokens on `:root` and its dark variants — a colour written directly into a rule will look
correct in one theme and wrong in the other.

## Honesty rules for this page

The site sells software that exists. Four things in the pitch deck are **not built yet** and
appear here only as "soon" or under the roadmap — automatic client notifications over Telegram,
cash accounting, multiple branches, and the iOS app. Keep them there until they ship.

Excel export **was** on that list and came off it on 2026-08-26: `export_orders.php`,
`export_payroll.php` and `export_materials.php` exist, so it is now listed as an ordinary
feature of the Standard plan rather than a promise. That is the only direction this list should
ever move - a feature leaves it when the code lands, never before.

A workshop that buys on a promise and finds the feature missing is a refund and a bad
recommendation in a market that runs on recommendations.
