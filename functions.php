<?php

// Clustering inteligente - siempre calcula una posición
function calculateCluster() {
    $positions = DB::getUserPositions();
    
    if (count($positions) < 2) {
        return null; // Necesitamos al menos 2 usuarios
    }
    
    // ALGORITMO ADAPTATIVO con múltiples estrategias
    
    // 1. Intentar cluster denso (estrategia original pero más flexible)
    $denseCluster = findDenseCluster($positions);
    if ($denseCluster) {
        return $denseCluster;
    }
    
    // 2. Si no hay cluster denso, usar cluster por proximidad
    $proximityCluster = findProximityCluster($positions);
    if ($proximityCluster) {
        return $proximityCluster;
    }
    
    // 3. Como último recurso, calcular centroide de todos
    return calculateCentroid($positions);
}

// Estrategia 1: Buscar cluster denso (versión mejorada)
function findDenseCluster($positions) {
    $minUsers = max(3, min(5, count($positions) * 0.3)); // Entre 3 y 5, o 30% de usuarios
    $radius = 150; // Radio más amplio: 150m
    
    $clusters = [];
    foreach ($positions as $i => $pos1) {
        $cluster = [$pos1];
        foreach ($positions as $j => $pos2) {
            if ($i !== $j && haversineDistance($pos1['lat'], $pos1['lon'], $pos2['lat'], $pos2['lon']) <= $radius) {
                $cluster[] = $pos2;
            }
        }
        if (count($cluster) >= $minUsers) {
            $clusters[] = $cluster;
        }
    }
    
    if (empty($clusters)) {
        return null;
    }
    
    // Cluster más grande
    $biggest = array_reduce($clusters, function($a, $b) {
        return count($a) > count($b) ? $a : $b;
    }, []);
    
    return [
        'lat' => array_sum(array_column($biggest, 'lat')) / count($biggest),
        'lon' => array_sum(array_column($biggest, 'lon')) / count($biggest),
        'confidence' => calculateConfidence(count($biggest), count($positions), 'dense'),
        'user_count' => count($biggest),
        'method' => 'dense_cluster'
    ];
}

// Estrategia 2: Cluster por proximidad (usuarios que se dirigen al mismo sitio)
function findProximityCluster($positions) {
    if (count($positions) < 3) {
        return null;
    }
    
    // Buscar el área con más usuarios en un radio mayor
    $radius = 300; // Radio amplio: 300m
    $bestArea = null;
    $maxUsers = 0;
    
    foreach ($positions as $center) {
        $nearbyUsers = [];
        foreach ($positions as $user) {
            if (haversineDistance($center['lat'], $center['lon'], $user['lat'], $user['lon']) <= $radius) {
                $nearbyUsers[] = $user;
            }
        }
        
        if (count($nearbyUsers) > $maxUsers) {
            $maxUsers = count($nearbyUsers);
            $bestArea = $nearbyUsers;
        }
    }
    
    if ($maxUsers < 3) {
        return null;
    }
    
    return [
        'lat' => array_sum(array_column($bestArea, 'lat')) / count($bestArea),
        'lon' => array_sum(array_column($bestArea, 'lon')) / count($bestArea),
        'confidence' => calculateConfidence(count($bestArea), count($positions), 'proximity'),
        'user_count' => count($bestArea),
        'method' => 'proximity_cluster'
    ];
}

// Estrategia 3: Centroide ponderado (último recurso)
function calculateCentroid($positions) {
    // Eliminar outliers extremos (usuarios muy alejados)
    $filteredPositions = removeOutliers($positions);
    
    if (empty($filteredPositions)) {
        $filteredPositions = $positions; // Si se eliminan todos, usar todos
    }
    
    return [
        'lat' => array_sum(array_column($filteredPositions, 'lat')) / count($filteredPositions),
        'lon' => array_sum(array_column($filteredPositions, 'lon')) / count($filteredPositions),
        'confidence' => calculateConfidence(count($filteredPositions), count($positions), 'centroid'),
        'user_count' => count($filteredPositions),
        'method' => 'weighted_centroid'
    ];
}

// Eliminar usuarios muy alejados (outliers)
function removeOutliers($positions) {
    if (count($positions) <= 3) {
        return $positions; // Con pocos usuarios, no eliminar ninguno
    }
    
    // Calcular centro geográfico aproximado
    $centerLat = array_sum(array_column($positions, 'lat')) / count($positions);
    $centerLon = array_sum(array_column($positions, 'lon')) / count($positions);
    
    // Calcular distancia media
    $distances = [];
    foreach ($positions as $pos) {
        $distances[] = haversineDistance($centerLat, $centerLon, $pos['lat'], $pos['lon']);
    }
    
    $avgDistance = array_sum($distances) / count($distances);
    $maxAllowedDistance = $avgDistance * 2.5; // Eliminar usuarios a más de 2.5x la distancia media
    
    $filtered = [];
    foreach ($positions as $i => $pos) {
        if ($distances[$i] <= $maxAllowedDistance) {
            $filtered[] = $pos;
        }
    }
    
    return $filtered;
}

// Calcular confianza basada en método y distribución
function calculateConfidence($usersInCluster, $totalUsers, $method) {
    $baseConfidence = min(1.0, $usersInCluster / 15); // Máximo con 15 usuarios
    
    // Ajustar por método
    switch ($method) {
        case 'dense':
            return $baseConfidence * 1.0; // Máxima confianza
        case 'proximity':
            return $baseConfidence * 0.8; // 80% de confianza
        case 'centroid':
            return $baseConfidence * 0.6; // 60% de confianza
        default:
            return $baseConfidence * 0.5;
    }
}

// Distancia entre dos puntos (sin cambios)
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Radio tierra en metros
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $R * $c;
}

// Posición programada simple (sin cambios)
function getScheduledPosition() {
    $day = (int)date('d');
    
    $positions = [
        6 => ['lat' => 42.8167, 'lon' => -1.6432],
        7 => ['lat' => 42.8179, 'lon' => -1.6447], 
        8 => ['lat' => 42.8167, 'lon' => -1.6432],
        9 => ['lat' => 42.8179, 'lon' => -1.6447],
        10 => ['lat' => 42.8167, 'lon' => -1.6432],
        11 => ['lat' => 42.8179, 'lon' => -1.6447],
        12 => ['lat' => 42.8167, 'lon' => -1.6432],
        13 => ['lat' => 42.8179, 'lon' => -1.6447],
        14 => ['lat' => 42.8167, 'lon' => -1.6432]
    ];
    
    return $positions[$day] ?? $positions[6];
}

?>