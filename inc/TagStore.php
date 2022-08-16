<?php

class TagStore {

    function __construct() {
        global $tag_store;
        $this->tags = (object)array();
        $this->con = new SQLite3($tag_store);
    }

    public function readTag($tag) {
        if (!isset($this->tags->{$tag})) {
            return $this->_readTag($tag);
        }
        return $this->tags->{$tag};
    }

    public function saveTag($tag) {
        if (!isset($this->tags->{$tag})) {
            return false;
        }
        return $this->_saveTag($tag);
    }

    private function _readTag($tag) {

    }

    private function _ensureDirExists($dir) {
        if (!is_dir($dir) && !mkdir($dir, 077, true)) {
            //too appropriate way of failing...
            die('Failed to create directories...');
        }
    }

//    private function _readTag($tag) {
//        global $tag_store;
//        $this->_ensureDirExists($tag_store);
//        $fn = "tag_store.json";
//        if (!file_exists($fn)) return "";
//        try {
//            $content = json_decode(file_get_contents($fn));
//            $this->tags->{$tag} = $content;
//        } catch (Exception $e) {
//            //too appropriate way of failing...
//            die($e);
//        }
//        return $this->tags->{$tag};
//    }
//
//    private function _saveTag($tag) {
//        global $tag_store;
//        $this->_ensureDirExists($tag_store);
//        $fn = $tag_store . "/" . $tag . "_" . $this->prefix . ".json";
//        return file_put_contents($fn, json_encode($this->tags->{$tag})) !== false;
//    }
}