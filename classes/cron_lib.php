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

namespace mod_bacs;

require_once(dirname(__FILE__, 4) . '/config.php');
require_once(dirname(__FILE__, 2) . '/lib.php');
require_once(dirname(__FILE__, 2) . '/utils.php');
require_once(dirname(__FILE__, 2) . '/submit_verdicts.php');
require_once(dirname(__FILE__, 2) . '/classes/api/sybon_client.php');

use coding_exception;
use dml_exception;
use dml_transaction_exception;
use mod_bacs\api\sybon_client;
use core\session\exception;
use stdClass;

/**
 * Contains functions for synchronizing DB with Sybon.
 *
 * @package    mod_bacs
 * @copyright  SybonTeam, sybon.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_lib {
    /**
     * This function
     * @param int $contestid
     * @param int $groupid
     * @param array $changedcontests
     * @return void
     */
    public static function mark_to_rebuild_standings($contestid, $groupid, &$changedcontests) {
        if (!array_key_exists($contestid, $changedcontests)) {
            $changedcontests[$contestid] = [];
        }

        $changedcontests[$contestid][$groupid] = true;
    }

    /**
     * This function
     * @return void
     * @throws exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     */
    public static function cron_langs() {
        global $DB;

        $sybonapikey = get_config('mod_bacs', 'sybonapikey');
        $sybonclient = new sybon_client($sybonapikey);

        $langs = $sybonclient->get_compilers();

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('bacs_langs');

        foreach ($langs as $lang) {
            $langrecord = new stdClass();
            $langrecord->compiler_type         = $lang->type;
            $langrecord->lang_id               = $lang->id;
            $langrecord->name                  = $lang->name;
            $langrecord->description           = $lang->description;
            $langrecord->time_limit_millis     = $lang->timeLimitMillis;
            $langrecord->memory_limit_bytes    = $lang->memoryLimitBytes;
            $langrecord->number_of_processes   = $lang->numberOfProcesses;
            $langrecord->output_limit_bytes    = $lang->outputLimitBytes;
            $langrecord->real_time_limit_mills = $lang->realTimeLimitMillis;
            $langrecord->compiler_args         = $lang->args;

            $DB->insert_record('bacs_langs', $langrecord, false);
        }

        $transaction->allow_commit();
    }

    /**
     * Send some bunch of pending submits to Sybon
     *
     * @param array $changedcontests
     *
     * @throws exception
     * @throws dml_exception
     */
    public static function cron_sendsubmits(&$changedcontests): void {
        global $DB;

        $sybonapikey = get_config('mod_bacs', 'sybonapikey');
        $sybonclient = new sybon_client($sybonapikey);

        $maximumrecordsforsinglerequest = 20;

        $submitsforsending = $DB->get_records(
            'bacs_submits',
            ['result_id' => VERDICT_PENDING],
            'submit_time ASC',
            'id, task_id, source, lang_id, contest_id, group_id',
            0,
            $maximumrecordsforsinglerequest
        );

        // Array-sequence of objects that hold together submit IDs
        // and submits as models ready for sending to Sybon.
        $matchedsubmits = [];

        foreach ($submitsforsending as $submit) {
            // Make safety checks and ignore inconsistent records.
            if (!$DB->record_exists('bacs_langs', ['lang_id' => $submit->lang_id])) {
                trigger_error("Submit language not found, submit_id=$submit->id lang_id=$submit->lang_id", E_USER_WARNING);
                // Invalidate submit.
                $submit->result_id = VERDICT_SERVER_ERROR;
                $DB->update_record('bacs_submits', $submit);
                continue;
            }

            if (!$DB->record_exists('bacs_tasks', ['task_id' => $submit->task_id])) {
                trigger_error("Submit task not found, submit_id=$submit->id task_id=$submit->task_id", E_USER_WARNING);
                // Invalidate submit.
                $submit->result_id = VERDICT_SERVER_ERROR;
                $DB->update_record('bacs_submits', $submit);
                continue;
            }

            // Mark changes.
            self::mark_to_rebuild_standings($submit->contest_id, $submit->group_id, $changedcontests);

            // Prepare Sybon submit.
            $sybonsubmit = (object) [
                    'compilerId' => $submit->lang_id,
                    'solution' => base64_encode($submit->source),
                    'solutionFileType' => 'Text',
                    'problemId' => $submit->task_id,
                    'pretestsOnly' => false,
                    'continueCondition' => 'Always',
            ];

            // Add.
            $matchedsubmits[] = (object) [
                    'as_sybon_submit' => $sybonsubmit,
                    'submit_id' => $submit->id,
            ];
        }

        if (count($matchedsubmits) == 0) {
            return;
        }

        $sybonsubmits = array_map(
            function ($ms) {
                return $ms->as_sybon_submit;
            },
            $matchedsubmits
        );
        $syncids = $sybonclient->send_all_submits($sybonsubmits);

        foreach (array_map(null, $matchedsubmits, $syncids) as [$matchedsubmit, $syncid]) {
            $updaterecord = new stdClass();
            $updaterecord->id = $matchedsubmit->submit_id;
            $updaterecord->sync_submit_id = $syncid;
            $updaterecord->result_id = VERDICT_RUNNING;

            $DB->update_record('bacs_submits', $updaterecord);
        }
    }


    /**
     * Fetch results of given submits from Sybon
     *
     * @param array $changedcontests
     * @param array $submits
     *
     * @throws exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     */
    public static function cron_getresults_for_submits(&$changedcontests, &$submits): void {
        $sybonapikey = get_config('mod_bacs', 'sybonapikey');
        $sybonclient = new sybon_client($sybonapikey);

        $syncids = [];
        $syncidtosubmitid = [];

        foreach ($submits as $runningsubmit) {
            $syncid = $runningsubmit->sync_submit_id;

            $syncids[] = $syncid;
            $syncidtosubmitid[$syncid] = $runningsubmit->id;

            // Mark changes.
            self::mark_to_rebuild_standings(
                $runningsubmit->contest_id,
                $runningsubmit->group_id,
                $changedcontests
            );
        }

        $checkingresults = $sybonclient->get_submits_results($syncids);

        foreach ($checkingresults as $checkingresult) {
            global $DB;

            $syncid = $checkingresult->id;
            $submitid = $syncidtosubmitid[$syncid];

            if (is_null($checkingresult)) {
                continue;
            }
            if (!property_exists($checkingresult, 'buildResult')) {
                continue;
            }
            if ($checkingresult->buildResult->status == "PENDING") {
                // Check running submit next time.
                unset($submits[$submitid]);
                continue;
            }

            $transaction = $DB->start_delegated_transaction();

            $maxtimeused = 0;
            $maxmemoryused = 0;
            $testnumfailed = null;
            $pretest = true;
            $pretestfailed = false;
            $testfailedverdict = null;
            $failed = false;

            $testgroups = $checkingresult->testGroupResults;
            if ($testgroups) {
                $testsinfo = [];
                $testsinfooutput = [];

                $testid = 0 - 1;
                foreach ($testgroups as $testgroup) {
                    if ($testgroup->internalId != "pre") {
                        $pretest = false;
                    }

                    foreach ($testgroup->testResults as $testresult) {
                        $testid++;

                        $timeused = $testresult->resourceUsage->timeUsageMillis;
                        $memoryused = $testresult->resourceUsage->memoryUsageBytes;

                        $maxtimeused = max($maxtimeused, $timeused);
                        $maxmemoryused = max($maxmemoryused, $memoryused);

                        $testinfo = new stdClass();
                        $testinfo->submit_id = $submitid;
                        $testinfo->status_id = bacs_submit_verdict_by_server_status($testresult->status);
                        $testinfo->test_id = $testid;
                        $testinfo->time_used = $timeused;
                        $testinfo->memory_used = $memoryused;

                        $testsinfo[] = $testinfo;

                        if (property_exists($testresult, 'actualResult')) {
                            $testinfooutput = new stdClass();
                            $testinfooutput->submit_id = $submitid;
                            $testinfooutput->test_id = $testid;
                            $testinfooutput->output = $testresult->actualResult;

                            $testsinfooutput[] = $testinfooutput;
                        }

                        if ($testinfo->status_id != VERDICT_ACCEPTED) {
                            if (!$failed) {
                                $failed = true;
                                $testnumfailed = $testid;
                                $testfailedverdict = $testinfo->status_id;
                            }
                            if ($pretest) {
                                $pretestfailed = true;
                            }
                        }
                    }
                }

                // Cleanup possible previous tests info.
                $DB->delete_records('bacs_submits_tests', ['submit_id' => $submitid]);
                $DB->delete_records('bacs_submits_tests_output', ['submit_id' => $submitid]);

                $DB->insert_records('bacs_submits_tests', $testsinfo);
                $DB->insert_records('bacs_submits_tests_output', $testsinfooutput);
            }

            $submit = new stdClass();
            $submit->id = $submitid;

            if ($checkingresult->buildResult->status == "FAILED") {
                $submit->result_id = VERDICT_COMPILE_ERROR;
            } else if ($checkingresult->buildResult->status == "SERVER_ERROR") {
                $submit->result_id = VERDICT_SERVER_ERROR;
            } else if (is_null($testnumfailed)) {
                $submit->result_id = VERDICT_ACCEPTED;
            } else {
                $submit->result_id = $testfailedverdict;
            }

            $submit->test_num_failed = $testnumfailed;
            $submit->points = 0;

            // Decode and convert to UTF-8.
            $submit->info = base64_decode($checkingresult->buildResult->output);
            $mostlikelyencoding = mb_detect_encoding($submit->info, [ 'UTF-8', 'ASCII' ]);
            $submit->info = mb_convert_encoding($submit->info, 'UTF-8', $mostlikelyencoding);

            $submit->max_time_used = $maxtimeused;
            $submit->max_memory_used = $maxmemoryused;
            $DB->update_record('bacs_submits', $submit);

            bacs_calculate_sumbit_points($submitid);

            $transaction->allow_commit();

            // --- FOR WEBSOCKETS ---
            $runningsubmit_user_id = 0;
            foreach ($submits as $s) {
                if ($s->id == $submitid) {
                    $runningsubmit_user_id = $s->user_id;
                    break;
                }
            }

            if ($runningsubmit_user_id) {
                $ws_data =[
                    'submit_id' => $submitid,
                    'result_id' => $submit->result_id,
                    'points' => $submit->points,
                    'verdict_css_class' => bacs_verdict_to_css_class($submit->result_id),
                    'verdict_formatted' => format_verdict($submit->result_id),
                    'time_formatted' => format_time_consumed($maxtimeused),
                    'memory_formatted' => format_memory_consumed($maxmemoryused),
                ];
                bacs_notify_ws_broker($runningsubmit_user_id, $ws_data);
            }

            unset($submits[$submitid]);
        }

        if (!empty($submits)) {
            throw new Exception("Failed to process some submits");
        }
    }

    /**
     * Fetch results of some bunch of submits from Sybon
     *
     * @param array $changedcontests
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function cron_getresults(&$changedcontests): void {
        global $DB;

        $maximumrecordsforsinglerequest = 50;

        $runningsubmits = $DB->get_records_select(
            'bacs_submits',
            // Sync_submit_id > 0 also means it is not NULL.
            "result_id = :verdict_running AND sync_submit_id > 0",
            ['verdict_running' => VERDICT_RUNNING],
            'submit_time ASC',
            '*',
            0,
            $maximumrecordsforsinglerequest
        );

        if (count($runningsubmits) == 0) {
            return;
        }

        // Try to proccess all selected submits.
        try {
            self::cron_getresults_for_submits($changedcontests, $runningsubmits);
        } catch (Exception $e) {
            // Failed to process some of selected submits, processing them one by one.
            foreach ($runningsubmits as $submitid => $runningsubmit) {
                try {
                    $unprocessedrunningsubmit = [ $submitid => $runningsubmit ];
                    self::cron_getresults_for_submits($changedcontests, $unprocessedrunningsubmit);
                } catch (Exception $e) {
                    // Failed to process single submit so it's invalidated.
                    $runningsubmit->result_id = VERDICT_SERVER_ERROR;
                    $DB->update_record('bacs_submits', $runningsubmit);
                }
            }
        }
    }

    /**
     * Send some pending submits and fetch some checked submits information, rebuild standings
     *
     * @param bool $verbose
     *
     * @throws dml_exception
     * @throws exception
     */
    public static function cron_send($verbose): void {
        $changedcontests = [];

        if ($verbose) {
            print "<p><b>cron_getresults...</b></p>";
        }
        self::cron_getresults($changedcontests);

        if ($verbose) {
            print "<p><b>cron_sendsubmits...</b></p>";
        }
        self::cron_sendsubmits($changedcontests);

        foreach ($changedcontests as $bacsid => $groupidsset) {
            if ($verbose) {
                print "<p><b>rebuilding contest $bacsid...</b></p>";
            }

            bacs_rebuild_common_standings($bacsid);

            foreach ($groupidsset as $groupid => $v) {
                if ($verbose) {
                    print "<p>rebuilding group $groupid for contest $bacsid...</p>";
                }
                bacs_rebuild_standings_for_group($bacsid, $groupid);
            }
        }
    }

    /**
     * This function
     *
     * @return void
     * @throws dml_exception
     */
    public static function cron_tasks() {
        global $DB;

        $sybonapikey = get_config('mod_bacs', 'sybonapikey');
        $sybonclient = new sybon_client($sybonapikey);

        try {
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('bacs_tasks');
            $DB->delete_records('bacs_tasks_test_expected');
            $DB->delete_records('bacs_tasks_collections');
            $DB->delete_records('bacs_tasks_to_collections');

            $collections = $sybonclient->get_collections();

            $insertedtskids = [];
            foreach ($collections as $collectioninfo) {
                $collectionrecord = new stdClass();
                $collectionrecord->name = $collectioninfo->name;
                $collectionrecord->description = $collectioninfo->description;
                $collectionrecord->collection_id = $collectioninfo->id;
                // ...fill collections table information.
                $DB->insert_record('bacs_tasks_collections', $collectionrecord, false);

                $collection = $sybonclient->get_collection($collectioninfo->id);

                $collectionproblems = $collection->problems;

                foreach ($collectionproblems as $item) {
                    if (array_key_exists($item->id, $insertedtskids)) {
                        continue;
                    }

                    $insertedtskids[$item->id] = true;

                    $record = new stdClass();
                    $record->task_id = $item->id;
                    $record->name = $item->name;
                    $record->author = $item->author;
                    $record->time_limit_millis = $item->resourceLimits->timeLimitMillis;
                    $record->memory_limit_bytes = $item->resourceLimits->memoryLimitBytes;
                    $record->count_tests = abs($item->testsCount);
                    $record->count_pretests = count($item->pretests);

                    // ...fill tasks to collections table information.
                    $tasktocollectionrecord = new stdClass();
                    $tasktocollectionrecord->task_id = $record->task_id;
                    $tasktocollectionrecord->collection_id = $collectionrecord->collection_id;
                    $DB->insert_record('bacs_tasks_to_collections', $tasktocollectionrecord, false);

                    // ...find min index and renumerate them from 0.
                    $minarg = array_map(
                        function ($pretest) {
                            return $pretest->id;
                        },
                        $item->pretests
                    );

                    // PHP < 8 behavior.
                    if (!$minarg) {
                        $pretestindexfixdelta = false;
                    } else {
                        $pretestindexfixdelta = min($minarg);
                    }

                    foreach ($item->pretests as $pretest) {
                        $dbpretest = new stdClass();
                        $dbpretest->task_id = $record->task_id;
                        $dbpretest->test_id = $pretest->id - $pretestindexfixdelta;
                        $dbpretest->input = $pretest->input;
                        $dbpretest->expected = $pretest->output;

                        print "<p>Inserting pretest $dbpretest->test_id for problem $dbpretest->task_id...</p>";
                        $DB->insert_record('bacs_tasks_test_expected', $dbpretest, false);
                        print "<p>Inserted pretest!</p>";
                    }

                    $record->test_points = bacs_default_test_string(
                        $record->count_tests,
                        $record->count_pretests
                    );

                    if(isset($item->statementUrl)) {
                        $record->statement_url = $item->statementUrl;
                    }
                    $record->statement_urls = json_encode($item->statementUrls);
                    $record->names = json_encode($item->names);
                    $record->statement_format = $item->format;
                    $record->revision = md5(json_encode($item->statementUrls));

                    print "<p>Inserting problem $record->task_id...</p>";
                    $lastinsertid = $DB->insert_record('bacs_tasks', $record, false);
                    print "<p>Inserted problem!</p>";
                }
            }

            $transaction->allow_commit();
        } catch (Exception $e) {
            debugging($e->getMessage());
        }
    }

    /**
     * This function
     *
     * @return void
     * @throws dml_exception
     */
    public static function cron_task_url() {
        global $DB;

        $sybonapikey = get_config('mod_bacs', 'sybonapikey');
        $sybonclient = new sybon_client($sybonapikey);

        $tasks = $DB->get_records('bacs_tasks', [], null, 'id, task_id');
        foreach ($tasks as $task) {
            try {
                $newstatementurl = $sybonclient->get_problem_statement($task->task_id);
                $rec = new stdClass();
                $rec->id = $task->id;
                $rec->task_id = $task->task_id;
                $rec->statement_url = $newstatementurl;
                $lastinsertid = $DB->update_record_raw('bacs_tasks', $rec);
            } catch (Exception $e) {
                debugging($e->getMessage());
            }
        }
    }

    /**
     * This function
     *
     * @return void
     * @throws dml_exception
     */
    public static function cron_incidents($verbose=false) {
        global $DB;

        /// TODO apply contest incident settings here

        $queuedupdates = [];
        $queuedupdateswithuserid = [];

        $transaction = $DB->start_delegated_transaction();

        $sql = "SELECT fp.id AS fingerprints_id,
                       fp.submit_id, 
                       fp.contest_id,
                       fp.status,
                       fp.tokenseq,
                       fp.satokenseq,
                       fp.tokencounts,
                       fp.satokencounts,
                       fp.tokenset,
                       submit.task_id,
                       submit.source,
                       submit.user_id,
                       submit.result_id
                  FROM {bacs_submits_fingerprints} fp
                  JOIN {bacs_submits} submit ON fp.submit_id = submit.id
                 WHERE fp.status = 0 AND submit.result_id > 3
        ";
        $submitstoprocess = $DB->get_records_sql($sql);

        // Compute fingerprints
        foreach ($submitstoprocess as $submit) {
            if ($verbose) print "<p>Computing fingerprints for submit submit_id=$submit->submit_id ...</p>";
            
            $tokenseq = bacs_tokenize_submit($submit->source);

            $tokencountsmap = [];
            foreach ($tokenseq as $token) {
                if (!array_key_exists($token, $tokencountsmap)) {
                    $tokencountsmap[$token] = 0;
                }
                $tokencountsmap[$token] += 1;
            }

            ksort($tokencountsmap);

            $tokencounts = [];
            $satokencounts = [];
            $tokenset = [];
            foreach ($tokencountsmap as $token => $tokencount) {
                $tokencounts[] = "$token $tokencount";
                $satokencounts[] = $tokencount;
                $tokenset[] = $token;
            }
            sort($satokencounts, SORT_NUMERIC); // make ordered
            // $tokencounts and $tokenset are already ordered by $token due to order of iteration

            $satokenseqmap = [];
            $satokenseq = [];
            foreach ($tokenseq as $token) {
                if (!array_key_exists($token, $satokenseqmap)) {
                    $satokenseqmap[$token] = count($satokenseqmap);
                }
                $satokenseq[] = $satokenseqmap[$token];
            }

            // Update database && $submit
            $fptokenseq = implode(" ", $tokenseq);
            $fpsatokenseq = implode(" ", $satokenseq);
            $fptokencounts = implode(" ", $tokencounts);
            $fpsatokencounts = implode(" ", $satokencounts);
            $fptokenset = implode(" ", $tokenset);

            $fprecord = (object) [
                'id' => $submit->fingerprints_id,
                'submit_id' => $submit->submit_id,
                'contest_id' => $submit->contest_id,
                'status' => 1,
                'tokenseq'      => $fptokenseq,
                'satokenseq'    => $fpsatokenseq,
                'tokencounts'   => $fptokencounts,
                'satokencounts' => $fpsatokencounts,
                'tokenset'      => $fptokenset,
            ];

            $DB->update_record('bacs_submits_fingerprints', $fprecord);

            $submit->tokenseq      = $fptokenseq;
            $submit->satokenseq    = $fpsatokenseq;
            $submit->tokencounts   = $fptokencounts;
            $submit->satokencounts = $fpsatokencounts;
            $submit->tokenset      = $fptokenset;

            // Queue update
            $queuedupdates["$submit->contest_id"] = $submit->contest_id;
            $queuedupdateswithuserid["$submit->contest_id $submit->user_id"] = [$submit->contest_id, $submit->user_id];
        }

        // Update incidents
        $ksthreshold = 1;

        foreach ($queuedupdateswithuserid as [$contestid, $userid]) {
            if ($verbose) print "<p>Updating KS for contestid=$contestid userid=$userid ...</p>";

            // Kangaroo score (KS) of a segment of submits sequence is
            // ratio of result to time with heuristic formula
            // KS = ((ACCEPTED + 0.1 * OTHER)^(1.75) - 3) / TIMEDELTA
            // TIMEDELTA in minutes
            // Kangaroo score is "convex" metric. If range A overlaps range B, then KS
            // on range union cannot be less than lower of KS(A) and KS(B).
            // |A \cap B| > 0
            // KS(A \cup B) >= MIN(KS(A), KS(B))
            // (KS(A) > X && KS(B) > X) => KS(A \cup B) > X
            // Thus greedy approach detecting KS-incidents will give 
            // a set of max-inclusion KS-incident clusters including all involved submits

            $usersubmits = array_values($DB->get_records('bacs_submits', ['contest_id' => $contestid, 'user_id' => $userid], 'submit_time ASC'));
            
            // Iterate all segments of sorted submits
            for ($l = 0; $l < count($usersubmits); $l++) {
                $submitvalue = 0;
                $bestr = -1;
                $ksforbestr = -1;

                for ($r = $l; $r < count($usersubmits); $r++) {
                    $submitvalue += ($usersubmits[$r]->result_id == VERDICT_ACCEPTED ? 1 : 0.1);

                    if ($l == $r) continue;

                    $timedelta = ($usersubmits[$r]->submit_time - $usersubmits[$l]->submit_time) / 60;
                    $ks = (pow($submitvalue, 1.75) - 3) / $timedelta;

                    if ($ks >= $ksthreshold) {
                        $bestr = $r;
                        $ksforbestr = $ks;
                    }

                    //if ($verbose) print "<p>KS DEBUG l=$l r=$r submitvalue=$submitvalue timedelta=$timedelta ks=$ks </p>";
                }

                // If no KS incidents with current $l, then proceed to the next $l, otherwise generate largest one
                if ($bestr == -1) continue;
                
                $r = $bestr;
                $ks = $ksforbestr;

                // Cleanup possible old KS incidents
                $submitids = [];
                for ($i = $l; $i <= $r; $i++) $submitids[] = $usersubmits[$i]->id;

                [$insql, $inparams] = $DB->get_in_or_equal($submitids);
                $sql = "SELECT * FROM {bacs_incidents_to_submits} WHERE submit_id $insql";
                $delincidentstosubmits = $DB->get_records_sql($sql, $inparams);

                $delincidentids = [];
                foreach ($delincidentstosubmits as $its) $delincidentids[] = $its->incident_id;

                if (count($delincidentids) > 0) {
                    [$insql, $inparams] = $DB->get_in_or_equal($delincidentids);

                    $DB->delete_records_select('bacs_incidents', "id $insql",  $inparams);
                    $DB->delete_records_select('bacs_incidents_to_submits', "incident_id $insql",  $inparams);
                }

                // Generate KS incident
                if ($verbose) {
                    $submitidsasstr = '[' . implode(', ', $submitids) . ']';
                    print "<p>Generating KS-incident for contestid=$contestid userid=$userid ks=$ks " 
                        . "range=[$l; $r] submitids=$submitidsasstr ...</p>";
                }

                $incident = (object) [
                    'contest_id' => $contestid,
                    'method' => 'kangaroo',
                    'info' => json_encode([
                        'user_id' => $userid,
                        'kangaroo_score' => $ks,
                        'submit_ids' => $submitids,
                    ]),
                ];
                $incidentid = $DB->insert_record('bacs_incidents', $incident);

                $incidentstosubmits = [];
                foreach ($submitids as $submitid) {
                    $incidentstosubmits[] = (object) [
                        'incident_id' => $incidentid,
                        'submit_id' => $submitid,
                    ];
                }
                $DB->insert_records('bacs_incidents_to_submits', $incidentstosubmits);

                // Adjust $usersubmits pointer
                $l = $r;
            }
        }

        // Fingerprints match incidents
        $methods = ['tokenseq', 'satokenseq', 'tokencounts', 'satokencounts', 'tokenset'];

        foreach ($submitstoprocess as $submit) {
            foreach ($methods as $method) {
                $sql = "SELECT fp.id AS fingerprints_id,
                               fp.submit_id, 
                               fp.contest_id,
                               fp.status,
                               fp.tokenseq,
                               fp.satokenseq,
                               fp.tokencounts,
                               fp.satokencounts,
                               fp.tokenset,
                               submit.task_id,
                               submit.source,
                               submit.user_id,
                               submit.result_id
                          FROM {bacs_submits_fingerprints} fp
                          JOIN {bacs_submits} submit ON fp.submit_id = submit.id
                         WHERE fp.contest_id = :contest_id AND submit.result_id > 3 AND fp.$method = :submitfp
                ";
                $params = [
                    'submitfp' => $submit->$method,
                    'contest_id' => $submit->contest_id,
                ];
                $collidingsubmits = $DB->get_records_sql($sql, $params);

                // Check if multiple users are involved
                $useridcounts = array_count_values(array_map(function($s) {return $s->user_id;}, $collidingsubmits));
                
                if (count($useridcounts) <= 1) continue;

                // Find possible fingerprint incident
                $collidingsubmitids = [];
                foreach ($collidingsubmits as $collidingsubmit) $collidingsubmitids[] = $collidingsubmit->submit_id;
                [$insql, $params] = $DB->get_in_or_equal($collidingsubmitids, SQL_PARAMS_NAMED);

                $sql = "SELECT incident.id
                          FROM {bacs_incidents} incident
                          JOIN {bacs_incidents_to_submits} its ON incident.id = its.incident_id
                         WHERE incident.contest_id = :contest_id AND incident.method = :method AND its.submit_id $insql
                ";
                $params['method'] = $method;
                $params['contest_id'] = $submit->contest_id;

                $optionalincident = $DB->get_records_sql($sql, $params);

                // Update incident
                if (count($optionalincident) > 0) {
                    // Add submit to incident if not added already
                    $incidentid = array_values($optionalincident)[0]->id;

                    $submitisalreadyincluded = $DB->record_exists('bacs_incidents_to_submits', [
                        'incident_id' => $incidentid,
                        'submit_id' => $submit->submit_id
                    ]);
                    
                    if (!$submitisalreadyincluded) {
                        $DB->insert_record('bacs_incidents_to_submits', (object) [
                            'incident_id' => $incidentid,
                            'submit_id' => $submit->submit_id
                        ]);

                        if ($verbose) print "<p>Updated fingerprint incident method=$method incidentid=$incidentid with submitid=$submit->submit_id</p>";
                    }
                } else {
                    // Create fingerprint incident
                    $incident = (object) [
                        'contest_id' => $submit->contest_id,
                        'method' => $method,
                        'info' => json_encode([
                            'task_id' => $submit->task_id, 
                            // probably should be changed to most popular task_id in colliding submits instead of arbitrary one
                        ]),
                    ];
                    $incidentid = $DB->insert_record('bacs_incidents', $incident);

                    $itsrecords = [];
                    foreach ($collidingsubmits as $collidingsubmit) {
                        $itsrecords[] = (object) [
                            'submit_id' => $collidingsubmit->submit_id,
                            'incident_id' => $incidentid
                        ];
                    }
                    $DB->insert_records('bacs_incidents_to_submits', $itsrecords);

                    if ($verbose) {
                        $collidingsubmitidsasstr = '[' . implode(', ', $collidingsubmitids) . ']';
                        print "<p>Created fingerprint incident method=$method incidentid=$incidentid submits=$collidingsubmitidsasstr</p>";
                    }
                }
            }
        }

        // Update contest incidents_info cache
        foreach ($queuedupdates as $contestid) {
            if ($verbose) print "<p>Updating incidents_info for contestid=$contestid ...</p>";

            $contest = $DB->get_record('bacs', ['id' => $contestid]);
            $incidents = $DB->get_records('bacs_incidents', ['contest_id' => $contestid]);

            $incidentids = [];
            foreach ($incidents as $incident) $incidentids[] = $incident->id;
            [$insql, $inparams] = $DB->get_in_or_equal($incidentids);
            $incidentstosubmits = $DB->get_records_select('bacs_incidents_to_submits', "incident_id $insql", $inparams);

            $submitidsbyincidentid = [];
            foreach ($incidentstosubmits as $its) {
                if (!array_key_exists($its->incident_id, $submitidsbyincidentid)) {
                    $submitidsbyincidentid[$its->incident_id] = [];
                }
                $submitidsbyincidentid[$its->incident_id][] = $its->submit_id;
            }

            $incidentsinfo = [];
            foreach ($incidents as $incident) {
                $incidentsinfo[] = [
                    'id' => $incident->id,
                    'method' => $incident->method,
                    'info' => json_decode($incident->info),
                    'submit_ids' => $submitidsbyincidentid[$incident->id],
                ];
            }

            $contest->incidents_info = json_encode($incidentsinfo);

            $DB->update_record('bacs', $contest);
        }
        
        $transaction->allow_commit();
    }


}
