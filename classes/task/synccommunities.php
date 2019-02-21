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

namespace local_socialenergy\task;

defined('MOODLE_INTERNAL') || die;

/**
 * Synchronize Social Energy communities task.
 */
class synccommunities extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:synccommunities', 'local_socialenergy');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        // NOTE: this is very memory intensive and generally inefficient.
        $sql = "SELECT u.id, u.username
                  FROM {user} u
                 WHERE u.auth=:authtype AND u.deleted=0 AND u.suspended = 0 AND mnethostid=:mnethostid";
        $users = $DB->get_records_sql($sql, array('authtype' => 'oauth2', 'mnethostid' => 1));
        $memberrole = $DB->get_record('role', array('shortname' => 'member'), 'id', MUST_EXIST);
        $leaderrole = $DB->get_record('role', array('shortname' => 'ecleader'), 'id', MUST_EXIST);
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }
        $url = get_config('local_socialenergy', 'socialstatusurl');
        $dbcommunities = array();
        $courselist = array();
        foreach ($users as $user) {
            mtrace("Communities sync for user: " . $user->username);
            $params = json_encode(array('username' => $user->username));
            $headers = array('content-type: application/json', 'cache-control: no-cache',
                                sprintf('postman-token: %s', get_config('local_socialenergy', 'postmantoken')));
            $request = new \local_socialenergy\request($url, 'POST', $params, $headers);
            if ($request->send()) {
                $communities = json_decode($request->response, true);
                if ($communities === null) {
                    continue;
                }
                foreach ($communities as $community) {
                    $idnumber = 'C'.$community['Groupid'];
                    if (!array_key_exists($community['Groupid'], $dbcommunities) && !$DB->record_exists('course', array('idnumber' => $idnumber))) {
                        // Create community.
                        mtrace("... Creating community: " . $community['userGroups']);
                        $course = new \StdClass();
                        $course->category = get_config('local_socialenergy', 'communitycategoryid');
                        $course->fullname = $community['userGroups'];
                        $course->shortname = $community['userGroups'] . ' (C'.$community['Groupid'].')';
                        $course->idnumber = $idnumber;
                        $course->format = 'social';
                        create_course((object)$course);
                    }
                    if (array_key_exists($community['Groupid'], $dbcommunities)) {
                        $courseid = $dbcommunities[$community['Groupid']];
                    } else {
                        $courseid = $DB->get_field('course', 'id', array('idnumber' => $idnumber), MUST_EXIST);
                        $dbcommunities[$community['Groupid']] = $courseid;
                    }

                    if (!array_key_exists($courseid, $courselist)) {
                        $instance = $DB->get_record('enrol', array(
                            'courseid' => $courseid,
                            'enrol' => 'manual'
                        ), '*', MUST_EXIST);
                        $courselist[$courseid] = $instance;
                    }
                    $instance = $courselist[$courseid];
                    if ($community['roleUser'] === 'EC leader') {
                        $enrol->enrol_user($instance, $user->id, $leaderrole->id);
                    } else if ($community['roleUser'] === 'member') {
                        $enrol->enrol_user($instance, $user->id, $memberrole->id);
                    }
                }
                unset($communities);
            } else {
                mtrace('Error sending request: ' . $request->error);
            }
            unset($request);
        }
        unset($dbcommunities);
        unset($courselist);
        unset($users);
        mtrace('Task finished.');
    }

}