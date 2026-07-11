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
├── contact.php              handles the CTA contact form
├── subscribe.php            handles the Mailchimp email signup field
└── lib/PHPMailer/          vendored PHPMailer source (no Composer)
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
