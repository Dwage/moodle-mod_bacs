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
use mod_bacs\output\tasklist;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/utils.php');
require_once(dirname(__FILE__) . '/locale_utils.php');

require_login();


$contest = new contest();
$contest->pageisallowedforisolatedparticipantbacs = true;
$contest->initialize_page();

$contest->pageurlbacs = new moodle_url('/mod/bacs/tasks.php',['id' => $contest->coursemodule->id]);

echo $OUTPUT->header();

$contest->aceeditorshownbacs = true;
$contest->print_contest_header('tasks');

$contest->prepare_last_used_lang();

$tasklist = new tasklist();

$now = time();
$recenttime = $now - 5 * 60;
$tasklist->recentsubmitsbacs = $DB->count_records_select('bacs_submits', "submit_time > $recenttime AND user_id = $USER->id");

$tasklist->coursemoduleidbacs = $contest->coursemodule->id;
$tasklist->usercapabilitiesbacs = $contest->usercapabilitiesbacs;
$tasklist->showpointsbacs       = $contest->get_show_points();
$tasklist->showmaxpointsbacs    = !empty($contest->bacs->show_max_points);

foreach ($contest->tasks as $task) {
    $tasklisttask = new stdClass();

    $tasklisttask->show_max_points_setting = !empty($contest->bacs->show_max_points);

    // getting preferred languages from module settings
    $preferedlanguages = explode(',', get_config('mod_bacs', 'preferedlanguages'));
    $preferedlanguages = array_filter($preferedlanguages); // remove empty values
    // getting current language from moodle
    $currentlang = current_language();

    $tasklisttask->statement_url = $task->statement_url;

    if (!isset($task->statement_urls) || $task->statement_urls == "null") {
        $task->statement_urls = json_encode(["ru" => $task->statement_url]);
    }

    if(isset($task->statement_urls)) {
        $tasklisttask->statement_urls = is_string($task->statement_urls) ? json_decode($task->statement_urls, true) : $task->statement_urls;
        
        $tasklisttask->is_multi_statements = empty($tasklisttask->statement_urls) ? false : count($tasklisttask->statement_urls) > 0;

        if ($tasklisttask->is_multi_statements) {
            $tasklisttask->statement_urls = bacs_filter_multilingual_data($tasklisttask->statement_urls, $preferedlanguages, 'url');

            if (count($preferedlanguages) == 1) {
                // search url by priority: preferred language -> C -> RU -> first available
                $preferred_url = bacs_find_value_by_lang($tasklisttask->statement_urls, $preferedlanguages[0], 'url');

                if ($preferred_url === null) {
                    $preferred_url = bacs_find_value_by_lang($tasklisttask->statement_urls, 'C', 'url');
                }

                if ($preferred_url === null) {
                    $preferred_url = bacs_find_value_by_lang($tasklisttask->statement_urls, 'RU', 'url');
                }

                // if nothing is found, take the first available
                if ($preferred_url === null && !empty($tasklisttask->statement_urls)) {
                    $preferred_url = $tasklisttask->statement_urls[0]['url'];
                }
                $tasklisttask->is_multi_statements = false;
                $tasklisttask->statement_url = $preferred_url;
            }
        }
    }

    if(isset($task->names)) {
        $tasklisttask->names = is_string($task->names) ? json_decode($task->names, true) : $task->names;
        
        $tasklisttask->is_multi_names = empty($tasklisttask->names) ? 0 : count($tasklisttask->names) > 0;
        if ($tasklisttask->is_multi_names) {
            $tasklisttask->names = bacs_filter_multilingual_data($tasklisttask->names, [$currentlang], 'name');
        }
    }


    $tasklisttask->statement_format = $task->statement_format;
    $tasklisttask->name = $task->name;
    $tasklisttask->letter = $task->letter;
    $tasklisttask->task_id = $task->task_id;
    $tasklisttask->task_order = $task->task_order;
    $tasklisttask->is_missing = $task->is_missing;
    $tasklisttask->langs = $contest->langs;

    $tasklisttask->statement_format_is_html =
        (strtoupper($tasklisttask->statement_format) == 'HTML');


    $showsubmitsspamwarning = ($tasklist->recentsubmitsbacs > 40);
    $showsubmitsspampenalty = ($tasklist->recentsubmitsbacs > 50);

    $tasklisttask->can_submit = true;
    $tasklisttask->can_submit_message = "";

    if ($showsubmitsspamwarning) {
        $tasklisttask->can_submit_message = "<div class='alert alert-warning text-center' role='alert'>" .
            get_string('submissionsspamwarning', 'mod_bacs') .
            "</div>";
    }

    $now = time();

    $showforbiddenupsolving =
        $contest->upsolving == 0 &&
        $contest->endtime <= $now;

    $showvisiblegroupschangetosubmit =
        $contest->groupmode == VISIBLEGROUPS &&
        !$contest->usercapabilitiesbacs->accessallgroups &&
        !groups_is_member($contest->currentgroupidbacs, $USER->id);

    if ($task->is_missing) {
        $tasklisttask->can_submit = false;
        $tasklisttask->can_submit_message = "<div class='alert alert-danger text-center' role='alert'>" .
            get_string('submitmessagetaskismissing', 'mod_bacs') .
            "</div>";
    } else if ($showvisiblegroupschangetosubmit) {
        $tasklisttask->can_submit = false;
        $tasklisttask->can_submit_message = "<div class='alert alert-warning text-center' role='alert'>" .
            get_string('changegrouptosubmit', 'mod_bacs') .
            "</div>";
    } else if ($showforbiddenupsolving) {
        $tasklisttask->can_submit = false;
        $tasklisttask->can_submit_message = "<div class='alert alert-warning text-center' role='alert'>" .
            get_string('upsolvingisdisabled', 'mod_bacs') .
            "</div>";
    } else if ($showsubmitsspampenalty) {
        $tasklisttask->can_submit = false;
        $tasklisttask->can_submit_message = "<div class='alert alert-danger text-center' role='alert'>" .
            get_string('submissionsspampenalty', 'mod_bacs') .
            "</div>";
    } else if (!$contest->usercapabilitiesbacs->submit) {
        $tasklisttask->can_submit = false;
        $tasklisttask->can_submit_message = "<div class='alert alert-danger text-center' role='alert'>" .
            get_string('nopermissiontosubmit', 'mod_bacs') .
            "</div>";
    }

    $submitconditions =[
        'contest_id' => $contest->bacs->id,
        'user_id' => $USER->id,
        'task_id' => $task->task_id,
    ];
    if ($contest->currentgroupidbacs != 0) {
        $submitconditions['group_id'] = $contest->currentgroupidbacs;
    }

    $submits = $DB->get_records('bacs_submits', $submitconditions);

    $max_points = 0;
    if (!empty($task->test_points)) {
        $points_array = explode(',', $task->test_points);
        foreach ($points_array as $p) { $max_points += (int)$p; }
    } else {
        $max_points = 100;
    }
    $tasklisttask->max_points = $max_points;

    $tasklisttask->points = "-";
    $tasklisttask->tr_color_class = "verdict-none";
    $tasklisttask->current_status = "-"; 
    $tasklisttask->status_id = 0;
    $tasklisttask->best_submit_id = false; 

    $best_points = 0;
    $is_accepted = false;
    
    $latest_accepted_time = 0;
    $latest_accepted_submit_id = null;

    $latest_failed_time = 0;
    $latest_failed_status = "";
    $latest_failed_submit_id = null;
    $latest_failed_test_num = null; 

    $has_any_submits = false;

    foreach ($submits as $submit) {
        $has_any_submits = true;

        if ($submit->result_id == VERDICT_PENDING || $submit->result_id == VERDICT_RUNNING) {
            continue;
        }

        $points = intval($submit->points);
        if ($points > $best_points) {
            $best_points = $points;
        }

        if ($submit->result_id == VERDICT_ACCEPTED) {
            $is_accepted = true;
            if ($submit->submit_time > $latest_accepted_time) {
                $latest_accepted_time = $submit->submit_time;
                $latest_accepted_submit_id = $submit->id;
            }
        } else {
            if ($submit->submit_time > $latest_failed_time) {
                $latest_failed_time = $submit->submit_time;
                $latest_failed_status = format_verdict($submit->result_id);
                $latest_failed_submit_id = $submit->id;
                $latest_failed_test_num = $submit->test_num_failed;
            }
        }
    }

    if ($has_any_submits) {
        $tasklisttask->points = $best_points;
        
        if ($is_accepted) {
            $tasklisttask->tr_color_class = "verdict-accepted";
            $tasklisttask->current_status = format_verdict(VERDICT_ACCEPTED);
            $tasklisttask->best_submit_id = $latest_accepted_submit_id;
        } elseif ($latest_failed_time > 0) {
            $tasklisttask->tr_color_class = "verdict-failed";
            
            if ($latest_failed_test_num !== null) {
                $latest_failed_status .= " - " . ($latest_failed_test_num + 1);
            }
            
            $tasklisttask->current_status = $latest_failed_status;
            $tasklisttask->best_submit_id = $latest_failed_submit_id;
        }
    }

    $tasklisttask->time_formatted = format_time_consumed($task->time_limit_millis);
    $tasklisttask->memory_formatted = format_memory_consumed($task->memory_limit_bytes);

    $tasklisttask->change_lang_js = "aceeditsessions[$task->task_order].session.setMode(
        'ace/mode/' + document.getElementById('acelangselect$task->task_order')
            .options[document.getElementById('acelangselect$task->task_order').selectedIndex]
            .dataset
            .acemode
    );";

    $tasklisttask->submit_onclick_js = "
        document.getElementById('sendbuttonlocked$task->task_order').classList.add('d-inline-block');
        document.getElementById('sendbutton$task->task_order').classList.add('d-none');
    ";

    if ($tasklisttask->can_submit) {
        $prepareaceeditorjs = "prepare_editor($task->task_order)";
    } else {
        $prepareaceeditorjs = '';
    }

    $tasklisttask->td_toggle_attr = "
        data-bs-toggle='collapse'
        data-bs-target='#collapse$task->task_order'
        data-toggle='collapse'
        data-target='#collapse$task->task_order'
        onclick='$prepareaceeditorjs'
    ";

    $tasklisttask->submit_key = md5($USER->email . $USER->sesskey . $contest->coursemodule->id . $task->task_id);

    $tasklist->add_task($tasklisttask);
}

$ws_secret = get_config('mod_bacs', 'ws_secret');
$ws_url = get_config('mod_bacs', 'ws_url');

if (!empty($ws_secret) && !empty($ws_url)) {
    $tasklist->ws_url = $ws_url;
    $tasklist->ws_jwt = bacs_generate_jwt([
        'user_id' => $USER->id,
        'exp' => time() + 7200
    ], $ws_secret);
}

print $contest->bacsoutput->render($tasklist);


echo $OUTPUT->footer();