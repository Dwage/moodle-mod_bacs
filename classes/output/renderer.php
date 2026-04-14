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


// Standard GPL and phpdocs.
namespace mod_bacs\output;


use moodle_exception;
use plugin_renderer_base;

/**
 * Class renderer
 * @package mod_bacs
 */
class renderer extends plugin_renderer_base {
    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_x($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/x', $data);
    }

    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_results($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/results', $data);
    }

    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_standings($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/standings', $data);
    }

    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_tasklist($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/tasklist', $data);
    }

    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_statements($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/statements', $data);
    }

    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_contest_header($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/contest_header', $data);
    }

    /**
     * This function
     * @param object $page
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_contest_nav_menu($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_bacs/contest_nav_menu', $data);
    }
}
