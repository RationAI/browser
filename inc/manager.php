<?php


if (isset($_GET["ajax"]) || isset($_POST["ajax"])) {
    require_once "ajax.php";
} else {
    require_once "files.php";
}