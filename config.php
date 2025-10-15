<?php

function _read_env_browser($name, $default) {
    $value = getenv($name);
    if (empty($value) || $value == "") return $default;
    return $value;
}

define('FM_DEBUG', false);
define('FM_USE_AUTH', false);
define('FM_ADVANCED_MODE', true);
define('FM_XO_DB_ROOT', null); //disabled
define('FM_WSI_ANALYSIS_PAGE', null); //disabled
define('FM_XOPAT_URL', "/xopat/index.php");
define('FM_XOPAT_SOURCES', '/xopat/user_setup.php');
define('FM_BROWSE_ROOT', _read_env_browser("XO_FILES_MOUNT", "/mnt/data/"));

$image_preview_url_maker = function ($file) {
    return "/iipsrv/iipsrv.fcgi?Deepzoom={$file}_files/1/0_0.jpg";
};
define('FM_IMAGE_SERVER_URL_PATH', FM_BROWSE_ROOT);
