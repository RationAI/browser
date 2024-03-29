<?php
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '../');
}
require_once PATH_TO_IS_MANAGER . 'ajax/ajax_init.php';
//if we got here user already has rights to access, handled by ajax init

try {
    $file = $_POST["metadata"] ?? [];
    $session = $file["session"] ?? null;
    $data = $_POST["data"] ?? null;

    if (is_string($session) && $data) {
        if (!is_string($data)) $data = json_encode($data);
        if (strlen($data) > 10e6) error("Data too big.");
        xp_store_session(fm_tiff_fname_from_mirax(basename($session)), FM_USER_ID, $data);
        send_ok();
    }
} catch (Exception $e) {
    error($e->getMessage());
}
send_as_json(400, "Missing input data!");
