<?php
namespace local_participationexport\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/participationexport/lib.php');
/**
 * Scheduled task that runs every 24 hours to send quiz participation data
 * to an external Student Management System (SMS).
 */
class send_data_sms extends \core\task\scheduled_task{
    /**
     * Returns the name of the scheduled task to be shown in the admin UI.
     *
     * @return string
     */
    public function get_name(){
        return get_string('send_data_sms', 'local_participationexport');


    }
    /**
     * The code to be executed by this scheduled task.
     * Calls the `send_student_participation()` function from lib.php.
     */
    public function execute(){
        if (function_exists('local_participation_export_data')) {
            local_participation_export_data();  // Call the function
        } else {
            mtrace("Function 'local_participation_export_data' not found!");
        }
    }
}