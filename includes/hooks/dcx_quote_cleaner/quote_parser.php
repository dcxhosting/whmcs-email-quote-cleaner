<?php
/**
 * DCX WHMCS Quote Cleaner: detect a high-confidence quoted-email boundary.
 * Decode entities for matching only. Slice original message to retain escaping.
 */
declare(strict_types=1);
if (!defined('DCX_QUOTE_CLEANER_SENDER_HINTS')) {
    define('DCX_QUOTE_CLEANER_SENDER_HINTS', ['DCX', '@dcx.rs']);
}
if (!function_exists('dcx_quote_is_known_sender')) {
    function dcx_quote_is_known_sender(string $line, ?array $hints = null): bool {
        $hints = $hints ?? DCX_QUOTE_CLEANER_SENDER_HINTS;
        foreach ($hints as $hint) {
            if (!is_string($hint) || trim($hint) === '') continue;
            $pattern = $hint[0] === '@'
                ? '/' . preg_quote($hint, '/') . '(?![\p{L}\p{N}.])/iu'
                : '/(?<![\p{L}\p{N}])' . preg_quote($hint, '/') . '(?![\p{L}\p{N}])/iu';
            if (preg_match($pattern, $line)) return true;
        }
        return false;
    }
}
if (!function_exists('dcx_strip_quoted_reply')) {
    function dcx_strip_quoted_reply(string $message): array {
        if (trim($message) === '' || strlen($message) > 1048576)
            return ['changed'=>false,'message'=>$message,'rule'=>'empty-or-too-large'];
        $lines = preg_split('/\r\n|\n|\r/', $message, -1, PREG_SPLIT_OFFSET_CAPTURE);
        if (!is_array($lines) || count($lines) < 2)
            return ['changed'=>false,'message'=>$message,'rule'=>'not-multiline'];
        $plain = [];
        foreach ($lines as $entry) {
            $s = $entry[0];
            for ($i=0; $i<2; $i++) $s = html_entity_decode($s, ENT_QUOTES|ENT_HTML5, 'UTF-8');
            $plain[] = trim(str_replace(["\xc2\xa0","\xe2\x80\xaf"], ' ', $s));
        }
        $datePattern = '/(?:\b(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun)(?:day|sday|nesday|rsday|urday)?\b|\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b|\b\d{4}-\d{1,2}-\d{1,2}\b|\b\d{1,2}\/\d{1,2}\/\d{2,4}\b|\b\d{1,2}\s+\p{L}{3,12}\s+\d{4}\b)/iu';
        foreach ($lines as $index=>$entry) {
            $line = $plain[$index];
            if ($line === '' || $entry[1] === 0) continue;
            $prev = $plain[$index-1] ?? '';
            $paragraphBoundary = $prev === '' || $prev === '>' || $prev === '|';
            $rule = null;
            if (preg_match('/^On\s.{5,340}?\bwrote\s*:/iu', $line) && preg_match($datePattern, $line)) {
                $quotedBelow = false;
                for ($j=$index+1; $j<min($index+13,count($plain)); $j++) {
                    if (preg_match('/^>/', $plain[$j])) {$quotedBelow=true;break;}
                }
                if ($quotedBelow || dcx_quote_is_known_sender($line)) $rule='dated-wrote';
            } elseif (preg_match('/\bschrieb am\b.{0,160}\d{1,2}/iu',$line) && dcx_quote_is_known_sender($line)) {
                $rule='german-gmail';
            } elseif (preg_match('/^-{3,}\s*Original\s+message\s*-{3,}/iu',$line)) {
                $rule='original-message';
            } elseif (preg_match('/^From\s*:\s*(.*)$/iu',$line,$match) && dcx_quote_is_known_sender($match[1])) {
                $headers=[];
                for ($j=$index+1;$j<min($index+11,count($plain));$j++)
                    if (preg_match('/^(Date|Sent|To|Subject|Cc)\s*:/iu',$plain[$j],$hm)) $headers[strtolower($hm[1])]=true;
                if (count($headers)>=2) $rule='outlook-headers';
            } elseif (preg_match('/^>?\s*(?:Понедельник|Вторник|Среда|Четверг|Пятница|Суббота|Воскресенье).{0,160}\d{4}.{0,140}/iu',$line)
                && dcx_quote_is_known_sender($line)) {
                $rule='russian-mail';
            }
            if ($rule===null || !$paragraphBoundary) continue;
            $before=rtrim(substr($message,0,$entry[1]));
            if ($before==='') continue;
            return ['changed'=>true,'message'=>$before,'rule'=>$rule,'removed_bytes'=>strlen($message)-strlen($before)];
        }
        return ['changed'=>false,'message'=>$message,'rule'=>'no-safe-boundary'];
    }
}
