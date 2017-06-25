<?php
// This file is part of Moodle - http://moodle.org/
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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit questions in the module
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace recommend with the name of your module and remove this line.

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$r  = optional_param('r', 0, PARAM_INT);  // Recommend instance ID.
$action = optional_param('action', null, PARAM_ALPHA);

if ($id) {
	list($course, $cm) = get_course_and_cm_from_cmid($id, 'recommend');
} else if ($r) {
	list($course, $cm) = get_course_and_cm_from_instance($r, 'recommend');
} else {
	error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
if (!has_capability('mod/recommend:editquestions', $PAGE->context)) {
	redirect($cm->url);
}
$recommend = $PAGE->activityrecord;
$viewurl = new moodle_url('/mod/recommend/requestmentors.php', ['id' => $cm->id]);
$requestid = optional_param('requestid', null, PARAM_INT);

$urlparams = ['id' => $cm->id];
if ($action) {
	$urlparams['action'] = $action;
}
if ($requestid) {
	$urlparams['requestid'] = $requestid;
}

$url = new moodle_url('/mod/recommend/requestmentors.php', ['id' => $cm->id]);
$PAGE->set_url($url);
echo $OUTPUT->header();


$mentores = block_fn_mentor_get_mentees_by_mentor($courseid = $course->id);

$arr['mentores']= array_values($mentores);
foreach ($arr['mentores'] as $key=>$value){
	$arr['mentores'][$key]['mentee'] = array_values($arr['mentores'][$key]['mentee']);
}
$arr['r_id']= $cm->instance;
$arr['cm_id']= $cm->id;


echo $OUTPUT->render_from_template('recommend/list_mentees_by_mentor', $arr);


echo $OUTPUT->footer();