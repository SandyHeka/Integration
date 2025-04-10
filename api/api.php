<?php
use local_participation_export\service\quiz_data_service;
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/participation_export/db/services.php');
function get_access_token() {
    global $CFG;

    // Check if the required configuration values are available
    if (empty($CFG->client_id) || empty($CFG->client_secret)) {
        throw new Exception("Required configuration values are missing.");
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
      
        $info = curl_getinfo($ch);
        echo "cURL Info:\n";
        print_r($info);
        exit();
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

function get_student_data() {
    global $CFG;

    // Get the access token
    $token_data = get_access_token();
   
    if (!$token_data || empty($token_data['access_token'])) {
        echo "Failed to obtain access token.\n";
        return null;
    }

    $access_token = $token_data['access_token'];
    $api_url = $CFG->base_url . "Api/WS/v1/StudyPlanData/List";
    $records = local_participation_export_services::get_quiz_attempts();

    foreach($records as $data)

    {
        $student_id = $record['stu_id'];
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
            return null;
        }
    
        // Close the cURL session
        curl_close($ch);
    
        // Decode the JSON response
        $data = json_decode($response, true);
      
        // Check if there was an error decoding the response
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "JSON decode error: " . json_last_error_msg();
            return null;
        }
    
        // Return the student data
        
    }
  
    return $data;
 
   
}

try {
    $student_data = get_student_data();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
