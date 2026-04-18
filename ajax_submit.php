<?php
define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/classes/contest.php');

require_login();

$cmid = required_param('id', PARAM_INT);
$task_id = required_param('task_id', PARAM_INT);
$lang_id = required_param('lang_id', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM); 
$source = required_param('source', PARAM_RAW);

global $USER, $DB;

$submitkey = bacs_generate_submit_key($cmid, $task_id);

if (!hash_equals($submitkey, $key)) {
    die(json_encode(['status' => 'error', 'msg' => 'Security key mismatch']));
}

try {
    $contest = new \mod_bacs\contest();
    $contest->queryparamsbacs = (object)['id' => $cmid];
    $contest->initialize_page();

    $recenttime = time() - 5 * 60;
    
    $recentsubmits = $DB->count_records_select(
        'bacs_submits', 
        'submit_time > :recenttime AND user_id = :userid', 
        [
            'recenttime' => $recenttime, 
            'userid'     => $USER->id
        ]
    );
    
    if ($recentsubmits > 50) {
        die(json_encode(['status' => 'error', 'msg' => 'Submissions spam penalty']));
    }

    $record = new stdClass();
    $record->user_id = $USER->id;
    $record->contest_id = $contest->bacs->id;
    $record->group_id = $contest->currentgroupidbacs;
    $record->task_id = $task_id;
    $record->lang_id = $lang_id;
    $record->source = $source;
    $record->result_id = 1; // PENDING
    $record->submit_time = time();

    $submitid = $DB->insert_record('bacs_submits', $record);

    if ($contest->bacs->detect_incidents == 1) {
        bacs_mark_submit_for_incidents_recalc($submitid, $contest->bacs->id);
    }

    echo json_encode(['status' => 'ok', 'submit_id' => $submitid]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}