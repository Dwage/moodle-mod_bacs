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

$contest->pageurlbacs = new moodle_url('/mod/bacs/devirtualize.php', ['id' => $contest->coursemodule->id]);

echo $OUTPUT->header();

$contest->register_query_param('user_id', 0, PARAM_INT);
$contest->register_query_param('group_id', 0, PARAM_INT);

$contest->aceeditorshownbacs = false;
$contest->menushownbacs = false;
$contest->groupselectorshownbacs = false;
$contest->print_contest_header();

// ...check rights.
if (!$contest->usercapabilitiesbacs->edit) {
    throw new moodle_exception('generalnopermission', 'bacs');
}

// ...delete virtual participation.
$conditions = [
    'contest_id' => $contest->bacs->id,
    'user_id' => $contest->queryparamsbacs->user_id,
    'group_id' => $contest->queryparamsbacs->group_id,
];

$vpcount = $DB->count_records('bacs_virtual_participants', $conditions);
if ($vpcount == 0) {
    die("Database record does not exist.");
}
if ($vpcount > 1) {
    die("Invalid database record count. Found $vpcount database records. This issue must be manually fixed by programmer.");
}

$DB->delete_records('bacs_virtual_participants', $conditions);

bacs_redirect_via_js('/mod/bacs/virtual_participants_list.php?id=' . $contest->coursemodule->id);
die('Devirtualization successful / Виртуальное участие успешно отменено.');

echo $OUTPUT->footer();
