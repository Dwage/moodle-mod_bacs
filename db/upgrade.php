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

// This file keeps track of upgrades to
// the bacs module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die;

/**
 * This function
 * @param int $oldversion
 * @return true
 * @throws ddl_change_structure_exception
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_bacs_upgrade($oldversion) {
    global $CFG, $DB, $dbman;

    require_once(dirname(__FILE__) . '/upgradelib.php');
    require_once(dirname(__FILE__) . '/../utils.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2018050700) {
        tov3_process_log("Started migration...");

        upgrade_set_timeout(0);

        rename_table_safe('bacs_results', 'bacs_submits');

        tov3_m2m_normalize_task_order();

        tov3_migrate_submits();

        tov3_create_tasks_to_contests();
        tov3_generate_tasks_to_contests();

        tov3_create_cron();
        tov3_generate_cron();

        tov3_migrate_bacs();

        drop_table_safe('bacs_tasks');
        tov3_create_tasks();

        tov3_create_tasks_test_expected();
        tov3_create_submits_tests();
        tov3_create_submits_tests_output();
        tov3_create_langs();

        drop_table_safe('bacs_m2m');
        drop_table_safe('bacs_contests');
        drop_table_safe('bacs_fresults');
        drop_table_safe('bacs_grades');

        tov3_process_log('Finished migration!');
        upgrade_mod_savepoint(true, 2018050700, 'bacs');
    }

    if ($oldversion < 2018121100) {
        // ...add tasks author info.
        $tasks = new xmldb_table('bacs_tasks');
        $tasksauthor = new xmldb_field('author', XMLDB_TYPE_CHAR, 255, null, null, null, null);

        if (!$dbman->field_exists($tasks, $tasksauthor)) {
            $dbman->add_field($tasks, $tasksauthor);
        }

        // ...allow upsolving everywhere.
        $contests = $DB->get_recordset('bacs');
        foreach ($contests as $curcontest) {
            $curcontest->upsolving = 1;

            $DB->update_record('bacs', $curcontest);
        }
        upgrade_mod_savepoint(true, 2018121100, 'bacs');
    }

    if ($oldversion < 2019012502) {
        // ...add standings.
        $bacs = new xmldb_table('bacs');
        $bacsstandings = new xmldb_field('standings', XMLDB_TYPE_TEXT, null, null, null, null, null);

        if (!$dbman->field_exists($bacs, $bacsstandings)) {
            $dbman->add_field($bacs, $bacsstandings);
        }

        // ...build all the standings.
        $contests = $DB->get_records('bacs', null, '', 'id');
        foreach ($contests as $curcontest) {
            rebuild_standings($curcontest->id);
        }
        upgrade_mod_savepoint(true, 2019012502, 'bacs');
    }

    if ($oldversion < 2019121600) {
        // ...add max time and max memory.
        $bacssubmits = new xmldb_table('bacs_submits');
        $bacssubmitsmaxtimeused   = new xmldb_field('max_time_used', XMLDB_TYPE_INTEGER, 10, null, null, null, null);
        $bacssubmitsmaxmemoryused = new xmldb_field('max_memory_used', XMLDB_TYPE_INTEGER, 10, null, null, null, null);

        if (!$dbman->field_exists($bacssubmits, $bacssubmitsmaxtimeused)) {
            $dbman->add_field($bacssubmits, $bacssubmitsmaxtimeused);
        }
        if (!$dbman->field_exists($bacssubmits, $bacssubmitsmaxmemoryused)) {
            $dbman->add_field($bacssubmits, $bacssubmitsmaxmemoryused);
        }
        upgrade_mod_savepoint(true, 2019121600, 'bacs');
    }

    if ($oldversion < 2020120400) {
        try {
            $DB->delete_records('bacs_tasks_collections');
            $DB->delete_records('bacs_tasks_to_collections');
        } catch (Exception $e) {
            echo 'You haven\'t this DB';
        }

        // ...add tasks collections.
        create_table_safe(
            'bacs_tasks_collections',
            [
                ['id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null],
                ['name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null],
                ['description', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null],
                ['collection_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null],
            ],
            [
                ['primary', XMLDB_KEY_PRIMARY, ['id']],
            ]
        );

        // ...add tasks to collections.
        create_table_safe(
            'bacs_tasks_to_collections',
            [
                ['id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null],
                ['task_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
                ['collection_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
            ],
            [
                ['primary', XMLDB_KEY_PRIMARY, ['id']],
            ]
        );
        upgrade_mod_savepoint(true, 2020120400, 'bacs');
    }

    if ($oldversion < 2021021901) {
        // ...add bacs mode and presolving.
        $bacs = new xmldb_table('bacs');
        $bacsmode = new xmldb_field('mode', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $bacspresolving = new xmldb_field('presolving', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($bacs, $bacsmode)) {
            $dbman->add_field($bacs, $bacsmode);
        }
        if (!$dbman->field_exists($bacs, $bacspresolving)) {
            $dbman->add_field($bacs, $bacspresolving);
        }

        // ...change test_points to new format.
        $taskstocontests = $DB->get_recordset('bacs_tasks_to_contests');
        foreach ($taskstocontests as $curtasktocontest) {
            if (is_null($curtasktocontest->test_points)) {
                continue;
            }

            $curtasktocontest->test_points = '0,' . $curtasktocontest->test_points;

            $DB->update_record('bacs_tasks_to_contests', $curtasktocontest);
        }
        upgrade_mod_savepoint(true, 2021021901, 'bacs');
    }

    if ($oldversion < 2021071502) {
        // ...add group standings.
        create_table_safe(
            'bacs_group_standings',
            [
                ['id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null],
                ['contest_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
                ['group_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
                ['standings', XMLDB_TYPE_TEXT, null, null, null, null, null],
            ],
            [
                ['primary', XMLDB_KEY_PRIMARY, ['id']],
                ['unique_group_id', XMLDB_KEY_UNIQUE, ['contest_id', 'group_id']],
            ]
        );

        // ...add group_id to submits.
        $bacssubmits = new xmldb_table('bacs_submits');
        $bacsgroupid = new xmldb_field('group_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($bacssubmits, $bacsgroupid)) {
            $dbman->add_field($bacssubmits, $bacsgroupid);
        }
        upgrade_mod_savepoint(true, 2021071502, 'bacs');
    }

    if ($oldversion < 2021081302) {
        // ...rename.
        rename_table_safe('bacs_group_standings', 'bacs_group_info');

        // ...add starttime, endtime, upsolving, presolving.
        $bacsgroupinfo = new xmldb_table('bacs_group_info');
        $bacsgroupinfostarttime = new xmldb_field('starttime', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $bacsgroupinfoendtime = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $bacsgroupinfoupsolving = new xmldb_field('upsolving', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0);
        $bacsgroupinfopresolving = new xmldb_field('presolving', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0);
        $bacsgroupinfousegroupsettings = new xmldb_field(
            'use_group_settings',
            XMLDB_TYPE_INTEGER,
            1,
            null,
            XMLDB_NOTNULL,
            null,
            0
        );

        if (!$dbman->field_exists($bacsgroupinfo, $bacsgroupinfostarttime)) {
            $dbman->add_field($bacsgroupinfo, $bacsgroupinfostarttime);
        }
        if (!$dbman->field_exists($bacsgroupinfo, $bacsgroupinfoendtime)) {
            $dbman->add_field($bacsgroupinfo, $bacsgroupinfoendtime);
        }
        if (!$dbman->field_exists($bacsgroupinfo, $bacsgroupinfoupsolving)) {
            $dbman->add_field($bacsgroupinfo, $bacsgroupinfoupsolving);
        }
        if (!$dbman->field_exists($bacsgroupinfo, $bacsgroupinfopresolving)) {
            $dbman->add_field($bacsgroupinfo, $bacsgroupinfopresolving);
        }
        if (!$dbman->field_exists($bacsgroupinfo, $bacsgroupinfousegroupsettings)) {
            $dbman->add_field($bacsgroupinfo, $bacsgroupinfousegroupsettings);
        }
        upgrade_mod_savepoint(true, 2021081302, 'bacs');
    }

    if ($oldversion < 2022010600) {
        // ...add tasks statement format info.
        $tasks = new xmldb_table('bacs_tasks');
        $tasksstatementformat = new xmldb_field('statement_format', XMLDB_TYPE_CHAR, 255, null, null, null, null);

        if (!$dbman->field_exists($tasks, $tasksstatementformat)) {
            $dbman->add_field($tasks, $tasksstatementformat);
        }
        upgrade_mod_savepoint(true, 2022010600, 'bacs');
    }

    if ($oldversion < 2022041900) {
        // ...drop bacs_cron table.
        drop_table_safe('bacs_cron');

        // ...add sync_submit_id field to bacs_submits.
        $bacssubmits = new xmldb_table('bacs_submits');
        $bacssubmitssyncsubmitid = new xmldb_field('sync_submit_id', XMLDB_TYPE_INTEGER, 10, null, null, null, 0);

        if (!$dbman->field_exists($bacssubmits, $bacssubmitssyncsubmitid)) {
            $dbman->add_field($bacssubmits, $bacssubmitssyncsubmitid);
        }

        // ...fix submits that are awaiting results to be sent again.
        $sql = "UPDATE {bacs_submits}
                   SET result_id = :verdict_pending
                 WHERE result_id = :verdict_running";
        $params = ['verdict_pending' => 1, 'verdict_running' => 2];

        $DB->execute($sql, $params);
        upgrade_mod_savepoint(true, 2022041900, 'bacs');
    }

    if ($oldversion < 2022070200) {
        // ...add bacs virtual.
        $bacs = new xmldb_table('bacs');
        $bacsvirtual = new xmldb_field('virtual_mode', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($bacs, $bacsvirtual)) {
            $dbman->add_field($bacs, $bacsvirtual);
        }

        // ...add bacs_virtual_participants table.
        create_table_safe(
            'bacs_virtual_participants',
            [
                ['id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null],
                ['user_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
                ['contest_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
                ['group_id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
                ['starttime', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0],
            ],
            [
                ['primary', XMLDB_KEY_PRIMARY, ['id']],
                ['unique_virtual_participant', XMLDB_KEY_UNIQUE, ['user_id', 'contest_id', 'group_id']],
            ]
        );
        upgrade_mod_savepoint(true, 2022070200, 'bacs');
    }

    if ($oldversion < 2022072100) {
        $bacsvp = new xmldb_table('bacs_virtual_participants');
        $bacsvpendtime = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($bacsvp, $bacsvpendtime)) {
            $dbman->add_field($bacsvp, $bacsvpendtime);
        }
        upgrade_mod_savepoint(true, 2022072100, 'bacs');
    }

    if ($oldversion < 2022080300) {
        $bacs = new xmldb_table('bacs');
        $bacsisolateparticipants = new xmldb_field('isolate_participants', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($bacs, $bacsisolateparticipants)) {
            $dbman->add_field($bacs, $bacsisolateparticipants);
        }
        upgrade_mod_savepoint(true, 2022080300, 'bacs');
    }

    if ($oldversion < 2022090602) {
        $bacsvp = new xmldb_table('bacs_virtual_participants');
        $bacsvpendtime = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);

        $dbman->change_field_precision($bacsvp, $bacsvpendtime);
        upgrade_mod_savepoint(true, 2022090602, 'bacs');
    }

    if ($oldversion < 2024041200) {
        // Define index course (not unique) to be added to bacs.
        $table = new xmldb_table('bacs');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, ['course']);

        // Conditionally launch add index course.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index task_id (not unique) to be added to bacs_tasks.
        $table = new xmldb_table('bacs_tasks');
        $index = new xmldb_index('task_id', XMLDB_INDEX_NOTUNIQUE, ['task_id']);

        // Conditionally launch add index task_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index contest_id (not unique) to be added to bacs_tasks_to_contests.
        $table = new xmldb_table('bacs_tasks_to_contests');
        $index = new xmldb_index('contest_id', XMLDB_INDEX_NOTUNIQUE, ['contest_id']);

        // Conditionally launch add index contest_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index task_id (not unique) to be added to bacs_tasks_test_expected.
        $table = new xmldb_table('bacs_tasks_test_expected');
        $index = new xmldb_index('task_id', XMLDB_INDEX_NOTUNIQUE, ['task_id']);

        // Conditionally launch add index task_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index user_id (not unique) to be added to bacs_submits.
        $table = new xmldb_table('bacs_submits');
        $index = new xmldb_index('user_id', XMLDB_INDEX_NOTUNIQUE, ['user_id']);

        // Conditionally launch add index user_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index submit_time (not unique) to be added to bacs_submits.
        $table = new xmldb_table('bacs_submits');
        $index = new xmldb_index('submit_time', XMLDB_INDEX_NOTUNIQUE, ['submit_time']);

        // Conditionally launch add index submit_time.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index task_id (not unique) to be added to bacs_submits.
        $table = new xmldb_table('bacs_submits');
        $index = new xmldb_index('task_id', XMLDB_INDEX_NOTUNIQUE, ['task_id']);

        // Conditionally launch add index task_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index group_id (not unique) to be added to bacs_submits.
        $table = new xmldb_table('bacs_submits');
        $index = new xmldb_index('group_id', XMLDB_INDEX_NOTUNIQUE, ['group_id']);

        // Conditionally launch add index group_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index contest-group-user (not unique) to be added to bacs_submits.
        $table = new xmldb_table('bacs_submits');
        $index = new xmldb_index('contest-group-user', XMLDB_INDEX_NOTUNIQUE, ['contest_id', 'group_id', 'user_id']);

        // Conditionally launch add index contest-group-user.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index contest-user (not unique) to be added to bacs_submits.
        $table = new xmldb_table('bacs_submits');
        $index = new xmldb_index('contest-user', XMLDB_INDEX_NOTUNIQUE, ['contest_id', 'user_id']);

        // Conditionally launch add index contest-user.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index submit_id (not unique) to be added to bacs_submits_tests.
        $table = new xmldb_table('bacs_submits_tests');
        $index = new xmldb_index('submit_id', XMLDB_INDEX_NOTUNIQUE, ['submit_id']);

        // Conditionally launch add index submit_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index test_id (not unique) to be added to bacs_submits_tests.
        $table = new xmldb_table('bacs_submits_tests');
        $index = new xmldb_index('test_id', XMLDB_INDEX_NOTUNIQUE, ['test_id']);

        // Conditionally launch add index test_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index status_id (not unique) to be added to bacs_submits_tests.
        $table = new xmldb_table('bacs_submits_tests');
        $index = new xmldb_index('status_id', XMLDB_INDEX_NOTUNIQUE, ['status_id']);

        // Conditionally launch add index status_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index submit_id (not unique) to be added to bacs_submits_tests_output.
        $table = new xmldb_table('bacs_submits_tests_output');
        $index = new xmldb_index('submit_id', XMLDB_INDEX_NOTUNIQUE, ['submit_id']);

        // Conditionally launch add index submit_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index test_id (not unique) to be added to bacs_submits_tests_output.
        $table = new xmldb_table('bacs_submits_tests_output');
        $index = new xmldb_index('test_id', XMLDB_INDEX_NOTUNIQUE, ['test_id']);

        // Conditionally launch add index test_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index lang_id (not unique) to be added to bacs_langs.
        $table = new xmldb_table('bacs_langs');
        $index = new xmldb_index('lang_id', XMLDB_INDEX_NOTUNIQUE, ['lang_id']);

        // Conditionally launch add index lang_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index contest_id (not unique) to be added to bacs_group_info.
        $table = new xmldb_table('bacs_group_info');
        $index = new xmldb_index('contest_id', XMLDB_INDEX_NOTUNIQUE, ['contest_id']);

        // Conditionally launch add index contest_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index mdl_bacsvirtpart_usecongro_uix (unique) to be added to bacs_virtual_participants.
        $table = new xmldb_table('bacs_virtual_participants');
        $index = new xmldb_index('mdl_bacsvirtpart_usecongro_uix', XMLDB_INDEX_UNIQUE, ['user_id', 'contest_id', 'group_id']);

        // Conditionally launch add index mdl_bacsvirtpart_usecongro_uix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024041200, 'bacs');
    }

    if ($oldversion < 2024052700) {
        // Changing the default of field use_group_settings on table bacs_group_info to 0.
        // Changing the default of field starttime on table bacs_group_info to 0.
        // Changing the default of field endtime on table bacs_group_info to 0.
        // Changing the default of field upsolving on table bacs_group_info to 0.
        // Changing the default of field presolving on table bacs_group_info to 0.
        $table = new xmldb_table('bacs_group_info');
        $field1 = new xmldb_field('use_group_settings', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'group_id');
        $field2 = new xmldb_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'use_group_settings');
        $field3 = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'starttime');
        $field4 = new xmldb_field('upsolving', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'endtime');
        $field5 = new xmldb_field('presolving', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'upsolving');

        $dbman->change_field_default($table, $field1);
        $dbman->change_field_default($table, $field2);
        $dbman->change_field_default($table, $field3);
        $dbman->change_field_default($table, $field4);
        $dbman->change_field_default($table, $field5);

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024052700, 'bacs');
    }

    if ($oldversion < 2024052800) {
        // Changing the default of field starttime on table bacs to 0.
        // Changing the default of field endtime on table bacs to 0.
        // Changing the default of field upsolving on table bacs to 1.
        // Changing the default of field mode on table bacs to 0.
        // Changing the default of field presolving on table bacs to 0.
        // Changing the default of field virtual_mode on table bacs to 0.
        $table = new xmldb_table('bacs');
        $field1 = new xmldb_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'name');
        $field2 = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'starttime');
        $field3 = new xmldb_field('upsolving', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'endtime');
        $field4 = new xmldb_field('mode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'standings');
        $field5 = new xmldb_field('presolving', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'mode');
        $field6 = new xmldb_field('virtual_mode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'presolving');
        $field7 = new xmldb_field('isolate_participants', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'virtual_mode');

        $dbman->change_field_default($table, $field1);
        $dbman->change_field_default($table, $field2);
        $dbman->change_field_default($table, $field3);
        $dbman->change_field_default($table, $field4);
        $dbman->change_field_default($table, $field5);
        $dbman->change_field_default($table, $field6);
        $dbman->change_field_default($table, $field7);

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024052800, 'bacs');
    }

    if ($oldversion < 2024052900) {
        // Changing the default of field time_limit_millis on table bacs_tasks to 0.
        // Changing the default of field memory_limit_bytes on table bacs_tasks to 0.
        $table = new xmldb_table('bacs_tasks');
        $field1 = new xmldb_field(
            'time_limit_millis',
            XMLDB_TYPE_INTEGER,
            '7',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'name'
        );
        $field2 = new xmldb_field(
            'memory_limit_bytes',
            XMLDB_TYPE_INTEGER,
            '7',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'time_limit_millis'
        );

        $dbman->change_field_default($table, $field1);
        $dbman->change_field_default($table, $field2);

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024052900, 'bacs');
    }

    if ($oldversion < 2024053000) {
        // Changing the default of field time_limit_millis on table bacs_langs to 0.
        // Changing the default of field memory_limit_bytes on table bacs_langs to 0.
        // Changing the default of field number_of_processes on table bacs_langs to 0.
        // Changing the default of field output_limit_bytes on table bacs_langs to 0.
        // Changing the default of field real_time_limit_mills on table bacs_langs to 0.
        $table = new xmldb_table('bacs_langs');
        $field1 = new xmldb_field(
            'time_limit_millis',
            XMLDB_TYPE_INTEGER,
            '7',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'description'
        );
        $field2 = new xmldb_field(
            'memory_limit_bytes',
            XMLDB_TYPE_INTEGER,
            '7',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'time_limit_millis'
        );
        $field3 = new xmldb_field(
            'number_of_processes',
            XMLDB_TYPE_INTEGER,
            '3',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'memory_limit_bytes'
        );
        $field4 = new xmldb_field(
            'output_limit_bytes',
            XMLDB_TYPE_INTEGER,
            '7',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'number_of_processes'
        );
        $field5 = new xmldb_field(
            'real_time_limit_mills',
            XMLDB_TYPE_INTEGER,
            '7',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'output_limit_bytes'
        );

        $dbman->change_field_default($table, $field1);
        $dbman->change_field_default($table, $field2);
        $dbman->change_field_default($table, $field3);
        $dbman->change_field_default($table, $field4);
        $dbman->change_field_default($table, $field5);

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024053000, 'bacs');
    }

    if ($oldversion < 2024062100) {
        // Changing the default of field starttime on table bacs_virtual_participants to 0.
        // Changing the default of field endtime on table bacs_virtual_participants to 0.
        $table = new xmldb_table('bacs_virtual_participants');
        $field1 = new xmldb_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'group_id');
        $field2 = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'starttime');

        $dbman->change_field_default($table, $field1);
        $dbman->change_field_default($table, $field2);

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024062100, 'bacs');
    }

    if ($oldversion < 2024090300) {
        // Changing type of field test_points on table bacs_tasks to text.
        $table = new xmldb_table('bacs_tasks');
        $field = new xmldb_field('test_points', XMLDB_TYPE_TEXT, null, null, null, null, null, 'count_pretests');
        // Launch change of type for field test_points.
        $dbman->change_field_type($table, $field);
        // Changing type of field test_points on table bacs_tasks_to_contests to text.
        $table = new xmldb_table('bacs_tasks_to_contests');
        $field = new xmldb_field('test_points', XMLDB_TYPE_TEXT, null, null, null, null, null, 'task_order');
        // Launch change of type for field test_points.
        $dbman->change_field_type($table, $field);
        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2024090300, 'bacs');
    }

    if ($oldversion < 2025011401) {

        // Define table bacs_incidents to be created.
        $table = new xmldb_table('bacs_incidents');

        // Adding fields to table bacs_incidents.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contest_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('method', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('info', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table bacs_incidents.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table bacs_incidents.
        $table->add_index('contest-method', XMLDB_INDEX_NOTUNIQUE, ['contest_id', 'method']);

        // Conditionally launch create table for bacs_incidents.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011401, 'bacs');
    }

    if ($oldversion < 2025011402) {

        // Define table bacs_incidents_to_submits to be created.
        $table = new xmldb_table('bacs_incidents_to_submits');

        // Adding fields to table bacs_incidents_to_submits.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('incident_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('submit_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table bacs_incidents_to_submits.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table bacs_incidents_to_submits.
        $table->add_index('unique-incident-submit', XMLDB_INDEX_UNIQUE, ['incident_id', 'submit_id']);

        // Conditionally launch create table for bacs_incidents_to_submits.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011402, 'bacs');
    }

    if ($oldversion < 2025011403) {

        // Define table bacs_submits_fingerprints to be created.
        $table = new xmldb_table('bacs_submits_fingerprints');

        // Adding fields to table bacs_submits_fingerprints.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('submit_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('contest_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('tokenseq', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('tokencounts', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('satokencounts', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('tokenset', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table bacs_submits_fingerprints.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('submit_id', XMLDB_KEY_FOREIGN_UNIQUE, ['submit_id'], 'bacs_submits', ['id']);

        // Conditionally launch create table for bacs_submits_fingerprints.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011403, 'bacs');
    }

    if ($oldversion < 2025011404) {

        // Define index contest_id (not unique) to be added to bacs_submits_fingerprints.
        $table = new xmldb_table('bacs_submits_fingerprints');
        $index = new xmldb_index('contest_id', XMLDB_INDEX_NOTUNIQUE, ['contest_id']);

        // Conditionally launch add index contest_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011404, 'bacs');
    }

    if ($oldversion < 2025011500) {

        // Define field status to be added to bacs_submits_fingerprints.
        $table = new xmldb_table('bacs_submits_fingerprints');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'contest_id');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011500, 'bacs');
    }

    if ($oldversion < 2025011501) {

        // Define index status (not unique) to be added to bacs_submits_fingerprints.
        $table = new xmldb_table('bacs_submits_fingerprints');
        $index = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        // Conditionally launch add index status.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011501, 'bacs');
    }

    if ($oldversion < 2025011502) {
        // Define field detect_incidents to be added to bacs.
        $table = new xmldb_table('bacs');
        $field = new xmldb_field('detect_incidents', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isolate_participants');

        // Conditionally launch add field detect_incidents.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011502, 'bacs');
    }

    if ($oldversion < 2025011503) {
        // Define field incidents_settings to be added to bacs.
        $table = new xmldb_table('bacs');
        $field = new xmldb_field('incidents_settings', XMLDB_TYPE_TEXT, null, null, null, null, null, 'detect_incidents');

        // Conditionally launch add field incidents_settings.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011503, 'bacs');
    }

    if ($oldversion < 2025011601) {
        // Define field satokenseq to be added to bacs_submits_fingerprints.
        $table = new xmldb_table('bacs_submits_fingerprints');
        $field = new xmldb_field('satokenseq', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tokenseq');

        // Conditionally launch add field satokenseq.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025011601, 'bacs');
    }

    if ($oldversion < 2025012800) {
        // Define field incidents_info to be added to bacs.
        $table = new xmldb_table('bacs');
        $field = new xmldb_field('incidents_info', XMLDB_TYPE_TEXT, null, null, null, null, null, 'incidents_settings');

        // Conditionally launch add field incidents_info.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025012800, 'bacs');
    }

    if ($oldversion < 2025060900) {
        // Define field statements_urls to be added to bacs_tasks.
        $table = new xmldb_table('bacs_tasks');
        $field = new xmldb_field('statement_urls', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Conditionally launch add field incidents_info.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025061500, modname: 'bacs');
    }

    if ($oldversion < 2025073000) {
        // Define field names to be added to bacs_tasks.
        $table = new xmldb_table('bacs_tasks');
        $field = new xmldb_field('names', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Conditionally launch add field incidents_info.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025073000, 'bacs');
    }

    if ($oldversion < 2025073100) {
        // Define field show_max_points to be added to bacs.
        $table = new xmldb_table('bacs');
        $field = new xmldb_field('show_max_points', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'incidents_info');

        // Conditionally launch add field show_max_points.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bacs savepoint reached.
        upgrade_mod_savepoint(true, 2025073100, 'bacs');
    }

    return true;
}
