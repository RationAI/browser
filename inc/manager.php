<?php

$content = file_get_contents("php://input");

global $global_input;
try {
    $global_input = (array)json_decode($content);
} catch (Exception $e) {
    //pass not a valid input
    $global_input = array();
}

require_once "config.php";
global $use_auth, $default_timezone;

// if fm included
if (defined('FM_EMBED')) {
    $use_auth = false;
} else {
    @set_time_limit(600);

    date_default_timezone_set($default_timezone);

    ini_set('default_charset', 'UTF-8');
    if (version_compare(PHP_VERSION, '5.6.0', '<') && function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    if (function_exists('mb_regex_encoding')) {
        mb_regex_encoding('UTF-8');
    }

    session_cache_limiter('');
    session_name('filemanager');
}
session_start();

if (empty($auth_users)) {
    $use_auth = false;
}

if (isset($_GET["ajax"]) || isset($_POST["ajax"]) || isset($global_input["ajax"])) {
    require_once "ajax.php";
} else {
    require_once "files.php";
}
