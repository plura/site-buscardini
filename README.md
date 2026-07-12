# Buscardini — placeholder site

One-page "coming soon" placeholder for **buscardini.net**, the parent
brand for three sub-brands: Comunica (strategic communication), LUMU
(association for Guinea-Bissau's future), and BMangu (mango export B2B).
Full-viewport screen with a contact modal and a newsletter signup;
UI copy is PT-PT throughout. Designed in Claude Design and exported as
static HTML/CSS/JS. Deploys straight over FTP to `/public_html/` (see
`.vscode/sftp.json`) — there is no build step or WordPress install here,
just the tracked files in this repo.

## Structure

```
index.html                 the page itself — static, no templating
assets/
├── css/styles.css          all styling; CSS custom properties define the palette
├── js/script.js             modal, contact form, newsletter — fetch() against process/
└── media/                   sub-brand mask SVGs, favicon

process/                   PHP backend for the two dynamic bits of the page
├── .htaccess               rewrites /process/contact and /process/subscribe to their .php handlers
├── config.example.php      committed template — copy to config.php and fill in
├── config.php               real credentials — gitignored, never committed
├── contact.php              handles the CTA contact form, sends both emails below
├── templates/                compiled email HTML consumed by contact.php — committed (no build step on the live server)
│   ├── contact.html          notification to the team
│   └── contact-reply.html    auto-reply to the visitor
├── subscribe.php            handles the Mailchimp email signup field
├── lang.php                 t($text, $lang = 'pt') — dictionary keyed by the English source string
└── lib/PHPMailer/          vendored PHPMailer source (no Composer)

mail-template/              MJML source for transactional emails tied to the contact form
├── contact.mjml             notification sent to the team when the CTA form is submitted
├── contact-reply.mjml       auto-reply confirmation sent back to the visitor
├── dist/                    compiled HTML output — gitignored, generated via npx (see below)
└── _partials/
    ├── _head.mjml            fonts, mj-class design tokens (shared by every template)
    ├── _header.mjml          wordmark + divider
    ├── _footer.mjml          divider + credits
    ├── _credits.mjml         "powered by Plura" line
    └── _fields.mjml          Name/Email/Message rows, reused in both templates
```

## Backend endpoints

- `POST /process/contact` — `{ name, email, message }`. Sends an email via
  PHPMailer over SMTP using the credentials in `process/config.php`.
- `POST /process/subscribe` — `{ email }`. Adds the address to a Mailchimp
  Audience via the Mailchimp Marketing API (double opt-in). PHPMailer is
  not involved in this one — Mailchimp sends its own confirmation email.

`assets/js/script.js` posts both as a **JSON body**
(`Content-Type: application/json`, `fetch` + `JSON.stringify`), not a
form-encoded one — both PHP handlers read `php://input` and `json_decode`
it rather than using `$_POST`, which is never populated for JSON bodies.
Endpoint URLs are set at the top of `script.js` (`CONTACT_URL`,
`SUBSCRIBE_URL`) if they ever need to move.

Both return JSON: `{ "success": bool, "message": string }`, and both
accept an optional `website` field as a spam honeypot — but note the
current form markup in `index.html` doesn't send one, so the honeypot
check is inert until a hidden `website` field is added there. The
frontend also doesn't currently read the `message` field from the
response — it only checks the HTTP status code and shows its own
hardcoded success/error copy either way, so `respond()`'s message text
isn't visible to users yet (still correct to keep it right, since any
future frontend change could start reading it).

Response strings go through `process/lang.php`'s `t($text, $lang = 'pt')` —
a flat dictionary keyed by the English source string (`t('Thanks!')` →
`'Obrigado!'`), not PHP's native `gettext()`. Deliberately skipped native
gettext here: it needs the `gettext` extension enabled server-side plus
OS-level locales, and PHP defines its own `_()` alias when that extension
is loaded — declaring a same-named function risks a fatal redeclare (or a
silent handoff to the real gettext, unconfigured) on hosts where it's on.
`t()` falls back to the original string when a key or language is
missing, so an untranslated string never breaks the response. Only `pt`
is populated for now; add a language by adding a second key under each
dictionary entry.

## Setup before going live

1. Copy `process/config.example.php` to `process/config.php`.
2. Fill in:
   - `smtp` — host/port/username/password for sending the contact email.
   - `contact` — the `to`/`from` addresses for that email.
   - `mailchimp` — API key (format `xxxx-us21`) and Audience/List ID.
3. Deploy via the SFTP extension — `process/config.php` is gitignored but
   will still need to be uploaded manually (or the extension configured to
   include it) since it isn't in git.

Requires `.htaccess`-driven URL rewriting (`RewriteRule`/`AllowOverride`)
for the clean `/process/contact` and `/process/subscribe` URLs to work.
buscardini.net's actual host runs **LiteSpeed (LSAPI)**, not stock
Apache — LiteSpeed honors `.htaccess` in the same syntax and should work
unchanged, but it's not literally Apache, so worth hitting both URLs
directly once deployed to confirm the rewrite resolves rather than
assuming. Confirmed via `phpinfo()` on the live host: PHP 8.5.6,
`openssl` and `curl` both enabled (needed for PHPMailer/SMTP and the
Mailchimp API respectively) — no extension gaps expected there.

## Email templates (mail-template/)

MJML source for the two emails the contact form triggers, following the
Buscardini email design spec (Cormorant Garamond + IBM Plex Mono, warm
off-white palette, square corners, no shadows), copy in European
Portuguese. Wired into `process/contact.php`, which sends the
notification (to the team) and the reply (to the visitor) via PHPMailer.

Run from inside `mail-template/`, no local install — `npx` fetches the
compiler on demand rather than adding a `node_modules/` to the project:

```
npx mjml contact.mjml -o dist/contact.html --config.allowIncludes true --config.includePath .
npx mjml contact-reply.mjml -o dist/contact-reply.html --config.allowIncludes true --config.includePath .

# add -w before the filename to rebuild on save while editing, e.g.:
npx mjml -w contact.mjml -o dist/contact.html --config.allowIncludes true --config.includePath .
```

VS Code's MJML extension is configured (`.vscode/settings.json`, repo
root) to resolve `mj-include` for live preview — that's a separate
mechanism from the `npx` build above, and is where `mjml.allowIncludes`/
`mjml.includePath` actually need to live for the extension to find them.

**After any content/design change to either template**, rebuild both and
copy the output into `process/templates/` — that's what `contact.php`
actually reads at runtime (the live server has no build step, so this
copy has to be committed, unlike `mail-template/dist/`):

```
cp dist/contact.html dist/contact-reply.html ../process/templates/
```

`{{name}}`, `{{email}}`, `{{message}}` are raw placeholder tokens left in
the compiled HTML; `contact.php`'s `getBody()` substitutes them with
`htmlspecialchars()`-escaped submission data (and `nl2br()` for the
message) after loading the file — they sit inside already-styled
elements, so the substitution inherits the correct inline styles without
needing to rebuild the HTML structure.
