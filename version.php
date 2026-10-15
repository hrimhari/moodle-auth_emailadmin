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
 * Version details
 * NOTE: Based on "email" by Martin Dougiamas (http://dougiamas.com)
 *
 * @package    auth
 * @subpackage emailadmin
 * @copyright  2012 onwards Felipe Carasso (http://carassonet.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2030110300;        // Above all fork variants (original 2030105080, eummena/ewallah 20301101xx, our 1.5.1 = 2030110200).
$plugin->requires  = 2017111300;        // Requires this Moodle version.
$plugin->supported = [401, 405];        // Tested up to Moodle 4.5.
$plugin->component = 'auth_emailadmin';      // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.5.2';
