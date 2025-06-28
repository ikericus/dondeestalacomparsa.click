<?php 
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test - Simulador de Posiciones Programadas</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    
    <style>
        body { 
            font-family: 'BlinkMacSystemFont', -apple-system, 'Segoe UI', 'Roboto', sans-serif; 
            height: 100vh; 
            margin: 0;
        }
        
        #test-map { 
            height: calc(100vh - 200px); 
            min-height: 400px;
        }
        
        .controls-section {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-bottom: 3px solid #cc3333;
        }
        
        .time-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: #cc3333;
            text-align: center;
            margin: 1rem 0;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            border: 2px solid #cc3333;
        }
        
        .slider-container {
            margin: 1rem 0;
        }
        
        .slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: #ddd;
            outline: none;
            -webkit-appearance: none;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #cc3333;
            cursor: pointer;
        }
        
        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #cc3333;
            cursor: pointer;
            border: none;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #cc3333;
            margin-top: 1rem;
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
        
        .position-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    
    <!-- Header -->
    <header>
        <section class="hero is-small" style="background: linear-gradient(135deg, #d63031 0%, #cc3333 100%);">
            <div class="hero-body py-3">
                <div class="container">
                    <div class="has-text-centered">
                        <h1 class="title is-4 has-text-white mb-1">
                            🧪 Test - Simulador de Posiciones
                        </h1>
                        <h2 class="subtitle is-6 has-text-white">
                            Visualiza las posiciones programadas de los gigantes
                        </h2>
                    </div>
                </div>
            </div>
        </section>
    </header>

    <!-- Controles -->
    <section class="controls-section">
        <div class="container">
            <div class="columns">
                <!-- Selector de fecha -->
                <div class="column is-4">
                    <div class="field">
                        <label class="label">📅 Fecha de San Fermín</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select id="date-selector">
                                    <option value="6">6 de julio - Chupinazo</option>
                                    <option value="7">7 de julio</option>
                                    <option value="8">8 de julio</option>
                                    <option value="9">9 de julio</option>
                                    <option value="10">10 de julio</option>
                                    <option value="11">11 de julio</option>
                                    <option value="12">12 de julio</option>
                                    <option value="13">13 de julio</option>
                                    <option value="14">14 de julio - Pobre de mí</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Display de hora -->
                <div class="column is-4">
                    <div class="field">
                        <label class="label">🕐 Hora actual</label>
                        <div class="time-display" id="time-display">
                            10:00:00
                        </div>
                    </div>
                </div>
                
                <!-- Controles de reproducción -->
                <div class="column is-4">
                    <div class="field">
                        <label class="label">⚡ Controles</label>
                        <div class="buttons">
                            <button class="button is-primary" id="play-btn" onclick="togglePlayback()">
                                ▶️ Reproducir
                            </button>
                            <button class="button is-light" onclick="resetTime()">
                                🔄 Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slider de tiempo -->
            <div class="slider-container">
                <label class="label">🎛️ Control de tiempo (arrastra para cambiar la hora)</label>
                <input type="range" id="time-slider" class="slider" min="600" max="900" value="600" step="5">
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                    <span>10:00</span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span>15:00</span>
                </div>
            </div>
            
            <!-- Información de estado -->
            <div class="info-box">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span class="live-indicator"></span>
                        <strong>Estado:</strong> <span id="status-text">Listo para simular</span>
                    </div>
                    <div class="position-info" id="position-info">
                        Posición: No cargada
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mapa -->
    <main>
        <div id="test-map"></div>
    </main>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        let testMap;
        let dayPath;
        let giantMarker;
        let startMarker;
        let finishMarker;
        let pathPoints = [];
        let isPlaying = false;
        let playInterval;
        
        const centro = [42.81690537406873, -1.6432940644729581];
        
        // Inicializar mapa
        function initTestMap() {
            testMap = L.map('test-map').setView(centro, 15);
            
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(testMap);
        }
        
        // Función de peticiones AJAX
        function request(url, method, data, fnSuccess, fnError) {
            fetch(url, {
                method: method,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: method === 'POST' ? Object.keys(data).map(key => 
                    encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
                ).join('&') : null
            })
            .then(response => response.json())
            .then(fnSuccess)
            .catch(fnError);
        }
        
        // Obtener horarios del día
        function getDaySchedule(day) {
            const schedules = {
                6: { start: '17:00', end: '21:00' },   // Chupinazo
                7: { start: '09:30', end: '15:00' },   // Primer día completo
                8: { start: '09:30', end: '15:00' },
                9: { start: '09:30', end: '15:00' },
                10: { start: '09:30', end: '15:00' },
                11: { start: '09:30', end: '15:00' },
                12: { start: '09:30', end: '15:00' },
                13: { start: '09:30', end: '15:00' },
                14: { start: '09:30', end: '15:00' }   // Pobre de mí
            };
            return schedules[day] || { start: '09:30', end: '15:00' };
        }
        
        // Convertir hora HH:MM a minutos
        function timeToMinutes(timeStr) {
            const [hours, minutes] = timeStr.split(':').map(Number);
            return hours * 60 + minutes;
        }
        
        // Configurar slider según horarios del día
        function configureTimeSlider(day) {
            const schedule = getDaySchedule(day);
            const minMinutes = timeToMinutes(schedule.start);
            const maxMinutes = timeToMinutes(schedule.end);
            
            const slider = document.getElementById('time-slider');
            slider.min = minMinutes;
            slider.max = maxMinutes;
            slider.value = minMinutes; // Empezar en la hora de inicio
            
            // Actualizar etiquetas del slider
            updateSliderLabels(schedule.start, schedule.end);
            
            // Actualizar display de tiempo
            updateTimeDisplay();
            
            return { minMinutes, maxMinutes };
        }
        
        // Actualizar etiquetas del slider
        function updateSliderLabels(startTime, endTime) {
            const labelsContainer = document.querySelector('.slider-container').lastElementChild;
            labelsContainer.innerHTML = `
                <span>${startTime}</span>
                <span></span>
                <span></span>
                <span></span>
                <span>${endTime}</span>
            `;
        }
        
        // Cargar recorrido del día
        function loadDayPath(day) {
            document.getElementById('status-text').textContent = 'Cargando recorrido...';
            
            // Configurar horarios del día
            const timeRange = configureTimeSlider(day);
            
            request('api.php/path', 'POST', { day: day }, function(points) {
                // Limpiar marcadores anteriores
                if (dayPath) testMap.removeLayer(dayPath);
                if (startMarker) testMap.removeLayer(startMarker);
                if (finishMarker) testMap.removeLayer(finishMarker);
                if (giantMarker) testMap.removeLayer(giantMarker);
                
                if (!points || points.length == 0) {
                    document.getElementById('status-text').textContent = 'Sin recorrido disponible';
                    return;
                }
                
                pathPoints = points;
                
                // Dibujar recorrido
                dayPath = L.polyline(pathPoints, {color: '#cc3333', weight: 5}).addTo(testMap);
                
                // Marcadores de inicio y fin
                var start = pathPoints[0];
                var finish = pathPoints[pathPoints.length - 1];
                
                const schedule = getDaySchedule(day);
                
                startMarker = L.marker([start.lat, start.lng], {
                    icon: L.icon({ 
                        iconUrl: 'inicio.png', 
                        iconSize: [32, 32], 
                        popupAnchor: [0, -10]
                    })
                }).addTo(testMap).bindPopup(`🏁 Inicio del recorrido<br>⏰ ${schedule.start}`);
                
                finishMarker = L.marker([finish.lat, finish.lng], {
                    icon: L.icon({ 
                        iconUrl: 'final.png', 
                        iconSize: [32, 32]
                    })
                }).addTo(testMap).bindPopup(`🏆 Final del recorrido<br>⏰ ${schedule.end}`);
                
                // Ajustar vista del mapa
                testMap.fitBounds(dayPath.getBounds(), { padding: [20, 20] });
                
                document.getElementById('status-text').textContent = 
                    `Recorrido cargado - Horario: ${schedule.start} a ${schedule.end}`;
                
                // Cargar posición inicial
                updateGiantPosition();
                
            }, function(error) { 
                console.error('Error cargando recorrido:', error);
                document.getElementById('status-text').textContent = 'Error cargando recorrido';
            });
        }
        
        // Actualizar posición de los gigantes
        function updateGiantPosition() {
            const day = document.getElementById('date-selector').value;
            const timeMinutes = document.getElementById('time-slider').value;
            const hours = Math.floor(timeMinutes / 60);
            const minutes = timeMinutes % 60;
            
            // Crear fecha simulada (mes 6 = julio, día específico)
            const simulatedDate = new Date(2025, 6, parseInt(day), hours, minutes, 0);
            const isoDate = simulatedDate.toISOString();
            
            console.log(`Debug: Day=${day}, Hours=${hours}, Minutes=${minutes}, ISO=${isoDate}`);
            
            document.getElementById('status-text').textContent = 'Obteniendo posición...';
            
            // Usar la API con la fecha específica
            request('api.php/position', 'POST', { 
                day: day, 
                date: isoDate
            }, function(data) {
                if (data && data.lat && data.lon) {
                    // Actualizar o crear marcador de gigantes
                    if (giantMarker) {
                        giantMarker.setLatLng([Number(data.lat), Number(data.lon)]);
                    } else {
                        giantMarker = L.marker([Number(data.lat), Number(data.lon)], {
                            icon: L.icon({ 
                                iconUrl: 'king.png', 
                                iconSize: [64, 64], 
                                className: 'blinking', 
                                popupAnchor: [0, -30]
                            })
                        }).addTo(testMap);
                    }
                    
                    // Actualizar popup
                    const timeStr = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
                    giantMarker.bindPopup(`👑 Gigantes y Cabezudos<br>📍 Posición a las ${timeStr}<br>📅 ${day} de julio<br>🕐 ${isoDate.split('T')[1].substring(0,5)}`);
                    
                    // Actualizar información
                    document.getElementById('status-text').textContent = `Posición actualizada - ${timeStr}`;
                    document.getElementById('position-info').textContent = 
                        `Posición: ${Number(data.lat).toFixed(6)}, ${Number(data.lon).toFixed(6)}`;
                        
                    // Centrar en el marcador si no hay otros elementos visibles
                    if (!dayPath) {
                        testMap.setView([Number(data.lat), Number(data.lon)], 16);
                    }
                } else {
                    document.getElementById('status-text').textContent = 'Sin posición disponible para esta hora';
                    document.getElementById('position-info').textContent = 'Posición: No disponible';
                    
                    // Ocultar marcador si no hay posición
                    if (giantMarker) {
                        testMap.removeLayer(giantMarker);
                        giantMarker = null;
                    }
                }
            }, function(error) { 
                console.error('Error obteniendo posición:', error);
                document.getElementById('status-text').textContent = 'Error obteniendo posición';
                document.getElementById('position-info').textContent = 'Posición: Error';
            });
        }
        
        // Convertir minutos a formato HH:MM:SS
        function minutesToTimeString(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ':00';
        }
        
        // Actualizar display de tiempo
        function updateTimeDisplay() {
            const timeMinutes = document.getElementById('time-slider').value;
            document.getElementById('time-display').textContent = minutesToTimeString(timeMinutes);
        }
        
        // Toggle reproducción automática
        function togglePlayback() {
            const playBtn = document.getElementById('play-btn');
            
            if (isPlaying) {
                // Detener reproducción
                clearInterval(playInterval);
                isPlaying = false;
                playBtn.textContent = '▶️ Reproducir';
                playBtn.className = 'button is-primary';
            } else {
                // Iniciar reproducción
                isPlaying = true;
                playBtn.textContent = '⏸️ Pausar';
                playBtn.className = 'button is-warning';
                
                playInterval = setInterval(() => {
                    const slider = document.getElementById('time-slider');
                    let currentTime = parseInt(slider.value);
                    const maxTime = parseInt(slider.max);
                    const minTime = parseInt(slider.min);
                    
                    // Avanzar 5 minutos
                    currentTime += 5;
                    
                    // Si llegamos al final, reiniciar al inicio del horario
                    if (currentTime > maxTime) {
                        currentTime = minTime;
                    }
                    
                    slider.value = currentTime;
                    updateTimeDisplay();
                    updateGiantPosition();
                }, 500); // Actualizar cada 500ms para que sea fluido
            }
        }
        
        // Resetear tiempo
        function resetTime() {
            const slider = document.getElementById('time-slider');
            slider.value = slider.min; // Volver al inicio del horario
            updateTimeDisplay();
            updateGiantPosition();
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            initTestMap();
            
            // Selector de fecha
            document.getElementById('date-selector').addEventListener('change', function() {
                const selectedDay = this.value;
                loadDayPath(selectedDay);
            });
            
            // Slider de tiempo
            document.getElementById('time-slider').addEventListener('input', function() {
                console.log(`Slider changed to: ${this.value} minutes`);
                updateTimeDisplay();
                updateGiantPosition();
            });
            
            // Cargar día inicial (día actual o 6 si no es San Fermín)
            const currentDate = new Date();
            const currentDay = currentDate.getDate();
            const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-based
            
            let initialDay = 6;
            if (currentMonth === 7 && currentDay >= 6 && currentDay <= 14) {
                initialDay = currentDay;
            }
            
            document.getElementById('date-selector').value = initialDay;
            updateTimeDisplay();
            
            // Cargar recorrido inicial con configuración de horarios
            setTimeout(() => {
                loadDayPath(initialDay);
            }, 500);
        });
        
        // Limpiar interval al salir
        window.addEventListener('beforeunload', function() {
            if (playInterval) {
                clearInterval(playInterval);
            }
        });
    </script>
</body>
</html>