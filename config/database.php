<?php
// Database Connection Handler
// Uses configuration from database_config.json

class Database {
    private $conn;
    private $config;
    
    public function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    private function loadConfig() {
        $config_file = __DIR__ . '/database_config.json';
        
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            $this->config = [
                'host' => $config['host'] ?? 'localhost',
                'dbname' => $config['database'] ?? 'restaurant_pos',
                'username' => $config['username'] ?? 'root',
                'password' => $config['password'] ?? '',
                'port' => $config['port'] ?? 3306,
                'charset' => $config['charset'] ?? 'utf8mb4'
            ];
        } else {
            // Fallback to default if config not found
            $this->config = [
                'host' => 'localhost',
                'dbname' => 'restaurant_pos',
                'username' => 'root',
                'password' => '',
                'port' => 3306,
                'charset' => 'utf8mb4'
            ];
        }
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            $this->conn = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "<br><br>
                 Please run <a href='config/setup_database.php'>Database Setup</a> to configure your connection.");
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function testConnection() {
        try {
            $this->connect();
            return ['success' => true, 'message' => 'Connected successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>