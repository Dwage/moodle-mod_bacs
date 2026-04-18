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

if ($ADMIN->fulltree) {

    $sybonapikey = new admin_setting_configtext(
        'mod_bacs/sybonapikey',
        get_string('sybonapikey', 'mod_bacs'),
        get_string('configsybonapikey', 'mod_bacs'),
        "NoDefaultKey",
        PARAM_TEXT,
        30
    );

    $sybonapikey->set_updatedcallback(function () {
        \mod_bacs\cron_lib::cron_langs();
        \mod_bacs\cron_lib::cron_tasks();
    });

    $settings->add(
        $sybonapikey);

    $settings->add(
        new admin_setting_configtext(
            'mod_bacs/minselectableyear',
            get_string('minselectableyear', 'mod_bacs'),
            get_string('configminselectableyear', 'mod_bacs'),
            "2014",
            PARAM_INT,
            5
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'mod_bacs/maxselectableyear',
            get_string('maxselectableyear', 'mod_bacs'),
            get_string('configmaxselectableyear', 'mod_bacs'),
            "2032",
            PARAM_INT,
            5
        )
    );

    $settings->add(
        new admin_setting_configmultiselect(
            'mod_bacs/preferedlanguages',
            get_string('preferedlanguage', 'mod_bacs'),
            get_string('configpreferedlanguage', 'mod_bacs'),
            [],
            [
                'en' => 'English',
                'ru' => 'Русский',
                'es' => 'Español',
                'C' => 'Default author language'
            ],
        )
    );

    // WebSockets
    $settings->add(new admin_setting_configtext('mod_bacs/ws_url', 
        'WebSocket URL (Public)', 'Публичный URL Node.js сервера (например, http://moodle.site:3000)', '', PARAM_URL));

    $settings->add(new admin_setting_configtext('mod_bacs/ws_internal_url', 
        'WebSocket URL (Internal API)', 'Внутренний URL для вебхуков (например, http://localhost:3000/internal/notify)', '', PARAM_URL));

    global $CFG;
    $default_ws_secret = hash('sha256', $CFG->siteidentifier . 'bacs_websocket_secret_v1');

    $settings->add(new admin_setting_configpasswordunmask('mod_bacs/ws_secret', 
        'WebSocket Secret Key', 'Секретный ключ для JWT и вебхуков. Если пусто — генерируется автоматически.', $default_ws_secret, PARAM_RAW));


    $settings->add(new admin_setting_configpasswordunmask('mod_bacs/submit_salt', 
        'Submit Hash Salt', 
        'Соль для хэширования ключей при отправке посылок (создается автоматически, если пуста)', 
        '', 
        PARAM_RAW
    ));
}
