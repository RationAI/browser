<?php

defined('FM_SHOW_HIDDEN') || define('FM_SHOW_HIDDEN', false);
// Requires presence of the config data values.

/**
 * todo consider BFS with time limit instead?
 * File walking iterator, respects '.pull' files that make it skip directory files
 * and treat child directories as if their content was pulled out, but only for certain file types.
 * Also respects all other browsing settings on the config.
 * @param $path string root path of the browser
 * @param $rel_start string relative relative path to root where to start iterating (offset)
 * @param $wsi_path string relative path for WSI image server (which can have different root set)
 * @param $callback callable called on valid items, args are (is_file:bool,
 *  name: string, relative_path: string, rel_start: string (unchanged), wsi_path: string (unchanged));
 *  the file absolute path is FM_ROOT_PATH + rel_start + relative_path + name
 * @param $filename_predicate callable ($file, $rel_start) predicate to accept/decline items
 * @param $max_recursion int
 * @param $recursion_count int @private
 * @param string $fname_append @private
 * @param array $pulls @private
 * @return void
 */
function file_scan(string   $path,
                   string   $rel_start,
                   string   $wsi_path,
                   callable $callback,
                   callable $filename_predicate,
                   int      $max_recursion=-1,
                   //args used in recurring calls
                   int      $recursion_count=0,
                   string   $fname_append='',
                   array    $pulls=array()) {

    if ($recursion_count >= $max_recursion) return; //prevent recursion depth
    $new_pulls = array();
    $objects = is_readable($path) ? scandir($path) : array();
    if (file_exists("$path/.pull")) {
        foreach (fm_file_lines("$path/.pull") as $line) {
            $new_pulls[]=trim($line);
        }
    }

    if (is_array($objects)) {
        foreach ($objects as $file) {
            $recursion = $recursion_count;

            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
                continue;
            }

            $new_path = $path . '/' . $file;
            $valid_dir = is_dir($new_path) && !is_link($new_path) && !in_array($file, $GLOBALS['exclude_folders']);
            $valid_file = true;

            $will_pull = count($pulls) > 0;
            if ($will_pull) {
                $reducer = function ($file, $b) {
                    if (!$file) return false;
                    if (gettype($b) === "string" && str_ends_with($file, $b)) return false;
                    return $file;
                };
                $valid_file = array_reduce($pulls, $reducer, $file) === false;
            }

            if (count($new_pulls) < 1) {
                $recursion++; //pull does not count as a step
            }

            if ($filename_predicate($file, $new_path)) {
                if ($valid_file && is_file($new_path)) {
                    $callback(true, $file, $fname_append, $rel_start, $wsi_path);
                } else if ($valid_dir && !$will_pull) {
                    $callback(false, $file, $fname_append, $rel_start, $wsi_path);
                }
            }

            if ($valid_dir) {
                file_scan($new_path,
                    $rel_start === '' ? $file : $rel_start . '/' . $file,
                    $wsi_path === '' ? $file : $wsi_path . '/' . $file,
                    $callback, $filename_predicate,
                    $max_recursion, $recursion, "$fname_append/$file", $new_pulls);
            }
        }
    }
}
