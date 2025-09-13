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

namespace local_f2freport;

/**
 * Report builder for F2F local.
 *
 * This class is responsible for fetching and preparing all data required for the local.
 *
 * @package     local_f2freport
 * @copyright   2025 Carlos
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder {
    /**
     * Normalises a date field from a date_selector.
     * - If array [day,month,year] -> timestamp 00:00.
     * - If int -> int.
     * - Else 0.
     *
     * @param mixed $v The value to normalize.
     * @return int The normalized timestamp.
     */
    public static function normalize_date($v): int {
        if (is_array($v) && isset($v['day'], $v['month'], $v['year'])) {
            return make_timestamp((int)$v['year'], (int)$v['month'], (int)$v['day'], 0, 0, 0);
        }
        return (int)$v;
    }

    /**
     * Parses a CSV list into an array (lowercase/trim, empty filtered).
     *
     * @param string|null $csv The CSV string to parse.
     * @param array $fallback The fallback array to return if the CSV is empty.
     * @return array The parsed array of aliases.
     */
    public static function parse_aliases(?string $csv, array $fallback): array {
        $csv = trim((string)$csv);
        if ($csv === '') {
            return $fallback;
        }
        $out = [];
        foreach (explode(',', $csv) as $token) {
            $t = \core_text::strtolower(trim($token));
            if ($t !== '') {
                $out[] = $t;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Gets the list of courses having face-to-face activities.
     *
     * @return array An array of course options for a select menu.
     */
    public function get_course_options(): array {
        global $DB;
        $courseoptions = [0 => get_string('allcourses', 'local_f2freport')];
        $facetofaceid = $DB->get_field('modules', 'id', ['name' => 'facetoface'], IGNORE_MISSING);
        if ($facetofaceid) {
            $cms = $DB->get_records('course_modules', ['module' => $facetofaceid], '', 'id, course');
            if ($cms) {
                $courseids = array_values(array_unique(array_map(
                    function ($cm) {
                        return (int)$cm->course;
                    },
                    $cms
                )));
                if (!empty($courseids)) {
                    [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                    $courses = $DB->get_records_select_menu('course', "id $insql", $inparams, 'fullname ASC', 'id, fullname');
                    foreach ($courses as $cid => $fullname) {
                        $context = \context_course::instance((int)$cid, IGNORE_MISSING);
                        $label = $cid . ' - ' . format_string(
                            $fullname,
                            true,
                            ['context' => $context ?: \context_system::instance()]
                        );
                        $courseoptions[(int)$cid] = $label;
                    }
                }
            }
        }
        return $courseoptions;
    }

    /**
     * Gets the IDs of session fields (city/venue/room) via aliases.
     *
     * @return array An array of field IDs, keyed by 'city', 'venue', 'room'.
     */
    public function get_field_ids(): array {
        global $DB;

        $cfg = get_config('local_f2freport') ?: new \stdClass();
        $venuealiases = [
            'venue', 'lieu', 'building', 'site', 'centre', 'center', 'campus',
        ];
        $aliases = [
            'city'  => self::parse_aliases($cfg->aliases_city ?? '', ['city', 'ville', 'location']),
            'venue' => self::parse_aliases($cfg->aliases_venue ?? '', $venuealiases),
            'room'  => self::parse_aliases($cfg->aliases_room ?? '', ['room', 'salle', 'classroom', 'roomnumber']),
        ];

        $fieldids = ['city' => null, 'venue' => null, 'room' => null];
        if ($DB->get_manager()->table_exists('facetoface_session_field')) {
            $fields = $DB->get_records('facetoface_session_field', null, '', 'id, shortname, name');
            foreach ($fields as $f) {
                $sn = \core_text::strtolower(trim($f->shortname ?? ''));
                $nm = \core_text::strtolower(trim($f->name ?? ''));
                // City.
                if ($fieldids['city'] === null) {
                    if (in_array($sn, $aliases['city'], true) || in_array($nm, $aliases['city'], true)) {
                        $fieldids['city'] = (int)$f->id;
                    } else {
                        foreach ($aliases['city'] as $needle) {
                            if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                                $fieldids['city'] = (int)$f->id;
                                break;
                            }
                        }
                    }
                }
                // Venue.
                if ($fieldids['venue'] === null) {
                    if (in_array($sn, $aliases['venue'], true) || in_array($nm, $aliases['venue'], true)) {
                        $fieldids['venue'] = (int)$f->id;
                    } else {
                        foreach ($aliases['venue'] as $needle) {
                            if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                                $fieldids['venue'] = (int)$f->id;
                                break;
                            }
                        }
                    }
                }
                // Room.
                if ($fieldids['room'] === null) {
                    if (in_array($sn, $aliases['room'], true) || in_array($nm, $aliases['room'], true)) {
                        $fieldids['room'] = (int)$f->id;
                    } else {
                        foreach ($aliases['room'] as $needle) {
                            if (($sn !== '' && strpos($sn, $needle) !== false) || ($nm !== '' && strpos($nm, $needle) !== false)) {
                                $fieldids['room'] = (int)$f->id;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $fieldids;
    }

    /**
     * Get trainers for a list of sessions.
     *
     * @param array $sessions List of session objects (from the main report query).
     * @return array A map of sessionid => [userid1, userid2, ...].
     */
    public static function get_session_trainers(array $sessions): array {
        global $DB;
        $trainers_map = [];

        if (empty($sessions)) {
            return $trainers_map;
        }

        $sessionids = array_map(
            function ($s) {
                return (int)$s->sessionid;
            },
            $sessions
        );
        $courseids = array_map(
            function ($s) {
                return (int)$s->courseid;
            },
            $sessions
        );
        $courseids = array_unique($courseids);

        // Option 1: Try to get trainers from facetoface_session_roles (if available and used).
        if ($DB->get_manager()->table_exists('facetoface_session_roles')) {
            // Get trainer role IDs (editingteacher, teacher, trainer).
            $trainer_role_ids = $DB->get_fieldset_select('role', 'id', "shortname IN ('editingteacher', 'teacher', 'trainer')");

            if (!empty($trainer_role_ids)) {
                [$session_in_sql, $session_in_params] = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED, 'sids');
                [$role_in_sql, $role_in_params] = $DB->get_in_or_equal($trainer_role_ids, SQL_PARAMS_NAMED, 'rids');

                // Get all user name fields required by fullname().
                $user_fields = \core_user\fields::for_name();
                $user_fields_sql = $user_fields->get_sql('u', false, '', '', false);

                // First column must be unique for get_records_sql() - use composite key.
                $f2f_trainers_sql = "
                    SELECT
                        CONCAT(fsr.sessionid, '_', u.id) AS id,
                        fsr.sessionid,
                        u.id AS userid,
                        {$user_fields_sql->selects}
                    FROM
                        {facetoface_session_roles} fsr
                    JOIN
                        {user} u ON u.id = fsr.userid
                    {$user_fields_sql->joins}
                    WHERE
                        fsr.sessionid {$session_in_sql} AND fsr.roleid {$role_in_sql}
                ";
                $f2f_trainers_params = array_merge(
                    $session_in_params,
                    $role_in_params,
                    $user_fields_sql->params
                );

                $f2f_trainers_records = $DB->get_records_sql($f2f_trainers_sql, $f2f_trainers_params);

                $final_trainers_map = [];
                foreach ($f2f_trainers_records as $record) {
                    $final_trainers_map[(int)$record->sessionid][] = $record;
                }

                // If we found trainers via F2F specific table, we prioritize that.
                if (!empty($final_trainers_map)) {
                    return $final_trainers_map;
                }
            }
        }

        // Option 2: Fallback to Moodle core role assignments in course context.
        $trainer_role_ids = $DB->get_fieldset_select('role', 'id', "shortname IN ('editingteacher', 'teacher', 'trainer')");
        if (empty($trainer_role_ids)) {
            return $trainers_map;
        }

        [$course_in_sql, $course_in_params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cids');
        [$role_in_sql, $role_in_params] = $DB->get_in_or_equal($trainer_role_ids, SQL_PARAMS_NAMED, 'rids');

        // Get all user name fields required by fullname().
        $user_fields = \core_user\fields::for_name();
        $user_fields_sql = $user_fields->get_sql('u', false, '', '', false);

        // First column must be unique for get_records_sql() - use composite key with role assignment ID.
        $sql = "
            SELECT
                CONCAT(ra.id, '_', ctx.instanceid) AS id,
                u.id AS userid,
                ctx.instanceid AS courseid,
                {$user_fields_sql->selects}
            FROM
                {role_assignments} ra
            JOIN
                {context} ctx ON ctx.id = ra.contextid
            JOIN
                {user} u ON u.id = ra.userid
            {$user_fields_sql->joins}
            WHERE
                ctx.contextlevel = :contextlevelcourse AND
                ctx.instanceid {$course_in_sql} AND
                ra.roleid {$role_in_sql}
        ";
        $params = array_merge(
            $course_in_params,
            $role_in_params,
            $user_fields_sql->params,
            ['contextlevelcourse' => CONTEXT_COURSE]
        );

        $raw_course_trainers = $DB->get_records_sql($sql, $params);

        $course_to_trainers = [];
        foreach ($raw_course_trainers as $rt) {
            $course_to_trainers[(int)$rt->courseid][] = $rt;
        }

        // Map course trainers to sessions and return user objects directly.
        $final_trainers_map = [];
        foreach ($sessions as $session) {
            $session_id = (int)$session->sessionid;
            $courseid = (int)$session->courseid;
            if (isset($course_to_trainers[$courseid])) {
                $final_trainers_map[$session_id] = $course_to_trainers[$courseid];
            }
        }

        return $final_trainers_map;
    }

    /**
     * Build the SQL query for the sessions table.
     *
     * @param array $filters The filters to apply.
     * @param array $fieldids The field IDs for city, venue, and room.
     * @return array An array containing [fields, from, where, params, countsql].
     */
    public function build_sql(array $filters, array $fieldids): array {
        global $DB;

        $sessionscols   = $DB->get_columns('facetoface_sessions');
        $hasdirectdates = isset($sessionscols['timestart']) && isset($sessionscols['timefinish']);

        $timestartcol  = $hasdirectdates ? 's.timestart' : 'sd.timestart';
        $timefinishcol = $hasdirectdates ? 's.timefinish' : 'sd.timefinish';

        $fields = "
            s.id AS id,
            c.id AS courseid,
            c.fullname AS coursename,
            s.id AS sessionid,
            {$timestartcol} AS timestart,
            {$timefinishcol} AS timefinish,
            COALESCE(dcity.data,  :ns_city)   AS city,
            COALESCE(dvenue.data, :ns_venue)  AS venue,
            COALESCE(droom.data,  :ns_room)   AS room,
            COALESCE(su.participants, 0) AS totalparticipants,
            COALESCE(att.presentcount, 0) AS presentcount
        ";

        $from = "
            {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
        ";

        if (!$hasdirectdates) {
            $from .= "
                LEFT JOIN (
                    SELECT sessionid, MIN(timestart) AS timestart, MAX(timefinish) AS timefinish
                      FROM {facetoface_sessions_dates}
                  GROUP BY sessionid
                ) sd ON sd.sessionid = s.id
            ";
        }

        $from .= "
            LEFT JOIN {facetoface_session_data} dcity
                ON dcity.sessionid = s.id AND dcity.fieldid = :cityfieldid
            LEFT JOIN {facetoface_session_data} dvenue
                ON dvenue.sessionid = s.id AND dvenue.fieldid = :venuefieldid
            LEFT JOIN {facetoface_session_data} droom
                ON droom.sessionid = s.id AND droom.fieldid = :roomfieldid

            LEFT JOIN (
                SELECT sessionid, COUNT(1) AS participants
                  FROM {facetoface_signups}
              GROUP BY sessionid
            ) su ON su.sessionid = s.id

            LEFT JOIN (
                SELECT fsu.sessionid, COUNT(1) AS presentcount
                  FROM {facetoface_signups} fsu
                  JOIN (
                        SELECT signupid, MAX(id) AS maxid
                          FROM {facetoface_signups_status}
                      GROUP BY signupid
                  ) last ON last.signupid = fsu.id
                  JOIN {facetoface_signups_status} fss ON fss.id = last.maxid AND fss.statuscode = 100
              GROUP BY fsu.sessionid
            ) att ON att.sessionid = s.id
        ";

        $where  = '1=1';
        $params = [
            'ns_city'   => get_string('notspecified', 'local_f2freport'),
            'ns_venue'  => get_string('notspecified', 'local_f2freport'),
            'ns_room'   => get_string('notspecified', 'local_f2freport'),
            'cityfieldid'  => $fieldids['city'] ?? 0,
            'venuefieldid' => $fieldids['venue'] ?? 0,
            'roomfieldid'  => $fieldids['room'] ?? 0,
        ];

        if (!empty($filters['courseid'])) {
            $where .= ' AND f.course = :courseid';
            $params['courseid'] = (int)$filters['courseid'];
        }

        $now = time();
        if (!empty($filters['futureonly'])) {
            $where .= " AND {$timestartcol} >= :now";
            $params['now'] = $now;
        } else {
            if (!empty($filters['datefrom'])) {
                $where .= " AND {$timestartcol} >= :datefrom";
                $params['datefrom'] = (int)$filters['datefrom'];
            }
            if (!empty($filters['dateto'])) {
                $datetoend = (int)$filters['dateto'] + 86399;
                $where .= " AND {$timestartcol} <= :dateto";
                $params['dateto'] = $datetoend;
            }
        }

        $countfrom = "
            {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
        ";
        if (!$hasdirectdates) {
            $countfrom .= "
                LEFT JOIN (
                    SELECT sessionid, MIN(timestart) AS timestart, MAX(timefinish) AS timefinish
                      FROM {facetoface_sessions_dates}
                  GROUP BY sessionid
                ) sd ON sd.sessionid = s.id
            ";
        }
        $countsql = "SELECT COUNT(1) FROM $countfrom WHERE $where";

        return [$fields, $from, $where, $params, $countsql];
    }
}
