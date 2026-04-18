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

$contest->pageurlbacs = new moodle_url('/mod/bacs/virtual_participants_list.php', ['id' => $contest->coursemodule->id]);

echo $OUTPUT->header();

$contest->aceeditorshownbacs = false;
$contest->groupselectorshownbacs = false;
$contest->print_contest_header('virtual_participants');

// ...check rights.
if (!$contest->usercapabilitiesbacs->edit) {
    throw new moodle_exception('generalnopermission', 'bacs');
}

// ...get vp records.
if ($contest->currentgroupidorzerobacs == 0) {
    $conditions = "bvp.contest_id = :contest_id";
} else {
    $conditions = "bvp.contest_id = :contest_id AND g.id = :group_id";
}

$sql = "SELECT bvp.id, bvp.user_id, bvp.group_id, bvp.starttime, bvp.endtime, u.firstname, u.lastname, g.name AS groupname
          FROM {bacs_virtual_participants} bvp
    INNER JOIN {user} u ON u.id = bvp.user_id
     LEFT JOIN {groups} g ON g.id = bvp.group_id
         WHERE $conditions
      ORDER BY bvp.starttime ASC";
$params = [
    'contest_id' => $contest->bacs->id,
    'group_id' => $contest->currentgroupidorzerobacs,
];

$vprecords = $DB->get_records_sql($sql, $params);

// ...fixed possible newlines breaking JS code.
$devirtualizewarning = get_string('devirtualizewarning', 'mod_bacs');
$devirtualizewarning = str_replace("\r", "", $devirtualizewarning);
$devirtualizewarning = str_replace("\n", "", $devirtualizewarning);

$sesskey = sesskey(); 
print "<script type='text/javascript'>
    function bacs_devirtualize(coursemodule_id, user_id, group_id) {
        if (confirm('$devirtualizewarning')) {
            window.location.href =
                '/mod/bacs/devirtualize.php'
                + '?id=' + coursemodule_id
                + '&user_id=' + user_id
                + '&group_id=' + group_id
                + '&sesskey=$sesskey'; 
        }
    }
</script>";

print "<table class='cwidetable'>
    <thead>
        <tr>
            <td>" . get_string('n', 'mod_bacs') . "</td>
            <td>" . get_string('username', 'mod_bacs') . "</td>
            <td>" . get_string('groupname', 'mod_bacs') . "</td>
            <td>" . get_string('starttime', 'mod_bacs') . "</td>
            <td>" . get_string('endtime', 'mod_bacs') . "</td>
            <td>" . get_string('actions', 'mod_bacs') . "</td>
        </tr>
    </thead>
    <tbody>";

$rowindex = 0;
foreach ($vprecords as $curvp) {
    $rowindex++;

    $devirtualizeparamstr =
        $contest->coursemodule->id . ", " .
        $curvp->user_id . ", " .
        $curvp->group_id;

    print "<tr>
            <td>$rowindex</td>
            <td>$curvp->firstname $curvp->lastname</td>
            <td>$curvp->groupname</td>
            <td>" . userdate($curvp->starttime, "%d %B %Y (%A) %H:%M:%S") . "</td>
            <td>" . userdate($curvp->endtime, "%d %B %Y (%A) %H:%M:%S") . "</td>
            <td>
                <button class='btn btn-danger' onclick='bacs_devirtualize($devirtualizeparamstr)'>
                    " . get_string('devirtualize', 'mod_bacs') . "
                </button>
            </td>
        </tr>";
}

print "</tbody>";
print "</table>";

// ...empty list clarification label.
if (count($vprecords) == 0) {
    print '<p>' . get_string('virtualparticipantslistisempty', 'mod_bacs') . '</p>';
}

echo $OUTPUT->footer();
