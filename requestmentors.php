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
$mentees = optional_param('mentees', null, PARAM_RAW);

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

$manager = new mod_recommend_request_manager($cm, $recommend);

if ($action === null) {
	\mod_recommend\event\course_module_viewed::create_from_cm($cm, $course, $recommend)->trigger();
	$completion = new completion_info($course);
	$completion->set_module_viewed($cm);
} else if ($action === 'requestmentor' && $manager->can_add_request()) {
	$form = new mod_recommend_add_request_mentors_form(null, ['manager' => $manager]);
	if ($form->is_cancelled()) {
		redirect($viewurl);
	} else if ($data = $form->get_data()) {
		$manager->add_requests_mentor($data,$mentees);
		\core\notification::add(get_string('requestscreated', 'mod_recommend'),
				core\output\notification::NOTIFY_SUCCESS);
		redirect($viewurl);
	}
	$title = get_string('addrequest', 'mod_recommend');
	$PAGE->navbar->add($title);
}



$mentores = block_fn_mentor_get_mentees_by_mentor($courseid = $course->id);

$arr['mentores']= array_values($mentores);
foreach ($arr['mentores'] as $key=>$value){
	$arr['mentores'][$key]['mentee'] = array_values($arr['mentores'][$key]['mentee']);
}
$arr['r_id']= $cm->instance;
$arr['cm_id']= $cm->id;

$mentortable = $OUTPUT->render_from_template('recommend/list_mentees_by_mentor', $arr);
$form = new mod_recommend_add_request_mentors_form(null, ['manager' => $manager , 'mentortable' => $mentortable] );
$form->display();


echo $OUTPUT->footer();