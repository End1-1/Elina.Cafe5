<?php
# © 2025 , Kudryashov Vasili
# Created: 2025-05-25 13:31:11
# Last Modified: 2025-11-09 16:49:03

class Db
{
    private $dbhost = "localhost";
    private $dbschema = "store";
    private $dbuser = "root";
    private $dbpass = "root5";
    protected $dbconnection;
    protected $result = ["status" => 1];

    public function __construct()
    {
        $this->dbconnection = new mysqli($this->dbhost, $this->dbuser, $this->dbpass, $this->dbschema);
        if ($this->dbconnection->connect_error) {
            die("Connection failed: " . $this->dbconnection->connect_error);
        }
    }

    public function beginTransaction() {
        return  $this->dbconnection->begin_transaction();
    }

    public function commit() {
        return $this->dbconnection->commit();
    }

    public function select($query, $types = "", $params = [], $noreturn = false)
    {
        $stmt = $this->dbconnection->prepare($query);
        if ($stmt === false) {
            dieWithCode("Prepare failed: {$this->dbconnection->error}");
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            dieWithCode("Execute failed:{$stmt->error}");
        }

        $result = $stmt->get_result();
        if (!$noreturn) {
            if ($result === false) {
                dieWithCode("Get result failed: {$stmt->error}");
            }
        }

        return $result;
    }

    public function insert($table, $params)
    {
        $fields = "";
        $values = "";
        $bindValues = [];
        $bindTypes = "";

        foreach ($params as $k => $v) {
            if (!empty($fields)) {
                $fields .= ",";
                $values .= ",";
            }
            $fields .= $k;
            $values .= "?";
            $bindValues[] = $v;

            $bindTypes .= match (gettype($v)) {
                'integer' => 'i',
                'double' => 'd',
                default => 's',
            };
        }

        $sql = "INSERT INTO $table ($fields) VALUES ($values)";
        if (!$stmt = $this->dbconnection->prepare($sql)) {
            dieWithCode($this->dbconnection->error);
        }

        $stmt->bind_param($bindTypes, ...$bindValues);

        if (!$stmt->execute()) {
            dieWithCode($stmt->error);
        }

        $id = $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    public function update($table, $params, $id, $field = "f_id")
    {
        $sql = "update $table set ";
        $bindTypes = "";
        $bindValues = [];
        foreach ($params as $k => $v) {
            if (!empty($bindTypes)) {
                $sql .= ",";
            }
            $sql .= "$k=?";
            array_push($bindValues, $v);
            switch (gettype($v)) {
                case "integer":
                    $bindTypes .= "i";
                    break;
                case "double":
                    $bindTypes .= "d";
                    break;
                default:
                    $bindTypes .= "s";
                    break;
            }
        }
        $sql .= " where $field=?";
        $bindTypes .= "s";
        array_push($bindValues, $id);
        if (!$stmt = $this->dbconnection->prepare($sql)) {
            dieWithCode($this->dbconnection->error);
        }

        $stmt->bind_param($bindTypes, ...$bindValues);
        if (!$stmt->execute()) {
            dieWithCode($stmt->error);
        }
        $stmt->close();
    }

    public function delete($tableName, $id, $fieldName = "f_id")
    {
        $sql = <<<EOD
        delete  from $tableName where $fieldName =?
        EOD;
        if (!$stmt = $this->dbconnection->prepare($sql)) {
            dieWithCode($this->dbconnection->error);
        }
        $bindTypes = "";
        switch (gettype($id)) {
            case "integer":
                $bindTypes .= "i";
                break;
            case "double":
                $bindTypes .= "d";
                break;
            default:
                $bindTypes .= "s";
                break;
        }
        $stmt->bind_param($bindTypes, $id);
        if (!$stmt->execute()) {
            dieWithCode($stmt->error);
        }
        $stmt->close();
    }


    public function callJsonProcedure($procName, $jsonIn)
    {
        $this->dbconnection->query("SET @f_out = NULL");
        $sql = "CALL $procName(?, @f_out)";
        $stmt = $this->dbconnection->prepare($sql);
        if (!$stmt) {
            dieWithCode("Prepare failed: {$this->dbconnection->error}");
        }

        $stmt->bind_param("s", $jsonIn);

        if (!$stmt->execute()) {
            dieWithCode("Execute failed: {$stmt->error}");
        }
        $stmt->close();

        $result = $this->dbconnection->query("SELECT @f_out as f_out");
        if (!$result) {
            dieWithCode("Select OUT param failed: {$this->dbconnection->error}");
        }
        $row = $result->fetch_assoc();
        return $row['f_out'] ?? null;
    }

    public function echoResult()
    {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($this->result);
    }
}
