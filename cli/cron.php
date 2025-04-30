<?php
// CLI script flag
define('CLI_SCRIPT', true);

// Load Moodle config and environment
require(__DIR__ . '/../../../config.php');

// Load the task class directly
require_once($CFG->dirroot . '/local/participationexport/classes/task/send_data_sms.php');

use local_participationexport\task\send_data_sms;

mtrace("🚀 Running: send_data_sms");

try {
    $task = new send_data_sms();
    $task->execute();
    mtrace("✅ Task completed.");
} catch (Throwable $e) {
    // Throwable catches both Exception and Error
    mtrace("❌ Task failed: " . $e->getMessage());
}
