<?php
   class Database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "luoitea_house_2";

    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->query("CREATE DATABASE IF NOT EXISTS $this->dbname");
        $this->conn->select_db($this->dbname);
    }
}
?>