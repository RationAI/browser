<?php
# Simple server database API

# 0) define configuration - inline or in specific file
$db_path = "/mnt/data/visualization/importer/data/.user.sqlite";

# 1) include IO - parse POST, GET or raw JSON input
require_once "io.php"; //$_DATA, send, error, Database class and error handler definition
global $_DATA;
# 2) possibly include authentication, here none required

# 3) process request
$db = new Database($db_path);
$db->exec("CREATE TABLE IF NOT EXISTS user_info (id VARCHAR(255), record VARCHAR(255), value TEXT, UNIQUE(id, record));");

switch ($_DATA["action"]) {
    case "load":
        send($db->read($db->run("SELECT * FROM user_info WHERE id=? AND record=? LIMIT 1", [
            [$_DATA["id"], SQLITE3_TEXT],
            [$_DATA["key"], SQLITE3_TEXT]
        ])));
    case "save":
        $data = $db->read($db->run("INSERT OR REPLACE INTO user_info VALUES (?, ?, ?)", [
            [$_DATA["id"], SQLITE3_TEXT],
            [$_DATA["key"], SQLITE3_TEXT],
            [$_DATA["value"], SQLITE3_TEXT]
        ]));
        send();
//    case "contents":
//        send($db->read_all($db->run("SELECT * FROM user_info")));
    default:
        error("Invalid action command " + $_DATA["action"]);
}

