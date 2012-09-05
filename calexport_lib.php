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
 * Library file with all the classes used in this plugin
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/calexport_plugins.php');
global $CFG;
require_once($CFG->libdir . '/bennu/bennu.inc.php');

class calexport_lib {
    private static $settings;

    private static $plugins;

    public static function get_plugin_config() {
        if (!isset(self::$settings)) {
            self::$settings = get_config('local_calexport');
        }
        return self::$settings;
    }

    /**
     * Main selection screen output function
     * @param $courseid INT Course ID if using in a course (or 0 if not)
     *
     * returns string html to output on page
     */
    public static function init_selection_screen($courseid = null) {
        global $PAGE, $USER, $OUTPUT;
        $output = '';//gets returned with all page content

        $renderer = $PAGE->get_renderer('local_calexport');

        $config = self::get_plugin_config();

        //Work out if we need to do course related stuff
        if ($courseid == null) {
            $courseid = optional_param('course', 0, PARAM_INT);
        }

        //Work out if personal etc events were deselected
        $allevents = optional_param('allevents', 1, PARAM_INT);

        $output .= html_writer::start_tag('div', array('id' => 'calexport'));//Container div fro all of cal export

        if ($courseid == 0) {
            //Show instructs
            $output .= html_writer::start_tag('div', array('id' => 'calexport_instructs'));
            $output .= html_writer::tag('p', get_string('instructions', 'local_calexport'));
            $output .= html_writer::end_tag('div');
        }

        //Options - these need to be in a form that can be submitted back to the same page
        //This is because the main export links are generated with javascript, so if js is disabled you need the form
        $output .= html_writer::start_tag('form', array('id' => 'calexport_options', 'method' => 'post', 'action' => ''));

        if ($courseid) {
            //Show events check box
            $output .= $renderer->get_course_export_opts($allevents, $courseid);
        }

        //user info so we can test this on data export
        $output .= html_writer::start_tag('div');
        if ($courseid) {
            $output .= html_writer::empty_tag('input', array('name' => 'course', 'type' => 'hidden', 'value' => $courseid));
        }
        $output .= html_writer::empty_tag('input', array('name' => 'u', 'type' => 'hidden', 'value' => $USER->username));
        $token = self::generate_token($USER->username, $USER->email);
        $output .= html_writer::empty_tag('input', array('name' => 'token', 'type' => 'hidden', 'value' => $token));

        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'id' => 'calexport_options_submit', 'value' => get_string('submit', 'local_calexport')));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('form');

        $output .= html_writer::start_tag('div', array('id' => 'calexport_links', 'class' => 'calexportbox'));
        //Link to calendar export page
        $params = array();
        if ($courseid) {
            $params['course'] = $courseid;
            if ($allevents == 0) {
                $params['allevents'] = $allevents;
            }
        }
        $params['u'] = $USER->username;
        $params['token'] = $token;

        //plugins add their own params to query string
        foreach (self::return_plugin_classnames() as $plugin) {
            if (method_exists($plugin, 'return_query_params')) {
                $plugparams = $plugin::return_query_params($courseid);
                $params = array_merge($plugparams, $params);
            }
        }

        $link = new moodle_url('/local/calexport/data.php', $params);

        $output .= $renderer->get_export_to_header($courseid);

        if ($courseid == 0) {
            $calname = get_string('personal', 'local_calexport');
        } else {
            $calname = get_string('course', 'local_calexport');
        }

        if (!empty($config->googlecal)) {
            //write add to google button
            $googlink = $config->googlecal . '/render?cid=' . urlencode(htmlspecialchars_decode((string)$link));
            $output .= $renderer->get_export_button('google', 'google', get_string('googleicon', 'local_calexport'),
                $googlink, get_string('googleicon_desc', 'local_calexport', $calname));
        }

        //write ical icon for feed + download
        $output .= $renderer->get_export_button('ical', 'download', get_string('ical', 'local_calexport'),
            $link, get_string('ical_desc', 'local_calexport', $calname));

        //Write feed details
        $output .= $renderer->get_feed_icon($calname, $link);

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Add external js and css to the current PAGE object
     * @param $incss boolean Include css lib: default true
     */
    public static function add_external_libs($incss = true) {
        global $PAGE;
        $link = new moodle_url('/local/calexport/data.php');
        if ($incss) {
            $PAGE->requires->css('/local/calexport/styles.css');
        }
        $PAGE->requires->js_init_call('M.local_calexport.init_link_update', array((string)$link,
            get_string('manualcopy', 'local_calexport'), get_string('autocopy', 'local_calexport')));
    }

    /**
     * Generates the authentication token so requests can be checked for validity
     * Uses the standard calendar salt - if you change this old links will be invalid
     * @param $username string username from user table
     * @param $email string email from user table
     * @return sring
     */
    public static function generate_token($username, $email) {
        global $CFG;
        return sha1($username . $email . $CFG->calendar_exportsalt);
    }

    /**
     * Create an ical event using the bennu library
     * @param recordset $eventrec row from event table (or object with eqiv props)
     * @param string $hostaddress moodle web address
     * @param string $shortname course shortname (optional)
     * @return iCalendar_event
     */
    public static function create_ical_event($event, $hostaddress = null, $shortname = null) {
        if (!$hostaddress) {
            $hostaddress = self::return_host_address();
        }
        $ev = new iCalendar_event();
        $ev->add_property('uid', $event->id.'@'.$hostaddress);
        $ev->add_property('summary', $event->name);
        $ev->add_property('description', $event->description);
        $ev->add_property('class', 'PUBLIC'); // PUBLIC / PRIVATE / CONFIDENTIAL
        $ev->add_property('last-modified', Bennu::timestamp_to_datetime($event->timemodified));
        $ev->add_property('dtstamp', Bennu::timestamp_to_datetime()); // now
        $ev->add_property('dtstart', Bennu::timestamp_to_datetime($event->timestart)); // when event starts
        if ($event->timeduration > 0) {
            //dtend is better than duration, because it works in Microsoft Outlook and works better in Korganizer
            $ev->add_property('dtend', Bennu::timestamp_to_datetime($event->timestart + $event->timeduration));
        }
        //Add different event categories (seems to break in Google if you have no category)
        if ($event->courseid != 0 && $event->courseid != SITEID && $shortname) {
            $ev->add_property('categories', $shortname);
        } else if ($event->courseid == 0 && $event->groupid == 0 && $event->userid != 0) {
            $ev->add_property('categories', 'Personal');
        } else {
            $ev->add_property('categories', 'Site');
        }
        if ($event->eventtype == 'tutorgroupevent' && stripos($event->description, 'location') !== false) {
            $ev->add_property('location', str_ireplace('location:', '', $event->description));
        }
        return $ev;
    }

    /**
     * Gets the current host address ready to put in calendar
     * @return string
     */
    public static function return_host_address() {
        global $CFG;
        $hostaddress = str_replace('http://', '', $CFG->wwwroot);
        return str_replace('https://', '', $hostaddress);
    }

    /**
     * Returns classnames of any plugins
     * @return array
     */
    public static function return_plugin_classnames() {
        if (!empty(self::$plugins)) {
            return self::$plugins;
        }
        self::$plugins = array();
        foreach (get_declared_classes() as $classname) {
            if (strpos($classname, 'calexport_plugin_') !== false) {
                self::$plugins[] = $classname;
            }
        }
        return self::$plugins;
    }
}

/**
 * Abstract class that defines calexport plugins
 * @author The Open University
 *
 */
abstract class calexport_plugin {

    /**
     * Return form contents to be displayed for your plugin
     * @param INT $courseid Id of course, 0 if none
     * @return string
     */
    public static function get_options($courseid) {}

    /**
     * Return array to be added to moodle_url params
     * This will be used to generate the link to calendar data (this is also done by js file)
     * @param INT $courseid Id of course, 0 if none
     * @return array
     */
    public static function return_query_params($courseid) {}

    /**
     * Add your calendar items to the calendar object
     * You need to do something like $ical->add_component(...).
     * @param $ical iCalendar Reference to iCalendar object
     * @param $timestart int
     * @param $timeend int
     * @param $userid int
     * @param $groups array false if none
     * @param $courseid int
     */
    public static function add_to_ical(iCalendar &$ical, $timestart, $timeend, $userid, $groups, $courseid, $shortname) {}
}
