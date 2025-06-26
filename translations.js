// Sistema de traducciones simple
let translations = {};
let currentLang = 'cas';

// Cargar traducciones
async function loadTranslations() {
    try {
        const response = await fetch('translations.json');
        translations = await response.json();
    } catch (error) {
        console.error('Error cargando traducciones:', error);
    }
}

// Obtener traducción
function t(key, replacements = {}) {
    const keys = key.split('.');
    let value = translations[currentLang];
    
    for (const k of keys) {
        if (value && value[k]) {
            value = value[k];
        } else {
            return key; // Si no encuentra la clave, devolver la clave
        }
    }
    
    // Reemplazar variables {variable}
    if (typeof value === 'string' && replacements) {
        for (const [placeholder, replacement] of Object.entries(replacements)) {
            value = value.replace(`{${placeholder}}`, replacement);
        }
    }
    
    return value || key;
}

// Establecer hora de inicio (actualizada)
function setStartTime(day) {
    const baseText = t('departure');
    const time = t(`routes.${day}.time`) || (day == 6 ? '17:00' : '9:30');
    window.startTimePopup = baseText + ' ' + time;
}

// Obtener día de la página actual
function getCurrentPageDay() {
    const urlParams = new URLSearchParams(window.location.search);
    const day = urlParams.get('dia');
    return day ? parseInt(day) : null;
}

// Funciones de notificaciones actualizadas
function requestNotifications() {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                scheduleNotifications();
                alert(t('notifications_activated'));
            }
        });
    } else {
        alert(t('notifications_not_supported'));
    }
}

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
                    userMarker.bindPopup(window.yourPositionPopup).openPopup();
                    setCenterAndZoom();
                } else {
                    userMarker.setLatLng(userLatLon);
                }
                
                sendUserPositionForClustering(position.coords.latitude, position.coords.longitude);
            });
        }
    } else { 
        alert(t('geolocation_not_available')); 
    }
}

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
        alert(t('resting_message'));
    }
}

function scheduleNotifications() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 45, 0, 0);
    
    const delay = tomorrow.getTime() - Date.now();
    if (delay > 0 && delay < 24 * 60 * 60 * 1000) {
        setTimeout(() => {
            new Notification('🎭 Comparsa de Gigantes', {
                body: t('comparsa_departure'),
                icon: 'king.png'
            });
        }, delay);
    }
}

// Detectar idioma de la URL al cargar
function initLanguage() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlLang = urlParams.get('lang');
    if (urlLang && translations[urlLang]) {
        currentLang = urlLang;
    } else {
        // Detectar idioma del navegador
        const browserLang = navigator.language.slice(0, 2);
        if (browserLang === 'eu') currentLang = 'eus';
        else if (browserLang === 'en') currentLang = 'eng';
        else currentLang = 'cas';
    }
}

// Inicializar traducciones al cargar la página
document.addEventListener('DOMContentLoaded', async function() {
    await loadTranslations();
    initLanguage();
    
    // Establecer variables globales para el mapa
    window.startTimePopup = t('departure');
    window.yourPositionPopup = t('your_position');
    window.giantsPositionPopup = t('giants_position');
    window.restingMessage = t('resting_message');
});

// Obtener información de ruta para un día específico
function getRouteInfo(day) {
    return {
        title: t('route_info', {day: day}),
        subtitle: t(`day_subtitles.${day}`),
        time: t(`routes.${day}.time`),
        start: t(`routes.${day}.start`),
        route: t(`routes.${day}.route`)
    };
}