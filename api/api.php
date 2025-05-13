<?php
// Enable error display for debugging — disable in production
ini_set('display_errors', 1);
error_reporting(E_ALL);
// define('CLI_SCRIPT', true);

require_once(dirname(__DIR__, 2) . '\..\config.php');
require_once(dirname(__DIR__, 1) . '\classes\services\quiz_data_services.php');

/**
 * Retrieves an access token from the external system using client credentials.
 *
 * @return array|null
 * @throws Exception if required credentials are missing.
 */
function get_access_token() {
    global $CFG;


    // Check if the required configuration values are available
    if (empty($CFG->client_id) || empty($CFG->client_secret)) {
        throw new Exception("Missing OAuth credentials");
    }

    $client_id = $CFG->client_id;
    $client_secret = $CFG->client_secret;
    $grant_type = 'client_credentials';
    $token_url =  $CFG->base_url . "oauth2/access_token";

    // Prepare the POST fields for the request
    $post_fields = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => $grant_type
    ];



    // Initialize cURL session
    $ch = curl_init();
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields,'', '&'));

    // Optional: Disable SSL verification (only for testing)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // Optional: Add headers for content type
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    // Execute the cURL request and get the response
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
  
    // Check if the request was successful
    if ($response === false) {
        // Handle error
      
        error_log('Access token cURL error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $response_data = json_decode($response, true);

    // Check if we received an access token
    if (isset($response_data['access_token'])) {
        // Close the cURL session here
        curl_close($ch);
        
        return [
            'access_token' => $response_data['access_token'],
            'expires_in' => $response_data['expires_in']
        ];
    } else {
        // Close the cURL session if no access token
        curl_close($ch);
        
        echo "Error: Access token not received.\n";
        return null;
    }
   
}

/**
 * Fetches participation data from Moodle and validates it with the external system.
 *
 * @return array|null Returns the final prepared data array or null on failure.
 */

function get_student_data() {
    global $CFG;

    // Get the access token
    $token_data = get_access_token();
    $records = \local_participationexport\services\quiz_data_service::get_quiz_attempts();
    if (!$token_data || empty($token_data['access_token'])) {
        error_log("Failed to obtain access token.");
        return null;
    }
    $access_token = $token_data['access_token'];
    $api_url = $CFG->base_url . "Api/WS/v1/StudyPlanData/List";
    $final_data = [];
    $missing_students = [];
    foreach ($records as $unitcode => $record) 
    {
        $student_id = $record->stu_id ?? null;
        if (!$student_id) continue;
        //$student_id = 100135726;  
        // Parameters for the API request
        $params = [
            'pageSize' => 100,
            'page' => 1,
            'p.StudentId' => $student_id
        ];
         // Prepare the full API URL with query parameters
        $getStudentPackage = $api_url . '?' . http_build_query($params,'', '&');
        $ch = curl_init();
   
        // Set cURL options for the API request
        curl_setopt($ch, CURLOPT_URL, $getStudentPackage);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "Accept: application/json"
        ]);
        // Optional: Disable SSL verification (only for testing)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // Execute the cURL request and get the response
        $response = curl_exec($ch);
    
        // Check if the request was successful
        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch);
            curl_close($ch);
           continue;
        }
    
        // Close the cURL session
        curl_close($ch);
    
        // Decode the JSON response
        $data = json_decode($response, true);
        $units = $data['DataSet'] ?? [];

        $matched_units = array_filter($units, function ($unit) use ($unitcode) {
            return isset($unit['spkStudyPackageCode']) && $unit['spkStudyPackageCode'] === $unitcode;
        });
        if (empty($matched_units)) {
            $missing_students[] = [
                'StudentId' => $student_id,
                'firstname' => $record->first,
                'lastname' => $record->last,
                'course' => $record->course,
                'shortname' => $record->shortname,
                'assignment' => $record->assignment,
                'StudyPackageCode' => $unitcode
            ];
            continue;
        }

        if (!isset($data['DataSet']) || !is_array($data['DataSet'])) {
            echo "No DataSet found in response for student ID $student_id\n";
            continue;
        }

        // Filter only matching unitcode
        $matched_units = array_filter($data['DataSet'], function ($unit) use ($unitcode) {
            return isset($unit['spkStudyPackageCode']) && $unit['spkStudyPackageCode'] === $unitcode;
        });

        foreach ($matched_units as $unit) {
            $timestamp = is_numeric($record->timefinished)
                ? (int) $record->timefinished
                : strtotime($record->timefinished);

            $final_data[] = [
                'StudyPackageCode' => $unitcode,
                'Start' => date('c', $timestamp),
                'End' => date('c', $timestamp),
                'Hours' => $record->timetaken,
                'StudentId' => $student_id,
                'StudyPackageVersionNumber' => 1,
                'StudentStudyPackageAttemptNumber' => 1,
                'AttendanceStatus' => 'H',
                'SubType' => '220',
                'Type' => 'PRT',
                'firstname' => $record->first,
                'lastname' => $record->last,
                'course' => $record->course,
                'shortname' => $record->shortname,
                'assignment' => $record->assignment
            ];
        }
    }
  
    return [
        'matched' => $final_data,
        'missing' => $missing_students
    ];
 
   
}


function send_student_participation(){
    global $CFG;

    $participationData = get_student_data();
    if (!$participationData || empty($participationData['matched'])) {
        echo "❌ No participation data to send.\n";
        return null;
    }

    $matched = $participationData['matched'];
    $token_data = get_access_token();
    if (!$token_data || empty($token_data['access_token'])) {
        echo "Failed to obtain access token.\n";
        return null;
    }
    $access_token = $token_data['access_token'];
    $api_url = rtrim($CFG->base_url, '/') . "/Api/WS/v1/Engagements/Import";

      
    // Prepare the final data
    $payload = json_encode(["Items" => $participationData], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    // Check for JSON encoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON encoding error: " . json_last_error_msg();
        return;
    }
    $headers = [
        'Authorization: Bearer ' . trim($access_token),
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($payload)
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
            error_log("❌ Curl error: $error");
            return null;
    } else {
        // Output response for debugging
        echo "Response: " . $response;
        echo "HTTP Status Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE); // Output HTTP status code for better debugging
    }

    // Close the cURL session
    curl_close($ch);
    return $participationData;
    
}

