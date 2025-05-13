<?php
namespace local_participationexport\services;
defined('MOODLE_INTERNAL') || die();


/**
 * Service class to fetch quiz participation data from Moodle.
 */
class quiz_data_service {
    /**
         * Retrieves finished quiz attempts that match specific criteria,
         * such as being part of a "Commencement" unit and having finished within today's date.
         *
         * @return array List of participation records from the Moodle database.
         */
    public static function get_quiz_attempts() {
        global $DB;
 
        $DateToday ="2024-02-22";
        $DateTodayPlus1 = "2024-02-23" ;
        // $dateToday = date('Y-m-d') "2024-02-22";
        // $dateTodayPlus1 = date('Y-m-d', strtotime('+1 day')) "2024-02-23";

        $sql = "
            SELECT
                SUBSTRING(q.name, 1, CHARINDEX(' ', q.name) - 1) AS Unit,
                SUBSTRING(q.name, CHARINDEX(' ', q.name) + 1, 
                    CHARINDEX(' ', q.name + ' ', CHARINDEX(' ', q.name) + 1) - CHARINDEX(' ', q.name) - 1) AS UnitDesc,
                DATEADD(SECOND, qa.timestart, '1970-01-01 00:00:00') AS TimeStarted,
                DATEADD(SECOND, qa.timefinish, '1970-01-01 00:00:00') AS TimeFinished,
                DATEDIFF(MINUTE, DATEADD(SECOND, qa.timestart, '1970-01-01 00:00:00'), DATEADD(SECOND, qa.timefinish, '1970-01-01 00:00:00')) AS TimeTaken,
                qa.timestart AS SSPNumber,
                CASE WHEN LEN(u.username) > 12 THEN LEFT(u.username, 12) ELSE u.username END AS STU_ID,
                u.firstname AS First,
                u.lastname AS Last,
                c.fullname AS Course,
                c.shortname AS ShortName,
                q.name AS Assignment
            FROM mdl_quiz_attempts AS qa
            JOIN mdl_quiz AS q ON q.id = qa.quiz
            JOIN mdl_user AS u ON u.id = qa.userid
            JOIN mdl_course AS c ON c.id = q.course
            WHERE qa.state = 'finished'
                AND DATEADD(SECOND, qa.timestart, '1970-01-01 00:00:00') >= ?
                AND DATEADD(SECOND, qa.timestart, '1970-01-01 00:00:00') < ?
                AND DATEADD(SECOND, qa.timefinish, '1970-01-01 00:00:00') > '2024-01-01'
                AND SUBSTRING(q.name, CHARINDEX(' ', q.name) + 1, 
                    CHARINDEX(' ', q.name + ' ', CHARINDEX(' ', q.name) + 1) - CHARINDEX(' ', q.name) - 1) = 'Commencement'
            ORDER BY TimeStarted DESC;
        ";
    

        $params = [
            $DateToday,        // First parameter: Date today
            $DateTodayPlus1    // Second parameter: Date tomorrow
        ];
        $results=    $DB->get_records_sql($sql, $params);
    
    
        error_log($sql); // Log the SQL query to the PHP error log
   
        return $results;
    }

}
