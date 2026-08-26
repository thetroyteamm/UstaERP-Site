# UstaCRM — customer website

The public site for UstaCRM, a workflow CRM for custom furniture workshops. It is written for
an owner deciding whether to buy: what the product does, what it costs, and a form that turns a
visitor into a phone call. There is no login here, and the page never talks to the CRM itself.

**Live at <https://thetroyteamm.github.io/UstaCRM-Site/>**

**One page: `index.html`.** No build step, no framework, no backend — HTML, CSS and JavaScript
in a single document, in Uzbek, Russian and English. `dist/` holds the builds a visitor can
download, and `tools/` the one script that refreshes them.

## Hosting

GitHub Pages publishes this repository from `master`, folder `/ (root)`. The `.nojekyll` file
keeps Pages from running the files through Jekyll; there is nothing here for it to build.

Nothing ties the site to that host. Anywhere static works — shared hosting, Netlify, Cloudflare
Pages, nginx on a VPS — copy `index.html` **and the `dist/` folder** into the web root. No PHP,
no database, no configuration.

The only request that leaves the visitor's browser is the Google Fonts stylesheet (Bitter, IBM
Plex Sans, IBM Plex Mono, Source Serif 4). To run with no external request at all, download
those four families, save them beside the page, and swap the `<link>` for local `@font-face`
rules.

To check a change before publishing it, serve the folder with any static file server and open
it in a browser. Opening the file straight from disk mostly works, but a real server behaves
the way a visitor's will.

## What to edit

Everything anyone normally wants to change sits in one of four places.

**Prices** — in the `STR` table near the bottom, under `'plans'`, once per language. A plan is
`[name, amount, unit, badge, [included], [coming soon], button]`. Anything in the *coming soon*
list renders greyed with a "soon" flag, which is how something planned is shown without being
promised. The badge marks the recommended plan: give it text and the card gains the border and
the tag, leave it empty (`''`) and it is an ordinary card.

**Phone number** — appears four times: the hero, the contact block, the footer and the Telegram
button. Search for `998905012611`.

**The Telegram button** points at a `t.me` link built from that number, which only resolves if
the number is reachable that way. A public username — `https://t.me/ustacrm`, say — is more
reliable and reads better to a customer.

**Copy** — all of it lives in `STR`, one block per language (`uz`, `ru`, `en`). The markup
carries `data-i18n` keys, never sentences, so a wording change happens in one place. The
repeated blocks — the 17 production stages, the features, the roles, the plans, the roadmap —
are arrays: add an item in all three languages and it appears on the page.

The 17 stage names are copied from the CRM's own translation table rather than retranslated. If
a stage is ever renamed there, rename it here too, or the site will describe a pipeline the
software does not run.

## The downloads

The Android and Windows builds are offered on their own platform cards, each showing the
version, the size and the date it was built, so a visitor can tell whether they already have
that one. There is nothing to download for the browser — that is rather the point of it.

**`#yuklab` links straight to them.** Send someone
<https://thetroyteamm.github.io/UstaCRM-Site/#yuklab> and the page opens at the downloads
instead of the top. The script re-applies that jump once it has built the page, because the
browser acts on the `#` before the sections exist and would otherwise land in the wrong place.

Publish a new build with:

```bash
php tools/publish_builds.php
```

It copies the APK and the exe out of the CRM project's own `dist` folder — the path is set at
the top of the script — keeps their build times, and writes the version, size and date into the
`BUILDS` block in `index.html`. **Do not edit that block by hand:** it is regenerated, and a
figure typed in by hand is a figure that will be wrong after the next build. Commit and deploy
`index.html` and `dist/` together.

If a build is missing from the source folder, the script writes `null` for it and its button
disappears. The page checks independently: each button asks the server whether its file is
really there and removes itself if the answer is no, so a site published without `dist/` offers
no dead links. A build hosted somewhere else — object storage, a release page — only needs its
`file` value pointed at that URL.

One thing to expect when pointing customers at the APK: downloaded outside the Play Store, the
phone asks permission to install from an unknown source. The page warns about this above the
cards.

## The trial form

The site is static, so by default there is nowhere to send a request. The form assembles the
answers into one message and offers three ways to deliver it — copy, Telegram, or a phone call.
Nothing is stored, and nothing is sent anywhere behind the visitor's back.

If the site later moves somewhere with a backend, set `ENDPOINT` near the bottom of the script
and the form will also POST the fields as JSON — `{name, phone, shop, city, size, note}` —
while still showing the message panel, so a failed request never costs a lead.

## Design notes

The design plan sits in a comment at the top of `index.html`: palette, typefaces and the layout
idea, with the reasoning for each. Read it before making visual changes. It explains, among
other things, why the logo orange (`#C75B22`) and the interface orange (`#C2561C`) are
deliberately different values — the one thing here that looks like a mistake and is not.

Light and dark are both supported through CSS custom properties. Colours are only ever defined
as tokens on `:root` and its dark variants; a colour written directly into a rule will look
right in one theme and wrong in the other.

Line endings are pinned by `.gitattributes` so the repository stores LF whatever the machine
committing it prefers.

## What the page may claim

Only what the software does today. Anything still being built is shown greyed with a "soon"
flag, or listed under the roadmap — never among the things the product does. Items move up that
list when the code ships, and never before. A workshop that buys on a promise and finds the
feature missing is a refund, in a market that runs on recommendations.
