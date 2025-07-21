<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    public static $instance = null; // Changé de public à public static
    public $conn;

    public function __construct() {
        $host = 'localhost';
        $db = 'library';
        $username = 'root';
        $password = '';
        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public static function connect() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }

    public static function disconnect() {
        if (self::$instance !== null) {
            self::$instance->conn = null;
            self::$instance = null;
        }
    }
}

$config = [
    'hash_passwords' => false // mot de passe non hashé comme demandé
];
?>