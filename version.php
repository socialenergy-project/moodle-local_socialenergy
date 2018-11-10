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
 * Local plugin "SocialEnergy"
 *
 * @package   local_socialenergy
 * @copyright 2017 Atanas Georgiev, Sofia University <atanas@fmi.uni-sofia.bg>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_socialenergy';
$plugin->version  = 2018110901;   // The (date) version of this module + 2 extra digital for daily versions.
$plugin->release = '1.0 (Build: 2018110901)';
$plugin->requires = 2017111301.04;  // Requires this Moodle version - at least 3.4.
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array(
    'block_dedication' => 2017042300,
);
