<?php


// get path
$p = isset($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');

// clean path
$p = fm_clean_path($p);

// instead globals vars
define('FM_PATH', $p);

$data = isset($_POST["ajax"]) ? $_POST : $_GET;

switch ($data["ajax"]) {
    case "search":
        $result = array();
        function searchFiles($path) {
            require_once "files.php";
            $parent = fm_get_parent_path(FM_PATH);
            global $result;

            $objects = is_readable($path) ? scandir($path) : array();
            if (is_array($objects)) {
                foreach ($objects as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
//todo respect?
//                    if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
//                        continue;
//                    }
                    $new_path = $path . '/' . $file;
                    if (is_file($new_path)) {
                        $result[] = $file;
                    }
                }
            }
        }

        break;


    default:
        throw new Exception("Unknown ajax request call: " . $data["ajax"]);
}
