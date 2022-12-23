<?php

class UserStore extends SQLite3
{

    function __construct($user_store)
    {
        $this->open($user_store, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $this->exec('PRAGMA journal_mode = wal;');
        $this->busyTimeout(5000);
        $this->try($this->exec("CREATE TABLE IF NOT EXISTS seen (user varchar(255), path varchar(255), UNIQUE(user, path))"));
    }

    private function try($result)
    {
        if (!$result) {
            //$this->reporter($this->lastErrorMsg());
            throw new Exception($this->lastErrorMsg());
        }
        return $result;
    }

    public function checkSeen($user, $path)
    {
        $stmt = $this->try($this->prepare("SELECT * FROM seen WHERE user=? AND path=? LIMIT 1"));
        $stmt->bindValue(1, $user, SQLITE3_TEXT);
        $stmt->bindValue(2, $path, SQLITE3_TEXT);
        return $this->try($stmt->execute());
    }

    public function setSeen($user, $path)
    {
        $stmt = $this->try($this->prepare("INSERT OR IGNORE INTO seen(user, path) VALUES (?, ?)"));
        $stmt->bindValue(1, $user, SQLITE3_TEXT);
        $stmt->bindValue(2, $path, SQLITE3_TEXT);
        return $this->try($stmt->execute());
    }
}
