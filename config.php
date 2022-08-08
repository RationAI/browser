<?php

define('ROOT_FOLDER', '.');

// Default language
$lang = 'en';

// Auth with login/password (set true/false to enable/disable it)
$use_auth = false;

// Users: array('Username' => 'Password', 'Username2' => 'Password2', ...), Password has to encripted into MD5
$auth_users = array(
    'root' => '827ccb0eea8a706c4c34a16891f84e7b', //12345
    'user1' => '827ccb0eea8a706c4c34a16891f84e7b', //12345
);

// Readonly users (usernames array)
$readonly_users = array(
    'user'
);

// Show or hide files and folders that starts with a dot
$show_hidden_files = false;

// Enable highlight.js (https://highlightjs.org/) on view's page
$use_highlightjs = true;

// highlight.js style
$highlightjs_style = 'vs';

// Enable ace.js (https://ace.c9.io/) on view's page
$edit_files = false;

// Send files though mail
$send_mail = false;

// Send files though mail
$toMailId = ""; //yourmailid@mail.com

// Default timezone for date() and time() - http://php.net/manual/en/timezones.php
$default_timezone = 'Europe/Prague'; // UTC

// Root path for file manager
$root_path = '../../';

// Root url for links in file manager.Relative to $http_host. Variants: '', 'path/to/subfolder'
// Will not working if $root_path will be outside of server document root
$root_url = '';

// Server hostname. Can set manually if wrong
$http_host = $_SERVER['HTTP_HOST'];

// input encoding for iconv
$iconv_input_encoding = 'UTF-8';

// date() format for file modification date
$datetime_format = 'd.m.y H:i';

// allowed upload file extensions
$upload_extensions = ''; // 'gif,png,jpg'

// show or hide the left side tree view
$show_tree_view = false;

// where are php files to look for
$php_path = ROOT_FOLDER . '/inc';

// absolute path to this script
$file_path = $php_path . '/files.php';

// where are assets to look for
$assets_path = ROOT_FOLDER . '/assets';

// where are js files to look for
$js_path = ROOT_FOLDER . '/js';

//Array of folders excluded from listing
$GLOBALS['exclude_folders'] = array(
    '.git', 'iipimage-martin'
);


// VIEWER CONF
$dzi_image_server = "https://rationai-vis.ics.muni.cz/iipsrv-martin/iipsrv.fcgi";
$viewer_url = "https://rationai-vis.ics.muni.cz/visualization/refactor/client/index.php";
