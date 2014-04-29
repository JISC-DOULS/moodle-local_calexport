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
 * 'Plugin' classes for the calexport local plugin.
 *
 * Should be named calexport_plugin_* and extend calexport_plugin class
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->libdir . '/bennu/bennu.inc.php');

/*
// Example. Any number of plugins may be defined by extending calexport_plugin.
class calexport_plugin_example extends calexport_plugin {
    public static function get_options($courseid) {
        return '<input type="hidden" name="fake" value="1"/>';//should use html writer
    }
    public static function return_query_params($courseid) {
        return array('fake' => 1);//for user opt would need to be dynamic to match current value
    }
    public static function add_to_ical(iCalendar &$ical, $timestart, $timeend, $userid, $groups,
            $courseid, $shortname) {
        $ev = new iCalendar_event();
        $ev->add_property('uid', '0123456');
        $ev->add_property('summary', 'I made this');
        $ev->add_property('description', 'In a plugin');
        $ev->add_property('class', 'PUBLIC'); // PUBLIC / PRIVATE / CONFIDENTIAL
        $ev->add_property('dtstamp', Bennu::timestamp_to_datetime()); // now
        $ev->add_property('dtstart', Bennu::timestamp_to_datetime()); // now
        $ical->add_component($ev);
    }
}
*/

/**
 * This plugin allows the user to select whether to export activities for the website
 * and if selected it adds the appropriate events to the ical export.
 */
class calexport_plugin_activities extends calexport_plugin {

    /**
     * Creates an element for use in the display form that allows the user to select this plugin.
     * @param int $courseid Id of course, 0 if none
     * @return string html element
     */
    public static function get_options($courseid) {
        global $OUTPUT;
        if (!self::check_basics($courseid)) {
            return '';
        }
        $activities = optional_param('activities', false, PARAM_BOOL);
        $params = array('type'=>'checkbox', 'name'=>'activities', 'id'=>'pw_activities');
        if ($activities) {
            $params['checked'] = 'checked';
        }
        $element = html_writer::start_tag('div', array('class'=>'inlinefield'));
        $element .= html_writer::empty_tag('input', $params);
        $element .= html_writer::tag('label', get_string('eventsactivities', 'local_calexport')
            . $OUTPUT->help_icon('eventsactivities', 'local_calexport'),
            array('for'=>'pw_activities'));
        $element .= html_writer::end_tag('div');
        return $element;
    }

    /**
     * Sets the parameter to be added to the feed URL.
     * @param int $courseid Id of course, 0 if none
     * @return array containing the parameter
     */
    public static function return_query_params($courseid) {
        $return = array();
        if (!self::check_basics($courseid)) {
            return $return;
        }
        // Note the default state has to be set to false otherwise
        // when javascript is disabled the parameter will never be passed through correctly
        $activities = optional_param('activities', false, PARAM_BOOL);
        if ($activities) {
            $return = array('activities'=>'true');
        }
        return $return;
    }

    /**
     * Called automatically to add website activities as events to the icalendar object.
     * @param iCalendar $ical to be updated by this plugin
     * @param int $timestart the earliest date an activity may start (for inclusion in export)
     * @param int $timeend the latest time an activity may end (for inclusion)
     * @param int $userid user id
     * @param array $groups array false if none
     * @param int $courseid Id of course, 0 if none
     * @param string $shortname course shortname
     * @uses $DB
     * @uses $CFG
     * @return none
     */
    public static function add_to_ical(iCalendar &$ical, $timestart, $timeend, $userid, $groups,
            $courseid, $shortname) {
        global $DB, $CFG;

        if (!self::check_basics($courseid)) {
            return;
        }
        $activities = optional_param('activities', false, PARAM_BOOL);
        if (!$activities) {
            return;
        }
        if (empty($userid)) {
            return;
        }
        $course=$DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        if (!is_enrolled($context, $userid)) {
            return;
        }

        $modinfo = get_fast_modinfo($course);
        $courseformatoptions = course_get_format($course)->get_format_options();
        $numsections = $courseformatoptions['numsections'];
        $sections = $modinfo->get_section_info_all();
        if ($numsections < 1) {
            return;
        }
        $weeksettings = studyplan_get_weeksettings($course->id);
        $studyplan = studyplan_get_settings($course->id);
        $weekdate = $studyplan->startdateoffset + $course->startdate; // should be Monday of week
        $weekdate += 7200; // Add two hours to avoid possible DST problems
        $day = 60*60*24;
        $week = $day*7;

        // Event section dates - events are associated with a section or week in a course
        // and we need to get the start and finish dates for each group of events.
        // (See course/format/studyplan/renderer.php process_section_data())
        $eventsections = array();
        foreach ($sections as $section) {
            if ($section->section == 0) {
                $weekdate = $weekdate - $week;
                // Ignore section 0 events as these do not have a date
                continue;
            }
            $weekdate = $weekdate + $week;
            if (empty($weeksettings[$section->id]->groupwithsectionid)) {
                $eventsections[$section->section] = new StdClass;
                $eventsections[$section->section]->sequence = $section->sequence;
                // Grouped weeks will be dealt with below
                $index = $section->section + 1;
                while (
                        !empty($sections[$index]) &&
                        !empty($weeksettings[$sections[$index]->id]->groupwithsectionid)
                        && $weeksettings[$sections[$index]->id]->groupwithsectionid ==
                        $section->id
                ) {
                    if ($sections[$index]->sequence) {
                        $eventsections[$section->section]->sequence .= ',' .
                                $sections[$index]->sequence;
                        $eventsections[$section->section]->sequence =
                                str_replace(',,', ',', $eventsections[$section->section]->sequence);
                    }
                    $index++;
                }
                $numweeks = $index - $section->section;
                $enddate = $weekdate + ($numweeks * 604800) - $day;
                $eventsections[$section->section]->startdate = $weekdate;
                $eventsections[$section->section]->enddate = $enddate;
            }
            if (empty($eventsections[$section->section]->sequence)) {
                unset($eventsections[$section->section]);
            }
        }

        // Filter out unwanted events then add to the ical.
        foreach ($eventsections as $es) {
            $cmids = explode(',', $es->sequence);
            foreach ($cmids as $cmid) {
                $cm = 0;
                if (array_key_exists($cmid, $modinfo->cms)) {
                    $cm = $modinfo->cms[$cmid];
                }
                if (empty($cm)) {
                    continue;
                }
                if (empty($cm->uservisible)) {
                    continue;
                }
                // export when (!empty($a) OR !empty($b)) is equivalent to
                // export except when (empty($a) && empty($b))
                if (isset($cm->showavailablity) && isset($cm->availableinfo)
                        && empty($cm->showavailablity) && empty($cm->availableinfo)) {
                    continue;
                }
                if ($cm->modname == 'heading') {
                    continue;
                }
                if ($course->enablecompletion) {
                    // include all labels where completion tracking is disabled at course level
                    // otherwise exclude labels without completion criteria (the tick box)
                    if ($cm->modname == 'label' && empty($cm->completion)) {
                        continue;
                    }
                }
                if ($es->startdate < $timestart) {
                    continue;
                }
                if ($es->enddate > $timeend) {
                    continue;
                }

                $hostaddress = calexport_lib::return_host_address();
                $mod = $DB->get_record($cm->modname, array('id'=>$cm->instance));
                $desc = $lastmodified = '';
                if (!empty($mod->intro)) {
                    $desc = $mod->intro;
                } else if (!empty($mod->summary)) {
                    $desc = trim(clean_param($mod->summary, PARAM_NOTAGS));
                }
                if (!empty($mod->timemodified)) {
                    $lastmodified = $mod->timemodified;
                }
                $created = $DB->get_field('course_modules', 'added', array('id'=>$cmid));
                $url = $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cmid;

                $bennu = new Bennu();
                $ev = new iCalendar_event();
                $ev->add_property('uid', $shortname . '-' . $cmid . '@' . $hostaddress);
                $ev->add_property('summary', $cm->name);
                if (!empty($desc)) {
                    // Remove tags as these are displayed as text not html in calendars
                    // Add the url as text on a new line at the end of the description
                    $desc = trim(clean_param($desc, PARAM_NOTAGS));
                    $desc = $desc . "\nLink: " . $url;
                } else {
                    $desc = 'Link: ' . $url;
                }
                $ev->add_property('description', $desc);
                $ev->add_property('class', 'PUBLIC');
                $ev->add_property('created', $bennu->timestamp_to_datetime($created));
                $ev->add_property('dtstamp', $bennu->timestamp_to_datetime()); //now
                $ev->add_property('dtstart', $bennu->timestamp_to_datetime($es->startdate));
                $ev->add_property('dtend', $bennu->timestamp_to_datetime($es->enddate));
                if (!empty($lastmodified)) {
                    $ev->add_property('last-modified', $bennu->timestamp_to_datetime($lastmodified));
                }
                $ev->add_property('url', $url);
                $ical->add_component($ev);
            }
        }
    }

    /**
     * Checks whether it is worthwhile progressing -
     * that studyplan is installed, courseid is OK and is a studyplan course.
     * @param int $courseid Id of course, 0 if none
     * @return bool true if basics check out OK
     */
    private static function check_basics($courseid) {
        global $CFG;
        if (file_exists($CFG->dirroot . '/course/format/studyplan/lib.php')) {
            include_once($CFG->dirroot . '/course/format/studyplan/lib.php');
            if (empty($courseid) || $courseid == SITEID) {
                // Note courseid is set to 0 for admins
                return false;
            }
            $studyplan = studyplan_get_settings($courseid);
            if ($studyplan->displaymode == STUDYPLAN_CALENDAR) {
                return true;
            }
        }
        return false;
    }
}
