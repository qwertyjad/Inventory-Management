<?php
class Database {
    private $host = "localhost";
    private $db_name = "inventory_db";
    private $username = "root";
    private $password = "";
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }

    public function prepare($query) {
        return $this->conn->prepare($query);
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>