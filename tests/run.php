<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/hooks/dcx_quote_cleaner/quote_parser.php';
$cases = [
    ['gmail', "Thanks!\n\nOn Wed, Oct 14, 2026 at 5:40 PM Support <support@example.com> wrote:\n> Old reply", true],
    ['thunderbird', "Hello\n\nOn 2026-10-08 01:29, DCX wrote:\n> Old reply", true],
    ['samsung', "OK\n\n-------- Original message --------From: DCX <support@dcx.rs>", true],
    ['technical', "Technical note:\n\nFrom: some user\nThis is an example", false],
    ['no-quote', "Plain customer reply", false],
];
$failures = 0;
foreach ($cases as [$label, $text, $expected]) {
    $result = dcx_strip_quoted_reply($text);
    if ($result['changed'] !== $expected) {
        fwrite(STDERR, "FAIL: $label\n");
        $failures++;
    } else {
        echo "PASS: $label\n";
    }
}
exit($failures > 0 ? 1 : 0);
