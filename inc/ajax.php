<?php

require_once "config.php";
require_once "functions.php";

// get path
$p = isset($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');

// clean path
$p = fm_clean_path($p);

// instead globals vars
define('FM_PATH', $p);

$data = isset($_POST["ajax"]) ? $_POST : $_GET;

switch ($data["ajax"]) {
    case "runDefaultVisualization":
        $wsi_filename = $data["filename"];
        $fileDir = $data["directory"];
        $relativeFileDir = $data["relativeDirectory"];
        //todo sanitize dirs? fm_clean_path

        function scan_json_def($full_path, $filename, $wsi_filename_text, $relativeFileDir, &$output) {
            $fname_text = pathinfo($filename, PATHINFO_FILENAME);

            if (preg_match("/$wsi_filename_text.*\.json/", $filename)) {
                try {
                    $output[$fname_text] = json_decode(file_get_contents($full_path));
                    $output[$fname_text]->file = "$relativeFileDir/{$output[$fname_text]->file}";
                } catch (Exception $e) {
                    //pass
                }
            } else if (preg_match("/$wsi_filename_text.*\.tif/", $filename)) {
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
        $result = array();
        $specs = array();
        $wsi_text = pathinfo($wsi_filename, PATHINFO_FILENAME);
        $default_vis = "heatmap";

        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..') continue;

                $fpath = "$fileDir/$file";
                if (is_dir($fpath)) {
                    $nested = is_readable($fpath) ? scandir($fpath) : array();
                    if (is_array($nested)) {
                        foreach ($nested as $nestedFile) {
                            if ($nestedFile == '.' || $nestedFile == '..') continue;

                            scan_json_def("$fpath/$nestedFile", $nestedFile, $wsi_text,
                                "$relativeFileDir/$file", $specs);
                        }
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
                        scan_json_def($fpath, $file, $wsi_text, $relativeFileDir, $specs);
                    }
                }
            }
        }

        foreach ($specs as $spec_name=>$spec) {
            if (isset($spec->default) && $spec->default === false) {
                $spec->default = $default_vis;
            }
        }

        usort($specs, function ($a, $b) {
            return $a->order - $b->order;
        });
        $shader_data = json_encode($specs);
        global $js_path, $assets_path, $viewer_url;
        echo <<<EOF
    

   <head>
   <script type="text/javascript" src="$js_path/viewerConfig.js">

   </script>
   </head><body>
   <script>
      const data = $shader_data;
   const tissuePath = '$relativeFileDir/$wsi_filename';
   window.viewerConfig = new ViewerConfig({
        windowName: 'viewerConfig',
        viewerUrl: '$viewer_url',
        data: '',
   });
   
   viewerConfig.setTissue(tissuePath);
   for (let key in data) {
       const spec = data[key];
       viewerConfig.setShaderFor(spec.file, spec.default);
   }
   viewerConfig.withSession('$fileDir/$wsi_filename').open();
</script>
</body>
   

EOF;

        break;

    case "storeSession":
        require_once "SessionStore.php";
        $content = $data["session"];
        $file = $data["filename"];

        global $session_store;
        $session = new SessionStore($session_store);

        if (strlen($content) > 10e6)
            die(json_encode(array("status"=>"error", "message" => "Data too big.")));
        $session->storeOne($file, $content);
        die(json_encode(array("status"=>"success")));

        break;

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
