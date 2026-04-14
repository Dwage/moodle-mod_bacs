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
 * Class standings
 * @package mod_bacs
 */
class standings implements renderable, templatable {
    /**
     * @var mixed
     */
    public $submitsjsonbacs;
    /**
     * @var mixed
     */
    public $studentsjsonbacs;
    /**
     * @var mixed
     */
    public $tasksjsonbacs;
    /**
     * @var mixed
     */
    public $tasks_for_template;
    /**
     * @var mixed
     */
    public $incidentsjsonbacs;
    /**
     * @var mixed
     */
    public $localizedstringsjsonbacs;

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
    public $starttime;
    /**
     * @var mixed
     */
    public $endtime;
    /**
     * @var mixed
     */
    public $mode;

    /**
     *
     */
    public function __construct() {
    }

    /**
     * This function
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        if (is_null($this->submitsjsonbacs) || $this->submitsjsonbacs == '') {
            $this->submitsjsonbacs = '[]';
        }
        if (is_null($this->studentsjsonbacs) || $this->studentsjsonbacs == '') {
            $this->studentsjsonbacs = '[]';
        }
        if (is_null($this->tasksjsonbacs) || $this->tasksjsonbacs == '') {
            $this->tasksjsonbacs = '[]';
        }
        if (is_null($this->incidentsjsonbacs) || $this->incidentsjsonbacs == '') {
            $this->incidentsjsonbacs = '[]';
        }
        if (is_null($this->localizedstringsjsonbacs) || $this->localizedstringsjsonbacs == '') {
            $this->localizedstringsjsonbacs = '[]';
        }

        $data = new stdClass();

        $data->submits_json           = $this->submitsjsonbacs;
        $data->students_json          = $this->studentsjsonbacs;
        $data->tasks_json             = $this->tasksjsonbacs;
        $data->tasks_for_template     = $this->tasks_for_template;
        $data->incidents_json         = $this->incidentsjsonbacs;
        $data->localized_strings_json = $this->localizedstringsjsonbacs;

        $data->starttime       = $this->starttime;
        $data->endtime         = $this->endtime;
        $data->coursemodule_id = $this->coursemoduleidbacs;
        $data->moodle_user_id  = $USER->id;
        $data->mode            = $this->mode;

        $data->mode_ioi_selected     = ($this->mode == 0);
        $data->mode_icpc_selected    = ($this->mode == 1);
        $data->mode_general_selected = ($this->mode == 2);

        $data->user_capability_viewany = $this->usercapabilitiesbacs->viewany;

        $now = time();
        $data->contest_is_over = ($this->endtime <= $now);

        return $data;
    }
}
