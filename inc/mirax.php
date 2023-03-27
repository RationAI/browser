<?php

function mirax_read_meta($path, $structured=true) {
    //stored as name.mrxs.tiff
    $file = pathinfo(pathinfo(basename($path), PATHINFO_FILENAME), PATHINFO_FILENAME);
    $directory = dirname($path);
    $data = [];
    try {
        $data = parse_ini_file("$directory/$file/Slidedat.ini", $structured, INI_SCANNER_TYPED);
    } catch (Exception $e) {
        //pass todo log
    }
    return $data;
}
