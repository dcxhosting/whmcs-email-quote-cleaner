<?php
/**
 * WHMCS Email Quote Cleaner by DCX Hosting.
 * Cleans stored client messages. Notification emails may retain original text.
 */
declare(strict_types=1);
if (!defined('WHMCS')) exit('Direct access denied');
$config = __DIR__ . '/dcx_quote_cleaner/config.local.php';
if (is_file($config)) require_once $config;
require_once __DIR__ . '/dcx_quote_cleaner/quote_parser.php';
if (!defined('DCX_QUOTE_CLEANER_DEBUG')) define('DCX_QUOTE_CLEANER_DEBUG', false);
if (!function_exists('dcx_quote_clean_stored_message_v3')) {
    function dcx_quote_clean_stored_message_v3(string $table,int $rowId,int $ticketId,bool $initial): void {
        if ($rowId<=0 || $ticketId<=0) return;
        if (($initial && $table!=='tbltickets') || (!$initial && $table!=='tblticketreplies')) return;
        try {
            $columns=$initial ? ['message','admin'] : ['tid','message','admin'];
            $row=\WHMCS\Database\Capsule::table($table)->where('id',$rowId)->first($columns);
            if ($row===null || trim((string)($row->admin ?? ''))!=='') return;
            if (!$initial && (int)($row->tid ?? 0)!==$ticketId) return;
            $original=(string)($row->message ?? '');
            $result=dcx_strip_quoted_reply($original);
            if (!$result['changed']) {
                if (DCX_QUOTE_CLEANER_DEBUG) error_log('[DCX Quote Cleaner] Skipped '.$table.' row='.$rowId.' reason='.$result['rule']);
                return;
            }
            $query=\WHMCS\Database\Capsule::table($table)->where('id',$rowId)->where('message',$original);
            if (!$initial) $query->where('tid',$ticketId);
            $affected=$query->update(['message'=>$result['message']]);
            if ((int)$affected===1) error_log(sprintf('[DCX Quote Cleaner] Cleaned %s row=%d ticket=%d rule=%s removed=%d bytes',$table,$rowId,$ticketId,$result['rule'],$result['removed_bytes']));
        } catch (\Throwable $e) {
            error_log(sprintf('[DCX Quote Cleaner] Error %s row=%d ticket=%d type=%s',$table,$rowId,$ticketId,get_class($e)));
        }
    }
}
add_hook('TicketOpen',1,static function(array $vars):void {
    $id=(int)($vars['ticketid']??0);
    dcx_quote_clean_stored_message_v3('tbltickets',$id,$id,true);
});
add_hook('TicketUserReply',1,static function(array $vars):void {
    dcx_quote_clean_stored_message_v3('tblticketreplies',(int)($vars['replyid']??0),(int)($vars['ticketid']??0),false);
});
