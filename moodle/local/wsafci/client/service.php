<?php

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');

$api_token = optional_param('api_token', null, PARAM_RAW);
$data = optional_param('data', null, PARAM_RAW);
$is_encoded = optional_param('is_encoded', 1, PARAM_INT);

// Initializing params array.
$params = array();

// Get API access token value.
$api_access_token = get_config('local_wsafci', 'api_token');

// Decrypt request data.
$dcryptParamsAry = encryptDecryptStringRes('decrypt', $data);
parse_str($dcryptParamsAry, $params);

if ($api_access_token != $api_token) {
    $data = array(
        'status' => 'fail',
        'code' => (int) 100,
        'message' => 'Invalid access token',
        'data' => array()
    );

    $json = json_encode($data, JSON_PRETTY_PRINT);
    echo $json;
    die;
} else {
    if (isset($params['action']) && $params['action'] == 'login_user') {
        require_once($CFG->dirroot . "/user/lib.php");
        require_once($CFG->libdir . "/enrollib.php");
        require_once($CFG->libdir . "/moodlelib.php");

        //20160930 - onstartdate
        $response = local_wsafci_process_login($params);

        if ($response['code'] == 100) {
            $data = array(
                'status' => 'fail',
                'code' => (int) 100,
                'message' => isset($response['errormessage']) && !empty($response['errormessage']) ? $response['errormessage'] : '',
                'redirecturl' => isset($response['redirecturl']) && !empty($response['redirecturl']) ? $response['redirecturl'] : '',
                'data' => array()
            );
        } else {
            $data = array(
                'status' => 'success',
                'code' => (int) 200,
                'message' => isset($response['successmessage']) && !empty($response['successmessage']) ? $response['successmessage'] : '',
                'redirecturl' => isset($response['redirecturl']) && !empty($response['redirecturl']) ? $response['redirecturl'] : '',
                'data' => array()
            );
        }

        $json = json_encode($data, JSON_PRETTY_PRINT);

        echo $json;
        die;
    } else {
        $data = array(
            'status' => 'fail',
            'code' => (int) 100,
            'message' => 'Invalid method call',
            'data' => array()
        );

        $json = json_encode($data, JSON_PRETTY_PRINT);
        echo $json;
        die;
    }
}