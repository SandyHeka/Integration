<?php
namespace local_participation_export\service;
defined('MOODLE_INTERNAL') || die();


class quiz_data_service  {

    public static function get_quiz_attempts() {
        global $DB;
        $DateToday = "2024-02-14";  
        $DateTodayPlus1 = "2025-02-15";
        $sql = "EXEC dbo.GetQuizAttempts ?, ?";

        $params = [
            $DateToday,        // First parameter: Date today
            $DateTodayPlus1    // Second parameter: Date tomorrow
        ];

        error_log($sql); // Log the SQL query to the PHP error log
   
        return $DB->get_records_sql($sql, $params);
    }


}