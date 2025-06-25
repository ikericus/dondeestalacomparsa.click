<?php

// Clustering simple
function calculateCluster() {
    $positions = DB::getUserPositions();
    
    if (count($positions) < 5) {
        return null;
    }
    
    // Algoritmo básico: encontrar el grupo más denso
    $clusters = [];
    foreach ($positions as $i => $pos1) {
        $cluster = [$pos1];
        foreach ($positions as $j => $pos2) {
            if ($i !== $j && haversineDistance($pos1['lat'], $pos1['lon'], $pos2['lat'], $pos2['lon']) <= 100) {
                $cluster[] = $pos2;
            }
        }
        if (count($cluster) >= 5) {
            $clusters[] = $cluster;
        }
    }
    
    if (empty($clusters)) {
        return null;
    }
    
    // Cluster más grande
    $biggest = [];
    foreach ($clusters as $cluster) {
        if (count($cluster) > count($biggest)) {
            $biggest = $cluster;
        }
    }
    
    // Calcular centroide
    $lat_sum = $lon_sum = 0;
    foreach ($biggest as $pos) {
        $lat_sum += $pos['lat'];
        $lon_sum += $pos['lon'];
    }
    
    return [
        'lat' => $lat_sum / count($biggest),
        'lon' => $lon_sum / count($biggest),
        'confidence' => min(1.0, count($biggest) / 20),
        'user_count' => count($biggest)
    ];
}

// Distancia entre dos puntos
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

// Posición programada simple
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