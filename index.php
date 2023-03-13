<?php

//relative difference of index.php wrt. file manager
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '');
}

//run
require PATH_TO_IS_MANAGER . 'inc/init.php';
require PATH_TO_IS_MANAGER . 'inc/files.php';
