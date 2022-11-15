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

    private function try($result)
    {
        if (!$result) {
            //$this->reporter($this->lastErrorMsg());
            throw new Exception($this->lastErrorMsg());
        }
        return $result;
    }

    public function readOne($id, $user)
    {
        $stmt = $this->try($this->prepare("SELECT * FROM sessions WHERE id=? AND user=? LIMIT 1"));
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $stmt->bindValue(2, $user, SQLITE3_TEXT);
        return $this->try($stmt->execute());
    }

    public function storeOne($id, $user, $content)
    {
        $stmt = $this->try($this->prepare("DELETE FROM sessions WHERE user=? AND id=?"));
        $stmt->bindValue(1, $user, SQLITE3_TEXT);
        $stmt->bindValue(2, $id, SQLITE3_TEXT);
        $stmt->execute();

        $stmt = $this->try($this->prepare("INSERT INTO sessions(id, user, session) VALUES (?, ?, ?)"));
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $stmt->bindValue(2, $user, SQLITE3_TEXT);
        $stmt->bindValue(3, $content, SQLITE3_TEXT);
        return $this->try($stmt->execute());
    }
}
