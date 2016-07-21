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
 * Prints a particular instance of recommend
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace recommend with the name of your module and remove this line.

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$r  = optional_param('r', 0, PARAM_INT);  // Recommend instance ID.
$action = optional_param('action', null, PARAM_ALPHA);
$requestid = optional_param('requestid', null, PARAM_INT);

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'recommend');
} else if ($r) {
    list($course, $cm) = get_course_and_cm_from_instance($r, 'recommend');
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$recommend = $PAGE->activityrecord;

$manager = new mod_recommend_request_manager($cm, $recommend);
$viewurl = new moodle_url('/mod/recommend/view.php', ['id' => $cm->id]);

if ($action === null) {
    \mod_recommend\event\course_module_viewed::create_from_cm($cm, $course, $recommend)->trigger();
} else if ($action === 'addrequest' && $manager->can_add_request()) {
    $form = new mod_recommend_add_request_form(null, ['manager' => $manager]);
    if ($form->is_cancelled()) {
        redirect($viewurl);
    } else if ($data = $form->get_data()) {
        $manager->add_requests($data);
        // TODO add success message.
        redirect($viewurl);
    }
} else if ($action === 'deleterequest' && $requestid) {
    if ($manager->can_delete_request($requestid)) {
        require_sesskey();
        $manager->delete_request($requestid);
        \core\notification::add('Request was deleted', // TODO
                \core\output\notification::NOTIFY_SUCCESS);
    } else {
        \core\notification::add('Sorry, this request can not be deleted', // TODO
                \core\output\notification::NOTIFY_ERROR);
    }
    redirect($viewurl);
} else if ($action === 'approverequest' && $requestid &&
        $manager->can_approve_requests() && confirm_sesskey()) {
    if ($manager->accept_request($requestid)) {
        \core\notification::add('Recommendation accepted', // TODO
                \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']));
} else if ($action === 'rejectrequest' && $requestid &&
        $manager->can_approve_requests() && confirm_sesskey()) {
    if ($manager->reject_request($requestid)) {
        \core\notification::add('Recommendation rejected', // TODO
                \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect(new moodle_url($viewurl, ['requestid' => $requestid, 'action' => 'viewrequest']));
}

// Print the page header.

$urlparams = ['id' => $cm->id];
if ($action) {
    $urlparams['action'] = $action;
}
if ($requestid) {
    $urlparams['requestid'] = $requestid;
}
$PAGE->set_url('/mod/recommend/view.php', $urlparams);
$PAGE->set_title(format_string($recommend->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($recommend->name));

// Conditions to show the intro can change to look for own settings or whatever.
if ($recommend->intro && !$action) {
    echo $OUTPUT->box(format_module_intro('recommend', $recommend, $cm->id), 'generalbox mod_introbox', 'recommendintro');
}

if ($action === 'addrequest') {
    $form->display();
} else if ($action === 'viewrequest') {
    if ($manager->can_view_requests()) {
        $request = new mod_recommend_recommendation(null, $requestid);
        $request->show_request($requestid);
    }
} else {
    if ($table = $manager->get_requests_table()) {
        echo $OUTPUT->heading('Your recommendations', 3); // TODO lang string
        echo html_writer::table($table);
    }
    if ($manager->can_add_request()) {
        $addrequesturl = new moodle_url($viewurl, ['action' => 'addrequest']);
        echo html_writer::div(html_writer::link($addrequesturl,
                get_string('addrequest', 'recommend')), 'addrequest');
    }
    if ($manager->can_view_requests()) {
        $table = $manager->get_all_requests_table();
        echo $OUTPUT->heading('All requests', 3); // TODO lang string
        echo html_writer::table($table);
    }
}

// Finish the page.
echo $OUTPUT->footer();
