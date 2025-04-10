<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/cronlib.php');

$task = new \local_participation_export\task\send_data_sms();
$task->execute();
