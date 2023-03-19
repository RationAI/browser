<?php

//Default Image Server Preview URL Maker (for tif pyramid previews)
//Default Image Server Preview URL Maker (for tif pyramid previews)
$image_preview_url_maker = function ($file) {
    return "http://localhost:8080/iipsrv.fcgi?Deepzoom={$file}_files/0/0_0.jpg";
};

//Url of the Viewer
$viewer_url = "http://localhost:8080/xopat/index.php";

// Default language
$lang = 'en';

// Auth with login/password (set true/false to enable/disable it)
$use_auth = false;


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
$root_path = '/var/www/data/';

// Default root for the WSI server
$image_server_root_path = $root_path;

// Root url for links in file manager.Relative to $http_host. Variants: '', 'path/to/subfolder'
// Will not working if $root_path will be outside of server document root
//if front end path and root path differ use a proxy link that will
//translate one url to the other (e.g. using htaccess at SERVER/proxy) and
//files directory with htaccess redirect from front end to root path
// example: RewriteRule $path/to/proxy/(.*)^ /real/absolute/server/url/$1 [L, QSA]
$root_url = $root_path;

// Root url for the source files (JS, assets)
$sources_url = PATH_TO_IS_MANAGER;

// Server hostname. Can set manually if wrong
$http_host = $_SERVER['HTTP_HOST'];

// input encoding for iconv
$iconv_input_encoding = 'UTF-8';

// date() format for file modification date
$datetime_format = 'd.m.y H:i';

// allowed upload file extensions
$upload_extensions = ''; // 'gif,png,jpg'

//Array of folders excluded from listing
$GLOBALS['exclude_folders'] = array(
    '.git'
);

//Path to the analysis enpoint, leave as false if you don't know
$wsi_analysis_endpoint = "http://localhost:8081/importer/server/analysis.php";

//must be full url
$wsi_status_full_endpoint = false;

// OVERRIDE ALL PROPS WITH USER SETTINGS
if (defined('FM_CONFIG') && is_file(FM_CONFIG) ) {
    include(FM_CONFIG);
}

//Relative or absolute path to the viewer source src folder. Only required for shader configurator.
defined('XOPAT_SOURCES') || define('XOPAT_SOURCES', 'http://localhost:8080/xopat/user_setup.php');

// Path to the database repository root
defined('XO_DB_ROOT') || define('XO_DB_ROOT', "../xo_db/");

// DEFINE ALL HARDCODED VALUES
// where are php files to look for
$php_path = $sources_url . 'inc';

// path to this script
$file_path = $sources_url . 'files.php';

// where are assets to look for
$assets_path = $sources_url . 'assets';

// where are js files to look for
$js_path = $sources_url . 'js';

