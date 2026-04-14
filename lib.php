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

defined('MOODLE_INTERNAL') || die();

use mod_bacs\api\sybon_client;
use core\session\exception;

require_once(__DIR__ . '/classes/api/sybon_client.php');

/**
 * This function
 * @param int $contestid
 * @param string $newtaskidsstring
 * @param string $newtestpointsstring
 * @return true
 * @throws dml_exception
 * @throws dml_transaction_exception
 */
function bacs_set_contest_tasks($contestid, $newtaskidsstring, $newtestpointsstring) {
    global $DB;

    if (strlen($newtaskidsstring) > 0) {
        $newtaskids = explode('_', $newtaskidsstring);
    } else {
        $newtaskids = [];
    }

    if (strlen($newtestpointsstring) > 0) {
        $newtestpoints = explode('_', $newtestpointsstring);
    } else {
        if (count($newtaskids) == 0) {
            $newtestpoints = [];
        } else {
            $newtestpoints = [''];
        }
    }

    // ...validate data.
    foreach ($newtaskids as $curid) {
        if (!is_numeric($curid)) {
            throw new Exception('"' . (string)$curid . '" is not a valid task ID.');
        }
    }

    if (count($newtaskids) > 26) {
        throw new Exception('Contest is limited to maximum of 26 tasks.');
    }

    if (count($newtaskids) !== count(array_flip($newtaskids))) {
        throw new Exception('Duplicate tasks are not allowed.');
    }

    // Actual tasks presence check is disabled
    // due to improved rendering of missing tasks.
    // Otherwise contest with a single missing task cannot
    // be edited without deleting this task.

    if (count($newtaskids) !== count($newtestpoints)) {
        throw new Exception(
            'Task IDs and test points are not matched. ' .
            'Provided ' . count($newtaskids) . ' task IDs ' .
            'and ' . count($newtestpoints) . ' test points sets.'
        );
    }

    foreach ($newtestpoints as $curteststring) {
        if ($curteststring === '') {
            continue;
        }

        $curtestvalues = explode(',', $curteststring);
        foreach ($curtestvalues as $curtestvalue) {
            if (!is_numeric($curtestvalue) || ((int)$curtestvalue < 0)) {
                throw new Exception(
                    '"' . $curtestvalue . '" is not valid test value ' .
                    '(in test string ' . $curteststring . ').'
                );
            }
        }
    }

    // ...apply.
    $transaction = $DB->start_delegated_transaction();

    $DB->delete_records("bacs_tasks_to_contests", ["contest_id" => $contestid]);

    $taskorder = 0;
    foreach ($newtaskids as $key => $curid) {
        $taskorder += 1;
        $curteststring = $newtestpoints[$key];

        $newrecord = new stdClass();
        $newrecord->task_order = $taskorder;
        $newrecord->task_id = (int)$curid;
        $newrecord->contest_id = $contestid;
        if ($curteststring !== '') {
            $newrecord->test_points = $curteststring;
        }

        $lastinsertid = $DB->insert_record("bacs_tasks_to_contests", $newrecord);
    }

    $transaction->allow_commit();

    // ...on success.
    return true;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $bacs
 * @return bool|int
 */
function bacs_add_instance($bacs) {
    global $DB;

    $lastinsertedid = $DB->insert_record("bacs", $bacs);

    bacs_set_contest_tasks(
        $lastinsertedid,
        $bacs->contest_task_ids,
        $bacs->contest_task_test_points
    );

    return $lastinsertedid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $bacs
 * @return bool
 */
function bacs_update_instance($bacs) {
    global $DB;

    $bacs->id = $bacs->instance;

    bacs_set_contest_tasks(
        $bacs->id,
        $bacs->contest_task_ids,
        $bacs->contest_task_test_points
    );

    return $DB->update_record("bacs", $bacs);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool
 */
function bacs_delete_instance($id) {
    global $DB;

    $bacs = $DB->get_record("bacs", ["id" => $id]);

    if (!$bacs) {
        return false;
    }

    bacs_delete_submits($bacs->id, 0 /* all users */);
    $DB->delete_records("bacs_tasks_to_contests", ['contest_id' => $bacs->id]);

    $result = true;
    if (!$DB->delete_records("bacs", ["id" => $bacs->id])) {
        $result = false;
    }
    return $result;
}

/**
 * Given contest ID, user ID or submit ID (or any subset of them),
 * this function will delete all information about
 * submits that match them.
 *
 * @param int $contestid Concrete contest (bacs) ID or zero in case of all contests
 * @param int $userid Concrete user ID or zero in case of all users
 * @param int $submitid Concrete submit ID or zero in case of multiple submits
 */
function bacs_delete_submits($contestid, $userid, $submitid = 0) {
    global $DB;

    if ($contestid == 0 && $userid == 0 && $submitid == 0) {
        throw new Exception(
            "Contest ID, user ID and submit ID cannot be zero at the same time. " .
            "Total deletion of all stored submits must be a mistake or a specially tracked action."
        );
    }

    // ...prepare conditions.
    $conditions = [];
    if ($contestid > 0) {
        $conditions[] = "bs.contest_id = $contestid";
    }
    if ($userid > 0) {
        $conditions[] = "bs.user_id = $userid";
    }
    if ($submitid > 0) {
        $conditions[] = "bs.id = $submitid";
    }

    $sqlconditions = implode(" AND ", $conditions);

    // ...request changed contests.
    $sql = "SELECT id, contest_id
              FROM {bacs_submits} bs
             WHERE $sqlconditions";

    $submitstodelete = $DB->get_records_sql($sql);
    $changedcontests = [];
    foreach ($submitstodelete as $cursubmit) {
        $changedcontests[$cursubmit->contest_id] = true;
    }

    // ...delete test outputs.
    $sql = "DELETE bsto
              FROM {bacs_submits_tests_output} bsto
         LEFT JOIN {bacs_submits} bs ON bs.id = bsto.submit_id
             WHERE $sqlconditions";

    $DB->execute($sql);

    // ...delete tests.
    $sql = "DELETE bst
              FROM {bacs_submits_tests} bst
         LEFT JOIN {bacs_submits} bs ON bs.id = bst.submit_id
             WHERE $sqlconditions";

    $DB->execute($sql);

    // ...delete submits.
    $sql = "DELETE bs
              FROM {bacs_submits} bs
             WHERE $sqlconditions";

    $DB->execute($sql);

    // ...update standings.
    foreach ($changedcontests as $contestid => $v) {
        bacs_rebuild_all_standings($contestid);
    }
}

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function bacs_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_INTRO:
            return false;

        default:
            return null;
    }
}

/**
 * This function
 * @return true
 */
function bacs_cron() {
    return true;
}

/**
 * This function
 * @param int $bacsid
 * @return void
 * @throws dml_exception
 */
function bacs_rebuild_all_standings($bacsid) {
    global $DB;

    if (!$DB->record_exists('bacs', ['id' => $bacsid])) {
        trigger_error("Invoked rebuilding all standings for missing contest with id=$bacsid", E_USER_WARNING);
        return;
    }

    bacs_rebuild_common_standings($bacsid);

    $bacs = $DB->get_record('bacs', ['id' => $bacsid]);
    $course = $DB->get_record('course', ['id' => $bacs->course]);

    if (groups_get_course_groupmode($course) == 0) {
        return;
    }

    $allgroups = groups_get_all_groups($course->id);

    foreach ($allgroups as $groupid => $curgroup) {
        bacs_rebuild_standings_for_group($bacsid, $groupid);
    }
}

/**
 * This function
 * @param int $bacsid
 * @return void
 * @throws dml_exception
 */
function bacs_rebuild_common_standings($bacsid) {
    global $DB;

    if (!$DB->record_exists('bacs', ['id' => $bacsid])) {
        trigger_error("Invoked rebuilding common standings for missing contest with id=$bacsid", E_USER_WARNING);
        return;
    }

    $standings = [];

    $submits = $DB->get_records('bacs_submits', ['contest_id' => $bacsid]);
    foreach ($submits as $cursubmit) {
        $standings[] = [
            'id'          => $cursubmit->id,
            'user_id'     => $cursubmit->user_id,
            'task_id'     => $cursubmit->task_id,
            'submit_time' => $cursubmit->submit_time,
            'result_id'   => $cursubmit->result_id,
            'points'      => $cursubmit->points,
        ];
    }

    // ...update.
    $updatecontest = new stdClass();
    $updatecontest->id = $bacsid;
    $updatecontest->standings = json_encode($standings);

    $DB->update_record('bacs', $updatecontest);
}

/**
 * This function
 * @param int $bacsid
 * @param int $groupid
 * @return void
 * @throws dml_exception
 */
function bacs_rebuild_standings_for_group($bacsid, $groupid) {
    global $DB;

    if (!$DB->record_exists('bacs', ['id' => $bacsid])) {
        trigger_error("Invoked rebuilding group standings for missing contest with id=$bacsid", E_USER_WARNING);
        return;
    }

    if ($groupid == 0) {
        return;
    }
    if (!$DB->record_exists('bacs_group_info', ['contest_id' => $bacsid, 'group_id' => $groupid])) {
        $groupstandingsentry = new stdClass();
        $groupstandingsentry->group_id = $groupid;
        $groupstandingsentry->contest_id = $bacsid;
        $groupstandingsentry->standings = '';

        $DB->insert_record('bacs_group_info', $groupstandingsentry);
    }

    $standings = [];

    $submits = $DB->get_records('bacs_submits', ['contest_id' => $bacsid, 'group_id' => $groupid]);
    foreach ($submits as $cursubmit) {
        $standings[] = [
            'id'          => $cursubmit->id,
            'user_id'     => $cursubmit->user_id,
            'task_id'     => $cursubmit->task_id,
            'submit_time' => $cursubmit->submit_time,
            'result_id'   => $cursubmit->result_id,
            'points'      => $cursubmit->points,
        ];
    }

    $groupstandingsentry = $DB->get_record('bacs_group_info', ['contest_id' => $bacsid, 'group_id' => $groupid]);

    // ...update.
    $updategroupstandings = new stdClass();
    $updategroupstandings->id = $groupstandingsentry->id;
    $updategroupstandings->contest_id = $bacsid;
    $updategroupstandings->group_id = $groupid;
    $updategroupstandings->standings = json_encode($standings);

    $DB->update_record('bacs_group_info', $updategroupstandings);
}

/**
 * This function
 * @param int $usedmillis
 * @return string
 * @throws coding_exception
 */
function format_time_consumed($usedmillis) {
    if (is_null($usedmillis)) {
        return "-";
    }

    return ($usedmillis / 1000) . " " . get_string("seconds_short", 'mod_bacs');
}

/**
 * This function
 * @param int $usedbytes
 * @return string
 */
function format_memory_consumed($usedbytes) {
    if (is_null($usedbytes)) {
        return "-";
    }

    if ($usedbytes < 1024) {
        return $usedbytes . " B";
    }
    if ($usedbytes < 1024 * 1024) {
        return number_format($usedbytes / 1024, 2) . " KB";
    }
    return number_format($usedbytes / (1024 * 1024), 2) . " MB";
}

/**
 * This function
 * @param int $verdict
 * @return lang_string|string
 * @throws coding_exception
 */
function format_verdict($verdict) {
    return get_string("submit_verdict_" . $verdict, 'mod_bacs');
}

/**
 * This function tokenizes the submit source code in a simple way
 * independent of the programming language used
 * 
 * @param string $submitsource Source code of the submit 
 * @return string[] Sequence of tokens
 * @throws coding_exception
 */
function bacs_tokenize_submit($submitsource) {
    // Prepare submit source
    $submitsource .= "\n";

    $submitsource = filter_var($submitsource, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH); // Remove characters with codes > 127
    $submitsource = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $submitsource); // Remove special characters   
    $submitsource = preg_replace('/#[^\n]*\n/', "\n", $submitsource); // Remove hash line comments 
    $submitsource = preg_replace('/\/\/[^\n]*\n/', "\n", $submitsource); // Remove double slash line comments

    // Character sets
    $emptychars = [
        " " => true,
        "\n" => true,
        "\r" => true,
        "\t" => true,
    ];

    $tokenallowedchars = [];
    foreach (array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'), ['_']) as $ch) {
        $tokenallowedchars[$ch] = true;
    }

    /// Tokenize
    $tokens = [];
    $token = "";
    foreach (mb_str_split($submitsource) as $ch) {
        if (array_key_exists($ch, $tokenallowedchars)) {
            $token .= $ch;
            continue;
        }

        if ($token != "") {
            $tokens[] = $token;
            $token = "";
        }

        if ( !array_key_exists($ch, $emptychars)) {
            $tokens[] = $ch;
        }
    }

    return $tokens;
}

/**
 * Computes Levenshtein edit distance between two token sequences with edit window limit
 * 
 * @param string[] $tokenseq1
 * @param string[] $tokenseq2
 * @return int
 * @throws coding_exception
 */
function bacs_token_seq_edit_distance($tokenseq1, $tokenseq2) {
    // Approach is to compute all states of 
    // dist[pos1][delta] dynamic programming
    // where only two layers of pos1 are kept as $distcur and $distnext

    // allowed modifications are:
    // - Remove single token
    // - Insert single token
    // - Change single token

    $n1 = count($tokenseq1);
    $n2 = count($tokenseq2);

    // Max delta is heuristically chosen to be 10% of token count, but no less than 300
    $span = max(300, intval(0.1 * max($n1, $n2)));

    // If sizes are too different return max
    if (abs($n1-$n2) > $span) {
        return max($n1, $n2);
    }

    $distcur = [];
    for ($d = 0; $d <= min($span, $n2); $d++) {
        $distcur[$d] = $d;
    }

    for ($pos1 = 0; $pos1 < $n1; $pos1++) {
        $distnext = [];

        foreach ($distcur as $d => $v) {
            $token1 = $tokenseq1[$pos1];
            $token2 = ($pos1 + $d < $n2 ? $tokenseq2[$pos1 + $d] : null);

            $nextdvpairs = [
                $d-1 => $v+1,
                $d => ($token1 === $token2 ? $v : $v+1),
            ];

            foreach ($nextdvpairs as $nextd => $nextv) {
                // Validate
                if ($nextd < -$span) continue;
                if ($nextd > +$span) continue;

                $nextpos2 = $pos1 + 1 + $nextd;
                if ($nextpos2 < 0) continue;
                if ($nextpos2 > $n2) continue;

                // Apply
                if (array_key_exists($nextd, $distnext)) {
                    $distnext[$nextd] = min($distnext[$nextd], $nextv);
                } else {
                    $distnext[$nextd] = $nextv;
                }
            }
        }

        foreach ($distnext as $nextd => $nextv) {
            $nextpos2 = $pos1 + 1 + $nextd;
            if ($nextpos2 + 1 > $n2) continue;
            if ($nextd + 1 > $span) continue;

            if (array_key_exists($nextd, $distnext)) {
                $distnext[$nextd + 1] = min($distnext[$nextd + 1], $distnext[$nextd] + 1);
            } else {
                $distnext[$nextd + 1] = $distnext[$nextd] + 1;
            }
        }

        $distcur = $distnext;
    }

    // Result
    return $distcur[$n2 - $n1];
}

/**
 * Marks submit with given ID to have incidents recalculated
 * 
 * @param int $submitid ID of the submit
 * @param int $contestid ID of the mod_bacs contest, to which submit belongs to
 * @return null
 */
function bacs_mark_submit_for_incidents_recalc($submitid, $contestid) {
    global $DB;

    $fingerprintsrecord = $DB->get_record(
        'bacs_submits_fingerprints',
        ['submit_id' => $submitid], 
        'id, submit_id, contest_id, status',
        IGNORE_MISSING
    );

    if ($fingerprintsrecord) {
        $fingerprintsrecord->status = 0;

        $DB->update_record('bacs_submits_fingerprints', $fingerprintsrecord);
    } else {
        $fingerprintsrecord = new stdClass();
        $fingerprintsrecord->submit_id = $submitid;
        $fingerprintsrecord->contest_id = $contestid;
        $fingerprintsrecord->status = 0;
    
        $DB->insert_record('bacs_submits_fingerprints', $fingerprintsrecord, false);
    }
}

/**
 * This function
 * @return string[]
 */
function bacs_diagnostics_available_checks() {
    return [
        'sybon_api_collections',
        'sybon_api_compilers',
        'sybon_api_submits',
        'test_points_strings',
        'task_pretests',
        'task_statement_format',
        'deprecated_tasks',
        'duplicate_tasks',
    ];
}

/**
 * This function
 * @return object
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_test_points_strings() {
    global $DB;

    $recordsintotal = 0;
    $recordswithcustompoints = 0;
    $recordsmismatched = 0;
    $recordswithmissingtask = 0;

    $alltasks = $DB->get_records('bacs_tasks', [], '', 'id, task_id, count_tests');
    $taskbytaskid = [];

    foreach ($alltasks as $task) {
        $taskbytaskid[$task->task_id] = $task;
    }

    $rs = $DB->get_recordset('bacs_tasks_to_contests', [], '', '*');
    foreach ($rs as $tasktocontest) {
        $recordsintotal += 1;

        $task = $taskbytaskid[$tasktocontest->task_id];

        if (!$task) {
            $recordswithmissingtask += 1;
            print "<p>Task is missing for contest_id=$tasktocontest->contest_id task_id=$tasktocontest->task_id</p>";
            continue;
        }

        if (is_null($tasktocontest->test_points) || $tasktocontest->test_points == '') {
            continue;
        }

        $recordswithcustompoints += 1;

        $customtestcount = substr_count($tasktocontest->test_points, ',');
        if ($customtestcount != $task->count_tests) {
            $recordsmismatched += 1;
            print "<p>
                Test points settings do not match for
                    contest_id=$tasktocontest->contest_id
                    task_id=$tasktocontest->task_id
                    custom_test_count=$customtestcount
                    test_count=$task->count_tests
                </p>";
        }
    }
    $rs->close();

    // ...result.
    $messagelong = get_string('diagnostics:test_points_strings_msg', 'mod_bacs', (object) [
        'records_in_total' => $recordsintotal,
        'records_with_custom_points' => $recordswithcustompoints,
        'records_with_missing_task' => $recordswithmissingtask,
        'records_mismatched' => $recordsmismatched,
    ]);

    if ($recordsmismatched == 0 && $recordswithmissingtask == 0) {
        $result = (object) [
            'error_level' => 0,
            'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    } else {
        $result = (object) [
            'error_level' => 1,
            'message_short' => get_string('diagnostics:warning', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    }

    return $result;
}

/**
 * This function
 * @return object
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_task_pretests() {
    global $DB;

    $tasksintotal = 0;
    $taskswithwrongpretestscount = 0;
    $taskswithwrongpretestsindex = 0;
    $taskswithoutpretests = 0;

    $tasks = $DB->get_records('bacs_tasks');
    $taskstestexpected = $DB->get_records('bacs_tasks_test_expected');

    $pretestsgroupedbytaskid = [];
    foreach ($taskstestexpected as $pretest) {
        $pretestsgroupedbytaskid[$pretest->task_id][] = $pretest->test_id;
    }

    foreach ($tasks as $task) {
        $tasksintotal += 1;

        if (array_key_exists($task->task_id, $pretestsgroupedbytaskid)) {
            $taskpretests = $pretestsgroupedbytaskid[$task->task_id];
        } else {
            $taskpretests = [];
        }

        sort($taskpretests);

        $pretestsareindexedincorrectly = false;
        for ($testindex = 0; $testindex < count($taskpretests); $testindex++) {
            if ($testindex != $taskpretests[$testindex]) {
                $pretestsareindexedincorrectly = true;
                break;
            }
        }

        if ($pretestsareindexedincorrectly) {
            $taskpretestsstr = '[' . implode(', ', $taskpretests) . ']';
            print "<p>Task task_id=$task->task_id has wrong pretest numeration: $taskpretestsstr</p>";

            $taskswithwrongpretestsindex += 1;
        }

        if ($task->count_pretests != count($taskpretests)) {
            $taskrealpretestcount = count($taskpretests);
            print "<p>Task task_id=$task->task_id
 is expected to have $task->count_pretests pretests, but found $taskrealpretestcount pretests</p>";

            $taskswithwrongpretestscount += 1;
        }

        if (count($taskpretests) == 0) {
            print "<p>Task task_id=$task->task_id has no pretests</p>";

            $taskswithoutpretests += 1;
        }
    }

    // ...result.
    $messagelong = get_string('diagnostics:task_pretests_msg', 'mod_bacs', (object) [
        'tasks_in_total' => $tasksintotal,
        'tasks_with_wrong_pretests_count' => $taskswithwrongpretestscount,
        'tasks_with_wrong_pretests_index' => $taskswithwrongpretestsindex,
        'tasks_without_pretests' => $taskswithoutpretests,
    ]);

    $diagnosticcheckresultok =
        $taskswithwrongpretestscount == 0 &&
        $taskswithwrongpretestsindex == 0 &&
        $taskswithoutpretests == 0;

    if ($diagnosticcheckresultok) {
        $result = (object) [
            'error_level' => 0,
            'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    } else {
        $result = (object) [
            'error_level' => 1,
            'message_short' => get_string('diagnostics:warning', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    }

    return $result;
}

/**
 * This function
 *
 * @return object
 * @throws exception
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_sybon_api_collections() {
    $sybonapikey = get_config('mod_bacs', 'sybonapikey');
    $sybonclient = new sybon_client($sybonapikey);

    $collections = $sybonclient->get_collections();

    $collectionscount = count($collections);

    return $result = (object) [
        'error_level' => 0,
        'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
        'message_long' => get_string('diagnostics:sybon_api_collections_msg', 'mod_bacs', $collectionscount),
    ];
}

/**
 * This function
 * @return object
 * @throws exception
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_sybon_api_compilers() {
    $sybonapikey = get_config('mod_bacs', 'sybonapikey');
    $sybonclient = new sybon_client($sybonapikey);

    $langs = $sybonclient->get_compilers();

    $langscount = count($langs);

    return $result = (object) [
        'error_level' => 0,
        'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
        'message_long' => get_string('diagnostics:sybon_api_compilers_msg', 'mod_bacs', $langscount),
    ];
}

/**
 * This function
 * @return object
 * @throws exception
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_sybon_api_submits(): object {
    global $DB;

    require_once(dirname(__FILE__) . '/submit_verdicts.php');

    $sybonapikey = get_config('mod_bacs', 'sybonapikey');
    $sybonclient = new sybon_client($sybonapikey);

    // Sybon allows fetching results for any submit for any key with rights for given task?
    $lastsubmit = array_values($DB->get_records_select(
        'bacs_submits',
        // Sync_submit_id > 0 also means it is not NULL.
        "sync_submit_id > 0",
        ['verdict_running' => VERDICT_RUNNING],
        'submit_time DESC',
        '*',
        0,
        1
    ));

    if (count($lastsubmit) == 0) {
        return (object) [
            'error_level' => 1,
            'message_short' => get_string('diagnostics:warning', 'mod_bacs'),
            'message_long' => get_string('diagnostics:sybon_api_submits_msg_no_submits', 'mod_bacs'),
        ];
    }

    $lastsubmitsyncid = intval($lastsubmit[0]->sync_submit_id);

    $checkingresults = $sybonclient->get_submits_results([ $lastsubmitsyncid ]);

    return (object) [
        'error_level' => 0,
        'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
        'message_long' => get_string('diagnostics:sybon_api_submits_msg', 'mod_bacs', $lastsubmitsyncid),
    ];
}

/**
 * This function
 * @return object
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_deprecated_tasks() {
    global $DB;

    $deprecatedtaskids = [
        // N equalities.
        14250,
        14495,
    ];

    [$insql, $inparams] = $DB->get_in_or_equal($deprecatedtaskids);
    $sql = "SELECT * FROM {bacs_tasks} WHERE task_id $insql";
    $deprecatedtasks = $DB->get_records_sql($sql, $inparams);

    foreach ($deprecatedtasks as $curtask) {
        print "<p>Deprecated task task_id=$curtask->task_id is available</p>";
    }

    $deprecatedtasksfound = count($deprecatedtasks);

    // ...result.
    $messagelong = get_string('diagnostics:deprecated_tasks_msg', 'mod_bacs', $deprecatedtasksfound);

    if ($deprecatedtasksfound == 0) {
        $result = (object) [
            'error_level' => 0,
            'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    } else {
        $result = (object) [
            'error_level' => 1,
            'message_short' => get_string('diagnostics:warning', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    }

    return $result;
}

/**
 * This function
 * @return object
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_duplicate_tasks() {
    global $DB;

    $tasksubstitutionmap = [
        // Troll Yan and history.
        '182709' => '182717',
    ];

    $taskswiththesamename = 0;
    $taskstobereplaced = 0;
    $taskswithoutreplacement = 0;

    $tasks = $DB->get_records('bacs_tasks');
    $taskcountbytaskid = [];
    $taskcountbytaskname = [];

    foreach ($tasks as $curtask) {
        if (!array_key_exists($curtask->task_id, $taskcountbytaskid)) {
            $taskcountbytaskid[$curtask->task_id] = 0;
        }
        $taskcountbytaskid[$curtask->task_id] += 1;

        if (!array_key_exists($curtask->name, $taskcountbytaskname)) {
            $taskcountbytaskname[$curtask->name] = 0;
        }
        $taskcountbytaskname[$curtask->name] += 1;
    }

    foreach ($tasksubstitutionmap as $taskidfrom => $taskidto) {
        if (!array_key_exists($taskidfrom, $taskcountbytaskid)) {
            continue;
        }

        if (array_key_exists($taskidto, $taskcountbytaskid)) {
            $taskstobereplaced += 1;
            print "<p>Task task_id=$taskidfrom should be replaced with task_id=$taskidto</p>";
        } else {
            $taskswithoutreplacement += 1;
            print "<p>Task task_id=$taskidfrom should be replaced with task_id=$taskidto, but this task is not available</p>";
        }
    }

    foreach ($taskcountbytaskname as $taskname => $taskcount) {
        if ($taskcount < 2) {
            continue;
        }

        $taskswiththesamename += $taskcount;
        print "<p>Multiple ($taskcount) tasks with name '$taskname' are available</p>";
    }

    // ...result.
    $messagelong = get_string('diagnostics:duplicate_tasks_msg', 'mod_bacs', (object) [
        'tasks_to_be_replaced' => $taskstobereplaced,
        'tasks_without_replacement' => $taskswithoutreplacement,
        'tasks_with_the_same_name' => $taskswiththesamename,
    ]);

    $diagnosticcheckresultok =
        $taskswiththesamename == 0 &&
        $taskstobereplaced == 0 &&
        $taskswithoutreplacement == 0;

    if ($diagnosticcheckresultok) {
        $result = (object) [
            'error_level' => 0,
            'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    } else {
        $result = (object) [
            'error_level' => 1,
            'message_short' => get_string('diagnostics:warning', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    }

    return $result;
}

/**
 * This function
 * @return object
 * @throws coding_exception
 * @throws dml_exception
 */
function bacs_diagnostics_task_statement_format() {
    global $DB;

    $withdoc = 0;
    $withpdf = 0;
    $withhtml = 0;
    $withotherformat = 0;

    $tasks = $DB->get_records('bacs_tasks');

    foreach ($tasks as $curtask) {
        if (in_array($curtask->statement_format, ['doc', 'docx'])) {
            $withdoc += 1;
        } else if ($curtask->statement_format == 'pdf') {
            $withpdf += 1;
        } else if (in_array($curtask->statement_format, ['htm', 'html'])) {
            $withhtml += 1;
        } else {
            $withotherformat += 1;
            print "<p>Task task_id=$curtask->task_id has unusual statement format: '$curtask->statement_format'</p>";
        }
    }

    // ...result.
    $messagelong = get_string('diagnostics:task_statement_format_msg', 'mod_bacs', (object) [
        'with_doc' => $withdoc,
        'with_pdf' => $withpdf,
        'with_html' => $withhtml,
        'with_other_format' => $withotherformat,
    ]);

    if ($withotherformat == 0) {
        $result = (object) [
            'error_level' => 0,
            'message_short' => get_string('diagnostics:ok', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    } else {
        $result = (object) [
            'error_level' => 1,
            'message_short' => get_string('diagnostics:warning', 'mod_bacs'),
            'message_long' => $messagelong,
        ];
    }

    return $result;
}

/**
 * Generates a JWT token using HS256.
 *
 * @param array $payload Data to include in the token payload
 * @param string $secret Secret key used to sign the token
 * @return string Generated JWT token
 */
function bacs_generate_jwt($payload, $secret) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='],['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Sends submission notification to the WebSocket broker.
 *
 * @param int $userid ID of the user who submitted
 * @param array $submitdata Data related to the submission
 * @return void
 */
function bacs_notify_ws_broker($userid, $submitdata) {
    $url = get_config('mod_bacs', 'ws_internal_url');
    $secret = get_config('mod_bacs', 'ws_secret');
    if (empty($url) || empty($secret)) return;

    $payload =[
        'user_id' => $userid,
        'data' => $submitdata
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER,['Content-Type: application/json', 'X-Auth-Secret: ' . $secret]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    curl_exec($ch);
    curl_close($ch);
}