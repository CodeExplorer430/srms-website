<?php
/**
 * Database Class
 * Handles database connections and queries
 */
class Database {
    private $connection;
    
    /**
     * Constructor - establishes database connection
     */
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Connect to the database
     */
    private function connect() {
        $host = DB_SERVER;
        $user = DB_USERNAME;
        $pass = DB_PASSWORD;
        $db = DB_NAME;
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        
        $this->connection = new mysqli($host, $user, $pass, $db, $port);
        
        if ($this->connection->connect_error) {
            die('Database connection failed: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset('utf8mb4');
    }
    
    /**
     * Execute a query
     * 
     * @param string $sql - SQL query to execute
     * @return mixed - Query result or false on failure
     */
    public function query($sql) {
        $result = $this->connection->query($sql);
        
        if (!$result) {
            error_log('Query Error: ' . $this->connection->error . ' in query: ' . $sql);
        }
        
        return $result;
    }
    
    /**
     * Fetch a single row from a query result
     * 
     * @param string $sql - SQL query to execute
     * @return array|null - Associative array of row data or null if no results
     */
    public function fetch_row($sql) {
        $result = $this->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Fetch all rows from a query result
     * 
     * @param string $sql - SQL query to execute
     * @return array - Array of associative arrays containing row data
     */
    public function fetch_all($sql) {
        $result = $this->query($sql);
        $rows = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    /**
     * Escape a string for SQL insertion
     * 
     * @param string $string - String to escape
     * @return string - Escaped string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Get the ID of the last inserted row
     * 
     * @return int - ID of the last inserted row
     */
    public function insert_id() {
        return $this->connection->insert_id;
    }
    
    /**
     * Close the database connection
     */
    public function close() {
        $this->connection->close();
    }
}