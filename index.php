<?php

define('PATH_TO_IS_MANAGER', './is/');

function exception_handler(Throwable $exception) {
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
}

set_exception_handler('exception_handler');

define('FM_CONFIG', PATH_TO_IS_MANAGER . 'config.php');
require PATH_TO_IS_MANAGER . 'inc/manager.php';