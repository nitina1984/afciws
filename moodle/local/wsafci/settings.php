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
 * Plugin setting page
 *
 * @package   local-wsafci
 * @copyright Nitin Agrawal <nitinagrawal.mca@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    global $CFG, $USER, $DB;

    $moderator = get_admin();
    $site = get_site();

    $settings = new admin_settingpage('local_wsafci', get_string('pluginname', 'local_wsafci'));
    $ADMIN->add('localplugins', $settings);

    $name = 'local_wsafci/api_token';
    $title = get_string('api_token', 'local_wsafci');
    $description = get_string('api_token_desc', 'local_wsafci');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $settings->add($setting);

    $name = 'local_wsafci/wp_ajax_script_url';
    $title = get_string('wp_ajax_script_url', 'local_wsafci');
    $description = get_string('wp_ajax_script_url_desc', 'local_wsafci');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $settings->add($setting);
}