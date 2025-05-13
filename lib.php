<?php
use PHPMailer\PHPMailer\PHPMailer;
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


    if (!$records || empty($records['matched']) && empty($records['missing'])) {
        mtrace("No records found for quiz attempt");
        return;
    }
    $matched = $records['matched'];
    $missing = $records['missing'];
    $todayDate = date("Y-m-d");
    $filename = $CFG->dataroot . "/exportData/participation_export_" . $todayDate . ".csv";

   
    if (!empty($matched)) {
        if (file_exists($filename)) unlink($filename);
        $file = fopen($filename, 'w');
        fputcsv($file, ['Unit', 'TimeStarted', 'TimeFinished', 'TimeTaken', 'SSPNumber', 'STU_ID', 'First', 'Last', 'Course', 'ShortName', 'Assignment']);
        foreach ($matched as $r) {
            fputcsv($file, [$r['StudyPackageCode'], $r['Start'], $r['End'], $r['Hours'], $r['StudyPackageVersionNumber'], $r['StudentId'], $r['firstname'], $r['lastname'], $r['course'], $r['shortname'], $r['assignment']]);
        }
        fclose($file);
        mtrace("Matched data exported.");
    }
    if (!empty($missing)) {
        $missingFile = $CFG->dataroot . "/exportData/missing_in_sms_" . $todayDate . ".csv";
        if (file_exists($missingFile)) unlink($missingFile);
        $file = fopen($missingFile, 'w');
        fputcsv($file, ['StudentId', 'First', 'Last', 'Course', 'ShortName', 'Assignment', 'StudyPackageCode']);
        foreach ($missing as $m) {
            fputcsv($file, [$m['StudentId'], $m['firstname'], $m['lastname'], $m['course'], $m['shortname'], $m['assignment'], $m['StudyPackageCode']]);
        }
        fclose($file);
        mtrace("⚠️ Missing users exported.");
    }
    if (!empty($matched) && !empty($missing)) {
        local_participation_email_export_files($filename, $missingFile);
    }
   
}


function send_email_with_attachment(string $toemail, string $subject, string $bodytext, string $bodyhtml, array $attachments = []): bool {
    global $CFG;

    $support = core_user::get_support_user();
    $to = (object)[
        'id' => -1,
        'email' => $toemail,
        'maildisplay' => 1,
        'firstname' => 'Report',
        'lastname' => 'Bot',
        'username' => 'noreply'
    ];

    // Get Moodle's configured emailer
    $mail = get_mailer();

    $mail->addAddress($toemail);
    $mail->setFrom($support->email, fullname($support));
    $mail->Subject = $subject;
    $mail->Body = $bodyhtml;
    $mail->AltBody = $bodytext;
    $mail->isHTML(true);

    foreach ($attachments as $file) {
        if (file_exists($file)) {
            $mail->addAttachment($file);
        }
    }

    if (!$mail->send()) {
        mtrace("Mail error: " . $mail->ErrorInfo);
        return false;
    }

    return true;
}

function local_participation_email_export_files(string $matchedFile, string $missingFile = null): void {
    $recipients = ['sandesh.heka@swtafe.edu.au'];

    $subject = "Moodle Participation Export – " . date('Y-m-d');
    $bodytext = "Attached are the participation exports for today.\n\n• Matched student records\n• Missing (Moodle-only) students\n\nRegards,\nMoodle Admin";
    $bodyhtml = nl2br($bodytext);

    $attachmentFiles = [];
    if (file_exists($matchedFile)) {
        $attachmentFiles[] = $matchedFile;
    }
    if ($missingFile && file_exists($missingFile)) {
        $attachmentFiles[] = $missingFile;
    }

    foreach ($recipients as $email) {
        $success = send_email_with_attachment($email, $subject, $bodytext, $bodyhtml, $attachmentFiles);
        if ($success) {
            mtrace(" Email sent with attachments to $email");
        } else {
            mtrace("Failed to send email to $email");
        }
    }
}
