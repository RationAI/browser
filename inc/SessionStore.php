<?php

class SessionStore {

    function __construct($session_store) {
        $this->con = new SQLite3($session_store, SQLITE3_OPEN_CREATE);
        $this->con->exec("CREATE TABLE IF NOT EXISTS sessions (id varchar(255) PRIMARY KEY, session TEXT);");
    }

    /**
     * @param callable $sqlCreator function that takes in WHERE clause argument
     * @param Array $conditions condition list from which to generate WHERE clause content
     * @return false|SQLite3Result
     */
    private function _commonQuery($sqlCreator, $conditions) {
        $sql = join(" AND ", array_map(function($_) {return "?=?";}, $conditions));

        $stmt = $this->con->prepare($sqlCreator($sql));

        $i = 1;
        foreach ($conditions as $key => $value) {
            $stmt->bindValue($i++, $key, SQLITE3_TEXT);
            $stmt->bindValue($i++, $value, SQLITE3_TEXT);
        }
        return $stmt->execute();
    }

    public function readOne($id) {
        return $this->_commonQuery(function ($where) {
            return "SELECT * FROM sessions WHERE $where";
        }, array("id" => $id));
    }

    public function storeOne($id, $content) {
        $stmt = $this->con->prepare("INSERT INTO sessions(id, session) VALUES (?, ?)");
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $stmt->bindValue(2, $content, SQLITE3_TEXT);
        return $stmt->execute();
    }


}