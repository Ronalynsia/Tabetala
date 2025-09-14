<?php
require_once __DIR__ . "/../config/config.php";

class Database {
    private $host = "localhost";   // usually localhost
    private $db_name = "tabetala";  // palitan ng database name mo
    private $username = "root";    // db username
    private $password = "";        // db password
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
