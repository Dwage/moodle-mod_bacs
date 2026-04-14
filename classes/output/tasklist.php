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

namespace mod_bacs\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Class tasklist
 * @package mod_bacs
 */
class tasklist implements renderable, templatable {

    /**
     * @var mixed
     */
    public $showmaxpointsbacs;
    /**
     * @var mixed
     */
    public $ws_url;
    /**
     * @var mixed
     */
    public $ws_jwt;
    /**
     * @var mixed
     */
    public $cansubmitbacs;
    /**
     * @var mixed
     */
    public $cansubmitmessagebacs;
    /**
     * @var mixed
     */
    public $recentsubmitsbacs;
    /**
     * @var mixed
     */
    public $showpointsbacs;

    /**
     * @var mixed
     */
    public $coursemoduleidbacs;
    /**
     * @var mixed
     */
    public $usercapabilitiesbacs;
    /**
     * @var mixed
     */
    public $tasks = [];

    /**
     *
     */
    public function __construct() {
    }

    /**
     * This function
     * @param object $task
     * @return void
     */
    public function add_task($task) {
        $this->tasks[] = $task;
    }

    /**
     * This function
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        foreach ($this->tasks as $task) {
            $task->show_points = $this->showpointsbacs;
        }

        $data = new stdClass();

        $data->user_capability_readtasks = $this->usercapabilitiesbacs->readtasks;

        $data->tasks              = $this->tasks;
        $data->can_submit         = $this->cansubmitbacs;
        $data->can_submit_message = $this->cansubmitmessagebacs;
        $data->coursemodule_id    = $this->coursemoduleidbacs;
        $data->show_points        = $this->showpointsbacs;
        $data->show_max_points_setting = $this->showmaxpointsbacs;

        $data->ws_url = $this->ws_url;
        $data->ws_jwt = $this->ws_jwt;

        return $data;
    }
}
