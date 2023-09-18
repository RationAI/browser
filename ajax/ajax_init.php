<?php
// Ajax API
defined('PATH_TO_IS_MANAGER') or die('Invalid access!');

function require_presence_any($var, $missing, ...$types) {
    if (!isset($var) || !array_reduce($types,
            function ($carry, $item) use ($var) {return $carry || gettype($var) === $item;}, false)) {
        $types = implode(", ", $types);
        send(400, "Invalid request: missing or invalid '$missing'! (any from [$types], got '$var')");
    }
}

function require_presence($var, $type, $missing) {
    if (!isset($var) || gettype($var) !== $type) {
        send(400, "Invalid request: missing or invalid '$missing'! (type $type, got '$var')");
    }
}

function send_as_json($code, $data) {
    send($code, json_encode($data));
}

function send_ok($data=[]) {
    $data["status"] = "success";
    send_as_json(200, $data);
}

function error($msg) {
    send_as_json(502, array(
        "status" => "error",
        "message" => $msg,
    ));
}

function send($code, $data)
{
    echo $data;
    http_response_code($code);
    exit;
}

set_exception_handler(function (Throwable $exception) {
    send(502, $exception->getMessage());
});

require_once PATH_TO_IS_MANAGER . 'inc/init.php';
//db proxy
if (USES_DATABASE) require_once XO_DB_ROOT . "include.php";

//Checks, defaults

//todo some things will fail if they become un-registered make sure
//the registration will stay until they are active, e.g. by constant refresh!
if (_FM_USE_AUTH && !FM_LOGGED) {
    send(403, ["status"=>"unauthorized"]);
}
