<?php

//relative difference of index.php wrt. file manager
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '');
}

//for debug see what's going on
set_exception_handler(function ($exception) {
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
});

//custom configuration file
const FM_CONFIG = PATH_TO_IS_MANAGER . 'config.php';

//run
require PATH_TO_IS_MANAGER . 'inc/init.php';
require PATH_TO_IS_MANAGER . 'inc/files.php';
