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
require PATH_TO_IS_MANAGER . 'inc/login_functions.php';

function finish($success) {
    $url = isset($_GET["redirect"]) ?
        ($_GET["redirect"] . ($success ? ("?" . $_GET["token"] . "=" . FM_USER_ID) : ""))
        : null;
    if ($url) {
        fm_redirect($url);
    } else {
        ?>
<script>
    //wait for session
    setTimeout(()=>{
        window.close();
    }, 500);
</script>
<?php
    }
};

// Web APP Auth Endpoint
if (FM_USE_AUTH) {
    require_once PATH_TO_IS_MANAGER . 'inc/login_functions.php';
    fm_require_or_show_login(function (){
        finish(true);
    }, function ($type) {
        finish(true);
    }, function ($type, $e) {
        finish(false);
    });
} else {
    finish(true);
}
