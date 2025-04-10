<?php
defined('MOODLE_INTERNAL') || die();



require_once(__DIR__. '/classes/api.php');

function local_participation_export_data() {
    global $CFG;

    // $records = local_participation_export_services::get_quiz_attempts();
    $recordsFromSMS = get_student_data();

    echo "SMS Data:\n";
    print_r($recordsFromSMS);

    if (empty($records)) {
        mtrace("No records found for quiz attempt");
        return;
    }

    // $todayDate = date("Y-m-d");
    // $filename = $CFG->dataroot . "/participation_export_" . $todayDate . ".csv";

    // // Corrected: checking for file existence with $filename instead of $existingFilePath
    // if (file_exists($filename)) {
    //     echo "File $filename";
    //     unlink($filename); // Deletes the existing file if it exists
    // }

    // $file = fopen($filename, 'w');

    // // Write the header row
    // fputcsv($file, ['Unit', 'UnitDesc', 'TimeStarted', 'TimeFinished', 'TimeTaken', 'SSPNumber', 'STU_ID', 'First', 'Last', 'Course', 'ShortName', 'Assignment']);

    // // Write data rows
    // foreach ($records as $record) {
        
    //     fputcsv($file, [
    //         $record->unit, $record->unitdesc, $record->timestarted, $record->timefinished, $record->timetaken, $record->sspnumber,
    //         $record->stu_id, $record->first, $record->last, $record->course, $record->shortname, $record->assignment
    //     ]);
    // }

    // fclose($file);
    mtrace("Data exported successfully");
}