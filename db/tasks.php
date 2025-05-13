<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_participationexport\\task\\send_data_sms',
        'blocking' => 0,
        'minute' => '0',     // At minute 0
        'hour' => '0',       // At hour 0 => Midnight
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
);

// $tasks = array(
//     array(
//         'classname' => 'local_participationexport\\task\\send_data_sms',
//         'blocking' => 0,
//         'minute' => '0',       // Runs at minute 0
//         'hour' => '0',         // Runs at hour 0 (midnight)
//         'day' => '*',          // Every day
//         'month' => '*',
//         'dayofweek' => '*'
//     ),
// );