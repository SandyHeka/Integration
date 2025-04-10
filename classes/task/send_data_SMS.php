<?php
namespace local_participation_export\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/participation_export/lib.php');
class send_data_SMS extends \core\task\scheduled_task{
    public function get_name(){
        return get_string('send_data_sms', 'local_particiaptionexport');

    }

    public function execute(){
        if (function_exists('local_participation_export_data')) {
            local_participation_export_data();  // Call the function
        } else {
            echo "Function 'local_participation_export_data' not found!";
        }
    }
}