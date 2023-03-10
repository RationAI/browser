<?php
global $_DATA;
if (count($_GET) > 0) {
    $_DATA = $_GET;
} else if (count($_POST)) {
    $_DATA = $_POST;
} else {
    try {
        $_DATA = (array)json_decode(file_get_contents("php://input"));
    } catch (Exception $e) {
        //pass not a valid input
        $_DATA = array();
    }
}

class Database extends SQLite3 {
    function __construct($db) {
        $this->enableExceptions(true);
        $this->open($db, SQLITE3_OPEN_READWRITE);
        $this->exec('PRAGMA journal_mode = wal;');
        $this->busyTimeout(5000);
    }

    public function run($sql, $data=array()) {
        $stmt = $this->try($this->prepare($sql));
        $i = 1;
        foreach ($data as $_ => $input) {
            $stmt->bindValue($i++, $input[0], $input[1]);
        }
        return $this->try($stmt->execute());
    }

    public function read($statement, $style=SQLITE3_ASSOC) {
        return $statement->fetchArray($style);
    }

    public function read_with($statement, $clbck, $style=SQLITE3_ASSOC) {
        while (($data = $statement->fetchArray($style))) {
            $clbck($data);
        }
    }

    public function read_all($statement, $style=SQLITE3_ASSOC) {
        $result = [];
        while (($data = $statement->fetchArray($style))) {
            $result[]=$data;
        }
        return $result;
    }

    public function try($result) {
        if (!$result) {
            //$this->reporter($this->lastErrorMsg());
            throw new Exception($this->lastErrorMsg());
        }
        return $result;
    }
}


set_exception_handler(function (Throwable $exception) {
    error($exception->getMessage());
});

function error($msg) {
    echo json_encode((object)array(
        "status" => "error",
        "message" => $msg,
    ));
    die();
}

function send($data=null) {
    $response = array("status" => "success");
    if ($data !== null) $response["data"] = $data;
    echo json_encode($response);
    die();
}
