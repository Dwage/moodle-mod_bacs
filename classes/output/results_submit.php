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


namespace mod_bacs\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use Exception;
use mod_bacs\contest;
use stdClass;

require_once(dirname(dirname(dirname(__FILE__))) . '/submit_verdicts.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

/**
 * Class results_submit
 * @package mod_bacs
 */
class results_submit {
    /**
     * @var mixed
     */
    public $resultidbacs = 0;
    /**
     * @var mixed
     */
    public $points = null;
    /**
     * @var mixed
     */
    public $idx = 0;
    /**
     * @var mixed
     */
    public $submitidbacs = 0;
    /**
     * @var mixed
     */
    public $maxtimeusedbacs = null;
    /**
     * @var mixed
     */
    public $maxmemoryusedbacs = null;
    /**
     * @var mixed
     */
    public $compilerinfobacs = "";
    /**
     * @var mixed
     */
    public $submittimebacs = 0;
    /**
     * @var mixed
     */
    public $source = "";
    /**
     * @var mixed
     */
    public $iscollapsedbacs = true;
    /**
     * @var mixed
     */
    public $solutioniscollapsedbacs = true;

    /**
     * @var mixed
     */
    public $langidbacs = 0;
    /**
     * @var mixed
     */
    public $langnamebacs = "";

    /**
     * @var mixed
     */
    public $taskidbacs = 0;
    /**
     * @var mixed
     */
    public $taskletterbacs = "";
    /**
     * @var mixed
     */
    public $tasknamebacs = "";

    /**
     * @var mixed
     */
    public $useridbacs = "";
    /**
     * @var mixed
     */
    public $userfirstnamebacs = "";
    /**
     * @var mixed
     */
    public $userlastnamebacs = "";

    /**
     * @var mixed
     */
    public $tests = [];
    /**
     * @var mixed
     */
    public $pretests = [];

    /**
     * @var mixed
     */
    public $coursemoduleidbacs;

    /**
     * @var mixed
     */
    public $aceeditorusedbacs = true;
    /**
     * @var mixed
     */
    public $aceeditorthemebacs = "";
    /**
     * @var mixed
     */
    public $aceeditormodebacs = "c_cpp";

    // ...these 5 parameters will be set by results class.
    /**
     * @var mixed
     */
    public $rcfgshowdateswithsentattimebacs;
    /**
     * @var mixed
     */
    public $rcfgshowfulltasknamesbacs;
    /**
     * @var mixed
     */
    public $rcfgprovidesubmitlinksinheaderbacs;
    /**
     * @var mixed
     */
    public $rcfgprovidesubmitlinksinbodybacs;
    /**
     * @var mixed
     */
    public $showdateatseparaterowbacs;

    /**
     *
     */
    public function __construct() {
    }

    /** @var array Alerts */
    public $alerts = [];

    /**
     * Loads all information from database about given submit for given contest
     * If contest bacs id does not match submit contest id, error is thrown
     *
     * @param object $dbsubmit Record from bacs_submits with ALL fields
     * @param contest $contest Contest to load submit data for
     * @param bool $loadfullinformation Set to true to load all details about submit
     */
    public function load_from($dbsubmit, $contest, $loadfullinformation = false) {
        global $DB, $USER;

        if (empty($contest->bacs)) {
            throw new Exception("Contest is not ready, call \$contest->initialize_page() before using it");
        }
        if ($dbsubmit->contest_id != $contest->bacs->id) {
            throw new Exception("This submit does not belong to provided contest");
        }

        $this->submittimebacs = $dbsubmit->submit_time;
        $this->points         = $dbsubmit->points;
        $this->submitidbacs   = $dbsubmit->id;
        $this->resultidbacs   = $dbsubmit->result_id;

        $this->compilerinfobacs  = (property_exists($dbsubmit, 'info') ? $dbsubmit->info : '');
        $this->source            = (property_exists($dbsubmit, 'source') ? $dbsubmit->source : '');
        $this->maxtimeusedbacs   = (property_exists($dbsubmit, 'max_time_used') ? $dbsubmit->max_time_used : 0);
        $this->maxmemoryusedbacs = (property_exists($dbsubmit, 'max_memory_used') ? $dbsubmit->max_memory_used : 0);

        $this->useridbacs = $dbsubmit->user_id;
        $conteststudents = $contest->get_students();
        if (array_key_exists($this->useridbacs, $conteststudents)) {
            // only names of students can be shown this way
            $submitauthor = $conteststudents[$this->useridbacs];
            $this->userfirstnamebacs  = $submitauthor->firstname;
            $this->userlastnamebacs   = $submitauthor->lastname;
        } else {
            $this->userfirstnamebacs  = "[USER ID=$dbsubmit->user_id]";
            $this->userlastnamebacs   = "";
        }

        $lang = $contest->get_lang_by_lang_id($dbsubmit->lang_id);
        $this->langidbacs         = $lang->lang_id;
        $this->langnamebacs       = $lang->name;
        $this->aceeditormodebacs = $lang->acemode;

        // Set task ID.
        $this->taskidbacs = $dbsubmit->task_id;

        // Check if task exists in current contest.
        $task = $contest->get_contest_task_by_task_id($dbsubmit->task_id);
        if (is_null($task)) {
            // Task is no longer in contest.
            $this->taskletterbacs = $dbsubmit->task_id; // Use task ID instead of letter.

            // Check if task exists in DB and get its name if so.
            if ($dbtask = $DB->get_record('bacs_tasks', [ 'task_id' => $dbsubmit->task_id ])) {
                $this->alerts[] = get_string('taskofsubmitismissingincontest', 'mod_bacs', [ 'taskid' => $dbsubmit->task_id ]);
                $this->tasknamebacs = $dbtask->name; // Found task name in database.
            } else {
                // Task not found even in database.
                $this->alerts[] = get_string('taskofsubmitismissingincontestanddb', 'mod_bacs', [ 'taskid' => $dbsubmit->task_id ]);
                $this->tasknamebacs = get_string('uppercasetasknotfound', 'mod_bacs');
            }
        } else {
            // Task exists in current contest.
            $this->taskletterbacs = $task->letter;
            $this->tasknamebacs = bacs_get_localized_name($task);
        }

        if (!$loadfullinformation) {
            return;
        }

        // ...tests.
        $testsinfo = $DB->get_records('bacs_submits_tests', ['submit_id' => $this->submitidbacs], 'test_id ASC');
        foreach ($testsinfo as $testinfo) {
            $this->add_test($testinfo);
        }

        // ...pretests.
        $sql = "SELECT bst.id,
                       bst.status_id,
                       btte.test_id,
                       btte.input,
                       btte.expected,
                       bsto.output
                  FROM {bacs_submits} bs
            INNER JOIN {bacs_tasks_test_expected} btte ON btte.task_id = bs.task_id
            INNER JOIN {bacs_submits_tests} bst ON bst.submit_id = bs.id AND bst.test_id = btte.test_id
            INNER JOIN {bacs_submits_tests_output} bsto ON bsto.submit_id = bst.submit_id AND bsto.test_id = bst.test_id
                 WHERE bs.id = :target_submit_id";
        $params = ['target_submit_id' => $this->submitidbacs];

        $pretestsinfo = $DB->get_records_sql($sql, $params);

        foreach ($pretestsinfo as $pretestinfo) {
            $this->add_pretest($pretestinfo);
        }
    }

    /**
     * This function
     * @param object $dbpretest
     * @return void
     */
    public function add_pretest($dbpretest) {
        $curpretest = new stdClass();

        $curpretest->test_id           = $dbpretest->test_id;
        $curpretest->test_id_natural   = $dbpretest->test_id + 1;
        $curpretest->input             = $dbpretest->input;
        $curpretest->output            = $dbpretest->output;
        $curpretest->expected          = $dbpretest->expected;
        $curpretest->status_id         = $dbpretest->status_id;
        $curpretest->verdict_css_class = bacs_verdict_to_css_class($dbpretest->status_id);
        $curpretest->verdict_formatted = format_verdict($dbpretest->status_id);

        $this->pretests[] = $curpretest;
    }

    /**
     * This function
     * @param object $dbtest
     * @return void
     */
    public function add_test($dbtest) {
        $curtest = new stdClass();

        $curtest->test_id           = $dbtest->test_id;
        $curtest->test_id_natural   = $dbtest->test_id + 1;
        $curtest->status_id         = $dbtest->status_id;
        $curtest->verdict_css_class = bacs_verdict_to_css_class($dbtest->status_id);
        $curtest->verdict_formatted = format_verdict($dbtest->status_id);

        $curtest->time_used           = $dbtest->time_used;
        $curtest->time_used_formatted = format_time_consumed($dbtest->time_used);

        $curtest->memory_used           = $dbtest->memory_used;
        $curtest->memory_used_formatted = format_memory_consumed($dbtest->memory_used);

        $this->tests[] = $curtest;
    }

    /**
     * This function
     * @param object $usedmillis
     * @return string
     * @throws coding_exception
     */
    public static function format_time_consumed($usedmillis) {
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
    public static function format_memory_consumed($usedbytes) {
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
     * @return string
     */
    public function verdict_to_css_class($verdict) {
        $verdictclass = 'verdict-failed';

        if ($verdict == VERDICT_ACCEPTED) {
            $verdictclass = 'verdict-accepted';
        } else if ($verdict == VERDICT_PENDING) {
            $verdictclass = 'verdict-none';
        } else if ($verdict == VERDICT_RUNNING) {
            $verdictclass = 'verdict-none';
        }

        return $verdictclass;
    }

    /**
     * This function
     * @param string $html
     * @return string
     */
    public function with_link_to_submit($html) {
        return "<a href='/mod/bacs/results_for_submit.php?id=$this->coursemoduleidbacs&submit_id=$this->submitidbacs'>$html</a>";
    }

    /**
     * This function
     * @return stdClass
     * @throws coding_exception
     */
    public function export_for_template() {
        $data = new stdClass();

        $data->available_submit_verdicts = array_map(
            function ($verdictid) {
                return (object) [
                    'verdict_id' => $verdictid,
                    'verdict_name' => get_string("submit_verdict_$verdictid", 'mod_bacs'),
                ];
            },
            range(0, 18)
        );

        if ($this->iscollapsedbacs) {
            $data->collapsed_class_header = 'collapsed';
            $data->collapsed_class_body = 'collapse';
        } else {
            $data->collapsed_class_header = '';
            $data->collapsed_class_body = '';
        }

        $data->solution_is_collapsed = $this->solutioniscollapsedbacs;

        $data->tests = $this->tests;
        $data->tests_present = (count($this->tests) > 0);

        $data->pretests = $this->pretests;
        $data->pretests_present = (count($this->pretests) > 0);

        $data->compiler_info   = $this->compilerinfobacs;
        $data->submit_time     = $this->submittimebacs;
        $data->source          = $this->source;
        $data->result_id       = $this->resultidbacs;
        $data->points          = $this->points;
        $data->idx             = $this->idx;
        $data->submit_id       = $this->submitidbacs;
        $data->max_time_used   = $this->maxtimeusedbacs;
        $data->max_memory_used = $this->maxmemoryusedbacs;

        $data->verdict_css_class         = bacs_verdict_to_css_class($this->resultidbacs);
        $data->max_time_used_formatted   = format_time_consumed($this->maxtimeusedbacs);
        $data->max_memory_used_formatted = format_memory_consumed($this->maxmemoryusedbacs);
        $data->verdict_formatted         = format_verdict($this->resultidbacs);
        $data->points_formatted          = $this->points;

        if ($this->rcfgprovidesubmitlinksinheaderbacs) {
            $data->verdict_formatted = $this->with_link_to_submit($data->verdict_formatted);
            $data->points_formatted  = $this->with_link_to_submit($data->points_formatted);
        }

        if ($this->rcfgshowdateswithsentattimebacs) {
            $data->submit_time_formatted = userdate($this->submittimebacs, "%d %B %Y (%A) %H:%M:%S");
        } else {
            $data->submit_time_formatted = userdate($this->submittimebacs, "%H:%M:%S");
        }

        if ($this->rcfgshowfulltasknamesbacs) {
            $data->task_name_formatted = $this->taskletterbacs . ". " . $this->tasknamebacs;
        } else {
            $data->task_name_formatted = $this->taskletterbacs;
        }

        $data->user_id        = $this->useridbacs;
        $data->user_firstname = $this->userfirstnamebacs;
        $data->user_lastname  = $this->userlastnamebacs;

        $data->lang_name       = $this->langnamebacs;
        $data->lang_id         = $this->langidbacs;

        $data->task_letter     = $this->taskletterbacs;
        $data->task_name       = $this->tasknamebacs;
        $data->task_id         = $this->taskidbacs;

        $data->coursemodule_id = $this->coursemoduleidbacs;

        $data->ace_editor_used  = $this->aceeditorusedbacs;
        $data->ace_editor_theme = $this->aceeditorthemebacs;
        $data->ace_editor_mode  = $this->aceeditormodebacs;

        $data->show_date_at_separate_row = $this->showdateatseparaterowbacs;

        $data->alerts = $this->alerts;

        $data->sesskey = sesskey();

        return $data;
    }
}
