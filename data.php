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
 * Data export page for calexport
 * Will use options sent to get calendar data
 * Outputs to iCal format
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/calexport_lib.php');


global $PAGE, $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->libdir . '/bennu/bennu.inc.php');

$courseid = optional_param('course', 0, PARAM_INT);
//Work out if personal etc events were deselected
$allevents = optional_param('allevents', true, PARAM_BOOL);

if ($courseid != 0) {
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
} else {
    $context = get_context_instance(CONTEXT_SYSTEM);
}
$PAGE->set_context($context);


//Do not need to login to use this screen - instead verify parameters sent
$username = required_param('u', PARAM_TEXT);
$authtoken = required_param('token', PARAM_ALPHANUM);

//Fetch user information
if (!$user = get_complete_user_data('username', $username)) {
    //No such user
    die('Invalid authentication');
}

//Check authentication token
if ($authtoken != calexport_lib::generate_token($username, $user->email)) {
    die('Invalid authentication');
}

$courses = array();
$groups = false;
$shortname = null;
$userid = $user->id;
$eventuserid = $user->id;

//If a course id check user is on course and then get group info
if ($courseid) {
    $mycourses = enrol_get_users_courses($user->id, true, 'id', 'shortname');

    foreach ($mycourses as $course) {
        if ($course->id == $courseid) {
            $courses[$course->id] = $course->shortname;
            $shortname = $course->shortname;
            $groups = array_keys(groups_get_all_groups($course->id, $user->id));
            break;
        }
    }

    //if no matching course found set courseid to 0
    if (empty($courses)) {
        $courseid = 0;
    }
}

if ($allevents == true) {
    //setup global events
    $courses[SITEID] = new stdClass;
    $courses[SITEID]->shortname = get_string('globalevents', 'calendar');
} else {
    $eventuserid = array();
}

//Events in the last 10 or next 60 days
$timestart = time() - 864000;
$timeend = time() + 5184000;

$events = calendar_get_events($timestart, $timeend, $eventuserid, $groups, array_keys($courses), false);

$ical = new iCalendar;
$ical->add_property('method', 'PUBLISH');
//Set calendar name
if ($courseid) {
    $ical->add_property('X-WR-CALNAME', $shortname);
} else {
    $ical->add_property('X-WR-CALNAME', get_string('icalname', 'local_calexport'));
}

$hostaddress = calexport_lib::return_host_address();

foreach ($events as $event) {
    if (!empty($event->modulename)) {
        $cm = get_coursemodule_from_instance($event->modulename, $event->instance);
        if (!groups_course_module_visible($cm, $user->id)) {
            continue;
        }
    }

    $ical->add_component(calexport_lib::create_ical_event($event, $hostaddress, $shortname));
}

//Add stuff to the iCal object in 'plugins'
foreach (calexport_lib::return_plugin_classnames() as $plugin) {
    if (method_exists($plugin, 'add_to_ical')) {
        $plugin::add_to_ical($ical, $timestart, $timeend, $userid, $groups, $courseid, $shortname);
    }
}

$serialized = $ical->serialize();
if (empty($serialized)) {
    die('bad serialization');
}

//IE compatibility HACK!
if (ini_get_bool('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

$filename = 'icalexport.ics';

header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Expires: '. gmdate('D, d M Y H:i:s', 0) .'GMT');
header('Pragma: no-cache');
header('Accept-Ranges: none'); // Comment out if PDFs do not work...
header('Content-disposition: attachment; filename='.$filename);
header('Content-length: '.strlen($serialized));
header('Content-type: text/calendar');

echo $serialized;
