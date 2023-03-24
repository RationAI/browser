<?php

//Move this file anywhere you like, just specify relative path to the root.
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '');
}

//init setup, private stuff, configs
require PATH_TO_IS_MANAGER . 'inc/init.php';
//include DB proxy
require_once XO_DB_ROOT . "include.php";
//run browser
require PATH_TO_IS_MANAGER . 'inc/files.php';
