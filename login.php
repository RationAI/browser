<?php

//Move this file anywhere you like, just specify relative path to the root.
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '');
}

//init setup, private stuff, configs
require PATH_TO_IS_MANAGER . 'inc/init.php';
//include DB proxy
if (USES_DATABASE) require_once XO_DB_ROOT . "include.php";
//run browser
require PATH_TO_IS_MANAGER . 'inc/login_functions.php';

function finish($success, $type) {
    $url = isset($_GET["redirect"]) ?
        ($_GET["redirect"] . ($success ? ("?" . $_GET["token"] . "=" . FM_USER_ID) : ""))
        : null;
    if ($url) {
        fm_redirect($url);
    } else if ($success) {
        ?>
<script>
    //wait for session
    setTimeout(()=>{
        window.close();
    }, 500);
</script>
<?php
    } else {
        //notify on fail
        if ($type === "login") fm_set_msg('Invalid Username / Password', 'error');
        else fm_set_msg('Unable to register: name or email already taken.', 'error');
        fm_redirect(FM_SELF_URL);
    }
};

// Web APP Auth Endpoint
if (_FM_USE_AUTH) {
    require_once PATH_TO_IS_MANAGER . 'inc/login_functions.php';
    fm_require_or_show_login(function (){
        finish(true, "login");
    }, function ($type) {
        finish(true, $type);
    }, function ($type, $e) {
        finish(false, $type);
    });
} else {
    finish(true, "login");
}
