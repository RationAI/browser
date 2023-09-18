<?php
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '../');
}

require_once PATH_TO_IS_MANAGER . 'ajax/ajax_init.php';
//if we got here user already has rights to access, handled by ajax ini

try {
    $ref_file = $_POST["tissue"] ?? null;
    $data = $_POST["data"] ?? null;


    if (is_string($ref_file) && $data) {
        //else store to the database
        if (!is_string($data)) $data = json_encode($data);
        if (strlen($data) > 10e6) error("Data too big.");

        $ret = [];
        if (USES_DATABASE) {
            xp_store_session(fm_tiff_fname_from_mirax(basename($ref_file)), FM_USER_ID, $data);
        } else {
            //save as file, relative to the directory of the slide (expects $ref_file) to contain the whole path
            $rel_path = pathinfo($ref_file, PATHINFO_DIRNAME);
            $write_target = FM_BROWSE_ROOT . $rel_path;
            if (is_writable($write_target)) {
                file_put_contents($write_target . "/session.html", $data);
                $rel_path = urlencode(substr($rel_path, 1)); //remove leading slash - 'nicer' link
                $ret["url"] = FM_ROOT_URL . "?p=$rel_path&view=session.html";
            } else {
                error("Path not writeable!");
            }
        }
        send_ok($ret);
    }
} catch (Exception $e) {
    error($e->getMessage());
}
send_as_json(400, "Missing input data!");
