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

require_once(dirname(__FILE__, 3) . '/config.php');
require_once(dirname(__FILE__, 1) . '/lib.php');
require_once(dirname(__FILE__, 1) . '/utils.php');

require_login();

$contest = new contest();
$contest->initialize_page();

require_capability('mod/bacs:edit', $PAGE->context);

require_sesskey();

$contest->pageurlbacs = new moodle_url('/mod/bacs/recalculate_incidents.php', ['id' => $contest->coursemodule->id]);

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
    $contestid = $contest->bacs->id;

    // Delete all old incidents
    $incidents = $DB->get_records('bacs_incidents', ['contest_id' => $contestid], '', 'id, contest_id');

    if (count($incidents) > 0) {
        $incidentids = [];
        foreach ($incidents as $incident) $incidentids[] = $incident->id;
    
        $incidentidsasstr = '[' . implode(', ', $incidentids) . ']';
        print "<p>Deleting old incidents with incident_id in $incidentidsasstr ... </p>";
    
        [$insql, $inparams] = $DB->get_in_or_equal($incidentids);
        $DB->delete_records_select('bacs_incidents', "id $insql",  $inparams);
        $DB->delete_records_select('bacs_incidents_to_submits', "incident_id $insql", $inparams);    
    }

    // Iterate submits and mark for incidents recalc
    $submits = $DB->get_records('bacs_submits', ['contest_id' => $contestid], '', 'id, contest_id');

    foreach ($submits as $submit) {
        bacs_mark_submit_for_incidents_recalc($submit->id, $contestid);

        print "<p>Marked submit with submit_id=$submit->id</p>";
    }
    
    // Success
    print "<p>Success! </p>";
} catch (Exception $e) {
    debugging($e->getMessage());
}

echo $OUTPUT->footer();
