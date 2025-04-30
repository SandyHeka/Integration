<?php
defined('MOODLE_INTERNAL') || die();



require_once(__DIR__. '/api/api.php');
/**
 * This function is executed by the scheduled task.
 * It calls the data aggregation service, and writes the output to a CSV file.
 */
function local_participation_export_data(): void  {
    global $CFG;

    // $records = local_participation_export_services::get_student_data();
    $records = send_student_participation();


    if (empty($records)) {
        mtrace("No records found for quiz attempt");
        return;
    }

    $todayDate = date("Y-m-d");
    $filename = $CFG->dataroot . "/exportData/participation_export_" . $todayDate . ".csv";

    // Corrected: checking for file existence with $filename instead of $existingFilePath
    if (file_exists($filename)) {
        mtrace("Removing existing file: $filename");
        unlink($filename); // Deletes the existing file if it exists
    }

    $file = fopen($filename, 'w');

    // Write the header row
    fputcsv($file, ['Unit',  'TimeStarted', 'TimeFinished', 'TimeTaken', 'SSPNumber', 'STU_ID', 'First', 'Last', 'Course', 'ShortName', 'Assignment']);

    // Write data rows
    foreach ($records as $record) {
        mtrace("Writing row for student ID: {$record['StudentId']}");
      
        fputcsv($file, [
            $record['StudyPackageCode'],
            $record['Start'],
            $record['End'],
            $record['Hours'],
            $record['StudyPackageVersionNumber'],
            $record['StudentId'],
            $record['firstname'],
            $record['lastname'],
            $record['course'],
            $record['shortname'],
            $record['assignment']
        ]);
    }
    

    fclose($file);
    mtrace("Data exported successfully");
}