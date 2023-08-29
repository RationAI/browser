<?php
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '../');
}
require_once PATH_TO_IS_MANAGER . 'ajax/ajax_init.php';
//if we got here user already has rights to access, handled by ajax init

if (!_FM_USE_AUTH) {
    //todo create 'incognito' user to act as zero user if system works without auth 'requires ID for RW operations'
    send_ok();
}

//keep session alive
$_SESSION['logged'] = $_SESSION['logged'];
//we re-use auth event for logging that the particular user visited this file
try {
    $file = $_POST["metadata"] ?? [];
    $session = $file["session"] ?? null;
    if ($session) {
        xo_file_seen_by(basename($session), FM_USER_ID);
    } else {
        send_ok([...FM_USER, "message"=>"No file to visit record!"]);
    }
} catch (Exception $e) {
    send_ok([...FM_USER, "message"=>"Unable to record visiting $file!", "error"=>$e]);
}
send_ok(FM_USER);
