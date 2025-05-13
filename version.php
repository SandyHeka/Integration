<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_participationexport'; // ✅ Must match folder name

$plugin->version   = 2025042309; // YYYYMMDDXX - today's date + increment
$plugin->requires  = 2023041900; // Requires Moodle 4.0+
$plugin->maturity  = MATURITY_STABLE; // STABLE, ALPHA, BETA, RC
$plugin->release   = '1.0.0'; // Human-readable version