<?php

//relative difference of index.php wrt. file manager
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '');
}
require PATH_TO_IS_MANAGER . 'inc/init.php';


function error($msg) {
    echo json_encode((object)array(
        "status" => "error",
        "message" => $msg,
    ));
    die();
}

function send($data=null) {
    $response = array("status" => "success");
    if ($data !== null) $response["data"] = $data;
    echo json_encode($response);
    die();
}

set_exception_handler(function (Throwable $exception) {
    error($exception->getMessage());
});

//todo send AUTH data from viewer and perform auth if required
global $user, $user_id;
$data = isset($_POST["ajax"]) ? $_POST : $_GET;
if (!isset($data["ajax"])) error("No Ajax Command!");

switch ($data["ajax"]) {
    case "runDefaultVisualization":
        //todo if not logged disable access?

        $wsi_filename = $data["filename"];
        $fileDir = "/" . fm_clean_path($data["directory"]);
        $relativeFileDir = fm_clean_path($data["relativeDirectory"]);

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
        echo <<<EOF
    
   <head>
   <script type="text/javascript" src="$js_path/viewerConfig.js">

   </script>
   </head><body>
<span id='$relativeFileDir/$wsi_filename-meta' style='display: none' data-microns-x='{$data["microns"]}' data-microns-y=''></span>
   <script>
      const data = $shader_data;
   const tissuePath = '$relativeFileDir/$wsi_filename';
   window.viewerConfig = new ViewerConfig({
        windowName: 'viewerConfig',
        viewerUrl: '$viewer_url',
        data: '',
   });
   
   viewerConfig.withUser('$user_id').setTissue(tissuePath);
   
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

        break;

    case "storeSession":
        try {
            $content = $data["session"];
            $file = $data["filename"];
            $user = $data["user"];

            if (strlen($content) > 10e6) error("Data too big.");

            throw new Exception("not implemented");
//           todo  if ($session->storeOne($file, $user, $content)) {
//                send();
//            }

            send();
        } catch (Exception $e) {
            error($e->getMessage());
        }
        break;
    case "setSeen":
        try {
            $file = $data["filename"];
            if ($user_id < 0) return;
            xo_file_seen_by(basename($file), $user_id);
            send();
        } catch (Exception $e) {
            error($e->getMessage());
        }
//    case "search":
//        $result = array();
//        function searchFiles($path)
//        {
//            require_once PATH_TO_IS_MANAGER . "inc/files.php";
//            $parent = fm_get_parent_path(FM_PATH);
//            global $result;
//
//            $objects = is_readable($path) ? scandir($path) : array();
//            if (is_array($objects)) {
//                foreach ($objects as $file) {
//                    if ($file == '.' || $file == '..') {
//                        continue;
//                    }
////todo respect?
////                    if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
////                        continue;
////                    }
//                    $new_path = $path . '/' . $file;
//                    if (is_file($new_path)) {
//                        $result[] = $file;
//                    }
//                }
//            }
//        }
//
//        break;

    default:
        error("Unknown ajax request call: " . $data["ajax"]);
}
