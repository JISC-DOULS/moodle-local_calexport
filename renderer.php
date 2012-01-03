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
 * Renderer
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_calexport_renderer extends plugin_renderer_base {

    /**
     * Return html for a radio button that selects whether to export only course events
     * @param INT $selected - if all events were selected (default is 1)
     */
    public function get_course_export_opts($selected, $courseid) {
        global $OUTPUT;

        if ($selected == 1) {
            $allcheck['checked'] = 'checked';
        } else {
            $allcheck['checked'] = 'notchecked';
        }

        $output = html_writer::start_tag('div', array('class' => 'calexportbox', 'id' => 'events'));
        $output .= html_writer::start_tag('fieldset', array('id' => 'eventsfieldset'));

        $output .= html_writer::tag('legend', get_string('courseinstructs', 'local_calexport'));
        $output .= $OUTPUT->heading(get_string('courseinstructs', 'local_calexport'), 3);
        $output .= $OUTPUT->help_icon('courseinstructs', 'local_calexport');
        $output .= $this->get_eventsall_checkbox();

        //Add in form options from any 'plugins'
        foreach (calexport_lib::return_plugin_classnames() as $plugin) {
            if (method_exists($plugin, 'get_options')) {
                $output .= $plugin::get_options($courseid);
            }
        }

        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function get_export_to_header($courseid = 0) {
        global $OUTPUT;
        $output = $OUTPUT->heading(get_string('exportheading', 'local_calexport'), 3);

        $instruct = get_string('export_instructs', 'local_calexport');

        $output .= html_writer::tag('div', $instruct);
        return $output;
    }

    public function get_export_button($id, $pix_url, $imgtext, $link, $text) {
        global $OUTPUT;

        $output = html_writer::start_tag('div', array('id' => $id, 'class' => 'calexport_link'));

        $output .= html_writer::start_tag('div', array('class' => 'calexport_link_icon'));

        if (!empty($pix_url)) {
            $icon = html_writer::empty_tag('img', array(
                'src' => $OUTPUT->pix_url($pix_url, 'local_calexport'),
                'alt' => $imgtext,
                'title' => $imgtext));
        } else {
            $icon = '';
        }
        $atext = html_writer::tag('span', $imgtext);

        $output .= html_writer::tag('a', $icon . $atext, array('href' => $link, 'id' => $id . '_link', 'class' => 'calexport_link_button'));

        $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('div', $text , array('class' => 'calexport_link_desc'));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function get_feed_icon($calname, $link) {
        global $OUTPUT;

        $help = $OUTPUT->help_icon('feed_desc_help', 'local_calexport');
        $output = html_writer::tag('p', get_string('feed_desc', 'local_calexport', $calname).$help);

        $atts = array('type' => 'text', 'id' => 'feedfield', 'value' => $link,
            'title' => get_string('feed', 'local_calexport'));
        $textfield = html_writer::empty_tag('input', $atts);

        $output .= self::get_export_button('feed', '', get_string('feed', 'local_calexport'), '#',
            $textfield);

        return $output;
    }

    public function get_eventsall_checkbox() {
        global $OUTPUT, $CFG;

        if ((!empty($CFG->local_persdatasync_child1) &&
            strpos($CFG->wwwroot, $CFG->local_persdatasync_child1)) ||
            (!empty($CFG->local_persdatasync_child2) &&
            strpos($CFG->wwwroot, $CFG->local_persdatasync_child2))) {
            $noexport = get_string('nopersonaleventsexport', 'local_calexport');
            $output = html_writer::tag('p', get_string('whatinstructs', 'local_calexport').$noexport);
            return $output;
        }

        $allcheck = array('type' => 'checkbox', 'name' => 'allevents', 'id' => 'pw_all', 'value' => '1');

        $output = html_writer::tag('p', get_string('whatinstructs', 'local_calexport'));
        //Only show personal entries option if admin setting enabled
        if (get_config('local_calexport', 'ce_enable_personal')) {
            $output .= html_writer::start_tag('p', array('class' => 'inlinefield'));
            $output .= html_writer::empty_tag('input', $allcheck);
            $labeltext = get_string('eventsall', 'local_calexport') .
                $OUTPUT->help_icon('eventsall', 'local_calexport');
            $output .= html_writer::tag('label', $labeltext, array('for' => 'pw_all'));

            $output .= html_writer::end_tag('p');
        }

        return $output;
    }
}
