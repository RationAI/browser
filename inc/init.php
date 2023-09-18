<?php

//We
//relative difference of index.php wrt. file manager
if (!defined('PATH_TO_IS_MANAGER')) {
    die("Cannot include the file directly without providing PATH_TO_IS_MANAGER global variable with path to the browser repository root.");
}

//for debug see what's going on
//if (FM_DEBUG) {
    set_exception_handler(function ($exception) {
        echo "Uncaught exception: " , $exception->getMessage(), "\n";
        var_dump($exception->getTraceAsString());
    });
//}

//custom configuration file
if (!defined('FM_CONFIG') && is_file(PATH_TO_IS_MANAGER . 'config.php')) {
    define('FM_CONFIG', PATH_TO_IS_MANAGER . 'config.php');
}

/**
 * Safely write data
 */
function file_safe_put_contents($filename, $data, $flags = 0, $context = null) {
    $tmp_file = ".$filename~";
    if (file_put_contents($tmp_file, $data, $flags, $context) === strlen($data)) {
        return rename($tmp_file, $filename, $context);
    }
    @unlink($tmp_file, $context);
    return false;
}

/**
 * Clean path
 * @param string $path
 * @return string
 */
function fm_clean_path($path)
{
    $path = $path ? trim($path) : "";
    $path = trim($path, '\\/');
    $path = str_replace(array('../', '..\\'), '', $path);
    if ($path == '..') {
        $path = '';
    }
    return str_replace('\\', '/', $path);
}

require_once PATH_TO_IS_MANAGER . "inc/config.php";
define('USES_DATABASE', boolval(XO_DB_ROOT));

// Parse input data
if (!count($_POST)) {
    try {
        $_POST = (array)json_decode(file_get_contents("php://input"), true) ?? [];
    } catch (Exception $e) {
        //pass not a valid input
        $_POST = [];
    }
}

// if fm included
if (defined('FM_EMBED')) {
    define('_FM_USE_AUTH', false);
} else {
    @set_time_limit(600);

    date_default_timezone_set(FM_DEFAULT_TIMEZONE);

    ini_set('default_charset', 'UTF-8');
    if (version_compare(PHP_VERSION, '5.6.0', '<') && function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    if (function_exists('mb_regex_encoding')) {
        mb_regex_encoding('UTF-8');
    }

    session_cache_limiter('');
    session_name('filemanager');

    define('_FM_USE_AUTH', FM_USE_AUTH && USES_DATABASE);
}
session_start();

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

require_once PATH_TO_IS_MANAGER . "inc/functions.php";

$is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';

define('FM_LOGGED', isset($_SESSION['logged']));
define('FM_USER', FM_LOGGED ? $_SESSION['logged'] : []);
define('FM_USER_ID', FM_LOGGED ? FM_USER["id"] : -1);


$rel_root_path = rtrim(FM_LOGGED ? (FM_USER["root"] ?? "") : "", '\\/');
$root_path = FM_BROWSE_ROOT . $rel_root_path;
$root_path = str_replace('\\', '/', $root_path);
if (!@is_dir($root_path)) {
    echo "<h1>Root path \"{$root_path}\" not found!</h1>";
    exit;
}
$image_server_root_url = fm_clean_path($rel_root_path);
$rel_path = fm_clean_path(FM_HTTP_PATH);

// abs path for site
defined('FM_SHOW_HIDDEN') || define('FM_SHOW_HIDDEN', FM_SHOW_HIDDEN_FILES);
defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
defined('FM_WSI_SERVER_PATH') || define('FM_WSI_SERVER_PATH', $image_server_root_url);
define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . FM_HTTP_HOST . (!empty($rel_path) ? '/' . $rel_path : ''));
defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . FM_HTTP_HOST . $_SERVER['PHP_SELF']);

// logout
if (isset($_GET['logout'])) {
    unset($_SESSION['logged']);
    unset($_SESSION['token']);
    fm_redirect(FM_SELF_URL);
}



//todo support this feature
const FM_READONLY = true;
const FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';

// get path
$p = $_GET['p'] ?? "";

// get search string
$s = $_GET['s'] ?? "";

// clean path
$p = fm_clean_path($p);

// instead globals vars
define('FM_PATH', $p);
define('FM_SEARCH_QUERY', $s);

defined('FM_TREEVIEW') || define('FM_TREEVIEW', false); //todo support?

unset($p, $s, $use_highlightjs, $highlightjs_style);

