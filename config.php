<?php
function _read_env_browser($name, $default) {
    $value = getenv($name);
    if (empty($value) || $value == "") return $default;
    return $value;
}

//Most used configurations for overriding
define('FM_DEBUG', false);
define('FM_USE_AUTH', true);
define('FM_ADVANCED_MODE', false);
define('FM_XO_DB_ROOT', "../xo_db/");
//define('FM_XO_DB_ROOT', null);
define('FM_WSI_ANALYSIS_PAGE', _read_env_browser("XO_BROWSER_ANALYSIS", "/importer/server/analysis.php"));
//define('FM_WSI_ANALYSIS_PAGE', null);
define('FM_BROWSE_ROOT', '/var/www/data/');
define('FM_XOPAT_URL', "/xopat/index.php");
define('FM_XOPAT_SOURCES', '/xopat/user_setup.php');

$image_preview_url_maker = function ($file) {
    return "/iipsrv/iipsrv.fcgi?Deepzoom={$file}_files/1/0_0.jpg";
};
