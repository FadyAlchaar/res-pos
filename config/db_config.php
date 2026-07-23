<?php
// Database Configuration Manager
// This file handles database connection settings

class DBConfig {
    private $config_file;
    private $settings;
    
    public function __construct() {
        $this->config_file = __DIR__ . '/database_config.json';
        $this->loadSettings();
    }
    
    private function loadSettings() {
        if (file_exists($this->config_file)) {
            $this->settings = json_decode(file_get_contents($this->config_file), true);
        } else {
            // Default settings
            $this->settings = [
                'host' => 'localhost',
                'database' => 'restaurant_pos',
                'username' => 'root',
                'password' => '',
                'port' => 3306,
                'charset' => 'utf8mb4'
            ];
        }
    }
    
    private function saveSettings() {
        file_put_contents($this->config_file, json_encode($this->settings, JSON_PRETTY_PRINT));
    }
    
    public function getSettings() {
        return $this->settings;
    }
    
    public function updateSettings($host, $database, $username, $password, $port) {
        $this->settings['host'] = $host;
        $this->settings['database'] = $database;
        $this->settings['username'] = $username;
        $this->settings['password'] = $password;
        $this->settings['port'] = $port;
        $this->saveSettings();
        return true;
    }
    
    public function testConnection($host = null, $database = null, $username = null, $password = null, $port = null) {
        $testHost = $host ?? $this->settings['host'];
        $testDb = $database ?? $this->settings['database'];
        $testUser = $username ?? $this->settings['username'];
        $testPass = $password ?? $this->settings['password'];
        $testPort = $port ?? $this->settings['port'];
        
        try {
            $pdo = new PDO(
                "mysql:host=$testHost;port=$testPort;charset=utf8mb4",
                $testUser,
                $testPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Check if database exists
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$testDb'");
            if ($stmt->fetch()) {
                return ['success' => true, 'message' => "Connected to database '$testDb' successfully"];
            } else {
                return ['success' => false, 'message' => "Database '$testDb' does not exist. Please create it first."];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "Connection failed: " . $e->getMessage()];
        }
    }
    
    public function generateConnectionString() {
        return "mysql:host={$this->settings['host']};port={$this->settings['port']};dbname={$this->settings['database']};charset={$this->settings['charset']}";
    }
}
?>