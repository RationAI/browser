<?php
//duplicated from mirax uploader -> move to xo_db?
function mirax_path_from_db_record($record) {
    $file = mirax_fname_from_tiff($record["name"]);
    return file_path_from_year_biopsy(
        pathinfo($file, PATHINFO_FILENAME), $record["root"], $record["biopsy"], true);
}
function mirax_fname_from_tiff($tiff) {
    if (preg_match("/^(.*)\.tiff?$/i", $tiff, $match)) {
        return $match[1];
    }
    if (str_ends_with($tiff, ".mrxs")) {
        return $tiff;
    }
    throw new Exception("File is not a mirax file! $tiff");
}
function file_path_year($year) {
    if (str_ends_with($year, "/")) return $year;
    return "$year/";
}

function file_path_biopsy($biopsy) {
    if (is_string($biopsy)) $biopsy = intval(trim($biopsy));
    $biopsy = str_pad($biopsy, 5, '0', STR_PAD_LEFT);

    $prefix_len = 2; //suffix is last two digits
    $prefix = substr($biopsy, 0, $prefix_len);
    $suffix = substr($biopsy, $prefix_len);
    return "$prefix/$suffix/";
}
function file_path_from_year_biopsy($filename_no_suffix, $year, $biopsy, $is_for_mirax) {
    $yp = file_path_year($year);
    $bp = file_path_biopsy($biopsy);

    if ($is_for_mirax) return "$yp$bp$filename_no_suffix/";
    else return "$yp$bp$filename_no_suffix/$filename_no_suffix/";
}
