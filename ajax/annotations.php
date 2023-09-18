<?php
if (!defined('PATH_TO_IS_MANAGER')) {
    define('PATH_TO_IS_MANAGER', '../');
}
require_once PATH_TO_IS_MANAGER . 'ajax/ajax_init.php';

if (!USES_DATABASE) {
    send(403, "Annotations cannot be stored without a database!");
}


//The code
$protocol = $command = $id = $tissue = $data = $metadata = null;

//Some fallback code for support of old style links
//own IO parsing
try {
    if (isset($_GET["Annotation"])) {
        //outdated API, deprecated
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

function annotation_file_name($name) {
    return preg_replace("/\s/", "_", $name) . ".json";
}

require_once "_functions.php";

//////////////////////////////////////////////////////////////////////////////////////////////
//dirty: should be in xo_db
function xo_get_annotation_meta($id) {
    global $db;
    return $db->read("SELECT u.id AS user_id, u.name, u.email,
        a.file_id, a.id, a.metadata, f.name, f.created, f.status, f.root, f.biopsy
        FROM xopat_annotations a
        LEFT OUTER JOIN users u ON a.author_user_id=u.id
        LEFT OUTER JOIN files f ON a.file_id=f.id
        WHERE a.id=? LIMIT 1", [[$id, PDO::PARAM_INT]], PDO::FETCH_ASSOC);
}
//////////////////////////////////////////////////////////////////////////////////////////////


try {
    require_once XO_DB_ROOT . 'interfaces/annotations.php';

    switch ($command) {
        case "remove":
            //todo do not remove, just e.g. unlink
            require_presence_any($id, "id", "string", "integer");
            xo_annotations_remove($id, true);
            send_list_of_annotations_meta($tissue);
            break;

        case "update":
            //todo if someone already uploaded, what now? compare metadata!
            require_presence_any($id, "id", "string", "integer");
            require_presence($data, "string", "data");
            require_presence($metadata, "array", "metadata");
            $format = $metadata["annotations-format"];
            require_presence($format, "string", "format type");
            xo_annotations_update($id, $data, $format, "");

            $record = xo_get_annotation_meta($id);
            if (isset($record["name"])) {
                try {
                    $metadata = json_decode($record["metadata"], true);
                    $name = $metadata["annotations-name"];
                    $filepath = mirax_path_from_db_record($record);
                    file_put_contents(FM_BROWSE_ROOT . $filepath . "annotations/". annotation_file_name($name), $data);
                } catch (Exception $exception) {
                    error($exception);
                }
            }
            //read annotations to given file by id
            send_list_of_annotations_meta("$id",
                "string", "", 'xo_annotations_list_similar_by_annotation_id');
            break;

        case "load":
            require_presence_any($id, "id", "string", "integer");
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
            $name = $metadata["annotations-name"];

            require_presence($user_id, "integer", "user id");
            require_presence($format, "string", "format type");
            xo_annotations_create($tissue, $user_id, json_encode($metadata), $data, $format, "");

            require_once XO_DB_ROOT . 'include.php';
            $record = xo_get_file_by_name($tissue);
            if (isset($record["id"])) {
                $filepath = mirax_path_from_db_record($record);
                if (!is_dir(FM_BROWSE_ROOT . $filepath . "annotations")) {
                    mkdir(FM_BROWSE_ROOT . $filepath . "annotations", 0777, true);
                }
                file_put_contents(FM_BROWSE_ROOT . $filepath . "annotations/". annotation_file_name($name), $data);
            }

            send_list_of_annotations_meta($tissue);
            break;

        default:
            require_presence(null, "--fail--", "command");

    }
} catch (Exception $e) {
    send_as_json(500, $e);
}



