<?php
require_once 'config.php';

class DB {
    private static $conn = null;
    
    public static function connect() {
        if (self::$conn === null) {
            $db = Config::db();
            self::$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
            
            if (self::$conn->connect_error) {
                die('Error BD: ' . self::$conn->connect_error);
            }
            self::$conn->set_charset('utf8mb4');
        }
        return self::$conn;
    }
    
    // Obtener última posición
    public static function getPosition($day, $date) {
        $conn = self::connect();
        $stmt = $conn->prepare("SELECT lat, lon, date FROM position WHERE day = ? AND date < ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("is", $day, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Obtener recorrido
    public static function getPath($day) {
        $conn = self::connect();
        $stmt = $conn->prepare("SELECT lat, lon as lng FROM path WHERE day = ? ORDER BY step");
        $stmt->bind_param("i", $day);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Insertar posición
    public static function insertPosition($lat, $lon, $day = null) {
        $conn = self::connect();
        if ($day) {
            $stmt = $conn->prepare("INSERT INTO position (lat, lon, day, date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("ddi", $lat, $lon, $day);
        } else {
            $stmt = $conn->prepare("INSERT INTO position (lat, lon, date) VALUES (?, ?, NOW())");
            $stmt->bind_param("dd", $lat, $lon);
        }
        return $stmt->execute();
    }
    
    // Posiciones de usuarios para clustering
    public static function insertUserPosition($user_id, $lat, $lon) {
        $conn = self::connect();
        $stmt = $conn->prepare("INSERT INTO user_positions (user_id, lat, lon, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sdd", $user_id, $lat, $lon);
        return $stmt->execute();
    }
    
    public static function getUserPositions() {
        $conn = self::connect();
        $result = $conn->query("SELECT lat, lon, timestamp FROM user_positions WHERE timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Limpiar datos antiguos (ejecutar una vez al día)
    public static function cleanup() {
        $conn = self::connect();
        $conn->query("DELETE FROM user_positions WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }
}
?>