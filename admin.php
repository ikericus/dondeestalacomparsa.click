<?php 
require_once 'config.php';

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
        body { font-family: 'BlinkMacSystemFont', -apple-system, 'Segoe UI', 'Roboto', sans-serif; }
        
        .admin-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
            height: 400px; 
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
        }
    </style>
</head>
<body>
    
    <!-- Header -->
    <section class="hero is-primary">
        <div class="hero-body py-4">
            <div class="container">
                <h1 class="title">🔬 Panel de Administración</h1>
                <h2 class="subtitle">Comparsa de Gigantes y Cabezudos</h2>
            </div>
        </div>
    </section>

    <!-- Contenido principal -->
    <section class="section">
        <div class="container">
            
            <!-- Estado del sistema -->
            <div class="admin-section">
                <h3 class="title is-4">📊 Estado del Sistema</h3>
                
                <div class="columns">
                    <div class="column">
                        <div class="metric-box">
                            <div class="metric-label">Estado Actual</div>
                            <div class="metric-value">
                                <span class="live-indicator"></span>
                                <span id="system-status">Cargando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="metric-box">
                            <div class="metric-label">Última Actualización</div>
                            <div class="metric-value" id="last-update">--:--:--</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métricas de usuarios -->
            <div class="admin-section">
                <h3 class="title is-4">👥 Usuarios Activos</h3>
                
                <div class="columns">
                    <div class="column">
                        <div class="metric-box">
                            <div class="metric-label">Usuarios Conectados</div>
                            <div class="metric-value" id="active-users">0</div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="metric-box">
                            <div class="metric-label">Confianza de Estimación</div>
                            <div class="metric-value" id="confidence-level">0%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posición estimada vs programada -->
            <div class="admin-section">
                <h3 class="title is-4">📍 Posicionamiento</h3>
                
                <div class="columns">
                    <div class="column">
                        <h4 class="subtitle is-6">Posición Estimada (Clustering)</h4>
                        <div class="content">
                            <p><strong>Coordenadas:</strong> <span id="estimated-coords">--</span></p>
                            <p><strong>Usuarios en cluster:</strong> <span id="cluster-users">--</span></p>
                        </div>
                    </div>
                    <div class="column">
                        <h4 class="subtitle is-6">Posición Programada</h4>
                        <div class="content">
                            <p><strong>Coordenadas:</strong> <span id="scheduled-coords">--</span></p>
                            <p><strong>Diferencia:</strong> <span id="position-difference">--</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mapa de administración -->
            <div class="admin-section">
                <h3 class="title is-4">🗺️ Mapa de Control</h3>
                <div id="admin-map"></div>
                <div class="content mt-3">
                    <p class="help">
                        <span style="color: #cc3333;">🔴 Rojo:</span> Posición programada |
                        <span style="color: #23d160;">🟢 Verde:</span> Posición estimada |
                        <span style="color: #3273dc;">🔵 Azul:</span> Usuarios activos
                    </p>
                </div>
            </div>

            <!-- Logs del sistema -->
            <div class="admin-section">
                <h3 class="title is-4">📝 Información del Sistema</h3>
                <div class="content">
                    <div class="notification is-info is-light">
                        <p><strong>Horarios de actividad:</strong></p>
                        <ul>
                            <li>6 julio: 17:00-21:00</li>
                            <li>7-14 julio: 09:30-15:00</li>
                        </ul>
                    </div>
                    
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input" type="text" placeholder="Comando manual..." id="manual-command">
                        </div>
                        <div class="control">
                            <button class="button is-primary" onclick="executeCommand()">Ejecutar</button>
                        </div>
                    </div>
                    
                    <div class="box" style="background: #1e1e1e; color: #fff; font-family: monospace; font-size: 0.8rem; max-height: 200px; overflow-y: auto;" id="system-log">
                        <div>Sistema iniciado...</div>
                    </div>
                </div>
            </div>

        </div>
    </section>

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
        
        // Inicializar mapa de administración
        function initAdminMap() {
            adminMap = L.map('admin-map').setView([42.8167, -1.6432], 15);
            
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(adminMap);
        }
        
        // Actualizar datos del panel
        function updateAdminData() {
            fetch(`api.php/admin?key=<?= Config::adminKey() ?>`)
                .then(response => response.json())
                .then(data => {
                    // Estado del sistema
                    const statusEl = document.getElementById('system-status');
                    if (data.active) {
                        statusEl.textContent = 'ACTIVO';
                        statusEl.className = 'status-active';
                    } else {
                        statusEl.textContent = 'INACTIVO';
                        statusEl.className = 'status-inactive';
                    }
                    
                    // Última actualización
                    document.getElementById('last-update').textContent = data.time || '--:--:--';
                    
                    // Usuarios activos
                    const userCount = data.estimated ? data.estimated.user_count : 0;
                    document.getElementById('active-users').textContent = userCount;
                    
                    // Confianza
                    const confidence = data.estimated ? Math.round(data.estimated.confidence * 100) : 0;
                    document.getElementById('confidence-level').textContent = confidence + '%';
                    
                    // Posiciones
                    if (data.estimated) {
                        document.getElementById('estimated-coords').textContent = 
                            `${data.estimated.lat.toFixed(6)}, ${data.estimated.lon.toFixed(6)}`;
                        document.getElementById('cluster-users').textContent = data.estimated.user_count;
                        
                        // Actualizar marcador estimado
                        if (estimatedMarker) {
                            estimatedMarker.setLatLng([data.estimated.lat, data.estimated.lon]);
                        } else {
                            estimatedMarker = L.marker([data.estimated.lat, data.estimated.lon], {
                                icon: L.icon({
                                    iconUrl: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#23d160"><circle cx="12" cy="12" r="10"/></svg>'),
                                    iconSize: [24, 24]
                                })
                            }).addTo(adminMap).bindPopup('Posición Estimada');
                        }
                    } else {
                        document.getElementById('estimated-coords').textContent = 'Sin datos';
                        document.getElementById('cluster-users').textContent = '0';
                    }
                    
                    if (data.scheduled) {
                        document.getElementById('scheduled-coords').textContent = 
                            `${data.scheduled.lat.toFixed(6)}, ${data.scheduled.lon.toFixed(6)}`;
                        
                        // Actualizar marcador programado
                        if (scheduledMarker) {
                            scheduledMarker.setLatLng([data.scheduled.lat, data.scheduled.lon]);
                        } else {
                            scheduledMarker = L.marker([data.scheduled.lat, data.scheduled.lon], {
                                icon: L.icon({
                                    iconUrl: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#cc3333"><circle cx="12" cy="12" r="10"/></svg>'),
                                    iconSize: [24, 24]
                                })
                            }).addTo(adminMap).bindPopup('Posición Programada');
                        }
                    }
                    
                    // Diferencia de posición
                    if (data.distance) {
                        document.getElementById('position-difference').textContent = Math.round(data.distance) + ' metros';
                    } else {
                        document.getElementById('position-difference').textContent = 'N/A';
                    }
                    
                    // Centrar mapa si tenemos posiciones
                    if (data.estimated && data.scheduled) {
                        const bounds = L.latLngBounds([
                            [data.estimated.lat, data.estimated.lon],
                            [data.scheduled.lat, data.scheduled.lon]
                        ]);
                        adminMap.fitBounds(bounds, { padding: [20, 20] });
                    }
                    
                    // Log del sistema
                    addToLog(`[${data.time}] Datos actualizados - Usuarios: ${userCount}, Estado: ${data.active ? 'ACTIVO' : 'INACTIVO'}`);
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
            logEl.appendChild(entry);
            logEl.scrollTop = logEl.scrollHeight;
            
            // Limitar a últimas 50 entradas
            while (logEl.children.length > 50) {
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
                addToLog(`[RESULTADO] Comando ejecutado`);
            }, 500);
            
            document.getElementById('manual-command').value = '';
        }
        
        // Forzar actualización
        function forceUpdate() {
            addToLog('[SISTEMA] Actualizando datos...');
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
            
            addToLog('[SISTEMA] Panel de administración iniciado');
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