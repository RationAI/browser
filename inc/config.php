<?php

error_reporting(E_ERROR);
ini_set('display_errors', 1);

//Default Image Server Preview URL Maker (for tif pyramid previews)
$image_preview_url_maker = function ($file) {
    return "";
};

//Url of the Viewer
$viewer_url = "";

// Default language
$lang = 'en';

// Auth with login/password (set true/false to enable/disable it)
$use_auth = false;

// Users: array('Username' => 'Password', 'Username2' => 'Password2', ...), Password has to encripted into MD5
$auth_users = array(
    'root' => '827ccb0eea8a706c4c34a16891f84e7b' //12345
);

// Readonly users (usernames array)
$readonly_users = array(

);

// User-dependent roots (default is $root_path)
$users_root = array(

);

// User-dependent front-end roots (default is $frontend_root_path)
$users_image_server_root = array(

);

// Show or hide files and folders that starts with a dot
$show_hidden_files = false;

// Enable highlight.js (https://highlightjs.org/) on view's page
$use_highlightjs = true;

// highlight.js style
$highlightjs_style = 'vs';

// Enable ace.js (https://ace.c9.io/) on view's page
$edit_files = true;

// Send files though mail
$send_mail = false;

// Send files though mail
$toMailId = ""; //yourmailid@mail.com

// Default timezone for date() and time() - http://php.net/manual/en/timezones.php
$default_timezone = 'Europe/Prague'; // UTC

// Root path for file manager
$root_path = $_SERVER['DOCUMENT_ROOT'];

// Default root for the WSI server
$image_server_root_path = '';

// Root url for links in file manager.Relative to $http_host. Variants: '', 'path/to/subfolder'
// Will not working if $root_path will be outside of server document root
//if front end path and root path differ use a proxy link that will
//translate one url to the other (e.g. using htaccess at SERVER/proxy) and
//files directory with htaccess redirect from front end to root path
// example: RewriteRule $path/to/proxy/(.*)^ /real/absolute/server/url/$1 [L, QSA]
$root_url = '';

// Root url for the source files (JS, assets)
if (!isset($sources_url)) {
    $sources_url = '';
}

// Server hostname. Can set manually if wrong
$http_host = $_SERVER['HTTP_HOST'];

// input encoding for iconv
$iconv_input_encoding = 'UTF-8';

// date() format for file modification date
$datetime_format = 'd.m.y H:i';

// allowed upload file extensions
$upload_extensions = ''; // 'gif,png,jpg'

// show or hide the left side tree view
$show_tree_view = true;

//Array of folders excluded from listing
$GLOBALS['exclude_folders'] = array(
    '.git'
);

$require_auth = false;

//Path to the SQlite tag database file
$tag_store = "/tags.sqlite";
//Path to the SQlite sessions db file
$session_store = "/sessions.sqlite";

// OVERRIDE ALL PROPS WITH USER SETTINGS
if (defined('FM_CONFIG') && is_file(FM_CONFIG) ) {
    include(FM_CONFIG);
}

// DEFINE ALL HARDCODED VALUES
// where are php files to look for
$php_path = $sources_url . '/inc';

// path to this script
$file_path = $sources_url . '/files.php';

// where are assets to look for
$assets_path = $sources_url . '/assets';

// where are js files to look for
$js_path = $sources_url . '/js';

