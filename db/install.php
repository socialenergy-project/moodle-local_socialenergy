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

function xmldb_local_socialenergy_install() {
    global $CFG, $DB;

    $result = true;

    $reports = array(
        array('idnumber' => 1, 'name' => 'Current total score', 'description' => 'Sum of LCMS scores from all courses since end user\'s 1st registration',
             'datefrom' => null, 'datetill' => null, 'competencyids' => null, 'courseids' => null),
        array('idnumber' => 2, 'name' => 'Last week\'s total score', 'description' => 'Sum of LCMS scores of all courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => null, 'courseids' => null),
        array('idnumber' => 3, 'name' => 'Last month\'s total score', 'description' => 'Sum of LCMS scores of all courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => null, 'courseids' => null),
        array('idnumber' => 4, 'name' => 'Competence 1 total score', 'description' => 'Sum of LCMS scores only for competence 1 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '1.1,1.2,1.3', 'courseids' => null),
        array('idnumber' => 5, 'name' => 'Last week\'s total score for competence 1', 'description' => 'Sum of LCMS scores only for competence 1 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '1.1,1.2,1.3', 'courseids' => null),
        array('idnumber' => 6, 'name' => 'Last month\'s total score for competence 1', 'description' => 'Sum of LCMS scores only for competence 1 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '1.1,1.2,1.3', 'courseids' => null),
        array('idnumber' => 7, 'name' => 'Competence 2 total score', 'description' => 'Sum of LCMS scores only for competence 2 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '2.1,2.2,2.3', 'courseids' => null),
        array('idnumber' => 8, 'name' => 'Last week\'s total score for competence 2', 'description' => 'Sum of LCMS scores only for competence 2 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '2.1,2.2,2.3', 'courseids' => null),
        array('idnumber' => 9, 'name' => 'Last month\'s total score for competence 2', 'description' => 'Sum of LCMS scores only for competence 2 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '2.1,2.2,2.3', 'courseids' => null),
        array('idnumber' => 10, 'name' => 'Competence 3 total score', 'description' => 'Sum of LCMS scores only for competence 3 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '3.1,3.2,3.3', 'courseids' => null),
        array('idnumber' => 11, 'name' => 'Last week\'s total score for competence 3', 'description' => 'Sum of LCMS scores only for competence 3 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '3.1,3.2,3.3', 'courseids' => null),
        array('idnumber' => 12, 'name' => 'Last month\'s total score for competence 3', 'description' => 'Sum of LCMS scores only for competence 3 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '3.1,3.2,3.3', 'courseids' => null),
        array('idnumber' => 13, 'name' => 'Competence 4 total score', 'description' => 'Sum of LCMS scores only for competence 4 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '4.1,4.2,4.3', 'courseids' => null),
        array('idnumber' => 14, 'name' => 'Last week\'s total score for competence 4', 'description' => 'Sum of LCMS scores only for competence 4 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '4.1,4.2,4.3', 'courseids' => null),
        array('idnumber' => 15, 'name' => 'Last month\'s total score for competence 4', 'description' => 'Sum of LCMS scores only for competence 4 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '4.1,4.2,4.3', 'courseids' => null),
        array('idnumber' => 16, 'name' => 'Competence 5 total score', 'description' => 'Sum of LCMS scores only for competence 5 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '5.1,5.2,5.3', 'courseids' => null),
        array('idnumber' => 17, 'name' => 'Last week\'s total score for competence 5', 'description' => 'Sum of LCMS scores only for competence 5 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '5.1,5.2,5.3', 'courseids' => null),
        array('idnumber' => 18, 'name' => 'Last month\'s total score for competence 5', 'description' => 'Sum of LCMS scores only for competence 5 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '5.1,5.2,5.3', 'courseids' => null),
        array('idnumber' => 19, 'name' => 'Competence 6 total score', 'description' => 'Sum of LCMS scores only for competence 6 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '6.1,6.2,6.3', 'courseids' => null),
        array('idnumber' => 20, 'name' => 'Last week\'s total score for competence 6', 'description' => 'Sum of LCMS scores only for competence 6 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '6.1,6.2,6.3', 'courseids' => null),
        array('idnumber' => 21, 'name' => 'Last month\'s total score for competence 6', 'description' => 'Sum of LCMS scores only for competence 6 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '6.1,6.2,6.3', 'courseids' => null),
        array('idnumber' => 22, 'name' => 'Competence 7 total score', 'description' => 'Sum of LCMS scores only for competence 7 courses',
             'datefrom' => null, 'datetill' => null, 'competencyids' => '7.1,7.2,7.3', 'courseids' => null),
        array('idnumber' => 23, 'name' => 'Last week\'s total score for competence 7', 'description' => 'Sum of LCMS scores only for competence 7 courses for the last 7 days',
             'datefrom' => '-7 day', 'datetill' => null, 'competencyids' => '7.1,7.2,7.3', 'courseids' => null),
        array('idnumber' => 24, 'name' => 'Last month\'s total score for competence 7', 'description' => 'Sum of LCMS scores only for competence 7 courses for the last 30 days',
             'datefrom' => '-30 day', 'datetill' => null, 'competencyids' => '7.1,7.2,7.3', 'courseids' => null),
    );

    foreach ($reports as $report) {

        // Check that the report not exists already.
        if (!$DB->record_exists('local_socialenergy_reports', array('idnumber' => $report['idnumber']))) {
            // Insert the score report settings.
            $record = new stdClass();
            $record->name = $report['name'];
            $record->description = $report['description'];
            $record->idnumber = $report['idnumber'];
            $record->datefrom = $report['datefrom'];
            $record->datetill = $report['datetill'];
            $record->competencyids = $report['competencyids'];
            $record->courseids = $report['courseids'];

            $DB->insert_record('local_socialenergy_reports', $record);
        }
    }

    return $result;
}