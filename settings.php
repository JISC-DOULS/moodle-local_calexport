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
 * Config settings for local/calexport
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs condition or error on login page
    $settings = new admin_settingpage(
            'local_calexport', get_string('pluginname', 'local_calexport'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
            'local_calexport/googlecal', get_string('admin_googledomain', 'local_calexport'),
            get_string('admin_googledomain_desc', 'local_calexport'), 'http://www.google.com/calendar', PARAM_RAW));

    $settings->add(new admin_setting_configcheckbox('local_calexport/ce_enable_personal',
        get_string('admin_enable_personal', 'local_calexport'),
        get_string('admin_enable_personal_desc', 'local_calexport'), 1));
}
