<?php

class SessionStore extends SQLite3
{

    function __construct($session_store)
    {
        $this->open($session_store, SQLITE3_OPEN_READWRITE);
        $this->try($this->exec("CREATE TABLE IF NOT EXISTS sessions (id varchar(255) PRIMARY KEY, session TEXT);"));
    }

    // public function reporter(...$args) {
    //     echo join(", ", $args);
    // }

    private function try($result)
    {
        if (!$result) {
            //$this->reporter($this->lastErrorMsg());
            throw new Exception($this->lastErrorMsg());
        }
        return $result;
    }

    /**
     * @param callable $sqlCreator function that takes in WHERE clause argument
     * @param Array $conditions condition list from which to generate WHERE clause content
     * @return false|SQLite3Result
     */
    private function _commonQuery($sqlCreator, $conditions)
    {
        $sql = join(" AND ", array_map(function ($_) {
            return "?=?";
        }, $conditions));

        $stmt = $this->prepare($sqlCreator($sql));

        $i = 1;
        foreach ($conditions as $key => $value) {
            $stmt->bindValue($i++, $key, SQLITE3_TEXT);
            $stmt->bindValue($i++, $value, SQLITE3_TEXT);
        }
        return $stmt->execute();
    }

    public function readOne($id)
    {
        return $this->_commonQuery(function ($where) {
            return "SELECT * FROM sessions WHERE $where";
        }, array("id" => $id));
    }

    public function storeOne($id, $content)
    {
        $stmt = $this->try($this->prepare("INSERT INTO sessions(id, session) VALUES (?, ?)"));
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $stmt->bindValue(2, $content, SQLITE3_TEXT);
        return $stmt->execute();
    }
}