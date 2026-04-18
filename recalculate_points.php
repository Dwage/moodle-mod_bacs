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

use mod_bacs\contest;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/utils.php');

require_login();

$contest = new contest();
$contest->initialize_page();

require_capability('mod/bacs:edit', $PAGE->context);

require_sesskey();

$contest->pageurlbacs = new moodle_url('/mod/bacs/recalculate_points.php', ['id' => $contest->coursemodule->id]);

echo $OUTPUT->header();

$contest->aceeditorshownbacs = false;
$contest->menushownbacs = false;
$contest->groupselectorshownbacs = false;
$contest->print_contest_header();

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

    print "Recalculating points for task $taskid for contest $contestid...<br>";

    if ($taskid == 0) {
        $testpointsstring = null;
    } else {
        $tasktocontest = $DB->get_record(
            'bacs_tasks_to_contests',
            ['contest_id' => $contestid, 'task_id' => $taskid],
            '*',
            MUST_EXIST
        );

        if (is_null($tasktocontest->test_points)) {
            $task = $DB->get_record('bacs_tasks', ['task_id' => $taskid], 'test_points', MUST_EXIST);
            $testpointsstring = $task->test_points;
        } else {
            $testpointsstring = $tasktocontest->test_points;
        }
    }

    // ...apply.
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
        'id'
    );

    foreach ($submits as $submit) {
        print "Recalculating submit $submit->id...<br>";
        bacs_calculate_sumbit_points($submit->id, $testpointsstring);
    }

    $transaction->allow_commit();

    bacs_rebuild_all_standings($contestid);

    // ...success.
    print "Success! <br>";
} catch (Exception $e) {
    debugging($e->getMessage());
}

echo $OUTPUT->footer();
