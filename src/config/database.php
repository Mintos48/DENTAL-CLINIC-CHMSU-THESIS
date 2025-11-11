<?php
/**
 * Database Configuration
 * Centralized database connection for all branches
 */

define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');
define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : 'dental_clinic_db');
define('DB_PORT', getenv('DB_PORT') ? getenv('DB_PORT') : 3306);

// Branches Configuration
define('BRANCHES', [
    'talisay' => [
        'id' => 1,
        'name' => 'Talisay Branch',
        'city' => 'Talisay',
        'address' => 'Talisay, Cebu'
    ],
    'silay' => [
        'id' => 2,
        'name' => 'Silay Branch',
        'city' => 'Silay',
        'address' => 'Silay, Negros Occidental'
    ],
    'sarabia' => [
        'id' => 3,
        'name' => 'Sarabia Branch',
        'city' => 'Sarabia',
        'address' => 'Sarabia, Iloilo'
    ]
]);

/**
 * Create database connection
 */
class Database {
    private static $connection;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                self::$connection = new mysqli(
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME,
                    DB_PORT
                );
                
                if (self::$connection->connect_error) {
                    die('Connection failed: ' . self::$connection->connect_error);
                }
                
                self::$connection->set_charset("utf8mb4");
            } catch (Exception $e) {
                die('Database Error: ' . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    public static function closeConnection() {
        if (self::$connection !== null) {
            self::$connection->close();
        }
    }
}

// Get connection instance
$db = Database::getConnection();
?>
