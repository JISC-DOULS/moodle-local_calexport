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
 * Language strings
 *
 * @package    local
 * @subpackage calexport
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Alternate calendar export';

$string['admin_enable_personal'] = 'Enable export of personal calendar entries';
$string['admin_enable_personal_desc'] = 'Show/hide option to export personal calendar entries.';
$string['admin_googledomain'] = 'Google domain';
$string['admin_googledomain_desc'] = 'Google Apps domain name, or empty to disable Google calendar button.';

$string['title'] = 'Export your calendar';

$string['instructions'] = 'You can export your OU personal calendar entries into other calendar software.';

$string['courseinstructs'] = 'What would you like to export?';
$string['courseinstructs_help'] = 'Exporting the learning materials on your study planner to a
personal calendar helps you to organise your study by using features provided by Google or other electronic calendars.';
$string['courseinstructs_link'] = 'http://learn1.open.ac.uk/mod/oucontent/view.php?id=63';

$string['whatinstructs'] = 'The export includes entries from your OU personal calendar that have
 come from your module, such as assessment cut-off dates. In addition, you can include:';

$string['eventsall'] = 'Personal entries from your OU personal calendar';
$string['eventsall_help'] = 'These are entries in your OU personal calendar that you have created
 yourself. To avoid duplication, don’t tick this box if you have already imported your OU personal calendar into your electronic calendar.';
$string['eventsall_link'] = 'http://learn1.open.ac.uk/mod/oucontent/view.php?id=63';

$string['exportheading'] = 'Where would you like to export to?';

$string['export_instructs'] = '<p>You can export items either to your Google Calendar or to an iCal file, which you can then import into any
electronic calendar that supports the iCal format.</p><p><strong>If</strong> you export to Google Calendar:</p>
<ul><li>updates to your study planner will be applied automatically</li>
<li>you will not be able to edit, move or delete exported items.</li></ul>
<p><strong>If</strong> you export to an iCal file and import into an electronic calendar:</p><ul>
<li>updates to your study planner will not be applied automatically</li>
<li>you will be able to edit, move and delete exported items.</li></ul>';

$string['personal'] = 'OU personal';
$string['course'] = 'module';

$string['googleicon'] = 'Google Calendar';
$string['googleicon_desc'] = 'Export to Google Calendar ';

$string['ical'] = 'Download iCal file';
$string['ical_desc'] = 'Export to an iCal file';

$string['feed'] = 'Copy web feed URL';
$string['feed_desc'] = '<strong>Alternatively</strong>, you can create a web feed that provides automatic updates from your study planner.';
$string['feed_desc_help'] = 'Copy web feed URL';
$string['feed_desc_help_help'] = 'Clicking on this button will provide you with a URL (a full web
 address) that you can use to subscribe to some web-based calendar applications.';
$string['feed_desc_help_link'] = 'http://learn1.open.ac.uk/mod/oucontent/view.php?id=63';

$string['submit'] = 'Update links to reflect selection';

$string['icalname'] = 'OU VLE';

$string['manualcopy'] = 'Select the feed URL text and then Ctrl+c to copy to the clipboard.';
$string['autocopy'] = 'Feed URL has been copied to the clipboard';

$string['eventsactivities'] = 'Learning materials from your study planner';
$string['eventsactivities_help'] = 'This includes links to documents, forums, wikis and blogs.
 They will appear in the same weeks in which they appear on your study planner. Items that sit
  outside the weeks of the planner will not be exported, and the export may not include all the
  text-only items.';
$string['eventsactivities_link'] = 'http://learn1.open.ac.uk/mod/oucontent/view.php?id=63';

$string['nopersonaleventsexport'] = '<em> Please note: you cannot export peronal events via this website.</em>';
