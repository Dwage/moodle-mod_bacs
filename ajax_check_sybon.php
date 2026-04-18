<?php
define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/classes/cron_lib.php');
require_once(dirname(__FILE__) . '/submit_verdicts.php'); 

$secret = bacs_get_ws_secret();

$provided_secret = isset($_SERVER['HTTP_X_AUTH_SECRET']) ? $_SERVER['HTTP_X_AUTH_SECRET'] : '';

if (!hash_equals($secret, $provided_secret)) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Forbidden']));
}

global $DB;
$updated_submits = [];

$json = file_get_contents('php://input');
$data = json_decode($json);

if (empty($data->submit_ids) || !is_array($data->submit_ids)) {
    die(json_encode(['status' => 'ok', 'updated_submits' => []]));
}

try {
    \mod_bacs\cron_lib::cron_send(false);

    $submit_ids = array_map('intval', $data->submit_ids);
    list($insql, $inparams) = $DB->get_in_or_equal($submit_ids);
    
    $sql = "SELECT * FROM {bacs_submits} WHERE id $insql AND result_id NOT IN (1, 2)";
    $finished_submits = $DB->get_records_sql($sql, $inparams);

    foreach ($finished_submits as $submit) {

        $v_formatted = format_verdict($submit->result_id);
        if ($submit->result_id != 13 && $submit->test_num_failed !== null) {
            $v_formatted .= " - " . ($submit->test_num_failed + 1);
        }

        $updated_submits[] = [
            'user_id' => (int)$submit->user_id,
            'submit_id' => (int)$submit->id,
            'task_id' => (int)$submit->task_id, 
            'points' => (int)$submit->points,
            'verdict_css_class' => bacs_verdict_to_css_class($submit->result_id),
            'verdict_formatted' => $v_formatted,
            'time_formatted' => format_time_consumed($submit->max_time_used),
            'memory_formatted' => format_memory_consumed($submit->max_memory_used)
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'updated_submits' => $updated_submits
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}