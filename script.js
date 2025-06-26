// Variables globales
var map;
var pathPoints;
var giantMarker;
var userMarker;
var dayPath;
var startMarker;
var finishMarker;
var startTimePopup;
var yourPositionPopup;
var giantsPositionPopup;
var restingMessage;
var watchID;

var centro = [42.81690537406873, -1.6432940644729581];

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

// Dibujar recorrido del día
function drawTodayPath(day) {
    request('api.php/path', 'POST', { day: day }, function(points) {
        
        if (dayPath != null) {
            map.removeLayer(dayPath);
            map.removeLayer(startMarker);
            map.removeLayer(finishMarker);
        }
        
        if (!points || points.length == 0) {
            return;
        }
        
        pathPoints = points;
        
        dayPath = L.polyline(pathPoints, {color: '#cc3333', weight: 5}).addTo(map);
        
        var start = pathPoints[0];
        var finish = pathPoints[pathPoints.length - 1];
        
        startMarker = L.marker([start.lat, start.lng], {
            icon: L.icon({ 
                iconUrl: 'inicio.png', 
                iconSize: [32, 32], 
                popupAnchor: [0, -10]
            })
        }).addTo(map);
        
        finishMarker = L.marker([finish.lat, finish.lng], {
            icon: L.icon({ 
                iconUrl: 'final.png', 
                iconSize: [32, 32]
            })
        }).addTo(map);
        
        setStartTime(day);
        
        if (startTimePopup) {
            startMarker.bindPopup(startTimePopup).openPopup();
        }
        
        setCenterAndZoom();
    }, function(error) { 
        console.log(error); 
    });
}

// Mover marcador de la comparsa
function moveGiantMarker(lat, lon) {
    if (giantMarker == null) {
        var giantIcon = L.icon({ 
            iconUrl: 'king.png', 
            iconSize: [64, 64], 
            className: 'blinking', 
            popupAnchor: [0, -30]
        });
        giantMarker = L.marker([lat, lon], {icon: giantIcon}).addTo(map);
        giantMarker.bindPopup(giantsPositionPopup).openPopup();
        setCenterAndZoom();
    } else {
        giantMarker.setLatLng([lat, lon]);
    }
}

// Obtener posición actual de la comparsa
function getGiantPosition() {
    // Primero intentar obtener estimación por clustering
    request('api.php/estimate', 'GET', {}, function(data) {
        if (data && data.lat && data.lon && data.confidence > 0.5) {
            moveGiantMarker(Number(data.lat), Number(data.lon));
        } else {
            // Fallback a posición programada
            getScheduledPosition();
        }
    }, function(error) {
        console.log('Error clustering:', error);
        getScheduledPosition();
    });
}

// Obtener posición programada
function getScheduledPosition() {
    request('api.php/position', 'POST', { 
        day: getDay(), 
        date: new Date().toISOString()
    }, function(data) {
        if (data && data.lat && data.lon) {
            moveGiantMarker(Number(data.lat), Number(data.lon));
        }
    }, function(error) { 
        console.log(error); 
    });
}

// Enviar posición del usuario para clustering
function sendUserPositionForClustering(lat, lon) {
    request('api.php/user_position', 'POST', {lat: lat, lon: lon}, 
        function(data) {
            console.log('Posición enviada para clustering');
        }, 
        function(error) {
            console.log('Error enviando posición:', error);
        }
    );
}

// Establecer centro y zoom del mapa
function setCenterAndZoom() {
    var bounds = [];
    
    if (giantMarker != null) { bounds.push(giantMarker.getLatLng()); }
    if (userMarker != null) { bounds.push(userMarker.getLatLng()); }
    if (startMarker != null) { bounds.push(startMarker.getLatLng()); }
    if (finishMarker != null) { bounds.push(finishMarker.getLatLng()); }
    if (pathPoints != null) { 
        pathPoints.forEach(point => bounds.push([point.lat, point.lng]));
    }
    
    if (bounds.length > 0) {
        map.fitBounds(bounds);
    } else {
        map.setView(centro, 16);
    }
}

// Verificar si los gigantes están activos
function gigantesBailando() {
    var now = new Date().getTime();
    
    if ((now > new Date(2025, 6, 6, 10, 0) && now < new Date(2025, 6, 6, 21, 0)) ||
        (now > new Date(2025, 6, 7, 9, 30) && now < new Date(2025, 6, 7, 15, 0)) ||
        (now > new Date(2025, 6, 8, 9, 30) && now < new Date(2025, 6, 8, 15, 0)) ||
        (now > new Date(2025, 6, 9, 9, 30) && now < new Date(2025, 6, 9, 15, 0)) ||
        (now > new Date(2025, 6, 10, 9, 30) && now < new Date(2025, 6, 10, 15, 0)) ||
        (now > new Date(2025, 6, 11, 9, 30) && now < new Date(2025, 6, 11, 15, 0)) ||
        (now > new Date(2025, 6, 12, 9, 30) && now < new Date(2025, 6, 12, 15, 0)) ||
        (now > new Date(2025, 6, 13, 9, 30) && now < new Date(2025, 6, 13, 15, 0)) ||
        (now > new Date(2025, 6, 14, 9, 30) && now < new Date(2025, 6, 14, 15, 0))) {
        return true;
    }
    
    return false;
}

// Obtener día actual
function getDay() {
    var now = new Date().getTime();
    if (now < new Date('07/07/2025')) {
        return 6;
    }
    if (now > new Date('14/07/2025')) {
        return 14;
    }
    return new Date().getDate();
}

// Control de localización del usuario
function addUserLocalizationControl() {
    L.Control.Button = L.Control.extend({
        options: {
            position: 'topleft'
        },
        onAdd: function (map) {
            var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            var button = L.DomUtil.create('a', 'leaflet-control-button', container);
            var img = L.DomUtil.create('img', '', button);
            img.src = 'user.png';
            img.style.width = '26px';
            img.style.height = '26px';
            L.DomEvent.disableClickPropagation(button);
            L.DomEvent.on(button, 'click', function(){
                subscribeUserPosition();
            });
            return container;
        },
        onRemove: function(map) {},
    });
    var control = new L.Control.Button();
    control.addTo(map);
}

// Control de localización de los gigantes
function addGiantsLocalizationControl() {
    L.Control.Button = L.Control.extend({
        options: {
            position: 'topleft'
        },
        onAdd: function (map) {
            var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            var button = L.DomUtil.create('a', 'leaflet-control-button', container);
            var img = L.DomUtil.create('img', '', button);
            img.src = 'king.png';
            img.style.width = '26px';
            img.style.height = '26px';
            L.DomEvent.disableClickPropagation(button);
            L.DomEvent.on(button, 'click', function(){
                subscribeGiantPosition();
            });
            return container;
        },
        onRemove: function(map) {},
    });
    var control = new L.Control.Button();
    control.addTo(map);
}

// Inicialización
function init() {
    map = L.map('map');
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        maxZoom: 20, 
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>' 
    }).addTo(map);
    
    // Cargar recorrido del día actual
    drawTodayPath(getDay());
    addUserLocalizationControl();
    addGiantsLocalizationControl();
    
    // Auto-tracking de usuarios para clustering
    startAutoUserTracking();
}

// Auto-tracking para clustering (sin mostrar marcador)
function startAutoUserTracking() {
    if (navigator.geolocation) {
        setInterval(() => {
            if (document.hasFocus()) {
                navigator.geolocation.getCurrentPosition(position => {
                    sendUserPositionForClustering(position.coords.latitude, position.coords.longitude);
                }, error => {
                    console.log('Error geolocalización:', error);
                });
            }
        }, 120000); // Cada 2 minutos
    }
}

// Iniciar cuando cargue la página
window.onload = init;

// Registrar Service Worker al final de script.js
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registrado con éxito:', registration.scope);
                
                // Manejar actualizaciones
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // Nueva versión disponible
                            if (confirm('Nueva versión disponible. ¿Actualizar?')) {
                                window.location.reload();
                            }
                        }
                    });
                });
            })
            .catch(error => {
                console.log('Error registrando SW:', error);
            });
    });
}

// Detectar cuando la app está funcionando offline
window.addEventListener('online', () => {
    console.log('Conexión restaurada');
    // Opcional: mostrar notificación
});

window.addEventListener('offline', () => {
    console.log('Sin conexión - modo offline');
    // Opcional: mostrar banner de offline
});