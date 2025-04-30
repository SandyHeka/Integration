<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_participationexport\\task\\send_data_sms',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
);