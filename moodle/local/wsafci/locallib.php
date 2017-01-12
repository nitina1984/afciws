<?php
/**
 * All the plugin specific functions should go to here.
 *
 * @package   local-wsafci
 * @copyright Nitin Agrawal <nitinagrawal.mca@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function local_wsafci_process_login($params) {
    global $CFG, $DB, $USER;

    $idnumber = (int) isset($params['id']) && !empty($params['id']) ? $params['id'] : null;
    $username = (string) isset($params['username']) && !empty($params['username']) ? $params['username'] : null;
    $firstname = (string) isset($params['firstname']) && !empty($params['firstname']) ? $params['firstname'] : null;
    $lastname = (string) isset($params['lastname']) && !empty($params['lastname']) ? $params['lastname'] : null;
    $email = (string) isset($params['email']) && !empty($params['email']) ? $params['email'] : null;
    $coursename = (string) isset($params['coursename']) && !empty($params['coursename']) ? $params['coursename'] : null;
    $onstartdate = isset($params['onstartdate']) && !empty($params['onstartdate']) ? $params['onstartdate'] : null;
    $isenroll = (int) isset($params['isenroll']) && !empty($params['isenroll']) ? $params['isenroll'] : 0;

    // Initializing variable.
    $proceed = true;
    $errormessage = "";
    $studentid = null;
    $code = 100;
    $auth = 'manual';

    // Initializing response array.
    $response = array();

    // Set default password.
    $password = 'Password1!';

    // Validating input.
    if (!isset($idnumber) || trim($idnumber) == "") {
        $errormessage = get_string("invalididnumber", "local_wsafci");
        $proceed = false;
    } else if (!isset($username) || trim($username) == "") {
        $errormessage = get_string("invalidusername", "local_wsafci");
        $proceed = false;
    } else if ($username !== strtolower($username)) {
        $errormessage = get_string("usernamelowercase");
        $proceed = false;
    } else if ($username !== clean_param($username, PARAM_USERNAME)) {
        $errormessage = get_string("invalidusername");
        $proceed = false;
    } else if (!isset($firstname) || trim($firstname) == "" || is_numeric($firstname)) {
        $errormessage = get_string("invalidfirstname", "local_wsafci");
        $proceed = false;
    } else if (!isset($lastname) || trim($lastname) == "" || is_numeric($lastname)) {
        $errormessage = get_string("invalidlastname", "local_wsafci");
        $proceed = false;
    } else if (!validate_email($email)) {
        $errormessage = get_string("invalidemailaddress", "local_wsafci");
        $proceed = false;
    }

    if ($isenroll == 1) {
        if (empty($coursename)) {
            $errormessage = get_string("invalidcoursename", "local_wsafci");
            $proceed = false;
        } else if (empty($onstartdate)) {
            $errormessage = get_string("invalidstartdate", "local_wsafci");
            $proceed = false;
        }
    }

    // Need to proceed if no error found.
    if ($proceed) {
        // Checking if student already exist at moodle instance.
        if ($DB->record_exists('user', array("username" => $username))) {
            try {
                // Start Database Transactions.
                $transaction = $DB->start_delegated_transaction();

                $studentid = $DB->get_field('user', 'id', array('username' => $username));

                // Initializing student object.
                $student = new stdClass();

                // Creating student info.
                $student->id = $studentid;
                $student->auth = $auth;
                $student->username = $username;
                $student->firstname = $firstname;
                $student->lastname = $lastname;
                $student->password = hash_internal_user_password($password);
                $student->email = $email;
                $student->idnumber = $idnumber;
                $student->confirmed = 1;
                $student->mnethostid = 1;
                $student->timecreated = time();

                user_update_user($student, false, false);

                // Commit code.
                $transaction->allow_commit();
                $code = 200;
            } catch (Exception $e) {
                $code = 100;
                $errormessage = $e->getMessage();
            }
        } else {
            try {
                // Start Database Transactions.
                $transaction = $DB->start_delegated_transaction();

                // Creating student info.
                $student = new stdClass();
                $student->auth = $auth;
                $student->username = $username;
                $student->firstname = $firstname;
                $student->lastname = $lastname;
                $student->password = hash_internal_user_password($password);
                $student->email = $email;
                $student->idnumber = $idnumber;
                $student->confirmed = 1;
                $student->mnethostid = 1;
                $student->timecreated = time();

                $studentid = user_create_user($student, false, false);

                if (!$studentid) {
                    $errormessage = get_string("studentnotcreated", "local_wsafci");
                    $code = 100;
                } else {
                    $code = 200;
                }

                // Commit code.
                $transaction->allow_commit();
            } catch (Exception $e) {
                $code = 100;
                $errormessage = $e->getMessage();
            }
        }
    }

    if ($code != 100) {
        if (isset($coursename) && !empty($coursename)) {
            //echo $onstartdate;
            //$date = date('F d, Y', $onstartdate);
            //20160822
            //echo $onstartdate;
            $onstartdateyear = substr($onstartdate, 0, 4);
            $onstartdatemonth = substr($onstartdate, 4, 2);
            $onstartdateday = substr($onstartdate, 6, 2);
            $onstartdatetime = mktime(0, 0, 0, $onstartdatemonth, $onstartdateday, $onstartdateyear);
            $compareonstartdate = date('F d, Y', $onstartdatetime);
            //echo $onstartdateyear."--".$onstartdatemonth."--".$onstartdateday;exit;

            $comparecoursename = "$coursename - $compareonstartdate";

            //SELECT id FROM mdl_course WHERE shortname LIKE 'AA1 - August 22, 2016'- Shortname format in Moodle must be AA1 - August 22, 2016
            $coursesql = "SELECT id FROM {$CFG->prefix}course WHERE shortname LIKE '$comparecoursename'";  //AA1 - August 31, 2015
            $courseid = $DB->get_field_sql($coursesql);

            if ($courseid) {
                if (!enrol_is_enabled('manual')) {
                    $errormessage = get_string("manualenrolmentfail", "local_wsafci");
                    $proceed = false;
                }

                if (!$enrol = enrol_get_plugin('manual')) {
                    $errormessage = get_string("manualenrolmentfail", "local_wsafci");
                    $proceed = false;
                }

                if (!$instances = $DB->get_records('enrol', array('enrol' => 'manual', 'courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED), 'sortorder,id ASC')) {
                    $errormessage = get_string("manualenrolmentfail", "local_wsafci");
                    $proceed = false;
                }

                // Need to proceed if no error found.
                if ($proceed) {
                    // Enrolment of user into course.
                    try {
                        // Start Database Transactions.
                        $transaction = $DB->start_delegated_transaction();

                        $instance = reset($instances);

                        $roleid = 5;

                        $context = context_course::instance($courseid);

                        $userroleids = array();
                        if ($roles = get_user_roles($context, $studentid, false)) {
                            foreach ($roles as $role) {
                                $userroleids[] = (int) $role->roleid;
                            }
                        }

                        if (!in_array($roleid, $userroleids)) {
                            $starttime = $onstartdatetime;
                            $endtime = 0;
                            $enrol->enrol_user($instance, $studentid, $roleid, $starttime, $endtime);
                        }

                        // Commit code.
                        $transaction->allow_commit();
                    } catch (Exception $e) {
                        $errormessage = $e->getMessage();
                        $code = 100;
                    }
                } else {
                    $code = 100;
                }

                $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
            } else {
                $code = 100;
                $errormessage = get_string("coursenotexist", "local_wsafci");
            }
        } else {
            $roleid = 5;
            $systemcontext = context_system::instance();
            role_assign($roleid, $studentid, $systemcontext->id);
            $redirecturl = $CFG->httpswwwroot;
        }
    }

    if ($code == 100) {
        $response['code'] = 100;
        $response['errormessage'] = $errormessage;

        return $response;
    } else {
        if ($isenroll == 0) {
            $student = $DB->get_record('user', array('id' => $studentid));
            complete_user_login($student);
            redirect($redirecturl);
            die;
            //header("HTTP/1.1 301 Moved Permanently");
            //header("Location: $redirecturl");
            //redirect(rawurlencode($redirecturl));
            $response['code'] = 200;
            $response['successmessage'] = get_string("enrolmentsuccess", "local_wsafci");
            $response['redirecturl'] = rawurlencode($redirecturl);

            return $response;
        } else {
            $response['code'] = 200;
            $response['successmessage'] = get_string("enrolmentsuccess", "local_wsafci");

            return $response;
        }
    }
}

function local_wsafci_get_course_status($courseid, $userid) {
    global $CFG, $DB, $USER;

    require_once("{$CFG->libdir}/completionlib.php");

    $status = null;
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    // Get course completion data.
    $info = new completion_info($course);

    // Load criteria to display.
    $completions = $info->get_completions($userid);

    // Flag to set if current completion data is inconsistent with what is stored in the database.
    $pendingupdate = false;

    // For aggregating activity completion.
    $activities = array();
    $activitiescomplete = 0;

    // For aggregating course prerequisites.
    $prerequisites = array();
    $prerequisitescomplete = 0;

    // Loop through course criteria.
    foreach ($completions as $completion) {
        $criteria = $completion->get_criteria();
        $complete = $completion->is_complete();

        if (!$pendingupdate && $criteria->is_pending($completion)) {
            $pendingupdate = true;
        }

        // Activities are a special case, so cache them and leave them till last.
        if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
            $activities[$criteria->moduleinstance] = $complete;

            if ($complete) {
                $activitiescomplete++;
            }

            continue;
        }

        // Prerequisites are also a special case, so cache them and leave them till last.
        if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_COURSE) {
            $prerequisites[$criteria->courseinstance] = $complete;

            if ($complete) {
                $prerequisitescomplete++;
            }

            continue;
        }
    }

    // Is course complete?
    $coursecomplete = $info->is_course_complete($userid);

    // Load course completion.
    $params = array(
        'userid' => $userid,
        'course' => $courseid
    );

    $ccompletion = new completion_completion($params);

    // Has this user completed any criteria?
    $criteriacomplete = $info->count_course_user_data($userid);

    if ($pendingupdate) {
        $status = get_string('pending', 'completion');
    } else if ($coursecomplete) {
        $status = get_string('complete');
    } else if (!$criteriacomplete && !$ccompletion->timestarted) {
        $status = get_string('notyetstarted', 'completion');
    } else {
        $status = get_string('inprogress', 'completion');
    }

    return $status;
}

function local_wsafci_send_userprogress() {
    global $CFG, $DB;

    $midnight = strtotime("midnight");

    $coursecompletionsql = "SELECT * FROM {$CFG->prefix}course_completions WHERE timecompleted > $midnight";
    $records = $DB->get_records_sql($coursecompletionsql);

    $wpscripturl = get_config('local_wsafci', 'wp_ajax_script_url');
    $apiaccesstoken = get_config('local_wsafci', 'api_token'); // API Access token
    $status = get_string('complete');

    if ($records) {
        foreach ($records as $record) {
            $student = $DB->get_record('user', array('id' => $record->userid));
            $course = $DB->get_record('course', array('id' => $record->course), '*', MUST_EXIST);

            // Calling remote script and send progress information
            ## Post array details.
            $id = $student->idnumber;
            $coursename = $course->shortname;
            $timecompleted = $record->timecompleted;
            $updatefrommoodle = 1;

            $pstring = "id=$id&coursename=$coursename&status=$status&timecompleted=$timecompleted&update_from_moodle=1";
            $encpstring = trim(encryptDecryptStringRes('encrypt', trim($pstring)));
            $postdataarray = array(
                'api_token' => $apiaccesstoken,
                'data' => $encpstring,
                'is_encoded' => 1
            );

            ## Calling the remote script.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $wpscripturl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdataarray);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            //SSL Settings
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            
            $result = curl_exec($ch);
            curl_close($ch);
            //$respData = json_decode(trim($result));
        }
    }
}

function encryptDecryptStringRes($action, $string) {
    $output = false;

    $encryptmethod = "AES-256-CBC";
    $secretkey = '012abc345fed';
    $secretiv = '456pqr132lap';

    // hash
    $key = hash('sha256', $secretkey);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secretiv), 0, 16);

    if( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encryptmethod, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
        $output = openssl_decrypt(base64_decode($string), $encryptmethod, $key, 0, $iv);
    }

    return $output;
}