# UstaERP — customer website

The public site for UstaERP, a workflow CRM for custom furniture workshops. It is written for
an owner deciding whether to buy: what the product does, what it costs, and a form that turns a
visitor into a phone call.

There is a sign-in screen, but no login: the page never receives a password and never talks to
the CRM. It routes a returning customer to their own workshop's server, which is the machine
that can check one. See **Signing in** below.

**The domain is `ustaxona.com`.** Nothing is published there yet — the site is being finished
first. `CNAME` in this repository already names it, so the day it goes live is the day the DNS
is pointed and Pages is told about the domain.

**One page: `index.html`.** No build step, no framework, no backend — HTML, CSS and JavaScript
in a single document, in Uzbek, Russian and English. `dist/` holds the builds a visitor can
download, and `tools/` the one script that refreshes them.

## Hosting

GitHub Pages publishes this repository from `master`, folder `/ (root)`. The `.nojekyll` file
keeps Pages from running the files through Jekyll; there is nothing here for it to build.

### The domain

`ustaxona.com` is registered through PS Cloud, so the records below are set in the DNS panel at
<https://console.pscloud.uz/domains>. The apex serves this site; every workshop gets a
subdomain of it, which is how the CRM tells one workshop from another — `resolveLoginTenant()`
in `api/tenant.php` reads the first label of the Host header, so `usta1.ustaxona.com` *is* the
workshop with slug `usta1`.

| Name | Type | Value | What it is |
|---|---|---|---|
| `@` | A | `185.199.108.153`, `185.199.109.153`, `185.199.110.153`, `185.199.111.153` | this site, on GitHub Pages |
| `www` | CNAME | `thetroyteamm.github.io` | the same site |
| `*` | A | the VPS address | every workshop, on the server that runs the CRM |

The wildcard is what makes a new customer a DNS non-event: `usta7.ustaxona.com` resolves the
day the tenant row is created, with nobody touching the zone. It must not point at Pages —
Pages would answer for it with this marketing page.

After the records propagate, set the custom domain in the repository's Pages settings (it reads
`CNAME` and will offer it) and turn **Enforce HTTPS** on. Do not skip that: without it the site
answers on plain HTTP, and a sign-in screen served over HTTP is a sign-in screen anybody on the
same Wi-Fi can rewrite.

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
the number is reachable that way. A public username — `https://t.me/ustaerp`, say — is more
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

**They are off the page for now.** The site is information only while the product is being
finished: `APP_BUILDS` is `null`, so no build is linked from anywhere and each platform card
carries the same "soon" flag the pricing table uses. To put the downloads back, name the builds
in `APP_BUILDS` in the order the cards are listed and drop the `true` from the `fillCards` call
beside it. Everything below describes how that worked and will work again.

**`#yuklab` links straight to them.** Send someone
<https://thetroyteamm.github.io/UstaERP-Site/#yuklab> and the page opens at the downloads
instead of the top. The script re-applies that jump once it has built the page, because the
browser acts on the `#` before the sections exist and would otherwise land in the wrong place.

Publish a new build with:

```bash
php tools/publish_builds.php
```

It copies the APK and the exe out of the CRM project's own `dist` folder, keeping their build
times, and writes the version, size and date into the
`BUILDS` block in `index.html`. **Do not edit that block by hand:** it is regenerated, and a
figure typed in by hand is a figure that will be wrong after the next build. Commit and deploy
`index.html` and `dist/` together.

The script expects the CRM project to sit beside this one. If it lives somewhere else, point
`USTAERP_BUILDS` at the folder holding the published builds:

```bash
USTAERP_BUILDS=/path/to/prototype/dist php tools/publish_builds.php
```

If a build is missing from the source folder, the script writes `null` for it and its button
disappears. The page checks independently: each button asks the server whether its file is
really there and removes itself if the answer is no, so a site published without `dist/` offers
no dead links. A build hosted somewhere else — object storage, a release page — only needs its
`file` value pointed at that URL.

One thing to expect when pointing customers at the APK: downloaded outside the Play Store, the
phone asks permission to install from an unknown source. The page warns about this above the
cards.

## Signing in

The screen behind the header's **Kirish** button, and at `#kirish` so it can be linked
directly. It does not sign anybody in — a static page has nothing to check a password against,
and one that asked for a workshop password would be indistinguishable from a phishing page.
What it does is route a person to the machine that can: a workshop lives at its own address and
its own server holds the password. The panel says so in as many words, in all three languages.

Everything is switched from the `AUTH` block near the bottom of the script:

```js
const AUTH = {
  live: false,          // the product is in preparation; every route says so
  domain: 'ustaxona.com',
  googleClientId: '',   // OAuth client id, Google Cloud console
  telegramBot: '',      // login bot username
  endpoint: ''          // where a provider token is POSTed to be checked
};
```

**`live`** is the one to flip first. While it is false, the workshop-address route and both
provider buttons answer with "the system is not open yet" instead of sending somebody to a host
that answers nothing. Set it to `true` once there is a workshop to reach.

**The workshop address** works the moment `live` is true and needs no backend at all. Somebody
types `usta1`, or pastes `https://usta1.ustaxona.com/login.html`, and lands on that workshop's
own login page; the address is remembered and offered back next time. A self-hosted install at
its own domain works too — anything with a dot in it is taken as a complete host, which matches
what the Android app already accepts.

**The provider buttons stay greyed with a "soon" flag until `endpoint` names a server**, and
that is not a formality. A Google ID token is a claim, not a proof, until a server has checked
its signature against Google's published keys, along with the audience, the issuer and the
expiry; a Telegram login is an HMAC of the bot token. Neither check is possible in a browser,
and nothing the browser can read out of a token can be trusted — a token says whatever the
person presenting it wants it to say. A site that read the email address out of an unverified
token and let somebody in on it would be handing out accounts to anyone who could type one.

So the endpoint has to exist first, in the CRM rather than here. What it must do:

- accept `{provider, payload}` over HTTPS from this origin only, and answer CORS accordingly;
- verify the payload with the provider — Google's certs for an ID token, an HMAC over the
  sorted fields for a Telegram login — and reject anything expired, replayed, or issued to a
  different `aud`;
- look the verified identity up against a workshop, never create one implicitly;
- return `{workshop: "usta1"}`, the slug alone. The page rebuilds the destination from it
  rather than following a URL, so a compromised endpoint can misdirect somebody to the wrong
  workshop but cannot change the scheme or send them off-site.

Until then the buttons are honest about being unfinished, which is the whole reason they are
drawn greyed rather than hidden.

## Security

The page is static, holds no secrets, and asks for no credentials. What is left to get right is
what it is allowed to load and what the host says about it.

**Content-Security-Policy** is set in a `<meta>` in the head. It starts from `default-src
'none'`, so the only things this page can reach are the Google Fonts stylesheet and its font
files, and — once sign-in is switched on — Google's and Telegram's identity endpoints. An
injected `<script src>` pointing anywhere else does not run. `'unsafe-inline'` is unavoidable
while the CSS and the script live in the document, which is the trade this one-file site makes.

**Three directives cannot come from a meta tag** and have to be sent as headers by whatever
serves the page: `frame-ancestors 'none'` (nobody frames this site), `Strict-Transport-Security`
and `X-Content-Type-Options: nosniff`. GitHub Pages cannot set custom headers at all, so on
Pages those are simply absent; putting Cloudflare in front, or serving from the VPS, is what
buys them. Enforce HTTPS regardless.

**No password is ever typed on this host.** The sign-in screen collects a workshop address and
nothing else, and says outright that UstaERP will never ask for a password here — which is the
sentence that lets a customer recognise a fake later.

**Nothing executable is offered from this domain** while the product is in preparation. Note
that taking the buttons off the page is not the same as taking the files off the host: anything
left in `dist/` stays fetchable by anyone who guesses the URL, so clear that folder before the
site is published, or move the builds behind a login when they come back.

**The redirect cannot be aimed from outside.** The only thing that opens the sign-in screen is
`#kirish`, which carries no workshop name; the address is typed by the person signing in, or
returned by the endpoint, and either way is validated as a host name before anything is built
from it. There is no URL parameter anywhere on this page that decides where a visitor is sent.

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
