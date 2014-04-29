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
 * Selection screen for alternate calendar export
 *
 * The selectable options on this page are sent through to the data export page
 * They should also be picked up on this page as well for non-javascript users
 *
 * This file just handles page setup - main functionality is in lib
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/calexport_lib.php');

global $PAGE, $OUTPUT;

$courseid = optional_param('course', 0, PARAM_INT);

$url = new moodle_url('/local/calexport/index.php');
if ($courseid !== 0) {
    $url->param('course', $courseid);
}
$PAGE->set_url($url);

//Need to login to use this screen
if ($courseid && $courseid != SITEID) {
    require_login($courseid);
} else {
    $PAGE->set_context(context_system::instance());
    require_login();
}

$PAGE->navbar->add(get_string('title', 'local_calexport'));

// Print title and header
$PAGE->set_title(get_string('title', 'local_calexport'));
$PAGE->set_heading($COURSE->fullname);

$PAGE->set_pagelayout('standard');
$PAGE->set_focuscontrol('pw_all');
calexport_lib::add_external_libs();

echo $OUTPUT->header();

//Main display etc is all handled in lib so can be used elsewhere

echo calexport_lib::init_selection_screen($courseid);

echo $OUTPUT->footer();
