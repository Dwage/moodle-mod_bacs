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
require_once(dirname(__FILE__) . '/locale_utils.php');

require_login();

$contest = new contest();
$contest->initialize_page();

$contest->pageurlbacs = new moodle_url('/mod/bacs/contest_actions.php', ['id' => $contest->coursemodule->id]);

print $OUTPUT->header();

$contest->aceeditorshownbacs = false;
$contest->print_contest_header('actions');

// ...check rights.
if (!$contest->usercapabilitiesbacs->edit) {
    throw new moodle_exception('generalnopermission', 'bacs');
}

// ...elements.
$htmlinputcoursemoduleid = "<input type='hidden' name='id' value='" . $contest->coursemodule->id . "'>";

$htmltaskidoptions = "<option value='0'>" . get_string('alltasks', 'bacs') . "</option>";
foreach ($contest->tasks as $task) {
    $localized_name = bacs_get_localized_name($task);
    $htmltaskidoptions .= "<option value='$task->task_id'>$task->letter. $localized_name</option>";
}

$htmlselecttaskid = "<select name='task_id' class='form-control mx-2 d-inline' value='0'>$htmltaskidoptions</select>";

print "<p></p>";

$htmlinputsesskey = "<input type='hidden' name='sesskey' value='" . sesskey() . "'>";
$sesskeyparam = "&sesskey=" . sesskey();

print "<p><form method='get' action='recalculate_points.php' class='form-inline'>
    " . get_string('recalculatepointsfor', 'bacs') . " <br>
    $htmlselecttaskid
    $htmlinputcoursemoduleid
    $htmlinputsesskey
    <input class='btn btn-primary' type='submit' value='" . get_string('recalculatepoints', 'bacs') . "'>
</form></p>";

print "<p><form method='get' action='rejudge_submits.php' class='form-inline'>
    " . get_string('rejudgesubmitsfor', 'bacs') . " <br>
    $htmlselecttaskid
    $htmlinputcoursemoduleid
    $htmlinputsesskey
    <input class='btn btn-primary' type='submit' value='" . get_string('rejudgesubmits', 'bacs') . "'>
</form></p>";

print "<p><a href='update_standings.php?id=" . $contest->coursemodule->id . $sesskeyparam . "'>
    <button class='btn btn-primary'>" . get_string('updatestandings', 'bacs') . "</button>
</a></p>";

print "<p><a href='recalculate_incidents.php?id=" . $contest->coursemodule->id . $sesskeyparam . "'>
    <button class='btn btn-primary'>" . get_string('recalculateincidents', 'bacs') . "</button>
</a></p>";

print "<p><a href='dashboard.php'>
    <button class='btn btn-warning'>" . get_string('dashboard', 'bacs') . "</button>
</a></p>";

print "<p><a href='diagnostics.php'>
    <button class='btn btn-warning'>" . get_string('plugindiagnosticspage', 'bacs') . "</button>
</a></p>";

print $OUTPUT->footer();
