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

use core_competency\api;

if ( $hassiteconfig ) {
    $settings = new admin_settingpage( 'local_socialenergy', get_string('pluginname', 'local_socialenergy') );
    $ADMIN->add( 'localplugins', $settings );

    if ($ADMIN->fulltree) {
        $context = context_system::instance();
        $frameworks = api::list_frameworks('shortname', 'ASC', null, null, $context);
        $options = array();
        foreach ($frameworks as $framework) {
            $options[$framework->get('id')] = $framework->get('shortname');
        }
        $settings->add(new admin_setting_configselect('local_socialenergy/competencyframeworkid', get_string('competencyframework', 'local_socialenergy'), '', '', $options));
        $settings->add( new admin_setting_configtext('local_socialenergy/learningplanname', get_string('learningplanname', 'local_socialenergy'), '', '', PARAM_TEXT));
        $settings->add( new admin_setting_configtext('local_socialenergy/loggedoutredirecturl', get_string('loggedoutredirecturl', 'local_socialenergy'), '', '', PARAM_URL));
    }
}
