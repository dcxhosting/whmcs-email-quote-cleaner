# Troubleshooting

If a quoted thread remains, first check whether the email created a new ticket or added a reply. Both routes have separate hook events. Confirm that both PHP files exist in the correct WHMCS `includes/hooks` paths, and check PHP error logs for `[DCX Quote Cleaner]`. The parser intentionally skips unfamiliar quote structures to protect meaningful customer messages.

The extension only updates stored customer ticket messages. It does not clean administrator replies, retroactively process old tickets, or guarantee quote-free outgoing notifications. No server restart is normally necessary.

Do not use generic `wrote:` or `>` deletion patterns: they can remove actual technical messages.
