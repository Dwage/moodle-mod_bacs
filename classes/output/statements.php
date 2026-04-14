<?php
namespace mod_bacs\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class statements implements renderable, templatable {
    public $coursemoduleidbacs;
    public $usercapabilitiesbacs;
    public $tasks = [];

    public function __construct() {}

    public function add_task($task) {
        $this->tasks[] = $task;
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->user_capability_readtasks = $this->usercapabilitiesbacs->readtasks;
        $data->coursemodule_id = $this->coursemoduleidbacs;
        $data->tasks = $this->tasks;

        return $data;
    }
}