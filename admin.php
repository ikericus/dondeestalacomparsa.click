<?php 
require_once 'config.php';

// Headers anti-cache para evitar cacheo en Hostinger
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Verificar clave de administrador
if (!isset($_GET['key']) || $_GET['key'] !== Config::adminKey()) {
    http_response_code(403);
    die('Acceso denegado');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Administración - Comparsa</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    
    <style>
        body { 
            font-family: 'BlinkMacSystemFont', -apple-system, 'Segoe UI', 'Roboto', sans-serif; 
            height: 100vh; 
            margin: 0;
        }
        
        .admin-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #cc3333;
        }
        
        .status-active { color: #23d160; font-weight: bold; }
        .status-inactive { color: #ff3860; font-weight: bold; }
        
        .metric-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid #cc3333;
        }
        
        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #cc3333;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        #admin-map { 
            height: 500px; 
            border-radius: 6px;
        }
        
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #23d160;
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: #cc3333 !important;
            border-color: #cc3333 !important;
        }
        
        .refresh-btn:hover {
            background: #b02d2d !important;
            border-color: #b02d2d !important;
            transform: translateY(-1px);
        }
        
        /* Estilos similares al index */
        .hero-body { padding: 1rem 1.5rem !important; }
        
        @media (max-width: 768px) {
            .hero-body { padding: 0.75rem 1rem !important; }
            .title { font-size: 1.25rem !important; }
            .subtitle { font-size: 0.8rem !important; }
            .admin-section { padding: 1rem; margin-bottom: 1rem; }
            .metric-box { padding: 0.75rem; }
            #admin-map { height: 350px; }
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: calc(100vh - 120px);
        }
        
        .command-input {
            background: #1e1e1e;
            color: #fff;
            font-family: monospace;
            border: 1px solid #444;
        }
        
        .command-input:focus {
            border-color: #cc3333;
            box-shadow: 0 0 0 0.125em rgba(204, 51, 51, 0.25);
        }
        
        .system-log {
            background: #1e1e1e;
            color: #fff;
            font-family: monospace;
            font-size: 0.8rem;
            max-height: 150px;
            overflow-y: auto;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #444;
        }
    </style>
</head>
<body>
    
    <!-- Header similar al index -->
    <header>
        <section class="hero" style="background: linear-gradient(135deg, #d63031 0%, #cc3333 100%);">
            <div class="hero-body py-3">
                <div class="container">
                    
                    <!-- Título principal -->
                    <div class="has-text-centered mb-3">
                        <h1 class="title is-5 has-text-white mb-1" style="font-weight: 600;">
                            🔬 Panel de Administración
                        </h1>
                        <h2 class="subtitle is-7 has-text-white mb-2" style="opacity: 0.95;">
                            Comparsa de Gigantes y Cabezudos - Control en Tiempo Real
                        </h2>
                    </div>
                    
                    <!-- Estado del sistema en header -->
                    <div class="has-text-centered">
                        <div style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; padding: 0.75rem; display: inline-block;">
                            <span class="live-indicator"></span>
                            <span id="system-status" class="has-text-white" style="font-weight: bold;">Cargando...</span>
                            <span class="has-text-white" style="opacity: 0.8; margin-left: 1rem;">Última actualización:</span>
                            <span id="last-update" class="has-text-white" style="font-weight: bold;">--:--:--</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </header>

    <!-- Contenido principal -->
    <main class="main-content">
        <section class="section py-4">
            <div class="container">
                
                <!-- Métricas principales -->
                <div class="admin-section">
                    <h3 class="title is-5" style="color: #cc3333; margin-bottom: 1.5rem;">👥 Análisis de Usuarios</h3>
                    
                    <div class="columns is-multiline">
                        <div class="column is-6-tablet is-3-desktop">
                            <div class="metric-box">
                                <div class="metric-label">Usuarios Activos (5 min)</div>
                                <div class="metric-value" id="active-users">0</div>
                            </div>
                        </div>
                        <div class="column is-6-tablet is-3-desktop">
                            <div class="metric-box">
                                <div class="metric-label">En Cluster Principal</div>
                                <div class="metric-value" id="cluster-users">--</div>
                            </div>
                        </div>
                        <div class="column is-6-tablet is-3-desktop">
                            <div class="metric-box">
                                <div class="metric-label">Confianza Estimación</div>
                                <div class="metric-value" id="confidence-level">0%</div>
                            </div>
                        </div>
                        <div class="column is-6-tablet is-3-desktop">
                            <div class="metric-box">
                                <div class="metric-label">Precisión Algoritmo Clustering</div>
                                <div class="metric-value" id="algorithm-precision">--</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mapa de administración -->
                <div class="admin-section">
                    <h3 class="title is-5" style="color: #cc3333; margin-bottom: 1.5rem;">🗺️ Mapa de Control</h3>
                    <div id="admin-map"></div>
                    <div class="content mt-3" style="background: #f8f9fa; padding: 1rem; border-radius: 6px;">
                        <p class="help has-text-centered">
                            <span style="color: #cc3333; font-weight: bold;">🔴 Rojo:</span> Posición programada | 
                            <span style="color: #23d160; font-weight: bold;">🟢 Verde:</span> Posición estimada | 
                            <span style="color: #3273dc; font-weight: bold;">🔵 Azul:</span> Usuarios conectados
                        </p>
                    </div>
                </div>

                <!-- Control del sistema -->
                <div class="admin-section">
                    <h3 class="title is-5" style="color: #cc3333; margin-bottom: 1.5rem;">⚙️ Control del Sistema</h3>
                    
                    <div class="columns">
                        <div class="column">
                            <div class="notification is-info is-light">
                                <p><strong>Horarios de actividad programados:</strong></p>
                                <ul style="margin-top: 0.5rem;">
                                    <li><strong>6 julio:</strong> 17:00-21:00</li>
                                    <li><strong>7-14 julio:</strong> 09:30-15:00</li>
                                </ul>
                            </div>
                        </div>
                        <div class="column">
                            <div class="field has-addons">
                                <div class="control is-expanded">
                                    <input class="input command-input" type="text" placeholder="Comando manual..." id="manual-command">
                                </div>
                                <div class="control">
                                    <button class="button" style="background: #cc3333; border-color: #cc3333; color: white;" onclick="executeCommand()">
                                        Ejecutar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="system-log" id="system-log">
                        <div>Sistema iniciado...</div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <!-- Footer similar al index -->
    <footer class="footer" style="background: #f8f9fa; padding: 1rem 1.5rem;">
        <div class="content has-text-centered">
            <p class="is-size-7" style="color: #666;">
                <strong style="color: #333;">Panel de Administración</strong> | 
                <a href="mailto:admin@dondeestalacomparsa.click" style="color: #cc3333;">admin@dondeestalacomparsa.click</a>
            </p>
        </div>
    </footer>

    <!-- Botón de actualización -->
    <button class="button is-primary is-large refresh-btn" onclick="forceUpdate()" title="Actualizar datos">
        <span class="icon">
            <span>🔄</span>
        </span>
    </button>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        let adminMap;
        let estimatedMarker;
        let scheduledMarker;
        let userMarkers = [];
        let updateInterval;
        
        // Función para obtener hora local de Pamplona
        function getPamplonaTime() {
            return new Date().toLocaleTimeString('es-ES', {
                timeZone: 'Europe/Madrid',
                hour12: false
            });
        }
        
        // Inicializar mapa de administración
        function initAdminMap() {
            adminMap = L.map('admin-map').setView([42.8167, -1.6432], 15);
            
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(adminMap);
        }
        
        // Limpiar marcadores de usuarios existentes
        function clearUserMarkers() {
            userMarkers.forEach(marker => adminMap.removeLayer(marker));
            userMarkers = [];
        }
        
        // Mostrar usuarios individuales en el mapa
        function showUsersOnMap(users) {
            clearUserMarkers();
            
            users.forEach((user, index) => {
                const userMarker = L.marker([user.lat, user.lon], {
                    icon: L.icon({
                        iconUrl: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#3273dc"><circle cx="12" cy="12" r="8"/></svg>'),
                        iconSize: [16, 16]
                    })
                }).addTo(adminMap);
                
                userMarker.bindPopup(`Usuario ${index + 1}<br>Última actualización: ${user.timestamp}`);
                userMarkers.push(userMarker);
            });
        }
                
        // Actualizar datos del panel
        function updateAdminData() {
            fetch(`api.php/admin?key=<?= Config::adminKey() ?>`)
                .then(response => response.json())
                .then(data => {
                    // Estado del sistema
                    const statusEl = document.getElementById('system-status');
                    if (data.active) {
                        statusEl.textContent = 'SISTEMA ACTIVO';
                        statusEl.className = 'has-text-white status-active';
                    } else {
                        statusEl.textContent = 'SISTEMA INACTIVO';
                        statusEl.className = 'has-text-white status-inactive';
                    }
                    
                    // Última actualización (hora local de Pamplona)
                    document.getElementById('last-update').textContent = getPamplonaTime();
                    
                    // Usuarios activos (todos los que enviaron posición en últimos 5 min)
                    const totalUsers = data.user_positions ? data.user_positions.length : 0;
                    document.getElementById('active-users').textContent = totalUsers;
                    
                    // Usuarios en cluster principal (solo los que contribuyen al cálculo)
                    const clusterUsers = data.estimated ? data.estimated.user_count : 0;
                    document.getElementById('cluster-users').textContent = clusterUsers;
                    
                    // Confianza
                    const confidence = data.estimated ? Math.round(data.estimated.confidence * 100) : 0;
                    document.getElementById('confidence-level').textContent = confidence + '%';
                    
                    // Precisión del algoritmo (distancia entre estimada y programada)
                    let precision = 'N/A';
                    if (data.distance && data.distance !== null) {
                        precision = Math.round(data.distance) + ' m';
                        document.getElementById('algorithm-precision').textContent = precision;
                    } else {
                        document.getElementById('algorithm-precision').textContent = 'N/A';
                    }
                    
                    // Mostrar usuarios individuales en el mapa
                    if (data.user_positions) {
                        showUsersOnMap(data.user_positions);
                    }
                    
                    // Posición estimada
                    if (data.estimated) {
                        // Actualizar marcador estimado
                        if (estimatedMarker) {
                            estimatedMarker.setLatLng([data.estimated.lat, data.estimated.lon]);
                        } else {
                            estimatedMarker = L.marker([data.estimated.lat, data.estimated.lon], {
                                icon: L.icon({
                                    iconUrl: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#23d160"><circle cx="12" cy="12" r="10"/></svg>'),
                                    iconSize: [24, 24]
                                })
                            }).addTo(adminMap).bindPopup(`Posición Estimada<br>Confianza: ${confidence}%<br>Usuarios: ${clusterUsers}`);
                        }
                    }
                    
                    // Posición programada
                    if (data.scheduled) {
                        // Actualizar marcador programado
                        if (scheduledMarker) {
                            scheduledMarker.setLatLng([data.scheduled.lat, data.scheduled.lon]);
                        } else {
                            scheduledMarker = L.marker([data.scheduled.lat, data.scheduled.lon], {
                                icon: L.icon({
                                    iconUrl: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#cc3333"><circle cx="12" cy="12" r="10"/></svg>'),
                                    iconSize: [24, 24]
                                })
                            }).addTo(adminMap).bindPopup('Posición Programada<br>Según recorrido oficial');
                        }
                    }
                    
                    // Centrar mapa si tenemos posiciones
                    if (data.estimated && data.scheduled) {
                        const bounds = L.latLngBounds([
                            [data.estimated.lat, data.estimated.lon],
                            [data.scheduled.lat, data.scheduled.lon]
                        ]);
                        
                        // Incluir usuarios en el bounds
                        if (data.user_positions) {
                            data.user_positions.forEach(user => {
                                bounds.extend([user.lat, user.lon]);
                            });
                        }
                        
                        adminMap.fitBounds(bounds, { padding: [20, 20] });
                    }
                    
                    // Log del sistema
                    addToLog(`[${getPamplonaTime()}] Usuarios activos: ${totalUsers}, En cluster: ${clusterUsers}, Confianza: ${confidence}%, Precisión: ${precision}`);
                })
                .catch(error => {
                    console.error('Error actualizando datos:', error);
                    addToLog(`[ERROR] Error al actualizar datos: ${error.message}`);
                });
        }
        
        // Añadir entrada al log
        function addToLog(message) {
            const logEl = document.getElementById('system-log');
            const entry = document.createElement('div');
            entry.textContent = message;
            entry.style.marginBottom = '0.25rem';
            logEl.appendChild(entry);
            logEl.scrollTop = logEl.scrollHeight;
            
            // Limitar a últimas 30 entradas
            while (logEl.children.length > 30) {
                logEl.removeChild(logEl.firstChild);
            }
        }
        
        // Ejecutar comando manual
        function executeCommand() {
            const command = document.getElementById('manual-command').value;
            if (!command) return;
            
            addToLog(`[COMANDO] ${command}`);
            
            // Simular ejecución de comando
            setTimeout(() => {
                addToLog(`[RESULTADO] Comando ejecutado correctamente`);
            }, 500);
            
            document.getElementById('manual-command').value = '';
        }
        
        // Forzar actualización
        function forceUpdate() {
            addToLog('[SISTEMA] Forzando actualización de datos...');
            updateAdminData();
        }
        
        // Inicializar todo
        document.addEventListener('DOMContentLoaded', function() {
            initAdminMap();
            updateAdminData();
            
            // Actualizar cada 10 segundos
            updateInterval = setInterval(updateAdminData, 10000);
            
            // Permitir ejecutar comando con Enter
            document.getElementById('manual-command').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    executeCommand();
                }
            });
            
            addToLog('[SISTEMA] Panel de administración iniciado correctamente');
        });
        
        // Limpiar interval al salir
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html>