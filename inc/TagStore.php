<?php
//Not used yet
//
//todo add metadata removal upon file deletion if used
class TagStore extends SQLite3 {

    private static $_operators = array(
        "AND" => "= ALL (",
        "OR" => "= SOME (",
    );

    function __construct() {
        global $tag_store;
        $this->open($tag_store, SQLITE3_OPEN_READWRITE);
        $this->exec("CREATE TABLE IF NOT EXISTS files (id INT AUTO_INCREMENT PRIMARY KEY, file TEXT);");
        $this->exec("CREATE TABLE IF NOT EXISTS tags (id INT AUTO_INCREMENT PRIMARY KEY, tag TEXT);");
        $this->exec("CREATE TABLE IF NOT EXISTS tagging (file_id INT, tag_id INT) PRIMARY KEY(file_id, tag_id);");
    }

    private function _commonQuery($sqlCreator, $operator, ...$elements) {
        $sql = join(",", array_map(function($_) {return "?";}, $elements));
        $op = isset(TagStore::$_operators[$operator]) ? TagStore::$_operators[$operator] : TagStore::$_operators["OR"];

        $stmt = $this->prepare($sqlCreator($op, $sql));
        for ($i = 0; $i < count($elements); $i++) {
            $stmt->bindValue($i+1, $elements[$i], SQLITE3_TEXT);
        }
        return $stmt->execute();
    }

    public function readFilesTags($operator="OR", ...$files) {
        return $this->_commonQuery(function ($operator, $list) {
            return "SELECT * FROM tagging JOIN 
    (SELECT id FROM files WHERE file {$operator}{$list}) as f ON tagging.file_id = f.id JOIN 
    tags as t ON tagging.tag_id = t.id";
        }, $operator, ...$files);
    }

    public function readTagsFiles($operator="OR", ...$tags) {
        return $this->_commonQuery(function ($operator, $list) {
            return "SELECT * FROM tagging JOIN 
    (SELECT id FROM tags WHERE tag {$operator}{$list})) as t ON tagging.tag_id = t.id JOIN 
    files as f ON tagging.file_id = f.id";
        }, $operator, ...$tags);
    }

    public function getFiles($operator="OR", ...$files) {
        return $this->_commonQuery(function ($operator, $list) {
            return "SELECT * FROM files WHERE file {$operator}{$list}";
        }, $operator, ...$files);
    }

    public function getTags($operator="OR", ...$tags) {
        return $this->_commonQuery(function ($operator, $list) {
            return "SELECT * FROM tags WHERE tag {$operator}{$list}";
        }, $operator, ...$tags);
    }

    public function tagFile($tag, $file) {
        $tags = $this->getTags("AND", $tag);
        $files = $this->getFiles("AND", $file);

        if (!$tags || count($tags) < 1) {
            $stmt = $this->prepare("INSERT INTO tags VALUES (?)");
            $stmt->bindValue(1, $tag, SQLITE3_TEXT);
            $stmt->execute();
            $tags = array(
                array("id" => SQLite3::lastInsertRowID())
            );
        }
        if (!$files || count($files) < 1) {
            $stmt = $this->prepare("INSERT INTO files VALUES (?)");
            $stmt->bindValue(1, $file, SQLITE3_TEXT);
            $stmt->execute();
            $files = array(
                array("id" => SQLite3::lastInsertRowID())
            );
        }

        $this->exec("INSERT OR IGNORE INTO INTO tagging VALUES ({$files[0]["id"]}, {$tags[0]["id"]})");
    }

    public function unTagFile($tag, $file) {
        $tags = $this->getTags("AND", $tag);
        $files = $this->getFiles("AND", $file);

        if (!$tags || count($tags) < 1) {
            return;
        }
        if (!$files || count($files) < 1) {
            return;
        }

        $this->exec("DELETE FROM tagging WHERE file_id={$files[0]["id"]} AND tag_id={$tags[0]["id"]}");
    }
}
