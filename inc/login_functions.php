<?php

function fm_require_or_show_login($on_logged, $on_success, $on_fail) {
    if (FM_LOGGED) {
        $on_logged();
    } elseif (isset($_POST['fm_usr'], $_POST['fm_pwd'])) {
        // Logging In
        sleep(1);
        $logged = false;
        try {
            $user = xo_get_user_by_with_auth("name", $_POST['fm_usr'], "xopat_browser");
            $logged = md5($_POST['fm_pwd']) === $user["secret"];
            unset($user["secret"]);
            $_SESSION['logged'] = $user;
        } catch (Exception $e) {
            //pass
        }
        if ($logged) {
            $on_success("login");
        } else {
            unset($_SESSION['logged']);
            $on_fail("login", new Exception("Invalid user name or password, or the verification method!"));
        }

    } elseif (isset($_POST['fm_register_usr'], $_POST['fm_register_pwd'])) {
        // Registration
        sleep(1);

        try {
            $user = xo_add_user($_POST['fm_register_usr'],
                "default",
                "xopat_browser",
                md5($_POST['fm_register_pwd']),
                $_POST['fm_register_email']
            );
            unset($user["secret"]);
            $_SESSION['logged'] = $user;
            $on_success("register");
        } catch (Exception $e) {
            unset($_SESSION['logged']);
            $on_fail("register", $e);
        }
    } else {
        // Form
        unset($_SESSION['logged']);
        fm_show_header_login();
        ?>

        <div class="container" style="max-width: 600px; margin: 0 auto;">
            <?php
            fm_show_message();
            ?>
            <form class="mt-5" action="" method="post" autocomplete="off">
                <h1 class="f1-light">RationAI File Browser</h1>
                <hr>
                <div class="form-group mt-5">
                    <input name="fm_usr" id="fm_usr" type="text" class="form-control" placeholder="Enter Username" required autocomplete="false">
                    <div class="invalid-feedback">
                        Username is required.
                    </div>
                </div>
                <div class="form-group">
                    <input name="fm_pwd" id="fm_pwd" type="password" class="form-control" placeholder="Enter Password" required>
                    <div class="invalid-feedback">
                        Password is required.
                    </div>
                </div>
                <input type="hidden" name="token" value="<?php echo htmlentities($_SESSION['token']); ?>" />
                <button type="submit" name="login" class="btn btn-info py-2 px-4""><i class="fa fa-sign-in"></i> Log In</button>
            </form>
            <br><hr><br>
            <form class="mt-5" action="" method="post" autocomplete="off">
                <h3 class="f3-light">Not yet a member? Register.</h3>
                <div class="form-group mt-5">
                    <input name="fm_register_usr" id="fm_register_usr" type="text" class="form-control" placeholder="Enter Username" required autocomplete="false">
                    <div class="invalid-feedback">
                        Username is required.
                    </div>
                </div>
                <div class="form-group">
                    <input name="fm_register_email" id="fm_register_email" type="email" class="form-control" placeholder="Enter Your Email" required autocomplete="false">
                    <div class="invalid-feedback">
                        Email is required.
                    </div>
                </div>
                <div class="form-group">
                    <input name="fm_register_pwd" id="fm_register_pwd" type="password" class="form-control" placeholder="Enter Password" required>
                    <div class="invalid-feedback">
                        Password is required.
                    </div>
                </div>
                <button type="submit" name="register"  class="btn btn-info py-2 px-4""><i class="fa fa-sign-in"></i> Register </button>
            </form>
        </div>
        <?php
        fm_show_footer_login();
        exit;
    }
}

/**
 * Show page header in Login Form
 */
function fm_show_header_login()
{
    $sprites_ver = '20160315';
    header("Content-Type: text/html; charset=utf-8");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");

    ?>
    <!DOCTYPE html>
    <html data-color-mode="auto" data-light-theme="light" data-dark-theme="dark_dimmed">
    <head>
        <meta charset="utf-8">
        <title>File Manager</title>
        <meta name="Description" CONTENT="Web Storage">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="<?php echo _FM_ASSETS_PATH ?>primer_css.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo _FM_ASSETS_PATH ?>index.css">
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo _FM_ASSETS_PATH ?>apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="<?php echo _FM_ASSETS_PATH ?>favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="<?php echo _FM_ASSETS_PATH ?>favicon-16x16.png">
        <link rel="mask-icon" href="<?php echo _FM_ASSETS_PATH ?>safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">

    </head>
    <body>


    <div id="wrapper" class="m-5">
    <?php
}

/**
 * Show page footer in Login Form
 */
function fm_show_footer_login()
{
    ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    </body>
    </html>
    <?php
}
