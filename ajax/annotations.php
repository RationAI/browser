<?php

if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '../');
}
require_once PATH_TO_IS_MANAGER . 'ajax/ajax_init.php';

//The code
$protocol = $command = $id = $tissue = $data = $metadata = null;

//Some fallback code for support of old style links
//own IO parsing
try {
    if (isset($_GET["Annotation"])) {
        $protocol = "Annotation";
        $parsed = explode('/', $_GET["Annotation"]);
        $command = trim(array_shift($parsed));
        //depends on the command, they do not use both params
        $id = $tissue = trim(implode('/', $parsed));
        $user_id = -1;
    } else {
        $protocol = $_POST["protocol"];
        $command = $_POST["command"];
        $id = $_POST["id"];
        $tissue = $_POST["tissuePath"];
        $data = $_POST["data"];
        $metadata = $_POST["metadata"];

        require_presence($metadata, "array", "metadata");
        $user_id = (int)$metadata["user"];
        if ($user_id < 1) {
            send(403, "Access denied for unregistered users!");
        }
    }
    $tissue = $tissue ? basename($tissue) : null;

} catch (Exception $e) {
    echo $e;
    die;
}

function send_list_of_annotations_meta($param,
                                       $param_type = "string",
                                       $err = "tissue unique id",
                                       $getter = 'xo_annotations_list_all')
{
    require_presence($param, $param_type, $err);
    $data = array_map(function ($x) {
        $x["metadata"] = json_decode($x["metadata"]);
        return $x;
    }, call_user_func($getter, $param));
    send_as_json(200, $data);
}


require_presence($protocol, "string", "protocol");

try {
    require_once XO_DB_ROOT . 'interfaces/annotations.php';

    switch ($command) {
        case "remove":
            require_presence($id, "string", "id");
            xo_annotations_remove($id, true);
            send_list_of_annotations_meta($tissue);
            break;

        case "update":
            //todo if someone already uploaded, what now? compare metadata!
            require_presence($id, "string", "id");
            require_presence($data, "string", "data");
            require_presence($metadata, "array", "metadata");
            $format = $metadata["annotations-format"];
            require_presence($format, "string", "format type");
            xo_annotations_update($id, $data, $format, "");

            //read annotations to given file by id
            send_list_of_annotations_meta($id,
                "string", "", 'xo_annotations_list_similar_by_annotation_id');
            break;

        case "load":
            require_presence($id, "string", "id");
            send_as_json(200, xo_annotations_read($id));
            break;

        case "list":
            send_list_of_annotations_meta($tissue);
            break;

        case "history":
            //todo untested
            require_presence($id, "string", "id");
            $data = xo_annotations_get_history($id);
            send_as_json(200, $data);
            break;

        case "save":
            require_presence($tissue, "string", "tissue unique id");
            require_presence($data, "string", "data");
            $format = $metadata["annotations-format"];
            require_presence($user_id, "integer", "user id");
            require_presence($format, "string", "format type");
            xo_annotations_create($tissue, $user_id, json_encode($metadata), $data, $format, "");

            send_list_of_annotations_meta($tissue);
            break;

        default:
            require_presence(null, "--fail--", "command");

    }
} catch (Exception $e) {
    send_as_json(500, $e);
}



