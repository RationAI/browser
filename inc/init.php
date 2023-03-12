<?php

// Make sure defined, if included directly, define as if included from root
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '../');
}

// Parse input data
global $_DATA;
if (count($_POST)) {
    $_DATA = $_POST;
} else {
    try {
        $_DATA = (array)json_decode(file_get_contents("php://input")) ?? [];
    } catch (Exception $e) {
        //pass not a valid input
        $_DATA = [];
    }
}

require_once PATH_TO_IS_MANAGER . "inc/config.php";
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

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

require_once PATH_TO_IS_MANAGER . "inc/functions.php";
require_once XO_DB_ROOT . "include.php";

//todo make these constants
global $is_https, $is_logged, $user, $user_id, $root_path, $root_url, $http_host, $show_hidden_files;

$is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';

$is_logged = isset($_SESSION['logged']);
$user = $is_logged ? $_SESSION['logged'] : [];
$user_id = $is_logged ? $user["id"] : -1;
$rel_root_path = rtrim($is_logged ? ($user["root"] ?? "") : "", '\\/');
$root_path .= $rel_root_path;
$root_path = str_replace('\\', '/', $root_path);
if (!@is_dir($root_path)) {
    echo "<h1>Root path \"{$root_path}\" not found!</h1>";
    exit;
}
$image_server_root_url = fm_clean_path($rel_root_path);
$root_url = fm_clean_path($root_url);

// abs path for site
defined('FM_SHOW_HIDDEN') || define('FM_SHOW_HIDDEN', $show_hidden_files);
defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
defined('FM_WSI_SERVER_PATH') || define('FM_WSI_SERVER_PATH', $image_server_root_url);
defined('FM_ROOT_URL') || define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . (!empty($root_url) ? '/' . $root_url : ''));
defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . $_SERVER['PHP_SELF']);

// logout
if (isset($_GET['logout'])) {
    unset($_SESSION['logged']);
    unset($_SESSION['token']);
    fm_redirect(FM_SELF_URL);
}



//todo support this feature
const FM_READONLY = false;
const FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';



// get path
$p = $_GET['p'] ?? "";

// get search string
$s = $_GET['s'] ?? "";

// clean path
$p = fm_clean_path($p);

// instead globals vars
define('FM_PATH', $p);
define('FM_USE_AUTH', $use_auth);
define('FM_SEARCH_QUERY', $s);

defined('FM_LANG') || define('FM_LANG', $lang);
defined('FM_EXTENSION') || define('FM_EXTENSION', $upload_extensions);
defined('FM_TREEVIEW') || define('FM_TREEVIEW', $show_tree_view);


defined('FM_ICONV_INPUT_ENC') || define('FM_ICONV_INPUT_ENC', $iconv_input_encoding);
defined('FM_USE_HIGHLIGHTJS') || define('FM_USE_HIGHLIGHTJS', $use_highlightjs);
defined('FM_HIGHLIGHTJS_STYLE') || define('FM_HIGHLIGHTJS_STYLE', $highlightjs_style);
defined('FM_DATETIME_FORMAT') || define('FM_DATETIME_FORMAT', $datetime_format);

unset($p, $s, $use_auth, $iconv_input_encoding, $use_highlightjs, $highlightjs_style);
