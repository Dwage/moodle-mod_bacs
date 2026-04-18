<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Description
 *
 * @package    mod_bacs
 * @copyright  SybonTeam, sybon.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bacs\api\sybon_client;
use mod_bacs\contest;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/utils.php');
require_once(dirname(__FILE__) . '/submit_verdicts.php');
require_once(__DIR__ . '/classes/api/sybon_client.php');

require_login();

global $OUTPUT, $DB;

$contest = new contest();
$contest->initialize_page();

require_capability('mod/bacs:edit', $PAGE->context);

require_sesskey();

$contest->pageurlbacs = new moodle_url('/mod/bacs/rejudge_submits.php', ['id' => $contest->coursemodule->id]);

echo $OUTPUT->header();

$contest->aceeditorshownbacs = false;
$contest->menushownbacs = false;
$contest->groupselectorshownbacs = false;
$contest->print_contest_header();

$sybonapikey = get_config('mod_bacs', 'sybonapikey');
$sybonclient = new sybon_client($sybonapikey);

// ...check rights.
if (!$contest->usercapabilitiesbacs->edit) {
    throw new moodle_exception('generalnopermission', 'bacs');
}

try {
    $contest->register_query_param('task_id', 0, PARAM_INT);
    $contest->register_query_param('submit_id', 0, PARAM_INT);

    $taskid = $contest->queryparamsbacs->task_id;
    $submitid = $contest->queryparamsbacs->submit_id;
    $contestid = $contest->bacs->id;

    print "Sending submits for rejudge (contest_id = $contestid, task_id = $taskid, submit_id = $submitid)...<br>";

    // ...safety and consistency checks.
    if ($taskid > 0) {
        if (!$DB->record_exists('bacs_tasks', ['task_id' => $taskid])) {
            throw new Exception("Task with task_id=$taskid not found.");
        }
        if (!$DB->record_exists('bacs_tasks_to_contests', ['task_id' => $taskid, 'contest_id' => $contestid])) {
            throw new Exception("Task with task_id=$taskid is not included to contest with contest_id=$contestid.");
        }
    }

    // ...rejudge.
    $transaction = $DB->start_delegated_transaction();

    $conditions = ['contest_id' => $contestid];
    if ($taskid > 0) {
        $conditions['task_id'] = $taskid;
    }
    if ($submitid > 0) {
        $conditions['id'] = $submitid;
    }

    $submits = $DB->get_records(
        'bacs_submits',
        $conditions,
        '',
        'id, result_id, sync_submit_id'
    );

    $submitidstorejudge = [];

    foreach ($submits as $submit) {
        print "Submit $submit->id: ";

        if ($submit->result_id == VERDICT_PENDING || $submit->result_id == VERDICT_RUNNING) {
            print "is not checked yet...<br>";
            continue;
        }

        if (is_null($submit->sync_submit_id) || $submit->sync_submit_id == 0) {
            // If submit has no associated Sybon submit,
            // then mark it to be sent again.
            $updatedresultid = VERDICT_PENDING;
            print "is marked for sending...<br>";
        } else {
            // Otherwise use Sybon rejudge
            // and mark this submit to check results again.
            $updatedresultid = VERDICT_RUNNING;
            $submitidstorejudge[] = $submit->sync_submit_id;
            print "is marked for rejudge...<br>";
        }

        if ($contest->bacs->detect_incidents == 1) {
            bacs_mark_submit_for_incidents_recalc($submit->id, $contestid);
        }

        // ...apply database changes for this submit.
        $DB->delete_records('bacs_submits_tests', ['submit_id' => $submit->id]);
        $DB->delete_records('bacs_submits_tests_output', ['submit_id' => $submit->id]);

        $updatesubmit = new stdClass();
        $updatesubmit->id = $submit->id;
        $updatesubmit->result_id = $updatedresultid;
        $updatesubmit->test_num_failed = null;
        $updatesubmit->points = null;
        $updatesubmit->max_time_used = null;
        $updatesubmit->max_memory_used = null;
        $updatesubmit->info = null;
        $lastinsertsubmit = $DB->update_record('bacs_submits', $updatesubmit);
    }

    // ...make Sybon request for rejudge if needed.
    if (count($submitidstorejudge) > 0) {
        $submitidsasstr = '[' . implode(',', $submitidstorejudge) . ']';
        print "Sending for Sybon rejudge submits $submitidsasstr...<br>";
        $sybonclient->rejudge_submits(array_map('intval', $submitidstorejudge));
    }

    $transaction->allow_commit();

    bacs_rebuild_all_standings($contestid);

    // ...success.
    print "Success! <br>";
} catch (Exception $e) {
    debugging($e->getMessage());
}

echo $OUTPUT->footer();
