<?php
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$apiaccesstoken = get_config('local_wsafci', 'api_token'); // API Access token
$pString = "action=login_user&id=123&username=student1&firstname=Student1&lastname=Patel&email=student1@gmail.com&coursename=AA1&onstartdate=20170109&isenroll=0";
$encpString = trim(encryptDecryptStringRes('encrypt', trim($pString)));

## Post array details : 
$postDataArray = array(
    'api_token' => '6e8b83adf0cee5k258599a242949dbd7294c7c6',
    'data' => $encpString,
    'is_encoded' => 1
);

echo $dcryptResAry = encryptDecryptStringRes('decrypt', $encpString);

echo "<pre>";
print_r($postDataArray);
echo "</pre>";

echo "<br/>TEST URL:<br/> $CFG->httpswwwroot/local/wsafci/client/service.php?api_token=$apiaccesstoken&data=$encpString";