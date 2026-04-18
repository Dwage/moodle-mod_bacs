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

$contest->pageurlbacs = new moodle_url('/mod/bacs/results_for_submit.php', ['id' => $contest->coursemodule->id]);

print $OUTPUT->header();

// ...setup targets.
$contest->register_query_param('submit_id', 0, PARAM_INT);
$contest->register_query_param('property', '', PARAM_TEXT);
$contest->register_query_param('value', '', PARAM_TEXT);

$submitid = $contest->queryparamsbacs->submit_id;
$propertyname = $contest->queryparamsbacs->property;
$propertyvalue = $contest->queryparamsbacs->value;

// ...check rights.
if (!$contest->usercapabilitiesbacs->edit) {
    throw new moodle_exception('generalnopermission', 'bacs');
}
$contest->require_capability_for_exact_submit($submitid);

// ...find submit.
$submit = $DB->get_record('bacs_submits', ['id' => $submitid], '*', MUST_EXIST);

// ...contest header.
$contest->aceeditorshownbacs = false;
$contest->print_contest_header('actions');

print "
    <p>Submit ID: $submitid</p>
    <p>Property name: $propertyname</p>
    <p>Property value: $propertyvalue</p>";

if ($propertyname == "points") {
    $updatesubmit = new stdClass();
    $updatesubmit->id = $submit->id;
    $updatesubmit->points = (int)$propertyvalue;
    $DB->update_record('bacs_submits', $updatesubmit);
} else if ($propertyname == "verdict") {
    $updatesubmit = new stdClass();
    $updatesubmit->id = $submit->id;
    $updatesubmit->points = 0;
    $updatesubmit->result_id = (int)$propertyvalue;
    $DB->update_record('bacs_submits', $updatesubmit);
} else if ($propertyname == "comment") {
    $updatesubmit = new stdClass();
    $updatesubmit->id = $submit->id;
    $updatesubmit->info = base64_decode($propertyvalue);
    $DB->update_record('bacs_submits', $updatesubmit);
} else {
    throw new Exception("Unknown submit property");
}

// ...on success.
bacs_rebuild_all_standings($contest->bacs->id);

bacs_redirect_via_js("results_for_submit.php?id=" . $contest->coursemodule->id . "&submit_id=$submitid");

echo $OUTPUT->footer();
