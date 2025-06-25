<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>¿Dónde está la comparsa? - San Fermín 2025</title>
    
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    
    <!-- Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RPTYE7WZQL"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-RPTYE7WZQL');
    </script>
    
    <style>
        #map { height: 75vh; }
        body { height: 100vh; margin: 0; }
        
        .live-indicator {
            color: #ff3860;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .admin-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            max-width: 300px;
            display: none;
        }
        
        @media (max-width: 768px) {
            .admin-panel {
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    
    <!-- Panel admin (solo visible con ?admin) -->
    <?php if (isset($_GET['admin']) && $_GET['admin'] === Config::adminKey()): ?>
    <div class="admin-panel" id="adminPanel">
        <h4 class="title is-6">🔬 Panel Admin</h4>
        <div class="content is-small">
            <p><strong>Estado:</strong> <span id="status">Cargando...</span></p>
            <p><strong>Usuarios activos:</strong> <span id="users">--</span></p>
            <p><strong>Estimación:</strong> <span id="estimate">--</span></p>
            <p><strong>Diferencia:</strong> <span id="distance">--</span></p>
            <p><strong>Última actualización:</strong> <span id="lastUpdate">--</span></p>
        </div>
        <button class="button is-small is-danger" onclick="document.getElementById('adminPanel').style.display='none'">Cerrar</button>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <section class="hero" style="background-color:#cc3333">
        <div class="hero-body py-4">
            <div class="container">
                <div class="level">
                    <div class="level-left">
                        <div>
                            <h1 id="title" class="title is-4 has-text-white mb-2">¿Dónde está la comparsa?</h1>
                            <h2 id="subtitle" class="subtitle is-6 has-text-white">
                                San Fermín 2025 
                                <span class="live-indicator">● EN VIVO</span>
                            </h2>
                        </div>
                    </div>
                    <div class="level-right">
                        <button class="button is-small is-white is-outlined" onclick="requestNotifications()">
                            🔔 Notificaciones
                        </button>
                        <span class="language-selector ml-3">
                            <a class="has-text-white" onclick="changeLan('cas')" title="Castellano"> CAS </a>
                            <a class="has-text-white ml-2" onclick="changeLan('eus')" title="Euskera"> EUS </a>
                            <a class="has-text-white ml-2" onclick="changeLan('eng')" title="English"> ENG </a>
                        </span>
                    </div>
                </div>
                
                <div class="field">
                    <label id="path" class="label has-text-white is-small">Día del recorrido:</label>
                    <div class="select is-small">
                        <select id="daySelect">
                            <option value="6">6 Julio</option>
                            <option value="7">7 Julio</option>
                            <option value="8">8 Julio</option>
                            <option value="9">9 Julio</option>
                            <option value="10">10 Julio</option>
                            <option value="11">11 Julio</option>
                            <option value="12">12 Julio</option>
                            <option value="13">13 Julio</option>
                            <option value="14">14 Julio</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mapa -->
    <div id="map"></div>

    <!-- Footer -->
    <footer class="footer py-3">
        <div class="content has-text-centered">
            <p class="is-size-7">
                <a href="mailto:contacto@dondeestalacomparsa.click">contacto@dondeestalacomparsa.click</a>
            </p>
        </div>
    </footer>

    <!-- JavaScript externo -->
    <script src="script.js"></script>
    
    <!-- Panel admin y funciones específicas del index -->
    <script>
        // Panel admin específico
        function updateAdminPanel() {
            if (!<?= isset($_GET['admin']) && $_GET['admin'] === Config::adminKey() ? 'true' : 'false' ?>) return;
            
            document.getElementById('adminPanel').style.display = 'block';
            
            const update = () => {
                fetch(`api.php/admin?key=<?= Config::adminKey() ?>`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('status').textContent = data.active ? 'ACTIVO' : 'INACTIVO';
                    document.getElementById('users').textContent = data.estimated ? data.estimated.user_count : '0';
                    document.getElementById('estimate').textContent = data.estimated ? 
                        `${data.estimated.lat.toFixed(6)}, ${data.estimated.lon.toFixed(6)}` : 'Sin datos';
                    document.getElementById('distance').textContent = data.distance ? 
        
        // Event listeners
        document.getElementById('daySelect').addEventListener('change', function() {
            loadDay(this.value);
            gtag('event', 'day_selected', { 'day': this.value });
        });
        
        // Inicializar todo
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            startUserTracking();
            updateAdminPanel();
        });
        
        // Modo tracking manual (si se añade ?track)
        <?php if (isset($_GET['track'])): ?>
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(pos => {
                fetch('api.php/track', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`
                });
                
                if (currentMarker) map.removeLayer(currentMarker);
                currentMarker = L.marker([pos.coords.latitude, pos.coords.longitude])
                    .addTo(map)
                    .bindPopup('📍 Tracking activo');
            });
        }
        <?php endif; ?>
    </script> => {
                    new Notification('🎭 Comparsa de Gigantes', {
                        body: 'La comparsa sale en 15 minutos',
                        icon: '/icon.png'
                    });
                }, delay);
            }
        }
        
        // Event listeners
        document.getElementById('daySelect').addEventListener('change', function() {
            loadDay(this.value);
            gtag('event', 'day_selected', { 'day': this.value });
        });
        
        // Inicializar todo
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            startUserTracking();
            updateAdminPanel();
        });
        
        // Modo tracking manual (si se añade ?track)
        <?php if (isset($_GET['track'])): ?>
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(pos => {
                fetch('api.php/track', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`
                });
                
                if (currentMarker) map.removeLayer(currentMarker);
                currentMarker = L.marker([pos.coords.latitude, pos.coords.longitude])
                    .addTo(map)
                    .bindPopup('📍 Tracking activo');
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>