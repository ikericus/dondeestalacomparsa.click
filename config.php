<?php

// Cargar variables de entorno
function loadEnv() {
    if (!file_exists('.env')) {
        die('Error: Crear archivo .env (copiar desde .env.example)');
    }
    
    foreach (file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

loadEnv();

// Configuración simple
class Config {
    // Base de datos
    public static function db() {
        return [
            'host' => $_ENV['DB_HOST'],
            'user' => $_ENV['DB_USER'], 
            'pass' => $_ENV['DB_PASS'],
            'name' => $_ENV['DB_NAME']
        ];
    }
    
    // Clave admin
    public static function adminKey() {
        return $_ENV['ADMIN_KEY'];
    }
    
    // Coordenadas de Pamplona
    public static function validCoords($lat, $lon) {
        return ($lat >= 42.7 && $lat <= 42.9 && $lon >= -1.8 && $lon <= -1.5);
    }
    
    // Horarios San Fermín (solo lo esencial)
    public static function isActive() {
        $day = (int)date('d');
        $hour = (int)date('H');
        
        // Solo activo 6-14 julio entre 10:00-14:00 y 18:00-21:00
        return ($day >= 6 && $day <= 14 && 
               (($hour >= 10 && $hour <= 14) || ($hour >= 18 && $hour <= 21)));
    }
}

// Log simple
function logError($msg) {
    error_log(date('Y-m-d H:i:s') . " - $msg\n", 3, 'app.log');
}

?>