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

$string['actions'] = 'Actions';
$string['actionswithcontest'] = 'Actions with contest';
$string['add'] = 'Add';
$string['advancedcontestsettings'] = 'Advanced contest settings';
$string['advancedsettingsmessage1'] =
    'This sections provides direct access for performing complicated operations with contest data. For example, fast contest copy, debugging or setting up tasks that are not present in database. ';
$string['advancedsettingsmessage2'] =
    'Note that other components do not track changes in these fields and will overwrite them on most operations.';
$string['advancedsettingsmessage3'] =
    'Use this only if you know what you are doing.';
$string['advancedwarning'] = 'Warning!';
$string['allcollections'] = 'All collections';
$string['alltasks'] = 'All tasks';
$string['alltasksfrom'] = 'All tasks from';
$string['amountofaccepted'] = 'Amount of accepted';
$string['amountofpretests'] = 'Amount of pretests';
$string['amountoftests'] = 'Amount of tests';
$string['amountoftried'] = 'Amount of tried';
$string['applyfilter'] = 'Apply filter';
$string['author'] = 'Author';
$string['backtosubmit'] = 'Back to submit';
$string['backtosubmits'] = 'Back to submits';
$string['bacs:addinstance'] = 'Add and remove contests from course';
$string['bacs:edit'] = 'Change any contest settings, rejudge submits';
$string['bacs:readtasks'] = 'View full statements of all tasks in contest';
$string['bacs:submit'] = 'Submit solutions of any task in contest';
$string['bacs:view'] = 'View tasks and own submits in contest';
$string['bacs:viewany'] = 'View detailed info about any submission in contest';
$string['bacsrating:averagerating'] = 'Average rating of participants';
$string['bacsrating:participants'] = 'Participants';
$string['bacsrating:participantslist'] = 'Participants list';
$string['bacsrating:participant'] = 'Participant';
$string['bacsrating:rated'] = 'Rated';
$string['bacsrating:rating'] = 'Rating';
$string['bacsrating:sortby'] = 'Sort by';
$string['bacsrating:sortby:rating_asc'] = 'Rating (Ascending)';
$string['bacsrating:sortby:rating_desc'] = 'Rating (Descending)';
$string['beforethecontest'] = 'Before the contest';
$string['bright_brighttheme'] = 'Bright';
$string['cannotviewsubmit'] = 'Cannot view this submit';
$string['changegrouptosubmit'] = 'In order to submit solutions, you have to select a group you are a member of.';
$string['charactermustbeadded'] = 'this character should be added';
$string['charactermustberemoved'] = 'this character should be removed';
$string['chartdayhourdistribution'] = 'Day hour distribution chart';
$string['chartverdicts'] = 'Verdicts chart';
$string['choosetask'] = 'Choose task';
$string['clear'] = 'Clear';
$string['clearform'] = 'Clear form';
$string['compare'] = 'Compare';
$string['comparison'] = 'Comparison';
$string['compilermessage'] = 'Compiler message';
$string['configmaxselectableyear'] = 'Maximum year that is possible to select in contest start time or contest end time in contest settings page';
$string['configminselectableyear'] = 'Minimum year that is possible to select in contest start time or contest end time in contest settings page';
$string['configpreferedlanguage'] = 'Languages that will be used by default for displaying task statements';
$string['preferedlanguage'] = 'Prefered languages';
$string['configsybonapikey'] = 'Sybon API key is used to send submissions, get languages and get tasks';
$string['contesthasstartednotification'] = 'The contest has started. Do you want to enter the contest?';
$string['contestmode'] = 'Contest mode';
$string['contestname'] = 'Contest name';
$string['contestsettings'] = 'Contest settings';
$string['contesttasks'] = 'Contest tasks';
$string['count'] = 'Count';
$string['course'] = 'Course';
$string['coverssametests'] = 'covers same tests as the new group';
$string['dark_darktheme'] = 'Dark';
$string['dashboard'] = 'Dashboard';
$string['dateandtime'] = 'Date and time';
$string['days_morethanxdays'] = 'days';
$string['default_defaulttheme'] = 'Default';
$string['delete'] = 'Delete';
$string['detectincidents'] = 'Detect incidents';
$string['devirtualize'] = 'Devirtualize';
$string['devirtualizewarning'] =
    'Are you sure that you want to remove virtual participation? User submits will NOT be removed. Virtual participation data cannot be restored.';
$string['diagnostics:check'] = 'Check';
$string['diagnostics:deprecated_tasks_msg'] = 'Deprecated tasks check. Deprecated tasks available: {$a}';
$string['diagnostics:duplicate_tasks_msg'] = 'Duplicate tasks check. {$a->tasks_to_be_replaced} tasks to be replaced / {$a->tasks_without_replacement} duplicates without replacement / {$a->tasks_with_the_same_name} tasks with the same name';
$string['diagnostics:duration'] = 'Duration';
$string['diagnostics:error'] = 'Error';
$string['diagnostics:message'] = 'Message';
$string['diagnostics:milliseconds_short'] = 'ms';
$string['diagnostics:ok'] = 'OK';
$string['diagnostics:showdetailedlogs'] = 'Show detailed logs';
$string['diagnostics:status'] = 'Status';
$string['diagnostics:sybon_api_collections_msg'] = 'Sybon tasks API check. Task collections available: {$a}';
$string['diagnostics:sybon_api_compilers_msg'] = 'Sybon compilers API check. Compilers/languages available: {$a}';
$string['diagnostics:sybon_api_submits_msg'] = 'Sybon submits API check. Test submit Sybon ID: {$a}';
$string['diagnostics:sybon_api_submits_msg_no_submits'] = 'Sybon submits API check: no submits available. There should be at least one submit (not "Pending") available to check Sybon submits API';
$string['diagnostics:task_pretests_msg'] = 'Task pretests check. {$a->tasks_in_total} tasks in total / {$a->tasks_with_wrong_pretests_count} tasks with wrong pretests count / {$a->tasks_with_wrong_pretests_index} tasks with wrong pretests numeration / {$a->tasks_without_pretests} tasks without pretests';
$string['diagnostics:task_statement_format_msg'] = 'Task statement format check. {$a->with_doc} in DOC(DOCX) / {$a->with_pdf} in PDF / {$a->with_html} in HTML / {$a->with_other_format} in other formats';
$string['diagnostics:test_points_strings_msg'] = 'Settings for tasks in contests check. {$a->records_in_total} records in total / {$a->records_with_custom_points} with custom points / {$a->records_with_missing_task} with missing task / {$a->records_mismatched} with mismatched points';
$string['diagnostics:warning'] = 'Warning';
$string['download'] = 'Download';
$string['duplicatetasks'] = 'Duplicate tasks are not allowed!';
$string['editortheme'] = 'Editor theme';
$string['endtime'] = 'End time';
$string['entercomment'] = 'Enter comment:';
$string['entercontest'] = 'Enter contest';
$string['entercontestwithoutvirtual'] = 'Enter contest without virtual participation';
$string['enterpoints'] = 'Enter points:';
$string['enterverdict'] = 'Enter verdict:';
$string['errordeletingtask'] = "Error deleting task with id=";
$string['fillwithintegers'] = "All fields must be filled with single non-negative integers.";
$string['format'] = 'Format';
$string['from'] = 'Start time of contest';
$string['fromshort'] = 'From';
$string['futurepointsnotification'] = 'Note that just changing test points will have no effect on past submits. You have to click "Recalculate points" in "Actions" menu to force past sumbits be judged by new test points.';
$string['generalnopermission'] = 'You have no permission for this operation!';
$string['gotogroupsettings'] = 'Go to special group settings';
$string['groupname'] = 'Group name';
$string['groupsettingsarenotused'] = 'Special group settings are not used';
$string['groupsettingsareused'] = '{$a->with_group_settings} out of {$a->total_count} groups are using special settings';
$string['hideinactive'] = 'Hide inactive';
$string['hidesolution'] = 'Hide solution';
$string['hideupsolving'] = 'Hide upsolving';
$string['id'] = 'ID';
$string['incident'] = 'Incident';
$string['incidentdetectiondisabledalert'] = 'Automatic incident detection for this contest is disabled. Enable "Detect incidents" in "Incidents" section in contest settings.';
$string['incidents'] = 'Incidents';
$string['incidentssettings'] = 'Incidents settings';
$string['input'] = 'Input';
$string['invalidrange'] = "Range is invalid!";
$string['isolatedparticipants'] = 'Isolated participants';
$string['isolateparticipants'] = 'Isolate participants';
$string['language'] = 'Language';
$string['lastimprovedat'] = 'Improved at';
$string['letterlimit26'] = 'List is limited to maximum of 26 letters. Adding more is not allowed.';
$string['letterlistempty'] = "Error deleting last letter: letters list is empty";
$string['linktothissubmission'] = 'Link to this submission';
$string['load_from_file'] = 'Load from file';
$string['maximumtasks26'] = "Contest is limited to maximum of 26 tasks. Adding more is not allowed.";
$string['maxselectableyear'] = 'Max selectable year';
$string['method'] = 'Method';
$string['memory'] = 'Memory';
$string['memorylimit'] = 'Memory limit';
$string['minselectableyear'] = 'Min selectable year';
$string['modulename'] = 'BACS contest';
$string['modulename_help'] = 'bacs is the plugin that do smth. Sure, it\'s better than nothing';
$string['modulenameplural'] = 'BACS contests';
$string['more'] = 'More';
$string['morethan'] = 'more than';
$string['mysubmits'] = 'My submits';
$string['n'] = 'N';
$string['negativepointsnotallowed'] = "Negative points are not allowed";
$string['nopermissiontosubmit'] = 'You have no permission to submit solutions.';
$string['not_found'] = 'Not found!';
$string['not_started'] = 'Contest has not started!';
$string['open'] = 'Open';
$string['outputexpected'] = 'Expected output';
$string['outputreal'] = 'Real output';
$string['penalty'] = "Penalty";
$string['pluginadministration'] = 'BACS settings';
$string['plugindiagnosticspage'] = 'Plugin diagnostics page';
$string['pluginname'] = 'BACS contests';
$string['points'] = "Points";
$string['pointsforfullsolution'] = 'Points for full solution';
$string['pointsformissingtask'] = "Cannot load test points for a missing task.";
$string['pointspergroup'] = 'Points for group';
$string['pointspertest'] = 'Points per test';
$string['presolving'] = 'Allow problem solving before the contest beginning';
$string['pretest'] = 'Pretest';
$string['privacy:metadata:bacs'] = 'Stores information about contests and standings';
$string['privacy:metadata:bacs:standings'] = 'JSON-cached information about all submits that are shown in standings';
$string['privacy:metadata:bacs_group_info'] = 'Stores special settings for groups and group standings';
$string['privacy:metadata:bacs_group_info:standings'] = 'JSON-cached information about all submits that are shown in group standings';
$string['privacy:metadata:bacs_submits'] = 'Stores submits information';
$string['privacy:metadata:bacs_submits:contest_id'] = 'Contest ID where submit was sent';
$string['privacy:metadata:bacs_submits:group_id'] = 'Group ID that given submit belongs to (or zero if group was not used)';
$string['privacy:metadata:bacs_submits:info'] = 'Compiler message or special info';
$string['privacy:metadata:bacs_submits:lang_id'] = 'Programming language ID';
$string['privacy:metadata:bacs_submits:max_memory_used'] = 'Maximum memory used across all tests in bytes';
$string['privacy:metadata:bacs_submits:max_time_used'] = 'Maximum time used across all tests in milliseconds';
$string['privacy:metadata:bacs_submits:points'] = 'Points';
$string['privacy:metadata:bacs_submits:result_id'] = 'Judging result';
$string['privacy:metadata:bacs_submits:source'] = 'Source code';
$string['privacy:metadata:bacs_submits:submit_time'] = 'Time when submit was created';
$string['privacy:metadata:bacs_submits:task_id'] = 'Task ID';
$string['privacy:metadata:bacs_submits:test_num_failed'] = 'Number of first failed test';
$string['privacy:metadata:bacs_submits:user_id'] = 'Author of submit';
$string['privacy:metadata:bacs_submits_tests'] = 'Stores information about all submit runs on each test';
$string['privacy:metadata:bacs_submits_tests:memory_used'] = 'Memory used in bytes';
$string['privacy:metadata:bacs_submits_tests:status_id'] = 'Judging result';
$string['privacy:metadata:bacs_submits_tests:submit_id'] = 'Submit ID';
$string['privacy:metadata:bacs_submits_tests:test_id'] = 'Test ID';
$string['privacy:metadata:bacs_submits_tests:time_used'] = 'Time used in milliseconds';
$string['privacy:metadata:bacs_submits_tests_output'] = 'Stores submit outputs on pretests';
$string['privacy:metadata:bacs_submits_tests_output:output'] = 'Submit output';
$string['privacy:metadata:bacs_submits_tests_output:submit_id'] = 'Submit ID';
$string['privacy:metadata:bacs_submits_tests_output:test_id'] = 'Test ID';
$string['privacy:metadata:sybon_checking_service'] = 'Is used to run solutions and obtain judgement results';
$string['privacy:metadata:sybon_checking_service:lang_id'] = 'Programming language ID';
$string['privacy:metadata:sybon_checking_service:source'] = 'Source code';
$string['privacy:metadata:sybon_checking_service:task_id'] = 'Task ID';
$string['privacy:metadata:sybon_checking_service:timestamp'] = 'Time when submit was passed to judgement';
$string['prog_lang'] = 'Programming language';
$string['programcode'] = 'Program code';
$string['rawcontesttaskids'] = 'Raw encoded task IDs string';
$string['rawcontesttasktestpoints'] = 'Raw encoded test points string';
$string['recalcpoints'] = 'Recalc points';
$string['recalculateincidents'] = 'Recalculate incidents';
$string['recalculatepoints'] = 'Recalculate points';
$string['recalculatepointsfor'] = 'Recalculate points for:';
$string['rejectsubmit'] = 'Reject submit';
$string['rejudge'] = 'Rejudge';
$string['rejudgesubmits'] = 'Rejudge submits';
$string['rejudgesubmitsfor'] = 'Rejudge submits for:';
$string['rememberlanguage'] = 'Remember choosen language';
$string['result'] = 'Result';
$string['resultsgraph'] = 'Results graph';
$string['search'] = 'Search';
$string['seconds_short'] = 's';
$string['send'] = 'Send';
$string['sendforjudgement'] = 'Send solution for judgement';
$string['sendinginprogress'] = 'Sending in progress';
$string['sentat'] = 'Sent at';
$string['setcomment'] = 'Set comment';
$string['setpoints'] = 'Set points';
$string['settings'] = 'Settings';
$string['setverdict'] = 'Set verdict';
$string['showfirstacceptedflag'] = 'Show first accepted flag';
$string['showincidentflags'] = 'Show incidents';
$string['showlastimprovementcolumn'] = 'Show last improvement column';
$string['showsolution'] = 'Show solution';
$string['showsubmitsfor'] = 'Show submits for';
$string['showsubmitsuptobest'] = 'Show submits upto best';
$string['showtestingflag'] = 'Show testing flag';
$string['showupsolving'] = 'Show upsolving';
$string['source'] = 'Source code';
$string['standings'] = 'Standings';
$string['standingsmode'] = 'Standings mode';
$string['standingssettings'] = 'Standings settings';
$string['starttime'] = 'Start time';
$string['startvirtualparticipationnow'] = 'Start virtual participation now';
$string['statement'] = 'Statement';
$string['status'] = 'Status';
$string['statusfrozen'] = 'Frozen';
$string['statusnotstarted'] = 'Not started';
$string['statusover'] = 'Over';
$string['statusrunning'] = 'Running';
$string['statusunknown'] = 'Unknown';
$string['submissionsspampenalty'] =
    "You have sent too many submits! You have sent 50 submits within last 5 minutes. Sending submits is temporarily prohibited. Try reloading this page later.";
$string['submissionsspamwarning'] =
    "You are sending too many submits! If you will send 50 submits in 5 minutes, you will temporarily lose ability to send submits.";
$string['submit_verdict_0'] = "Unknown";
$string['submit_verdict_1'] = "Pending";
$string['submit_verdict_10'] = "Output limit exceeded";
$string['submit_verdict_11'] = "Presentation error";
$string['submit_verdict_12'] = "Wrong answer";
$string['submit_verdict_13'] = "Accepted";
$string['submit_verdict_14'] = "Incorrect request";
$string['submit_verdict_15'] = "Insufficient data";
$string['submit_verdict_16'] = "Queries limit exceeded";
$string['submit_verdict_17'] = "Excess data";
$string['submit_verdict_18'] = "Submit rejected";
$string['submit_verdict_2'] = "Running";
$string['submit_verdict_3'] = "Server error";
$string['submit_verdict_4'] = "Compile error";
$string['submit_verdict_5'] = "Runtime error";
$string['submit_verdict_6'] = "Fail test";
$string['submit_verdict_7'] = "CPU time limit exceeded";
$string['submit_verdict_8'] = "Real time limit exceeded";
$string['submit_verdict_9'] = "Memory limit exceeded";
$string['submitmessagetaskismissing'] =
    'This task is missing in Moodle database. Delete this task from this contest or update information about available tasks.';
$string['submits'] = 'Submits';
$string['submitsfrom'] = 'Submits from';
$string['submitslowercase'] = 'submits';
$string['sumofpoints'] = 'Sum of points';
$string['sybonapikey'] = 'Sybon API key';
$string['task'] = 'Task';
$string['taskdynamics'] = 'Task dynamics';
$string['taskid'] = 'Task ID';
$string['tasklist'] = 'Task list';
$string['taskname'] = 'Task name';
$string['taskofsubmitismissingincontest'] = '
    Task (ID {$a->taskid}) of this submit is missing in current contest.
    You should add this task back to contest if you want this submit to display properly.';
$string['taskofsubmitismissingincontestanddb'] = '
    Task (ID {$a->taskid}) of this submit is missing both in this contest and Moodle database.
    You should update information about available tasks and add this task back to contest if you want this submit to display properly.';
$string['tasks'] = 'Tasks';
$string['test'] = 'Test';
$string['testgroup'] = 'Test group';
$string['testpoints'] = 'Test points';
$string['tests'] = 'Tests';
$string['therearenoresults'] = 'There are no results';
$string['time'] = 'Time';
$string['timelimit'] = 'Time limit';
$string['to'] = 'End time of contest';
$string['toshort'] = 'To';
$string['totalincidents'] = 'Total incidents of suspicious behavior';
$string['updatestandings'] = 'Update standings';
$string['uppercaselanguagenotfound'] = 'LANGUAGE NOT FOUND';
$string['uppercasetasknotfound'] = 'TASK NOT FOUND';
$string['upsolving'] = 'Allow upsolving';
$string['upsolving_help'] = 'Students will be allowed to send tasks after the end of the contest. Results of upsolving will be shown separately.';
$string['upsolvingisdisabled'] = 'Upsolving is disabled for this contest.';
$string['usecustomtestpoints'] = 'Use custom test points';
$string['usegroupsettings'] = 'Use special settings for this group';
$string['userdynamics'] = 'User dynamics';
$string['username'] = 'User name';
$string['verdict'] = 'Verdict';
$string['virtualparticipants'] = 'Virtual participants';
$string['virtualparticipantslist'] = 'Virtual participants list';
$string['virtualparticipantslistisempty'] = 'Virtual participants list is empty.';
$string['virtualparticipation'] = 'Virtual participation';
$string['virtualparticipationallow'] = 'Allow virtual participation';
$string['virtualparticipationallowmsg'] = 'Virtual participation is available in this contest. Virtual participation will become available after the contest start.';
$string['virtualparticipationalreadyhavesubmits'] = 'You cannot start virtual participation because you already have submits in this contest.';
$string['virtualparticipationconfirmstartdmsg'] = 'Are you sure that you want to start virtual participation now? You will not be able cancel virtual participation after start.';
$string['virtualparticipationdisable'] = 'Disable virtual participation';
$string['virtualparticipationdisabledmsg'] = 'Virtual participation is disabled in this contest.';
$string['virtualparticipationgeneralwarning'] = '
    Virtual participation is the way to participate in the contest at independent time.
    Results of all users are shown relatively to their different start times.
    If you already participated in this contest or if you have already seen the tasks of this contest you should upsolve instead.
    <br><br>
    <b>Warning!</b> Every user can start virtual participation only once. If you have any non-rejected submits in this contest you cannot participate virtually.';
$string['virtualparticipationonly'] = 'Only virtual participation';
$string['virtualparticipationonlymsg'] = 'This contest is virtual participation only. Virtual participation will become available after the contest start.';
$string['virtualparticipationselectyourgroup'] = 'You have to select your group in order to start virtual participation.';
$string['virtualparticipationstartedat'] = 'Your virtual participation started at';

$string['difficultyanalysis'] = 'Contest Difficulty Analysis';
$string['analyzecontestdifficulty'] = 'Analyze Contest Difficulty';
$string['no_plugin_installed'] = 'Plugin bacs_rating is not installed yet. You can\'t use this functionality';
$string['notasksselected'] = 'No tasks selected. Please add tasks to the contest first.';
$string['difficulty_analysis_students_can_solve'] = 'Students who can solve the task';
$string['difficulty_analysis_ideal_curve'] = 'Ideal curve';
$string['difficulty_analysis_number_of_students'] = 'Number of students';
$string['difficulty_analysis_tasks'] = 'Tasks';
$string['difficulty_analysis_no_students'] = 'No students are enrolled in this course.';
$string['difficulty_analysis_no_taskids'] = 'No task IDs were found.';
$string['difficulty_analysis_no_userids'] = 'No user IDs were found.';

$string['chart_mode_realtime'] = 'Real Time';
$string['chart_mode_normalized'] = 'Normalized (Race)';
$string['view_leaderdynamics'] = 'Leader Dynamics';
$string['view_resultsgraph'] = 'Results Graph';
$string['view_taskdynamics'] = 'Task Dynamics';
$string['select_task'] = 'Select task';
$string['view_monitor'] = 'View monitor';
$string['verdict_ok'] = 'Accepted';
$string['verdict_not_ok'] = 'Failed';
$string['timefromstart'] = 'Time from start';