<?php

require_once "config.php";
require_once "functions.php";

if(isset($_GET['toggleTree'])) {
    if ($_SESSION['treeHide'] == 'false') {
        $_SESSION['treeHide'] = 'true';
    } else {
        $_SESSION['treeHide'] = 'false';
    }
}

// if fm included
if (defined('FM_EMBED')) {
    $use_auth = false;
} else {
    @set_time_limit(600);

    date_default_timezone_set($default_timezone);

    ini_set('default_charset', 'UTF-8');
    if (version_compare(PHP_VERSION, '5.6.0', '<') && function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    if (function_exists('mb_regex_encoding')) {
        mb_regex_encoding('UTF-8');
    }

    session_cache_limiter('');
    session_name('filemanager');
}
session_start();


if (empty($auth_users)) {
    $use_auth = false;
}

$is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';

// clean and check $root_path
$root_path = rtrim($root_path, '\\/');
$root_path = str_replace('\\', '/', $root_path);
if (!@is_dir($root_path)) {
    echo "<h1>Root path \"{$root_path}\" not found!</h1>";
    exit;
}

// clean $root_url
$root_url = fm_clean_path($root_url);

// abs path for site
defined('FM_SHOW_HIDDEN') || define('FM_SHOW_HIDDEN', $show_hidden_files);
defined('FM_ROOT_PATH') || define('FM_ROOT_PATH', $root_path);
defined('FM_ROOT_URL') || define('FM_ROOT_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . (!empty($root_url) ? '/' . $root_url : ''));
defined('FM_SELF_URL') || define('FM_SELF_URL', ($is_https ? 'https' : 'http') . '://' . $http_host . $_SERVER['PHP_SELF']);

// logout
if (isset($_GET['logout'])) {
    unset($_SESSION['logged']);
    fm_redirect(FM_SELF_URL);
}

// Show image here
if (isset($_GET['img'])) {
    fm_show_image($_GET['img']);
}

// Auth
if ($use_auth) {
    if (isset($_SESSION['logged'], $auth_users[$_SESSION['logged']])) {
        // Logged
    } elseif (isset($_POST['fm_usr'], $_POST['fm_pwd'])) {
        // Logging In
        sleep(1);
        if (isset($auth_users[$_POST['fm_usr']]) && md5($_POST['fm_pwd']) === $auth_users[$_POST['fm_usr']]) {
            $_SESSION['logged'] = $_POST['fm_usr'];
            fm_set_msg('You are logged in');
            fm_redirect(FM_SELF_URL . '?p=');
        } else {
            unset($_SESSION['logged']);
            fm_set_msg('Invalid Username / Password', 'error');
            fm_redirect(FM_SELF_URL);
        }
    } else {
        // Form
        unset($_SESSION['logged']);
        fm_show_header_login();
        ?>

        <div class="container" style="max-width: 600px">
            <form class="mt-5" action="" method="post" autocomplete="off">
                <center><img src="<?php echo $assets_path ?>/blank.png" alt="File manager" class="img-fluid"></center>
                <hr>
                <div class="form-group mt-5">
                    <?php
                    fm_show_message();
                    ?>
                    <label><i class="fa fa-user"></i> Username</label>
                    <input name="fm_usr" id="fm_usr" type="text" class="form-control" placeholder="Enter Username" required autocomplete="false">
                    <div class="invalid-feedback">
                        Username is required.
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fa fa-lock"></i> Password</label>
                    <input name="fm_pwd" id="fm_pwd" type="password" class="form-control" placeholder="Enter Password" required>
                    <div class="invalid-feedback">
                        Password is required.
                    </div>
                </div>
                <button type="submit" class="btn btn-info py-2 px-4""><i class="fa fa-sign-in"></i> Log In</button>
            </form>
        </div>
        <?php
        fm_show_footer_login();
        exit;
    }
}

defined('FM_LANG') || define('FM_LANG', $lang);
defined('FM_EXTENSION') || define('FM_EXTENSION', $upload_extensions);
defined('FM_TREEVIEW') || define('FM_TREEVIEW', $show_tree_view);

define('FM_READONLY', !$require_auth ||
    ($use_auth && !empty($readonly_users) && isset($_SESSION['logged']) && in_array($_SESSION['logged'], $readonly_users)));

define('FM_IS_WIN', DIRECTORY_SEPARATOR == '\\');

// always use ?p=
if (!isset($_GET['p'])) {
    fm_redirect(FM_SELF_URL . '?p=');
}

// get path
$p = isset($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');

// get search string
$s = isset($_GET['s']) ? $_GET['s'] : (isset($_POST['s']) ? $_POST['s'] : '');

// clean path
$p = fm_clean_path($p);

// instead globals vars
define('FM_PATH', $p);
define('FM_USE_AUTH', $use_auth);
define('FM_SEARCH_QUERY', $s);

defined('FM_ICONV_INPUT_ENC') || define('FM_ICONV_INPUT_ENC', $iconv_input_encoding);
defined('FM_USE_HIGHLIGHTJS') || define('FM_USE_HIGHLIGHTJS', $use_highlightjs);
defined('FM_HIGHLIGHTJS_STYLE') || define('FM_HIGHLIGHTJS_STYLE', $highlightjs_style);
defined('FM_DATETIME_FORMAT') || define('FM_DATETIME_FORMAT', $datetime_format);

unset($p, $s, $use_auth, $iconv_input_encoding, $use_highlightjs, $highlightjs_style);

/*************************** ACTIONS ***************************/

// Download
if (isset($_GET['dl'])) {
    $dl = $_GET['dl'];
    $dl = fm_clean_path($dl);
    $dl = str_replace('/', '', $dl);
    $path = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $path .= '/' . FM_PATH;
    }
    if ($dl != '' && is_file($path . '/' . $dl)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path . '/' . $dl) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path . '/' . $dl));
        readfile($path . '/' . $dl);
        exit;
    } else {
        fm_set_msg('File not found', 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }
}

/*************************** /ACTIONS ***************************/

// get current path
$path = FM_ROOT_PATH;
$front_path = FM_PATH;
if (FM_PATH != '') {
    $path .= '/' . FM_PATH;
}

// check path
if (!is_dir($path)) {
    fm_redirect(FM_SELF_URL . '?p=');
}

// get parent folder
$parent = fm_get_parent_path(FM_PATH);

$folders = array();
$files = array();
if (empty(FM_SEARCH_QUERY)) {
    $objects = is_readable($path) ? scandir($path) : array();
    if (is_array($objects)) {
        foreach ($objects as $file) {
            if ($file == '.' || $file == '..' || preg_match('/.*\.(?:json|html)/', $file)) {
                continue;
            }
            if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
                continue;
            }
            $new_path = $path . '/' . $file;
            if (is_file($new_path)) {
                $files[] = array($file, $path, $front_path, "");
            } elseif (is_dir($new_path) && !is_link($new_path) && !in_array($file, $GLOBALS['exclude_folders'])) {
                $folders[] = $file;
            }
        }
    }
    unset($objects);
} else {
    function search($path, $rel_path, $front_path, $recursion_stack=0) {
        if ($recursion_stack > 2) return; //prevent recursion cycle at any cost
        global $folders, $files;
        $objects = is_readable($path) ? scandir($path) : array();
        $pattern = FM_SEARCH_QUERY;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..' || preg_match('/.*\.(?:json|html)/', $file)) {
                    continue;
                }
                if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
                    continue;
                }
                $new_path = $path . '/' . $file;
                if (is_file($new_path) && preg_match("#$pattern#i", $file)) {
                    $files[] = array($file, $rel_path, $front_path, "");
                } elseif (is_dir($new_path) && !is_link($new_path) && !in_array($file, $GLOBALS['exclude_folders'])) {
                    search($new_path, $rel_path . '/' . $file, $front_path . '/' . $file, $recursion_stack+1);
                }
            }
        }
    }
    search($path, FM_PATH != '' ? FM_PATH : '.', $front_path);
    fm_set_msg('Only 2 folders in depth are scanned due to many files - the list might be incomplete.', 'alert');
}

if (!empty($files)) {
    $key_extractor = function ($f) { return $f[0]; };
    $keys = array_map($key_extractor, $files);
    array_multisort($keys, SORT_NATURAL | SORT_FLAG_CASE, $files);
}
if (!empty($folders)) {
    natcasesort($folders);
}

// file viewer
if (isset($_GET['view'])) {
    $file = $_GET['view'];
    $file = fm_clean_path($file);
    $file = str_replace('/', '', $file);
    if ($file == '' || !is_file($path . '/' . $file)) {
        fm_set_msg('File not found', 'error');
        fm_redirect(FM_SELF_URL . '?p=' . urlencode(FM_PATH));
    }

    fm_show_header(); // HEADER

    $file_url = FM_ROOT_URL . fm_convert_win((FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $file);
    $file_path = $path . '/' . $file;
    $front_file_path = $front_path . '/' . $file;

    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = fm_get_mime_type($file_path);
    $filesize = filesize($file_path);

    $is_zip = false;
    $is_image = false;
    $is_audio = false;
    $is_video = false;
    $is_text = false;

    $view_title = 'File';
    $filenames = false; // for zip
    $content = ''; // for text

    if ($ext == 'zip') {
        $is_zip = true;
        $view_title = 'Archive';
        $filenames = fm_get_zif_info($file_path);
    } elseif (in_array($ext, fm_get_image_exts())) {
        $is_image = true;
        $view_title = 'Image';
    } elseif (in_array($ext, fm_get_audio_exts())) {
        $is_audio = true;
        $view_title = 'Audio';
    } elseif (in_array($ext, fm_get_video_exts())) {
        $is_video = true;
        $view_title = 'Video';
    } elseif (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
        $is_text = true;
        $content = file_get_contents($file_path);
    }

    ?>
    <div class="path pt-3 m-2">
        <? fm_show_nav_path(FM_PATH); ?>
        <p class="break-word f3-light"><b><?php echo $view_title ?> "<?php echo fm_enc(fm_convert_win($file)) ?>"</b></p>
        <p class="break-word">
            Full path: <?php echo fm_enc(fm_convert_win($file_path)) ?><br>
            File size: <?php echo fm_get_filesize($filesize) ?><?php if ($filesize >= 1000): ?> (<?php echo sprintf('%s bytes', $filesize) ?>)<?php endif; ?><br>
            MIME-type: <?php echo $mime_type ?><br>
            <?php
            // ZIP info
            if ($is_zip && $filenames !== false) {
                $total_files = 0;
                $total_comp = 0;
                $total_uncomp = 0;
                foreach ($filenames as $fn) {
                    if (!$fn['folder']) {
                        $total_files++;
                    }
                    $total_comp += $fn['compressed_size'];
                    $total_uncomp += $fn['filesize'];
                }
                ?>
                Files in archive: <?php echo $total_files ?><br>
                Total size: <?php echo fm_get_filesize($total_uncomp) ?><br>
                Size in archive: <?php echo fm_get_filesize($total_comp) ?><br>
                Compression: <?php echo round(($total_comp / $total_uncomp) * 100) ?>%<br>
                <?php
            }
            // Image info
            if ($is_image) {
                $image_size = getimagesize($file_path);
                if (!isset($image_size[0])) $image_size[0] = 0;
                if (!isset($image_size[1])) $image_size[1] = 0;
                echo 'Image sizes: ' . $image_size[0] . ' x ' . $image_size[1] . '<br>';
            }
            // Text info
            if ($is_text) {
                $is_utf8 = fm_is_utf8($content);
                if (function_exists('iconv')) {
                    if (!$is_utf8) {
                        $content = iconv(FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $content);
                    }
                }
                echo 'Charset: ' . ($is_utf8 ? 'utf-8' : '8 bit') . '<br>';
            }
            ?>
        </p>
        <p>
            <b><a class="btn-outline-primary btn-sm btn" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;dl=<?php echo urlencode($file) ?>"><i class="fa fa-cloud-download"></i> Download</a></b> &nbsp;
<!--  TODO open disabled - exact and front links might differ          <b><a class="btn-outline-primary btn-sm btn" href="--><?php //echo fm_enc($file_url) ?><!--" target="_blank"><i class="fa fa-external-link-square"></i> Open</a></b> &nbsp;-->
            <b><a class="btn-outline-primary btn-sm btn" href="?p=<?php echo urlencode(FM_PATH) ?>"><i class="fa fa-chevron-circle-left"></i> Back</a></b>
        </p>
        <?php
        if ($is_image) {
            if ($image_size[0] > 5000 && $image_size[1] > 5000) {
                echo 'Image too big: the viewer is not configured to open plain image.';
            } else {
                // Image content
                if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico'))) {
                    echo '<p><img src="' . fm_enc($file_url) . '" alt="" class="preview-img"></p>';
                }
            }
        } elseif ($is_audio) {
            // Audio content
            echo '<p><audio src="' . fm_enc($file_url) . '" controls preload="metadata"></audio></p>';
        } elseif ($is_video) {
            // Video content
            echo '<div class="preview-video"><video src="' . fm_enc($file_url) . '" width="640" height="360" controls preload="metadata"></video></div>';
        } elseif ($is_text) {
            if (FM_USE_HIGHLIGHTJS) {
                // highlight
                $hljs_classes = array(
                    'shtml' => 'xml',
                    'htaccess' => 'apache',
                    'phtml' => 'php',
                    'lock' => 'json',
                    'svg' => 'xml',
                );
                $hljs_class = isset($hljs_classes[$ext]) ? 'lang-' . $hljs_classes[$ext] : 'lang-' . $ext;
                if (empty($ext) || in_array(strtolower($file), fm_get_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
                    $hljs_class = 'nohighlight';
                }
                $content = '<pre class="with-hljs"><code class="' . $hljs_class . '">' . fm_enc($content) . '</code></pre>';
            } elseif (in_array($ext, array('php', 'php4', 'php5', 'phtml', 'phps'))) {
                // php highlight
                $content = highlight_string($content, true);
            } else {
                $content = '<pre>' . fm_enc($content) . '</pre>';
            }
            echo $content;
        }
        ?>
    </div>
    <?php
    fm_show_footer();
    exit;
}

//--- FILEMANAGER MAIN
fm_show_header(); // HEADER


$num_files = count($files);
$num_folders = count($folders);
$all_files_size = 0;

?>
<div id="double-panel-container" style="max-width: 100vw;">
<form id="file-browser-form" class="mt-3 mx-3 flex-grow-0" action="" method="post">

    <input type="hidden" name="viewer-config" value="">
    <input type="hidden" name="p" value="<?php echo fm_enc(FM_PATH) ?>">
    <input type="hidden" name="group" value="1">
    <?php if(FM_TREEVIEW) { ?>
        <div class="row">
            <div class="col-sm-3  <?php  echo $_SESSION['treeHide'] == 'true'?'d-none':''; ?>">
        <div class="file-tree-view border w-100" id="file-tree-view">
            <div class="tree-title"><i class="fa fa-align-left fa-fw"></i> Browse</div>
            <?php
            //file tre view
            //echo php_file_tree($root_path, "javascript:alert('You clicked on [link]');");
            ?>
        </div>
            </div>
        </div>
    <?php } ?>
            <div class="width-full mt-3 mt-md-0"><!--col-sm-<?php  echo $_SESSION['treeHide'] == 'true'?'12':'9'; ?>-->
                <div class="table-responsive">
                    <?php

                    // render wrt current relative path
                    fm_show_nav_path(FM_PATH);

                    fm_show_search_bar();

                    // messages
                    fm_show_message();

                    ?>
                    <table class="table" id="main-table"><thead><tr>
            <?php if (!FM_READONLY): ?>
                <th style="width:3%">
                    <label>
                        <input type="checkbox" title="Invert selection" onclick="checkbox_toggle()">
                    </label>
                </th><?php endif; ?>
            <th>Name</th>
            <th style="width:10%">Size</th>
            <th style="width:12%">Modified</th>
            <?php if (!FM_IS_WIN): ?>
                <th style="width:6%">Perms</th>
                <th style="width:10%">Owner</th><?php endif; ?>
            <th style="width:<?php if (!FM_READONLY): ?>13<?php else: ?>6.5<?php endif; ?>%">Actions</th>
        </tr>
        </thead>
        <?php
        // link to parent folder
        if ($parent !== false) {
            ?>
            <tr><?php if (!FM_READONLY): ?><td></td><?php endif; ?><td colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>"><a href="?p=<?php echo urlencode($parent) ?>"><i class="fa fa-chevron-circle-left"></i> ..</a></td></tr>
            <?php
        }
        foreach ($folders as $f) {
            $is_link = is_link($path . '/' . $f);
            $img = $is_link ? 'icon-link_folder' : 'fa fa-folder-o';
            $modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
            $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
            if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                $owner = posix_getpwuid(fileowner($path . '/' . $f));
                $group = posix_getgrgid(filegroup($path . '/' . $f));
            } else {
                $owner = array('name' => '?');
                $group = array('name' => '?');
            }
            ?>
            <tr>
                <?php if (!FM_READONLY): ?>
                    <td>
                    <label>
                        <input type="checkbox" name="file[]" value="<?php echo fm_enc($f) ?>">
                    </label>
                    </td><?php endif; ?>
                <td>
                    <div class="filename"><a href="?p=<?php echo urlencode(trim(FM_PATH . '/' . $f, '/')) ?>"><i
                                class="<?php echo $img ?>"></i> <?php echo fm_convert_win($f) ?>
                        </a><?php echo($is_link ? ' &rarr; <i>' . readlink($path . '/' . $f) . '</i>' : '') ?></div>
                </td>
                <td>Folder</td>
                <td><?php echo $modif ?></td>
                <?php if (!FM_IS_WIN): ?>
                    <td> <?php echo $perms ?>
                    </td>
                    <td><?php echo $owner['name'] . ':' . $group['name'] ?></td>
                <?php endif; ?>
                <td class="inline-actions"><?php if (!FM_READONLY): ?>
                    <?php endif; ?>
                    <a title="Direct link"
                       href="<?php echo fm_enc(FM_ROOT_URL . (FM_PATH != '' ? '/' . FM_PATH : '') . '/' . $f . '/') ?>"
                       target="_blank"><i class="fa fa-link" aria-hidden="true"></i></a>
                </td>
            </tr>
            <?php
            flush();
        }

        try {
            //TODO test
//            require_once "TagStore.php";
//            $tags = new TagStore();
//
//            $tags->tagFile("ahoj", "file1.php");
//            $tags->tagFile("need", "file2.php");
//            $tags->tagFile("ahoj", "file1.php");
//            $tags->tagFile("ahoj", "file3.php");
//            echo $tags->getFiles("file1.php") . "<br><br>";
//            echo $tags->getTags("need") . "<br><br>";
//            echo $tags->readFilesTags("file3.php") . "<br><br>";
//            echo $tags->readTagsFiles("ahoj") . "<br><br>";
//            $tags->unTagFile("ahoj", "file1.php");
//            $tags->unTagFile("ahoj", "file2.php");
//            echo $tags->readTagsFiles("ahoj") . "<br><br>";

        } catch (Exception $e) {
            echo $e;
        }

        foreach ($files as $file_data) {
            $fname = $file_data[0];
            $dirpath = $file_data[1];
            $full_path = $dirpath . '/' . $fname;
            $front_dirpath = $file_data[2];
            $full_front_path = "$file_data[2]/$fname";

            $is_link = is_link($full_path);
            $ext = pathinfo($fname, PATHINFO_EXTENSION);
            $is_tiff = strtolower($ext) === "tiff" || strtolower($ext) === "tif";
            $actions = "";
            $modif = date("d.m.y H:i", filemtime($full_path));
            $filesize_raw = filesize($full_path);
            $filesize = fm_get_filesize($filesize_raw);
            $filelink = '?p=' . urlencode($front_dirpath) . '&amp;view=' . urlencode($fname);
            $all_files_size += $filesize_raw;
            $perms = substr(decoct(fileperms($full_path)), -4);
            $img = $title_tags =  $title_prefix = $onimageclick = "";

            if (isset($file_data[3]) && $file_data[3] !== "") {
                $actions .= "<br><a href=\"{$file_data[3]}\">Last stored session</a>";
            }

            if ($is_tiff) {
                $img = $image_preview_url_maker($full_front_path);
                $img = "<img class='mr-2 tiff-preview' src=\"$img\">";
                $onimageclick = "onclick=\"viewerConfig.setTissue('$full_front_path');\"";
                $actions="
<a href=\"?ajax=runDefaultVisualization&filename={$fname}&directory={$dirpath}&relativeDirectory={$front_dirpath}\">Open As Default</a>
<br><br><a $onimageclick class='pointer'>Add as background.</a>
<br><a onclick=\"viewerConfig.setShaderFor('$full_front_path');\" class='pointer'>Add as layer.</a>";
                $title_tags = "onclick=\"go(false, '$fname', '$full_front_path');\" class=\"pointer\"";
                $title_prefix = "<i class='xopat'>&#xe802;</i>";
            } else {
                $img = $is_link ? 'fa fa-file-text-o' : fm_get_file_icon_class($fname);
                $img = "<i class=\"$img\"></i>&nbsp;";
                $title_tags = "href=\"$filelink\" title=\"File info\"";
                $onimageclick = "onclick=\"location.href = '$filelink';\"";
            }

            if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                $owner = posix_getpwuid(fileowner($full_path));
                $group = posix_getgrgid(filegroup($full_path));
            } else {
                $owner = array('name' => '?');
                $group = array('name' => '?');
            }
            ?>
            <tr class="viewer-config-draggable" data-source="<?php echo $full_front_path; ?>">
                <?php if (!FM_READONLY): ?><td><label><input type="checkbox" name="file[]" value="<?php echo fm_enc($fname) ?>"></label></td><?php endif; ?>
                <td style="display:flex; flex-direction: row">
                    <div class="icon-conatiner" <?php echo $onimageclick; ?>">
                        <?php echo $img ?>
                    </div>
                    <div class="action-container" style="display: flex; flex-direction: column">
                        <div class="filename"><a <?php echo $title_tags ?>><?php echo $title_prefix . fm_convert_win($fname) ?></a><?php echo ($is_link ? ' &rarr; <i>' . readlink($full_path) . '</i>' : '') ?></div>
                        <div class="viewer-actions hover-visible-only">
                            <?php echo $actions ?>
                        </div>
                    </div>
                </td>
                <td>
                    <span title="<?php printf('%s bytes', $filesize_raw) ?>"><?php echo $filesize ?></span>
                </td>
                <td><?php echo $modif ?>
                </td>
                <?php if (!FM_IS_WIN): ?>
                    <td><?php if (!FM_READONLY): ?><a title="<?php echo 'Change Permissions' ?>" href="?p=<?php echo urlencode($front_dirpath) ?>&amp;chmod=<?php echo urlencode($fname) ?>"><?php echo $perms ?></a><?php else: ?><?php echo $perms ?><?php endif; ?></td>
                    <td><?php echo fm_enc($owner['name'] . ':' . $group['name']) ?></td>
                <?php endif; ?>
                <td class="inline-actions">
                    <?php if ($is_tiff) {  ?>
                        <a title="Open in Viewer" onclick="go(false, '<?php echo $fname; ?>', '<?php echo $full_front_path; ?>');" class="pointer"><i class='xopat'>&#xe802;</i></a>
                    <?php } else { ?>
                        <!--todo we do not support direct links  <a title="Direct link" href="--><?php //echo fm_enc($front_dirpath) ?><!--" target="_blank"><i class="fa fa-link"></i></a>-->
                        <a title="Download" href="?p=<?php echo urlencode($front_dirpath) ?>&amp;dl=<?php echo urlencode($fname) ?>"><i class="fa fa-download"></i></a>
                    <?php } ?>
                </td>
            </tr>
            <?php flush();
        }

        if (empty($folders) && empty($files)) {
            ?>
            <tr><?php if (!FM_READONLY): ?><td></td><?php endif; ?><td colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>"><em><?php echo empty(FM_SEARCH_QUERY) ? 'Folder is empty' : 'No results found' ?></em></td></tr>
            <?php
        } else {
            ?>
            <tr><?php if (!FM_READONLY): ?><td class="gray"></td><?php endif; ?><td class="gray" colspan="<?php echo !FM_IS_WIN ? '6' : '4' ?>">
                    Full size: <span title="<?php printf('%s bytes', $all_files_size) ?>"><?php echo fm_get_filesize($all_files_size) ?></span>,
                    files: <?php echo $num_files ?>
                    <?php if (empty(FM_SEARCH_QUERY)) { echo ', folders: ' . $num_folders; } ?>
                </td></tr>
            <?php
        }
        ?>
    </table>
                </div>
            </div>
    <?php if (!FM_READONLY): ?>
        <div class="mb-5 row">
            <div class="col-sm-9 offset-sm-3"><a href="#/select-all" class="mt-2 mt-sm-0 btn2 btn btn-small btn-outline-primary" onclick="select_all();return false;"><i class="fa fa-check-square"></i> Select all</a> &nbsp;
            <a href="#/unselect-all" class="mt-2 mt-sm-0 btn2 btn btn-small btn-outline-primary" onclick="unselect_all();return false;"><i class="fa fa-window-close"></i> Unselect all</a> &nbsp;
            <a href="#/invert-all" class="mt-2 mt-sm-0 btn2 btn btn-small btn-outline-primary" onclick="invert_all();return false;"><i class="fa fa-th-list"></i> Invert selection</a> &nbsp;
            </div>
        </div>
    <?php endif; ?>
</form>
<div id="viewer-configurator" class="d-none"></div>
</div>
<script type="text/javascript">
    (function (window){
        let splitUrlMaker = ('<?php echo $image_preview_url_maker("$$%%[[==]]%%$$"); ?>').split('$$%%[[==]]%%$$');
        window.dziImagePreviewMaker = function (file) {
            let res = [splitUrlMaker[0]], i = 1;
            while( i < splitUrlMaker.length ) {
                res.push(file);
                res.push(splitUrlMaker[i++]); //suffix
                if (i >= splitUrlMaker.length) {
                    break;
                }
                res.push(splitUrlMaker[i++]); //prefix of the next occurrence
            }
            return res.join('');
        };

        window.viewerConfig = new ViewerConfig({
            windowName: 'viewerConfig',
            viewerUrl: '<?php echo $viewer_url; ?>',
            containerId: "viewer-configurator",
            tiffPreviewMaker: dziImagePreviewMaker,
            data: `<?php echo isset($_POST['viewer-config']) ? $_POST['viewer-config'] : ''; ?>`,
        });

        document.getElementById('file-browser-form').addEventListener('submit', () => {
            document.getElementById('viewer-config').value = viewerConfig.export();
        });
    }(window));
</script>

<?php

fm_show_footer();

//--- templates functions

function fm_show_search_bar() {
    ?>
    <input class="form-control" style="width: 100%;margin: 12px 0;" type="text" name="s" value="<?php echo FM_SEARCH_QUERY; ?>" placeholder="Search for files..."
           onkeydown="if(event.key === 'Enter') {
                const f=$('#file-browser-form');
                f.attr('action', '?=' + encodeURIComponent(this.value)).attr('method', 'get').submit();
           }"
    >
    <?php
}

/**
 * Show nav block
 * @param string $path
 */
function fm_show_nav_path($path)
{
    global $lang;
    ?>
    <nav class="navbar navbar-light bg-light navbar-expand-lg"">

        <?php
        $path = fm_clean_path($path);
        $root_url = "<a href='?p='><i class='fa fa-home' aria-hidden='true' title='" . FM_PATH . "'></i></a>";
        $sep = '<i class="fa fa-caret-right text-secondary"></i>';
        if ($path != '') {
            $exploded = explode('/', $path);
            $count = count($exploded);
            $array = array();
            $parent = '';
            for ($i = 0; $i < $count; $i++) {
                $parent = trim($parent . '/' . $exploded[$i], '/');
                $parent_enc = urlencode($parent);
                $array[] = "<a href='?p={$parent_enc}'>" . fm_enc(fm_convert_win($exploded[$i])) . "</a>";
            }
            $root_url .= $sep . implode($sep, $array);
        }
        echo '<div class="nav-item break-word float-left">' . $root_url . '</div>';
        ?>

<!--        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">-->
<!--            <span class="navbar-toggler-icon"></span>-->
<!--        </button>-->
        <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
        <div class="navbar-nav float-rightd ml-auto">
            <?php if (!FM_READONLY): ?>
                <li class="nav-item"><a class="nav-link mx-1" title="Search" href="javascript:showSearch('<?php echo urlencode(FM_PATH) ?>')"><i class="fa fa-search fa-fw"></i> Search</a></li>
            <li class="nav-item"><a class="nav-link mx-1" title="Upload files" href="?p=<?php echo urlencode(FM_PATH) ?>&amp;upload"><i class="fa fa-cloud-upload fa-fw" aria-hidden="true"></i> Upload Files</a></li>
                <li class="nav-item"><a class="nav-link mx-1" title="New folder" href style="outline: none;" data-toggle="modal" data-target="#createNewItem"><i class="fa fa-plus-square fa-fw"></i> New Item</a></li>
                <li class="nav-item"><a href="?toggleTree=true" class="nav-link mx-1" title="Toggle Directories List"><i class="fa fa-eye-slash fa-fw"></i> Toggle Tree View</a></li>
            <?php endif; ?>
            <?php if (FM_USE_AUTH): ?><li class="nav-item"><a class="nav-link ml-1" title="Logout" href="?logout=1"><i class="fa fa-sign-out fa-fw" aria-hidden="true"></i> Log Out</a></li><?php endif; ?>
        </div>
        </div>
    </nav>
    <?php
}

/**
 * Show message from session
 */
function fm_show_message()
{
    if (isset($_SESSION['message'])) {
        $class = isset($_SESSION['status']) ? $_SESSION['status'] : 'alert-success';
        if($class == 'error'){
            $class = 'alert-danger';
        }
        if($class == 'alert'){
            $class = 'alert-info';
        }
        if($class == 'ok'){
            $class = 'alert-success';
        }

        echo '<div class="mt-2 mx-3 message-container ' . $class . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['status']);
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

global $lang, $assets_path;
?>
    <!DOCTYPE html>
<html data-color-mode="auto" data-light-theme="light" data-dark-theme="dark_dimmed">
<head>
    <meta charset="utf-8">
    <title>File Manager</title>
    <meta name="Description" CONTENT="Web Storage">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="<?php echo $assets_path ?>/primer_css.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $assets_path ?>/login.css">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $assets_path ?>/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $assets_path ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $assets_path ?>/favicon-16x16.png">
    <link rel="manifest" href="<?php echo $assets_path ?>/site.webmanifest">
    <link rel="mask-icon" href="<?php echo $assets_path ?>/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

</head>
<body style="background: linear-gradient(to top left, #99ccff 0%, #ccffcc 100%);background-repeat: no-repeat;background-size: cover">


<div id="wrapper"">

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

/**
 * Show page header
 */
function fm_show_header()
{
$sprites_ver = '20160315';
header("Content-Type: text/html; charset=utf-8");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");

global $lang, $assets_path, $js_path;
?>
    <!DOCTYPE html>
<html data-color-mode="auto" data-light-theme="light" data-dark-theme="dark_dimmed">
<head>
    <meta charset="utf-8">
    <title>File Manager</title>
    <meta name="Description" CONTENT="Web Storage">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="<?php echo $assets_path ?>/primer_css.css">
    <link rel="stylesheet" href="<?php echo $assets_path ?>/xopat.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $assets_path ?>/index.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $assets_path ?>/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $assets_path ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $assets_path ?>/favicon-16x16.png">
<!--    <link rel="manifest" href="<?php echo $assets_path ?>/site.webmanifest">-->
    <link rel="mask-icon" href="<?php echo $assets_path ?>/safari-pinned-tab.svg" color="#5bbad5">

    <?php if (isset($_GET['view']) && FM_USE_HIGHLIGHTJS): ?>
        <link rel="stylesheet" href="<?php echo $js_path ?>/highlight.min.js">
    <?php endif; ?>

    <script type="text/javascript" src="<?php echo $js_path ?>/viewerRun.js"></script>
    <script type="text/javascript" src="<?php echo $js_path ?>/taggle.js"></script>
    <script type="text/javascript" src="<?php echo $js_path ?>/viewerConfig.js"></script>
    <link rel="stylesheet" href="<?php echo $assets_path ?>/viewer_config.css">

</head>
<body>

<div id="wrapper">

    <div class="modal" tabindex="-1" role="dialog" id="createNewItem">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-plus-square fa-fw"></i> Create New Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="newfile">Item Type &nbsp; : </label>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="customRadioInline1" name="newfile" value="file" class="custom-control-input">
                        <label class="custom-control-label" for="customRadioInline1">File</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="customRadioInline2" name="newfile" value="folder" class="custom-control-input" checked>
                        <label class="custom-control-label" for="customRadioInline2">Folder</label>
                    </div><br><br>

                        <label for="newfilename">Item Name : </label>
                        <input type="text" class="form-control" name="newfilename" id="newfilename" value="" placeholder="Enter Name">

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="submit" value="Create Now" onclick="newfolder('<?php echo fm_enc(FM_PATH) ?>');return false;" class="btn btn-success"><i class="fa fa-plus-square fa-fw"></i> Create Now</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<!--    <div id="searchResult" class="modalDialog">-->
<!--        <div class="model-wrapper"><a href="#close" title="Close" class="close">X</a>-->
<!--            <input type="search" name="search" value="" placeholder="Find a item in current folder...">-->
<!--            <h2>Search Results</h2>-->
<!--            <div id="searchresultWrapper"></div>-->
<!--        </div>-->
<!--    </div>-->

    <div class="modal" tabindex="-1" role="dialog" id="searchResult">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-search fa-fw"></i> Search Files and Folders</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="model-wrappe">
                        <input type="search" name="search" value="" class="w-100 form-control" placeholder="Find a item in current folder...">
                        <div id="searchresultWrapper" class="mt-2 p-2"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    }

    /**
     * Show page footer
     */
    function fm_show_footer()
    {

        global $js_path;
    ?>
</div>
<script>

    function change_checkboxes(e, t) {
        for (var n = e.length - 1; n >= 0; n--) e[n].checked = "boolean" == typeof t ? t : !e[n].checked
    }

    function get_checkboxes() {
        for (var e = document.getElementsByName("file[]"), t = [], n = e.length - 1; n >= 0; n--) (e[n].type = "checkbox") && t.push(e[n]);
        return t
    }

    function select_all() {
        change_checkboxes(get_checkboxes(), !0)
    }

    function unselect_all() {
        change_checkboxes(get_checkboxes(), !1)
    }

    function invert_all() {
        change_checkboxes(get_checkboxes())
    }

    function getSearchResult(e, t) {
        var n = [], a = [];
        return e.forEach(function (e) {
            "folder" === e.type ? (getSearchResult(e.items, t), e.name.toLowerCase().match(t) && n.push(e)) : "file" === e.type && e.name.toLowerCase().match(t) && a.push(e)
        }), {folders: n, files: a}
    }

    function checkbox_toggle() {
        var e = get_checkboxes();
        e.push(this), change_checkboxes(e)
    }

    function init_php_file_tree() {
        if (document.getElementsByTagName) {
            for (var e = document.getElementsByTagName("LI"), t = 0; t < e.length; t++) {
                var n = e[t].className;
                if (n.indexOf("pft-directory") > -1) for (var a = e[t].childNodes, o = 0; o < a.length; o++) "A" == a[o].tagName && (a[o].onclick = function () {
                    for (var e = this.nextSibling; ;) {
                        if (null == e) return !1;
                        if ("UL" == e.tagName) {
                            var t = "none" == e.style.display;
                            return e.style.display = t ? "block" : "none", this.className = t ? "open" : "closed", !1
                        }
                        e = e.nextSibling
                    }
                    return !1
                }, a[o].className = n.indexOf("open") > -1 ? "open" : "closed"), "UL" == a[o].tagName && (a[o].style.display = n.indexOf("open") > -1 ? "block" : "none")
            }
            return !1
        }
    }

    var searchEl = document.querySelector("input[type=search]"), timeout = null;
    searchEl.onkeyup = function (e) {
        clearTimeout(timeout);
        var t = JSON.parse(window.searchObj), n = document.querySelector("input[type=search]").value;
        timeout = setTimeout(function () {
            if (n.length >= 2) {
                var e = getSearchResult(t, n), a = "", o = "";
                e.folders.forEach(function (e) {
                    a += '<li class="' + e.type + '"><a href="?p=' + e.path + '">' + e.name + "</a></li>"
                }), e.files.forEach(function (e) {
                    o += '<li class="' + e.type + '"><a href="?p=' + e.path + "&view=" + e.name + '">' + e.name + "</a></li>"
                }), document.getElementById("searchresultWrapper").innerHTML = '<div class="model-wrapper">'+a+o+"</div>"
            }
        }, 500)
    }, window.onload = init_php_file_tree;
    if (document.getElementById("file-tree-view")) {
        var tableViewHt = document.getElementById("main-table").offsetHeight - 2;
        document.getElementById("file-tree-view").setAttribute("style", "height:" + tableViewHt + "px")
    }
    ;

    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.register("<?php echo $js_path ?>/serviceworker.js").then(function(registration){
            console.log("Service Worker registered with scope:", registration);
        }).catch(function(err) {
            console.log("Service worker registration failed:", err);
        });
    }
</script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<?php if (isset($_GET['view']) && FM_USE_HIGHLIGHTJS): ?>
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
<?php endif; ?>
<?php if (isset($_GET['edit']) && isset($_GET['env']) && FM_EDIT_FILE): ?>
    <script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js"></script>
    <script>var editor = ace.edit("editor");editor.getSession().setMode("ace/mode/javascript");</script>
<?php endif; ?>
</body>
</html>
<?php
}

/**
 * Show image
 * @param string $img
 */
function fm_show_image($img)
{
    $modified_time = gmdate('D, d M Y 00:00:00') . ' GMT';
    $expires_time = gmdate('D, d M Y 00:00:00', strtotime('+1 day')) . ' GMT';

    $img = trim($img);
    $images = fm_get_images();
    $image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAEElEQVR42mL4//8/A0CAAQAI/AL+26JNFgAAAABJRU5ErkJggg==';
    if (isset($images[$img])) {
        $image = $images[$img];
    }
    $image = base64_decode($image);
    if (function_exists('mb_strlen')) {
        $size = mb_strlen($image, '8bit');
    } else {
        $size = strlen($image);
    }

    if (function_exists('header_remove')) {
        header_remove('Cache-Control');
        header_remove('Pragma');
    } else {
        header('Cache-Control:');
        header('Pragma:');
    }

    header('Last-Modified: ' . $modified_time, true, 200);
    header('Expires: ' . $expires_time);
    header('Content-Length: ' . $size);
    header('Content-Type: image/png');
    echo $image;

    exit;
}

/**
 * Get base64-encoded images
 * @return array
 */
function fm_get_images()
{
    return array(
        'favicon' => 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJ
bWFnZVJlYWR5ccllPAAAAZVJREFUeNqkk79Lw0AUx1+uidTQim4Waxfpnl1BcHMR6uLkIF0cpYOI
f4KbOFcRwbGTc0HQSVQQXCqlFIXgFkhIyvWS870LaaPYH9CDy8vdfb+fey930aSUMEvT6VHVzw8x
rKUX3N3Hj/8M+cZ6GcOtBPl6KY5iAA7KJzfVWrfbhUKhALZtQ6myDf1+X5nsuzjLUmUOnpa+v5r1
Z4ZDDfsLiwER45xDEATgOI6KntfDd091GidzC8vZ4vH1QQ09+4MSMAMWRREKPMhmsyr6voYmrnb2
PKEizdEabUaeFCDKCCHAdV0wTVNFznMgpVqGlZ2cipzHGtKSZwCIZJgJwxB38KHT6Sjx21V75Jcn
LXmGAKTRpGVZUx2dAqQzSEqw9kqwuGqONTufPrw37D8lQFxCvjgPXIixANLEGfwuQacMOC4kZz+q
GdhJS550BjpRCdCbAJCMJRkMASEIg+4Bxz4JwAwDSEueAYDLIM+QrOk6GHiRxjXSkJY8KUCvdXZ6
kbuvNx+mOcbN9taGBlpLAWf9nX8EGADoCfqkKWV/cgAAAABJRU5ErkJggg==',
        'sprites' => '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
RK5CYII=',
    );
}
?>
