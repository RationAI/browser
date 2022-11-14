<?php

$content = file_get_contents("php://input");
global $global_input;
try {
    $global_input = (array)json_encode($content);
} catch (Exception $e) {
    //pass not a valid input
    $global_input = array();
}

if (isset($_GET["ajax"]) || isset($_POST["ajax"]) || isset($global_input["ajax"])) {
    require_once "ajax.php";
} else {
    require_once "files.php";
}