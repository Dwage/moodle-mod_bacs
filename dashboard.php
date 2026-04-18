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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locale_utils.php');
require_once("{$CFG->libdir}/formslib.php");

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$PAGE->set_title('MoodlePL plugin dashboard');
$PAGE->set_heading('MoodlePL plugin dashboard');

$PAGE->set_url(new moodle_url('/mod/bacs/dashboard.php', []));


// Filter form
class filter_form extends moodleform {
    private DateTime $default_from, $default_to;
    private $default_course_id = 0;
    private $default_task_id = 0;

    function __construct()
    {
        // Setting default filter values
        $this->default_from = new DateTime("now", core_date::get_server_timezone_object());
        $this->default_from->setTime(0, 0, 0); // From the start of today

        $this->default_to = new DateTime("now", core_date::get_server_timezone_object()); // Till now
        $ceil_seconds = 60 - (int)$this->default_to->format("s");
        $this->default_to->add(new DateInterval("PT{$ceil_seconds}S")); // Ceil to nearest minute

        parent::__construct();
    }

    // Get form data, defaults returned if there's no submitted data
    function get_data(): object {
        $form_data = parent::get_data();
        if(!$form_data) $form_data = (object)[
            'from' => $this->default_from->getTimestamp(),
            'to' => $this->default_to->getTimestamp(),
            'course_id' => $this->default_course_id,
            'task_id' => $this->default_task_id,
        ];
        return $form_data;
    }

    protected function definition()
    {
        $mform = $this->_form;

        // Date range selector filter (from/to)
        $mform->addElement('date_time_selector', 'from', get_string('fromshort', 'bacs'));
        $mform->setDefault('from', $this->default_from->getTimestamp());

        $mform->addElement('date_time_selector', 'to', get_string('toshort', 'bacs'));
        $mform->setDefault('to', $this->default_to->getTimestamp());

        // Course selector
        $courses = [0 => 'All'];
        foreach (get_available_courses() as $course) {
            $courses[$course->id] = $course->shortname;
        }
        $mform->addElement('select', 'course_id', get_string('course', 'bacs'), $courses);
        $mform->setDefault('course_id', $this->default_course_id);

        // Task selector
        $tasks = [0 => 'All'];
        foreach (get_all_tasks() as $task) {
            $localized_name = bacs_get_localized_name($task);
            $tasks[$task->task_id] = $localized_name;
        }
        $mform->addElement('select', 'task_id', get_string('task', 'bacs'), $tasks);
        $mform->setDefault('task_id', $this->default_task_id);

        // Apply filter button
        $mform->addElement('submit', 'apply_filter', get_string('applyfilter', 'bacs'));
    }
}

// Check is course available for current user
function is_course_available($course_id)
{
    $context = context_course::instance($course_id);
    return has_capability('mod/bacs:viewany', $context);
}

// Get courses filtered by availability
function get_available_courses(): array
{
    $available_courses = [];

    $courses = get_courses();
    foreach($courses as $course) {
        if (is_course_available($course->id))
            $available_courses[] = $course;
    }

    return $available_courses;
}

function get_all_tasks() {
    global $DB;
    return $DB->get_records('bacs_tasks', [], 'id, task_id, name');
}

function get_filtered_submits($form_data): array
{
    global $DB;

    // Form SQL query with filtering
    $params = [
        'from' => $form_data->from,
        'to'   => $form_data->to
    ];

    $sql_where = "(submit.submit_time BETWEEN :from AND :to)";
    
    if ($form_data->course_id != 0) {
        $sql_where .= " AND (course.id = :course_id)";
        $params['course_id'] = $form_data->course_id;
    }
    
    if ($form_data->task_id != 0) {
        $sql_where .= " AND (task.task_id = :task_id)";
        $params['task_id'] = $form_data->task_id;
    }

    $sql =
        "SELECT submit.id AS id, course.id AS course_id, submit.submit_time
         FROM {bacs_submits} submit
         JOIN {bacs} contest ON submit.contest_id = contest.id  
         JOIN {course} course ON contest.course = course.id
         JOIN {bacs_tasks} task ON submit.task_id = task.task_id
         WHERE $sql_where";

    // Filter submits by availability
    $submits = [];
    foreach ($DB->get_records_sql($sql, $params) as $submit) {
        if (is_course_available($submit->course_id))
            $submits[] = $submit;
    }

    return $submits;
}


function generate_day_hour_distrib_chart($form_data): \core\chart_bar
{
    $labels = []; // Chart labels
    $submits_per_hour = []; // Diagram values

    // Iterate over all 24 hours
    $day_period = new DatePeriod(
        new DateTime("00:00"),
        new DateInterval('PT1H'),
        new DateTime("24:00")
    );
    foreach ($day_period as $date) {
        $labels[] = $date->format("H:i"); // Hour xx:00
        $submits_per_hour[$date->format("H")] = 0; //
    }

    // Gather filtered submits from DB
    $submits = get_filtered_submits($form_data);

    foreach ($submits as $submit){
        $submits_per_hour[date("H", $submit->submit_time)]++;
    }

    $chart = new \core\chart_bar();
    $series = new \core\chart_series(get_string('submits', 'bacs'), array_values($submits_per_hour));
    $chart->set_labels($labels);
    $chart->add_series($series);

    return $chart;
}

class verdict_chart_database_manipulator{
    public static function get_visible_courses(){
        $all_courses = get_courses();
        foreach ($all_courses as $course){
            $context = context_course::instance($course->id);
            if (has_capability("mod/bacs:viewany", $context)){
                $visible_courses[] = $course;
            }
        }
        return $visible_courses;
    }
    public static function get_contests_from_the_course($course_id){
        global $DB;
        return $DB->get_records('bacs', ['course' => $course_id], '', 'id');
    }
    public static function get_tasks_from_the_contest($contest_id){
        global $DB;
        $sql = "SELECT t2.task_id, t2.name 
                  FROM {bacs_tasks_to_contests} t1 
            INNER JOIN {bacs_tasks} t2 ON t1.task_id = t2.task_id 
                 WHERE t1.contest_id = :contest_id";
        return $DB->get_records_sql($sql, ['contest_id' => $contest_id]);
    }
    public static function get_contests_from_any_courses($courses){
        $result_contests = array();
        foreach ($courses as $course){
            $contests = self::get_contests_from_the_course($course->id);
            if (count($contests)>0){
                foreach ($contests as $contest){
                    $result_contests[] = $contest;
                }
            }
        }
        return $result_contests;
    }
    public static function get_all_visible_tasks($courses){
        $result_contests = self::get_contests_from_any_courses($courses);
        if (count($result_contests)>0){
            foreach ($result_contests as $contest){
                $tasks = self::get_tasks_from_the_contest($contest->id);
                foreach ($tasks as $task){
                    $all_visible_tasks[$task->task_id] = bacs_get_localized_name($task);
                }
            }
        }
        return $all_visible_tasks;
    }
    public static function get_all_tasks() {
        global $DB;
        $all_tasks = [];
        foreach ($DB->get_records('bacs_tasks', [], 'id, task_id, name') as $task) {
            $all_tasks[$task->task_id] = bacs_get_localized_name($task);
        }

        return $all_tasks;
    }
    public static function get_task_by_task_id($task_id) {
        global $DB;
        return $DB->get_record('bacs_tasks', ['task_id' => $task_id], 'id, task_id, name');
    }
}
class verdict_chart_controller{
    public static function make_pie($test_form, $DB){
        $view_bag = new stdClass();
        $data = $test_form->get_data();
        $all_courses = verdict_chart_database_manipulator::get_visible_courses();
        $view_bag->course = $data->course_id;

        if ($data->task_id == 0) {
            $view_bag->name = get_string('alltasks', 'bacs');
        } else {
            $db_task = verdict_chart_database_manipulator::get_task_by_task_id($data->task_id);
            $view_bag->name = $db_task->name;
        }

        if ($data->course_id==0){
            $contests = verdict_chart_database_manipulator::get_contests_from_any_courses($all_courses);
        } else {
            $contests = verdict_chart_database_manipulator::get_contests_from_the_course($data->course_id);
        }

        for ($i = 0; $i<19; $i++){
            $count = 0;
            foreach ($contests as $contest) {
                $params = [
                    'from'       => $data->from,
                    'to'         => $data->to,
                    'contest_id' => $contest->id,
                    'result_id'  => $i
                ];

                $sql_where = "submit_time >= :from AND submit_time <= :to AND contest_id = :contest_id";
                
                if ($data->task_id != 0) {
                    $sql_where .= " AND task_id = :task_id";
                    $params['task_id'] = $data->task_id;
                }

                $count += $DB->get_field_sql("SELECT COUNT(*) FROM {bacs_submits} WHERE result_id = :result_id AND $sql_where", $params);
            }
            
            if ($count > 0) {
                $view_bag->counts[] = $count;
                $view_bag->label_values[] = format_verdict($i);
            }
        }

        return $view_bag;
    }
}
class verdict_chart_view{
    public static function render_data($test_form, $view_bag)
    {
        global $OUTPUT;

        $html = "";
        
        if ( !isset($view_bag->counts) || count($view_bag->counts) == 0) {
            $html .= "<div style='text-align: center;'>" . get_string('therearenoresults', 'bacs') . "</div>";
            return $html;
        }

        $html .= "<div style='text-align: center;'>$view_bag->name</div>";
        $serie = new core\chart_series("COUNT", $view_bag->counts);
        $chart = new core\chart_pie();
        $chart->set_labels($view_bag->label_values);
        $chart->add_series($serie);
        $html .= $OUTPUT->render($chart);

        return $html;
    }
}

function as_widget_caption($html) {
    return "<p style='text-align: center;'><b>$html</b></p>";
}

// Render page

// Creating filter form instance
$filter_form = new filter_form();

// Widgets
$widget_day_hour_distrib_chart = as_widget_caption(get_string('chartdayhourdistribution', 'bacs'));
$widget_day_hour_distrib_chart .= $OUTPUT->render(generate_day_hour_distrib_chart($filter_form->get_data()));

$widget_verdict_chart = as_widget_caption(get_string('chartverdicts', 'bacs'));
$view_bag = verdict_chart_controller::make_pie($filter_form, $DB);
$widget_verdict_chart .= verdict_chart_view::render_data($filter_form, $view_bag);

$widgets = [
    $widget_day_hour_distrib_chart,
    $widget_verdict_chart
];

echo $OUTPUT->header();

// Render
$filter_form->display();

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));'>";

foreach ($widgets as $widget) {
    echo "<div style=''>$widget</div>";
}

echo "</div>";

echo $OUTPUT->footer();
