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
var dateSelect;
var restingMessage;
var watchID;

var centro = [42.81690537406873, -1.6432940644729581];

// Cambio de idioma
function changeLan(lan) {
    var titulo = '';
    var subtitulo = '';
    var recorrido = '';
    
    var day = dateSelect.value;
    if (!day) { day = getDay(); }
    
    dateSelect.innerHTML = '';
    
    if (lan == 'cas') {
        titulo = '¿Dónde está la comparsa?';
        subtitulo = 'Recorridos de la comparsa de gigantes y cabezudos en San Fermín 2025';
        recorrido = 'Recorrido del día';
        startTimePopup = 'Salida ';
        yourPositionPopup = 'Tu posición';
        giantsPositionPopup = 'Posición de la comparsa';
        dateSelect.add(new Option('6 de julio', '6'));
        dateSelect.add(new Option('7 de julio', '7'));
        dateSelect.add(new Option('8 de julio', '8'));
        dateSelect.add(new Option('9 de julio', '9'));
        dateSelect.add(new Option('10 de julio', '10'));
        dateSelect.add(new Option('11 de julio', '11'));
        dateSelect.add(new Option('12 de julio', '12'));
        dateSelect.add(new Option('13 de julio', '13'));
        dateSelect.add(new Option('14 de julio', '14'));
        restingMessage = 'Ahora mismo los gigantes y cabezudos están descansando!';
    }
    if (lan == 'eus') {
        titulo = 'Non dago konpartsa?';
        subtitulo = 'Erraldoi eta buruhandien konpartsaren ibilbidea 2025eko San Ferminetan';
        recorrido = 'Eguneko ibilbidea';
        startTimePopup = 'Irteera ';
        yourPositionPopup = 'Zure posizioa';
        giantsPositionPopup = 'Konpartsaren posizioa';
        dateSelect.add(new Option('Uztailak 6', '6'));
        dateSelect.add(new Option('Uztailak 7', '7'));
        dateSelect.add(new Option('Uztailak 8', '8'));
        dateSelect.add(new Option('Uztailak 9', '9'));
        dateSelect.add(new Option('Uztailak 10', '10'));
        dateSelect.add(new Option('Uztailak 11', '11'));
        dateSelect.add(new Option('Uztailak 12', '12'));
        dateSelect.add(new Option('Uztailak 13', '13'));
        dateSelect.add(new Option('Uztailak 14', '14'));
        restingMessage = 'Oraintxe erraldoiak eta buru handiak atseden hartzen ari dira!';
    }
    if (lan == 'eng') {
        titulo = 'Where is the "comparsa"?';
        subtitulo = 'Tour of the troupe of giants and big heads in San Fermín 2025';
        recorrido = 'Tour of the day';
        startTimePopup = 'Departure ';
        yourPositionPopup = 'Your position';
        giantsPositionPopup = 'Position of the troupe';
        dateSelect.add(new Option('July 6th', '6'));
        dateSelect.add(new Option('July 7th', '7'));
        dateSelect.add(new Option('July 8th', '8'));
        dateSelect.add(new Option('July 9th', '9'));
        dateSelect.add(new Option('July 10th', '10'));
        dateSelect.add(new Option('July 11th', '11'));
        dateSelect.add(new Option('July 12th', '12'));
        dateSelect.add(new Option('July 13th', '13'));
        dateSelect.add(new Option('July 14th', '14'));
        restingMessage = 'Right now the giants and big heads are resting!';
    }
    
    dateSelect.value = day;
    
    document.getElementById('subtitle').innerText = subtitulo;
    document.getElementById('title').innerText = titulo;
    document.getElementById('path').innerText = recorrido;
    
    if (userMarker != null) {
        userMarker.bindPopup(yourPositionPopup);
    }
    if (startMarker != null) {
        setStartTime(getDay());
        startMarker.bindPopup(startTimePopup);
    }
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

// Suscribirse a la posición de la comparsa
function subscribeGiantPosition() {
    if (gigantesBailando()) {
        if (giantMarker != null) {
            map.removeLayer(giantMarker);
            giantMarker = null;
            setCenterAndZoom();
        } else {
            getGiantPosition();
            setInterval(function() { getGiantPosition(); }, 10000);
        }
    } else {
        alert(restingMessage);
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

// Suscribirse a la posición del usuario
function subscribeUserPosition() {
    if ("geolocation" in navigator) {
        if (watchID) {
            navigator.geolocation.clearWatch(watchID);
            map.removeLayer(userMarker);
            userMarker = null;
            watchID = null;
            setCenterAndZoom();
        } else {
            watchID = navigator.geolocation.watchPosition((position) => {
                var userLatLon = [position.coords.latitude, position.coords.longitude];
                
                if (userMarker == null) {
                    var userIcon = L.icon({ 
                        iconUrl: 'user.png', 
                        iconSize: [64, 64], 
                        className: 'blinking', 
                        popupAnchor: [0, -30]
                    });
                    userMarker = L.marker(userLatLon, {icon: userIcon}).addTo(map);
                    userMarker.bindPopup(yourPositionPopup).openPopup();
                    setCenterAndZoom();
                } else {
                    userMarker.setLatLng(userLatLon);
                }
                
                // Enviar posición para clustering
                sendUserPositionForClustering(position.coords.latitude, position.coords.longitude);
            });
        }
    } else { 
        alert('Geolocalización no disponible'); 
    }
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

// Establecer hora de inicio
function setStartTime(day) {
    var baseText = startTimePopup ? startTimePopup.split(' ')[0] : 'Salida';
    if (day == 6) {
        startTimePopup = baseText + ' 10:15';
    } else {
        startTimePopup = baseText + ' 9:30';
    }
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

// Notificaciones
function requestNotifications() {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                scheduleNotifications();
                alert('¡Notificaciones activadas!');
            }
        });
    } else {
        alert('Tu navegador no soporta notificaciones');
    }
}

function scheduleNotifications() {
    // Programar para el día siguiente a las 9:45 (15 min antes de las 10:00)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 45, 0, 0);
    
    const delay = tomorrow.getTime() - Date.now();
    if (delay > 0 && delay < 24 * 60 * 60 * 1000) { // Solo si es menos de 24h
        setTimeout(() => {
            new Notification('🎭 Comparsa de Gigantes', {
                body: 'La comparsa sale en 15 minutos',
                icon: 'king.png'
            });
        }, delay);
    }
}

// Inicialización
function init() {
    map = L.map('map');
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        maxZoom: 20, 
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>' 
    }).addTo(map);
    
    dateSelect = document.getElementById("daySelect");
    if (dateSelect) {
        dateSelect.addEventListener('change', function(){
            drawTodayPath(Number(this.value));
            // Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'day_selected', { 'day': this.value });
            }
        });
    }
    
    changeLan('cas');
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