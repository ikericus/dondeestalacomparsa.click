<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Rate limiting simple
session_start();
// $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
// if (isset($_SESSION["last_$ip"]) && (time() - $_SESSION["last_$ip"]) < 1) {
//     http_response_code(429);
//     exit(json_encode(['error' => 'Demasiadas peticiones']));
// }
// $_SESSION["last_$ip"] = time();

// Obtener endpoint
$endpoint = $_SERVER['PATH_INFO'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if ($endpoint === '/position') {
            $day = (int)$_POST['day'];
            $date = $_POST['date'];
            
            if ($day < 6 || $day > 14) {
                exit(json_encode(['error' => 'Día inválido']));
            }
            
            $result = DB::getPosition($day, $date);
            echo json_encode($result ?: ['status' => 'ok']);
        }
        
        elseif ($endpoint === '/path') {
            $day = (int)$_POST['day'];
            $result = DB::getPath($day);
            echo json_encode($result);
        }
        
        elseif ($endpoint === '/track') {
            $lat = (float)$_POST['lat'];
            $lon = (float)$_POST['lon'];
            
            if (!Config::validCoords($lat, $lon)) {
                exit(json_encode(['error' => 'Coordenadas inválidas']));
            }
            
            $day = (int)date('d');
            DB::insertPosition($lat, $lon, $day);
            echo json_encode(['status' => 'ok']);
        }
        
        elseif ($endpoint === '/user_position') {
            if (!Config::isActive()) {
                exit(json_encode(['status' => 'inactive']));
            }
            
            $lat = (float)$_POST['lat'];
            $lon = (float)$_POST['lon'];
            
            if (!Config::validCoords($lat, $lon)) {
                exit(json_encode(['error' => 'Coordenadas inválidas']));
            }
            
            DB::insertUserPosition(session_id(), $lat, $lon);
            echo json_encode(['status' => 'ok']);
        }
        
        else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
        }
    }
    
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        if ($endpoint === '/estimate') {
            if (!Config::isActive()) {
                exit(json_encode(['error' => 'Inactivo']));
            }
            
            $estimate = calculateCluster();
            echo json_encode($estimate);
        }
        
        elseif ($endpoint === '/admin' && isset($_GET['key']) && $_GET['key'] === Config::adminKey()) {
            $estimated = calculateCluster();
            $scheduled = getScheduledPosition();
            
            $data = [
                'estimated' => $estimated,
                'scheduled' => $scheduled,
                'distance' => $estimated ? haversineDistance(
                    $estimated['lat'], $estimated['lon'],
                    $scheduled['lat'], $scheduled['lon']
                ) : null,
                'time' => date('H:i:s'),
                'active' => Config::isActive()
            ];
            
            echo json_encode($data);
        }
        
        else {
            header('Location: index.php');
        }
    }
    
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => 'Error interno']);
}
?>