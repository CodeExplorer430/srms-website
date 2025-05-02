<?php
class Database {
    private $connection;
    
    // Constructor establishes database connection
    public function __construct() {
        try {
            $this->connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
            
            if($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            // Log error and provide better error message
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration in environment.php and ensure your database server is running.");
        }
    }
    
    // Execute query
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    // Fetch a single row as associative array
    public function fetch_row($sql) {
        $result = $this->query($sql);
        if($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }
    
    // Fetch all rows as associative array
    public function fetch_all($sql) {
        $result = $this->query($sql);
        $rows = [];
        
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    // Escape strings to prevent SQL injection
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    // Get the ID of the last inserted row
    public function insert_id() {
        return $this->connection->insert_id;
    }
    
    // Close the database connection
    public function close() {
        $this->connection->close();
    }
}
?>