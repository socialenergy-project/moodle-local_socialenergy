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
use core_competency\external\plan_exporter;
use tool_lp\external;
use local_socialenergy\scorelog;

require_once($CFG->libdir . "/externallib.php");

class local_socialenergy_external extends external_api {

    /**
     * Returns description of create_plan() parameters.
     *
     * @return \external_function_parameters
     */
    public static function create_plan_parameters() {
        return new external_function_parameters(array(
            'plan' => new external_single_structure(array(
                'name' => new external_value(PARAM_TEXT, 'Plan name', VALUE_OPTIONAL),
                'description' => new external_value(PARAM_RAW, 'Plan description', VALUE_OPTIONAL),
                'descriptionformat' => new external_value(PARAM_INT, 'Plan description format', VALUE_OPTIONAL),
                'username' => new external_value(core_user::get_property_type('username'), 'Username', VALUE_REQUIRED),
                'competencies' => new external_multiple_structure(new external_single_structure(array(
                    'idnumber' => new external_value(PARAM_RAW, 'Competency idnumber')
                ), 'competency', VALUE_REQUIRED), 'Competency list', VALUE_REQUIRED)
            ))
        ));
    }

    /**
     * Create a new learning plan.
     *
     * @param array $plan List of fields for the plan.
     * @return planid.
     */
    public static function create_plan($plan) {
        global $PAGE;
        global $DB;

        $params = self::validate_parameters(self::create_plan_parameters(), array('plan' => $plan));

        $plan['status'] = 1;
        if (empty($plan['name'])) {
            $plan['name'] = get_config('local_socialenergy', 'learningplanname');
        }

        $user = $DB->get_record('user', array('username' => $plan['username']), '*', MUST_EXIST);
        $plan['userid'] = $user->id;
        unset($plan['username']);

        $competencies = $plan['competencies'];
        unset($plan['competencies']);

        $context = context_user::instance($plan['userid']);
        self::validate_context($context);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        // Retrieve the manual enrolment plugin.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }

        $transaction = $DB->start_delegated_transaction();

        $planrecord = api::create_plan((object) $plan);
        $competencyframeworkid = get_config('local_socialenergy', 'competencyframeworkid');
        $courselist = array();
        foreach ($competencies as $competency) {
            $filters = array("competencyframeworkid" => $competencyframeworkid, "idnumber" => $competency['idnumber']);
            if (!$competencyrecord = $DB->get_record_select("competency",
                                        "competencyframeworkid = :competencyframeworkid AND idnumber = :idnumber", $filters)) {
                throw new invalid_parameter_exception('Invalid competency idnumber: ' . $competency['idnumber']);
            }
            api::add_competency_to_plan($planrecord->get('id'), $competencyrecord->id);
            $courses = api::list_courses_using_competency($competencyrecord->id);
            foreach ($courses as $course) {
                if (!array_key_exists($course->id, $courselist) && !is_enrolled(context_course::instance($course->id), $user)) {
                    $instance = $DB->get_record('enrol', array(
                        'courseid' => $course->id,
                        'enrol' => 'manual'
                    ), '*', MUST_EXIST);
                    $courselist[$course->id] = $instance;
                }
            }
        }

        foreach ($courselist as $id => $instance) {
            $enrol->enrol_user($instance, $user->id, $studentrole->id);
        }

        $transaction->allow_commit();

        return array('planid' => $planrecord->get('id'), 'name' => $planrecord->get('name'));
    }

    /**
     * Returns description of create_plan() result value.
     *
     * @return \external_description
     */
    public static function create_plan_returns() {
        return new external_single_structure(array(
            'planid' => new external_value(PARAM_INT, 'Plan id'),
            'name' => new external_value(PARAM_TEXT, 'Plan name')
        ));
    }

    /**
     * Returns description of method create_users parameters
     *
     * @return external_function_parameters
     */
    public static function create_user_parameters() {
        return new external_function_parameters(array(
            'userinfo' => new external_single_structure(array(
                'username' => new external_value(core_user::get_property_type('username'), 'Username.'),
                'firstname' => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
                'middlename' =>
                    new external_value(core_user::get_property_type('middlename'), 'The middle name of the user', VALUE_OPTIONAL),
                'lastname' => new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
                'email' => new external_value(core_user::get_property_type('email'), 'A valid and unique email address')
            ))
        ));
    }

    /**
     * Create user.
     *
     * @throws invalid_parameter_exception
     * @param array $userinfo
     * @return array $user
     */
    public static function create_user($userinfo) {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/lib/weblib.php");
        require_once($CFG->dirroot . "/user/lib.php");
        require_once($CFG->dirroot . "/user/editlib.php");

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:create', $context);

        $params = self::validate_parameters(self::create_user_parameters(), array(
            'userinfo' => $userinfo
        ));

        // Make sure that oauth2 is available.
        $availableauths = core_component::get_plugin_list('auth');
        if (empty($availableauths['oauth2'])) {
            throw new invalid_parameter_exception('Invalid authentication type: ' . $userinfo['auth']);
        }

        // Make sure that the username doesn't exist already.
        if ($DB->record_exists('user', array(
            'username' => $userinfo['username'],
            'mnethostid' => $CFG->mnet_localhost_id
        ))) {
            throw new invalid_parameter_exception('Username already exists: ' . $userinfo['username']);
        }

        $result = array();

        $transaction = $DB->start_delegated_transaction();

        $userinfo['password'] = '';
        $userinfo['confirmed'] = 1;
        $userinfo['secret'] = random_string(15);
        $userinfo['mnethostid'] = $CFG->mnet_localhost_id;
        $userinfo['auth'] = 'oauth2';

        // Start of user info validation.
        // Make sure we validate current user info as handled by current GUI. See user/editadvanced_form.php func validation().
        if (!validate_email($userinfo['email'])) {
            throw new invalid_parameter_exception('Email address is invalid: ' . $userinfo['email']);
        } else if (empty($CFG->allowaccountssameemail) && $DB->record_exists('user', array(
                'email' => $userinfo['email'],
                'mnethostid' => $userinfo['mnethostid']
            ))) {
            throw new invalid_parameter_exception('Email address already exists: ' . $userinfo['email']);
        }

        $id = user_create_user($userinfo, false, true);

        $result = array(
            'id' => $id,
            'username' => $userinfo['username']
        );

        $transaction->allow_commit();

        return $result;
    }

    /**
     * Returns description of method create_user result value.
     *
     * @return external_description
     */
    public static function create_user_returns() {
        return new external_single_structure(array(
            'id' => new external_value(core_user::get_property_type('id'), 'user id'),
            'username' => new external_value(core_user::get_property_type('username'), 'user name')
        ));
    }

    public static function get_user_dedication($course, $user) {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/blocks/dedication/dedication_lib.php");

        $context = context_course::instance($course->id);
        self::validate_context($context);

        // Params from request or default values.
        $mintime = $course->startdate;
        $maxtime = time();
        $limit = BLOCK_DEDICATION_DEFAULT_SESSION_LIMIT;
        $dm = new block_dedication_manager($course, $mintime, $maxtime, $limit);

        $total = $dm->get_user_dedication($user, true);

        return $total;
    }

    /**
     * Get scores of user.
     *
     * @param object $user
     * @return array of scores
     */
    public static function get_user_scores($user) {
        global $DB;

        $result   = array();
        $competencyframeworkid = get_config('local_socialenergy', 'competencyframeworkid');
        $scorelog = new scorelog();

        $reports = $DB->get_records("local_socialenergy_reports", array());

        foreach ($reports as $report) {

            $filters = array(
                'userid' => $user->id,
                'competencyframeworkid' => $competencyframeworkid,
                'datefrom' => strtotime($report->datefrom),
                'datetill' => strtotime($report->datetill),
                'competencyids' => $report->competencyids,
                'courseids' => $report->courseids,
            );

            $result[] = array(
                'id' => $report->idnumber,
                'name' => $report->name,
                'description' => $report->description,
                'value' => $scorelog->get_score($filters)
            );
        }

        return $result;
    }

    /**
     * Get list of courses user is enrolled in (only active enrolments are returned).
     * Please note the current user must be able to access the course, otherwise the course is not included.
     *
     * @param object $user
     * @return array of result
     */
    public static function get_users_courses($user) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once("$CFG->libdir/gradelib.php");
        require_once("$CFG->dirroot/grade/querylib.php");

        $courses = enrol_get_users_courses($user->id, true, 'id, shortname, fullname, idnumber, visible,
                    summary, summaryformat, format, showgrades, lang, enablecompletion, category, startdate, enddate');

        $result  = array();
        foreach ($courses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                // Current user can not access this course, sorry we can not disclose who is enrolled in this course.
                continue;
            }

            if ($user->id != $USER->id and !course_can_view_participants($context)) {
                // We need capability to view participants.
                continue;
            }

            list($course->summary, $course->summaryformat) =
                external_format_text($course->summary, $course->summaryformat, $context->id, 'course', 'summary', null);
            $course->fullname = external_format_string($course->fullname, $context->id);

            $progress = null;
            if ($course->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            }

            $timespent = self::get_user_dedication($course, $user);
            $url = new moodle_url('/course/view.php', array(
                'id' => $course->id
            ));

            $coursegrades = grade_get_course_grades($course->id, $user->id);

            $result[] = array(
                'id' => $course->id,
                'name' => $course->fullname,
                'description' => $course->summary,
                'url' => $url->out(true),
                'grademin' => $coursegrades->grademin,
                'grademax' => $coursegrades->grademax,
                'gradepass' => $coursegrades->gradepass,
                'grade' => $coursegrades->grades[$user->id]->grade,
                'dategraded' => $coursegrades->grades[$user->id]->dategraded,
                'progress' => $progress,
                'timespent' => $timespent
            );
        }

        return $result;
    }

    /**
     * Get user details.
     *
     * @param object $user
     * @return array of result
     */
    public static function get_user_details($user) {
        global $CFG, $USER;

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        if ($USER->id != $user->id) {
            // We must check if the current user can view other users grades.
            require_capability('moodle/grade:viewall', $systemcontext);
        }

        $url = new moodle_url('/user/profile.php', array(
            'id' => $user->id
        ));

        $result = array(
            'id' => $user->id,
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'url' => $url->out(true),
            'firstaccess' => $user->firstaccess,
            'lastaccess' => $user->lastaccess
        );

        return $result;
    }

    /**
     * Get user badges.
     *
     * @param object $user
     * @return array $result
     */
    public static function get_user_badges($user = null, $courseid = 0,
        $page = 0, $perpage = 0, $search = '', $onlypublic = false) {
        global $CFG, $USER;

        require_once($CFG->libdir . '/badgeslib.php');
        require_once($CFG->dirroot . '/badges/lib.php');

        if (empty($CFG->enablebadges)) {
            throw new moodle_exception('badgesdisabled', 'badges');
        }

        if (empty($CFG->badges_allowcoursebadges) && $courseid != 0) {
            throw new moodle_exception('coursebadgesdisabled', 'badges');
        }

        $usercontext = context_user::instance($user->id);
        self::validate_context($usercontext);

        if ($USER->id != $user->id) {
            require_capability('moodle/badges:viewotherbadges', $usercontext);
            // We are looking other user's badges, we must retrieve only public badges.
            $onlypublic = true;
        }

        $userbadges = badges_get_user_badges($user->id, $courseid, $page, $perpage, $search, $onlypublic);

        $result = array();

        foreach ($userbadges as $badge) {
            $context = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
            $badge->url = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1')->out(false);
            $result[] = array(
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'url' => $badge->url,
                'dateissued' => $badge->dateissued
            );
        }

        return $result;
    }

    /**
     * Returns description of method get_user_profile parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_user_profile_parameters() {
        $username = new external_value(core_user::get_property_type('username'), 'The username', VALUE_REQUIRED);
        $mintime  = new external_value(PARAM_INT, 'Start date timestamp', VALUE_OPTIONAL);
        $maxtime  = new external_value(PARAM_INT, 'End date timestamp', VALUE_OPTIONAL);
        $params   = array(
            'username' => $username
        );
        return new external_function_parameters($params);
    }

    public static function competency_exporter($userid, $competencies, &$output, $proficientonly = false) {
        foreach ($competencies as $competency) {
            $foundkey = array_search($competency->get('idnumber'), array_column($output, 'idnumber'));
            if ($foundkey !== false && $foundkey !== null) {
                continue;
            }
            $summary = external::data_for_user_competency_summary($userid, $competency->get('id'));
            if ($proficientonly && !$summary->usercompetency->proficiency) {
                continue;
            }
            $output[] = array(
                'id' => $competency->get('id'),
                'name' => $competency->get('shortname'),
                'description' => $competency->get('description'),
                'idnumber' => $competency->get('idnumber'),
                'proficiency' => ($summary->usercompetency->proficiency) ? 1 : 0,
                'grade' => $summary->usercompetency->grade,
                'gradename' => ($summary->usercompetency->grade) ? $summary->usercompetency->gradename : null
            );
        }
    }

    /**
     * Lists user's competencies from the ILPs and acquired competencies not from the ILPs.
     *
     * @param int $userid
     * @return array $usercompetencies
     */
    public static function get_user_competencies($userid) {
        $usercompetencies = array();

        // Get competencies from ILPs.
        $plans = api::list_user_plans($userid);
        foreach ($plans as $plan) {
            self::competency_exporter($userid, $plan->get_competencies(), $usercompetencies, false);
        }

        // Get acquired competencies which are not from ILPs.
        $filters      = array();
        $competencies = api::list_competencies($filters);
        self::competency_exporter($userid, $competencies, $usercompetencies, true);

        return $usercompetencies;
    }

    /**
     * Returns user profile.
     *
     * @return array user profile
     */
    public static function get_user_profile($username) {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/blocks/dedication/dedication_lib.php");

        $params = self::validate_parameters(self::get_user_profile_parameters(), array(
            'username' => $username
        ));

        // Default value for user.
        $user = null;
        if (empty($username)) {
            $user = core_user::get_user($USER->id, '*', MUST_EXIST);
        } else {
            $user = $DB->get_record('user', array(
                'username' => $params['username']
            ), '*', MUST_EXIST);
        }

        // Validate the user.
        core_user::require_active_user($user);

        $result[] = array(
            'user' => self::get_user_details($user),
            'scores' => self::get_user_scores($user),
            'competencies' => self::get_user_competencies($user->id),
            'badges' => self::get_user_badges($user),
            'courses' => self::get_users_courses($user)
        );

        return $result;
    }

    /**
     * Describes the user_get_profile return value.
     *
     * @return external_multiple_structure
     */
    public static function get_user_profile_returns() {
        return new external_multiple_structure(new external_single_structure(array(
            'user' => new external_single_structure(array(
                'id' => new external_value(core_user::get_property_type('id'), 'ID of the user'),
                'username' => new external_value(core_user::get_property_type('username'), 'The username', VALUE_OPTIONAL),
                'firstname' => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user', VALUE_OPTIONAL),
                'lastname' => new external_value(core_user::get_property_type('lastname'), 'The family name of the user', VALUE_OPTIONAL),
                'email' => new external_value(core_user::get_property_type('email'), 'An email address - allow email as root@localhost', VALUE_OPTIONAL),
                'url' => new external_value(PARAM_URL, 'Profile URL.'),
                'firstaccess' => new external_value(core_user::get_property_type('firstaccess'), 'first access to the site (0 if never)', VALUE_OPTIONAL),
                'lastaccess' => new external_value(core_user::get_property_type('lastaccess'), 'last access to the site (0 if never)', VALUE_OPTIONAL)
            )),
            'scores' => new external_multiple_structure(new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'Score id', VALUE_OPTIONAL),
                'name' => new external_value(PARAM_TEXT, 'Score name'),
                'description' => new external_value(PARAM_RAW, 'Score description'),
                'value' => new external_value(PARAM_RAW, 'Score value')
            ))),
            'competencies' => new external_multiple_structure(new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'Competence id', VALUE_OPTIONAL),
                'name' => new external_value(PARAM_TEXT, 'Competence name'),
                'description' => new external_value(PARAM_RAW, 'Competence description'),
                'idnumber' => new external_value(PARAM_RAW, 'id number'),
                'proficiency' => new external_value(PARAM_BOOL, 'proficiency', VALUE_DEFAULT, 0),
                'grade' => new external_value(PARAM_INT, 'grade type'),
                'gradename' => new external_value(PARAM_TEXT, 'grade name')))),
            'badges' => new external_multiple_structure(new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'Badge id.', VALUE_OPTIONAL),
                'name' => new external_value(PARAM_TEXT, 'Badge name.'),
                'description' => new external_value(PARAM_NOTAGS, 'Badge description.'),
                'url' => new external_value(PARAM_URL, 'Badge URL.'),
                'dateissued' => new external_value(PARAM_INT, 'Date issued.')
            ))),
            'courses' => new external_multiple_structure(new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'id of course'),
                'name' => new external_value(PARAM_RAW, 'long name of course'),
                'description' => new external_value(PARAM_RAW, 'summary', VALUE_OPTIONAL),
                'url' => new external_value(PARAM_URL, 'Course URL.'),
                'grademin' => new external_value(PARAM_RAW, 'Grade min value', VALUE_OPTIONAL),
                'grademax' => new external_value(PARAM_RAW, 'Grade max value', VALUE_OPTIONAL),
                'gradepass' => new external_value(PARAM_RAW, 'Grade pass value', VALUE_OPTIONAL),
                'grade' => new external_value(PARAM_RAW, 'Grade value', VALUE_OPTIONAL),
                'dategraded' => new external_value(PARAM_INT, 'Date issued.', VALUE_OPTIONAL),
                'progress' => new external_value(PARAM_FLOAT, 'Progress percentage', VALUE_OPTIONAL),
                'timespent' => new external_value(PARAM_RAW, 'Dedication time of the user to the course', VALUE_OPTIONAL)
            )))

        )));
    }

}