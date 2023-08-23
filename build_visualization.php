<?php
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '');
}
require PATH_TO_IS_MANAGER . 'inc/init.php';

$wsi_filename = $_GET["filename"];
$fileDir = "/" . fm_clean_path($_GET["directory"]);
$relativeFileDir = fm_clean_path($_GET["relativeDirectory"]);

if (!$wsi_filename || !$fileDir || !$relativeFileDir) {
    fm_set_msg('Invalid file!', 'error');
    ?>
    <script>
        window.history.back();
    </script>
    <?php
    die();
}

function scan_json_def($full_path, $filename, $wsi_filename_text, $relativeFileDir, &$output)
{
    $fname_text = pathinfo($filename, PATHINFO_FILENAME);

    //called twice to make sure, so
    $wsi_fname_text = strtok($wsi_filename_text, ".");
    if (preg_match("/$wsi_fname_text.*\.json/", $filename)) {
        try {
            $output[$fname_text] = json_decode(file_get_contents($full_path));
            $output[$fname_text]->file = "$relativeFileDir/{$output[$fname_text]->file}";
        } catch (Exception $e) {
            //pass
        }
    } else if (preg_match("/$wsi_fname_text.*\.tiff?/", $filename)) {
        if (!isset($output[$fname_text])) {
            $output[$fname_text] = (object)array(
                "file" => "$relativeFileDir/$filename",
                "order" => 0,
                "default" => false
            );
        }
    }
}

$objects = is_readable($fileDir) ? scandir($fileDir) : array();
$specs = array();
$wsi_text = pathinfo($wsi_filename, PATHINFO_FILENAME);
$default_vis = array();

if (is_array($objects)) {
    $root_specs = array();
    $specs[] = $root_specs;
    foreach ($objects as $file) {
        if ($file == '.' || $file == '..') continue;

        $fpath = "$fileDir/$file";
        if (is_dir($fpath)) {
            $nested = is_readable($fpath) ? scandir($fpath) : array();
            if (is_array($nested)) {
                $child_specs = array();
                foreach ($nested as $nestedFile) {
                    if ($nestedFile == '.' || $nestedFile == '..') continue;

                    scan_json_def("$fpath/$nestedFile", $nestedFile, $wsi_text,
                        "$relativeFileDir/$file", $child_specs);
                }
                $specs[] = $child_specs;
            }
        } else if (is_file($fpath) && $file !== $wsi_filename) {
            if ($file === "default.json") {
                //override default visualisation of heatmap with default.json if present
                try {
                    $default_vis = json_decode(file_get_contents($fpath));
                } catch (Exception $e) {
                    //pass
                }
            } else {
                scan_json_def($fpath, $file, $wsi_text, $relativeFileDir, $root_specs);
            }
        }
    }
}

foreach ($specs as $goal) {
    $i = 0;
    usort($goal, function ($a, $b) {
        return $a->order - $b->order;
    });

    foreach ($goal as $name => $spec) {
        if (isset($spec->default) && $spec->default === false) {
            if ($i < count($default_vis)) {
                $spec->default = $default_vis[$i];
            } else {
                $spec->default = "heatmap";
            }
        }
        $i++;
    }
}

$shader_data = json_encode($specs);
$viewer_url = FM_XOPAT_URL;
$js_path = _FM_JS_PATH;
$user_id = FM_USER_ID;
$wsi_meta_api = FM_WSI_IMPORTER_API ? FM_WSI_IMPORTER_API : "undefined";
global $browser_relative_root;
echo <<<EOF
    
   <head>
   <script type="text/javascript" src="$js_path/viewerConfig.js">

   </script>
   </head><body>
   <span id='$relativeFileDir/$wsi_filename-meta' style='display: none' data-microns-x='{$_GET["microns"]}' data-microns-y=''></span>
   <script>
   const data = $shader_data;
   const tissuePath = '$relativeFileDir/$wsi_filename';
   window.viewerConfig = new ViewerConfig({
        windowName: 'viewerConfig',
        viewerUrl: '$viewer_url',
        importerMetaEndpoint: '$wsi_meta_api',
        urlRoot: '$browser_relative_root',
        data: '',
   });
   
   viewerConfig.setTissue(tissuePath);
   
   let run = false;
   for (let goal of data) {
       if (goal.length < 1) continue;
       //just first set visualised for now, config cannot handle multiple :/
       for (let key in goal) {
           const spec = goal[key];
           viewerConfig.setShaderFor(spec.file, spec.default);
       }
       run = true;
       viewerConfig.open();
       break; 
   }

   if (!run) viewerConfig.open();
</script>
</body>

EOF;
