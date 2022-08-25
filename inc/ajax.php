<?php

switch ($_POST["ajax"]) {
    default:
        throw new Exception("Unknown ajax request call: " . $_POST["ajax"]);
}
