<?php

class SessionStore extends SQLite3
{

    function __construct($session_store)
    {
        $this->open($session_store, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $this->exec('PRAGMA journal_mode = wal;');
        $this->busyTimeout(5000);
        $this->try($this->exec("CREATE TABLE IF NOT EXISTS sessions (id varchar(255), user varchar(255), session TEXT, PRIMARY KEY (id, user));"));
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
        return $this->try($stmt->execute());
    }

    public function readOne($id, $user)
    {
        return $this->_commonQuery(function ($where) {
            return "SELECT * FROM sessions WHERE $where";
        }, array("id" => $id, "user" => $user));
    }

    public function storeOne($id, $user, $content)
    {
        $data = $this->readOne($id, $user);
        if ($data->fetchArray(SQLITE3_ASSOC) !== false) {
            $stmt = $this->try($this->prepare("UPDATE sessions SET session=? WHERE user=? AND id=? VALUES (?, ?, ?)"));
            $stmt->bindValue(1, $content, SQLITE3_TEXT);
            $stmt->bindValue(2, $user, SQLITE3_TEXT);
            $stmt->bindValue(3, $id, SQLITE3_TEXT);
            return $stmt->execute();
        }

        $stmt = $this->try($this->prepare("INSERT INTO sessions(id, user, session) VALUES (?, ?, ?)"));
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $stmt->bindValue(2, $user, SQLITE3_TEXT);
        $stmt->bindValue(3, $content, SQLITE3_TEXT);
        return $stmt->execute();
    }
}