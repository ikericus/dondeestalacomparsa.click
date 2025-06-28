<?php
require_once 'db.php';
require_once 'functions.php';

// Headers anti-cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Rate limiting simple
session_start();

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
            
            // Verificar que tenemos coordenadas
            if (!isset($_POST['lat']) || !isset($_POST['lon'])) {
                exit(json_encode(['error' => 'Coordenadas faltantes']));
            }
            
            $lat = (float)$_POST['lat'];
            $lon = (float)$_POST['lon'];
            
            if (!Config::validCoords($lat, $lon)) {
                exit(json_encode(['error' => 'Coordenadas inválidas']));
            }
            
            // Usar session_id() como user_id, o generar uno único
            $user_id = session_id();
            if (empty($user_id)) {
                $user_id = 'anon_' . uniqid();
            }
            
            $success = DB::insertUserPosition($user_id, $lat, $lon);
            
            if ($success) {
                echo json_encode(['status' => 'ok']);
            } else {
                echo json_encode(['error' => 'Error insertando posición']);
            }
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
            $userPositions = DB::getUserPositions();
            
            $data = [
                'estimated' => $estimated,
                'scheduled' => $scheduled,
                'user_positions' => $userPositions,
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