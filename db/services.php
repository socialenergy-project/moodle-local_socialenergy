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

$functions = array(
        'local_socialenergy_user_get_profile' => array(
                'classname'   => 'local_socialenergy_external',
                'methodname'  => 'get_user_profile',
                'classpath'   => 'local/socialenergy/externallib.php',
                'description' => 'Get user\'s profile.',
                'type'        => 'read',
        ),
        'local_socialenergy_user_create_user' => array(
                'classname'   => 'local_socialenergy_external',
                'methodname'  => 'create_user',
                'classpath'   => 'local/socialenergy/externallib.php',
                'description' => 'Creates a user.',
                'type'        => 'write',
                'capabilities' => 'moodle/user:create'
        ),
        'local_socialenergy_competency_create_plan' => array(
                'classname'   => 'local_socialenergy_external',
                'methodname'  => 'create_plan',
                'classpath'   => 'local/socialenergy/externallib.php',
                'description' => 'Creates a learning plan.',
                'type'        => 'write',
                'capabilities' => 'moodle/competency:planmanage'
        )
);

$services = array(
        'Social Energy Custom Services' => array(
                'functions' => array ('local_socialenergy_user_get_profile',
                                        'local_socialenergy_user_create_user', 'local_socialenergy_competency_create_plan'),
                'restrictedusers' => 1,
                'enabled' => 1,
        )
);

