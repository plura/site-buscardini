# Buscardini — placeholder site

One-page placeholder site, designed in Claude Design and exported as static
HTML/CSS/JS. Deploys straight over FTP to `/public_html/` (see
`.vscode/sftp.json`) — there is no build step or WordPress install here,
just the tracked files in this repo.

## Structure

```
process/                   PHP backend for the two dynamic bits of the page
├── .htaccess               rewrites /process/contact and /process/subscribe to their .php handlers
├── config.example.php      committed template — copy to config.php and fill in
├── config.php               real credentials — gitignored, never committed
├── contact.php              handles the CTA contact form, sends both emails below
├── contact/                 compiled email HTML consumed by contact.php — committed (no build step on the live server)
│   ├── contact.html          notification to the team
│   └── contact-reply.html    auto-reply to the visitor
├── subscribe.php            handles the Mailchimp email signup field
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

- `POST /process/contact` — `name`, `email`, `message`. Sends an email via
  PHPMailer over SMTP using the credentials in `process/config.php`.
- `POST /process/subscribe` — `email`. Adds the address to a Mailchimp
  Audience via the Mailchimp Marketing API (double opt-in). PHPMailer is
  not involved in this one — Mailchimp sends its own confirmation email.

Both return JSON: `{ "success": bool, "message": string }`. Both also
accept an optional hidden `website` field as a spam honeypot — leave it
empty/unrendered in the real form.

## Setup before going live

1. Copy `process/config.example.php` to `process/config.php`.
2. Fill in:
   - `smtp` — host/port/username/password for sending the contact email.
   - `contact` — the `to`/`from` addresses for that email.
   - `mailchimp` — API key (format `xxxx-us21`) and Audience/List ID.
3. Deploy via the SFTP extension — `process/config.php` is gitignored but
   will still need to be uploaded manually (or the extension configured to
   include it) since it isn't in git.

Requires Apache with `mod_rewrite` and `AllowOverride` enabled for the
clean `/process/contact` and `/process/subscribe` URLs to work (standard
on cPanel-style shared hosting).

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
copy the output into `process/contact/` — that's what `contact.php`
actually reads at runtime (the live server has no build step, so this
copy has to be committed, unlike `mail-template/dist/`):

```
cp dist/contact.html dist/contact-reply.html ../process/contact/
```

`{{name}}`, `{{email}}`, `{{message}}` are raw placeholder tokens left in
the compiled HTML; `contact.php`'s `getBody()` substitutes them with
`htmlspecialchars()`-escaped submission data (and `nl2br()` for the
message) after loading the file — they sit inside already-styled
elements, so the substitution inherits the correct inline styles without
needing to rebuild the HTML structure.
