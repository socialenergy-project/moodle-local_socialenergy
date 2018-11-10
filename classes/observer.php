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

class local_socialenergy_observer {

    /**
     * User logout event handler.
     *
     * @param \core\event\user_loggedout $event The event.
     * @return void
     */
    public static function user_loggedout(\core\event\user_loggedout $event) {
        global $DB;

        $url = get_config('local_socialenergy', 'loggedoutredirecturl');
        if (!empty($url)) {
            $user = $DB->get_record('user', array('id' => $event->objectid));
            if (!is_null($user) && isset($user->auth) && $user->auth == 'oauth2') {
                $params = array('username' => $user->username);
                $request = new local_socialenergy\request($url, 'POST', $params);
                if (!$request->send()) {
                    debugging('Error sending Single Sign Out request: ' . $request->error, DEBUG_DEVELOPER);
                }
            }
        }
    }
}
