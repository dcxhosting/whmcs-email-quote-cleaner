# WHMCS Email Quote Cleaner

**Automatically remove quoted email replies from WHMCS support tickets.** An open-source PHP hook by [DCX Hosting](https://dcx.rs/en/).

WHMCS can import an entire previous email thread when customers respond using Gmail, Outlook, Apple Mail, Thunderbird, Yahoo, Samsung Mail or Mail.ru. This tool identifies common quote boundaries, preserving the new customer message while cleaning redundant history from the **saved ticket record**.

**No WHMCS core modifications. No Composer dependencies. No API keys.**

## Example

**Before:**
```text
Thanks, everything is working now.

On Wed, Oct 14, 2026 at 5:40 PM Support <support@example.com> wrote:
> Your server is ready.
> Previous message and ticket footer...
```

**After:**
```text
Thanks, everything is working now.
```

## Features

- Cleans initial customer messages via `TicketOpen` and later customer replies via `TicketUserReply`.
- Handles dated `On ... wrote:` headers, Outlook `From/Date/Subject`, German Gmail, classic `Original message` and Russian Mail.ru formats.
- Understands HTML-escaped email text and nonbreaking spaces.
- Skips uncertain quote boundaries and preserves messages with no safely detected quote.
- Uses a conditional update to avoid overwriting a concurrently edited message.
- Leaves administrator replies and historical tickets unchanged.
- Supports customizable sender hints for non-DCX installations.

## Installation

Install the **two PHP files** in your WHMCS installation:

```text
includes/hooks/dcx_quote_cleaner.php
includes/hooks/dcx_quote_cleaner/quote_parser.php
```

For Linux installations, from your WHMCS root:

```bash
mkdir -p includes/hooks/dcx_quote_cleaner
cp /path/to/repo/includes/hooks/dcx_quote_cleaner.php includes/hooks/
cp /path/to/repo/includes/hooks/dcx_quote_cleaner/quote_parser.php includes/hooks/dcx_quote_cleaner/
php -l includes/hooks/dcx_quote_cleaner.php
php -l includes/hooks/dcx_quote_cleaner/quote_parser.php
```

Back up existing hook files before overwriting. No WHMCS core changes or PHP-FPM restart should be needed. Test a *new* email-imported ticket and inspect the saved body.

### Custom sender hints

Copy `includes/hooks/dcx_quote_cleaner/config.example.php` to `config.local.php` in the same directory, then replace the example support brand/email domain with your own. Do **not** commit `config.local.php`; it is gitignored.

## How it works

| WHMCS event | Database field | Result |
| --- | --- | --- |
| Customer opens ticket | `tbltickets.message` | Remove safely recognized older email quotes |
| Customer replies | `tblticketreplies.message` | Remove safely recognized older email quotes |
| Administrator replies | None | No changes |
| Existing historical tickets | None | No retroactive changes |

**Important:** These hooks clean the stored message **after the initial write**. Notifications might still include the original, uncleaned message. This is not a pre-send email filter. No support for admin reply cleaning is claimed. Test behavior in your own WHMCS version before production rollout.

WHMCS also has a built-in fixed-string break-line feature. See [Remove Quoted Email Replies](https://docs.whmcs.com/9-1/support/support-tutorials/remove-quoted-email-replies/). This project addresses variable dates, languages and dynamic quote headers.

## Security and contributions

The cleaner does not receive or transmit data to DCX or any third party. It uses your existing WHMCS database connection. Do not post private customer emails or confidential tickets in public issues. For bug reports, provide a reproducible, redacted quote sample.

MIT licensed. Independently maintained community software, **not an official WHMCS product**.

## Built by DCX Hosting

We built this tool to keep support tickets readable while operating Linux hosting and VPS infrastructure. We share practical tools used in daily hosting operations.

- [DCX Hosting](https://dcx.rs/en/): hosting and infrastructure services.
- [Managed VPS hosting in Serbia](https://dcx.rs/en/vps-eu/): NVMe VPS and Linux server management.
- [Managed VPS hosting in Los Angeles](https://dcx.rs/en/vps-usa-lax/): US-based VPS options for international projects.

Looking for a reliable hosting partner? Explore [DCX dedicated servers and VPS solutions](https://dcx.rs/en/).
