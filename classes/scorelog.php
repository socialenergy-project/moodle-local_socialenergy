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

namespace local_socialenergy;

defined('MOODLE_INTERNAL') || die;

class scorelog {

    /**
     * @var \stdClass A list of filters to be applied to the sql query.
     */
    protected $filters;

    /**
     *  Constructor
     */
    public function __construct() {
    }

    /**
     * Builds the sql and param list needed, based on the user selected filters.
     *
     * @return array containing sql to use and an array of params.
     */
    protected function get_joins_filters_params() {
        global $DB;

        $joins = '';
        $filter = '1 = 1';
        $params = array();

        if (!empty($this->filters->courseids)) {
            $list = explode(',', $this->filters->courseids);
            list($insql, $plist) = $DB->get_in_or_equal($list, SQL_PARAMS_NAMED);
            $filter .= " AND ggh.courseid $insql";
            $params += $plist;
        }
        if (!empty($this->filters->competencyids)) {
            $joins .= "\n\tJOIN {competency_coursecomp} coursecomp ON coursecomp.courseid = gi.courseid";
            $joins .= "\n\tJOIN {competency} comp ON comp.id = coursecomp.competencyid";
            $list = explode(',', $this->filters->competencyids);
            list($insql, $plist) = $DB->get_in_or_equal($list, SQL_PARAMS_NAMED);
            $filter .= " AND comp.idnumber $insql";
            $params += $plist;
            $filter .= " AND comp.competencyframeworkid=:competencyframeworkid";
            $params += array('competencyframeworkid' => $this->filters->competencyframeworkid);
        }
        if (!empty($this->filters->userid)) {
            $list = explode(',', $this->filters->userid);
            $filter .= " AND ggh.userid=:userid";
            $params += array('userid' => $this->filters->userid);
        }
        if (!empty($this->filters->datefrom)) {
            $filter .= " AND ggh.timemodified >= :datefrom";
            $params += array('datefrom' => $this->filters->datefrom);
        }
        if (!empty($this->filters->datetill)) {
            $filter .= " AND ggh.timemodified <= :datetill";
            $params += array('datetill' => $this->filters->datetill);
        }

        return array($joins, $filter, $params);
    }

    /**
     * Builds the complete sql with all the joins to get the grade history data.
     *
     * @return array containing sql to use and an array of params.
     */
    protected function get_sql_and_params() {

        list($joins, $where, $params) = $this->get_joins_filters_params();

        $sql = "SELECT COALESCE(SUM(best), 0) score
                FROM
                    (SELECT MAX(ggh.finalgrade) as best
                        FROM {grade_grades_history} ggh
                        JOIN {grade_items} gi ON gi.id = ggh.itemid AND gi.itemtype='course'
                        $joins
                    WHERE $where
                    GROUP BY gi.courseid) as bests";

        return array($sql, $params);
    }

    /**
     * Get scores.
     *
     * @param array $filters options are:
     *                          courseids : limit to courses
     *                          competencyids : limit to courses containing specific competencies
     *                          competencyframeworkid : limit to competencies from specific competency framework
     *                          userid : limit to specific user
     *                          datefrom : start of date range
     *                          datetill : end of date range
     */
    public function get_score($filters) {
        global $DB;

        $this->filters = (object)$filters;

        list($sql, $params) = $this->get_sql_and_params();
        return $DB->get_field_sql($sql, $params);
    }
}
