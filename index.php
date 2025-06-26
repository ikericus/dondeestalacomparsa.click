<?php 
require_once 'config.php';

// Función para construir URLs con parámetros de idioma
function buildLangUrl($newLang) {
    $params = $_GET;
    if ($newLang === 'cas') {
        unset($params['lang']); // Eliminar lang=cas ya que es por defecto
    } else {
        $params['lang'] = $newLang;
    }
    
    if (empty($params)) {
        return '/';
    }
    
    return '/?' . http_build_query($params);
}

// Redirección automática si estamos en San Fermín sin parámetro día
if (!isset($_GET['dia'])) {
    $current_date = new DateTime();
    $july_6 = new DateTime('2025-07-06');
    $july_14 = new DateTime('2025-07-14');
    
    if ($current_date >= $july_6 && $current_date <= $july_14) {
        $current_day = (int)$current_date->format('d');
        header("Location: ?dia=$current_day", true, 302);
        exit;
    } else {
        header("Location: ?dia=6", true, 302);
        exit;
    }
}

// Cargar traducciones desde JSON
$translations = json_decode(file_get_contents('translations.json'), true);
$lang = $_GET['lang'] ?? 'cas';
$current_lang = $translations[$lang] ?? $translations['cas'];

// SEO dinámico basado en parámetros
$day = isset($_GET['dia']) ? (int)$_GET['dia'] : null;
$seo_day = ($day >= 6 && $day <= 14) ? $day : null;

// Obtener títulos SEO del JSON
$page_title = $seo_day ? 
    str_replace('{day}', $seo_day, $current_lang['seo_day_title']) : 
    $current_lang['seo_default_title'];
$page_description = $current_lang['seo_description'];

// Códigos de idioma ISO
$lang_codes = ['cas' => 'es', 'eus' => 'eu', 'eng' => 'en'];
$iso_code = $lang_codes[$lang] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= $iso_code ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="Donde está la comparsa">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:image" content="https://www.dondeestalacomparsa.click/og-image.jpg">
    <meta property="og:url" content="https://www.dondeestalacomparsa.click<?= $seo_day ? '?dia=' . $seo_day : '' ?><?= $lang !== 'cas' ? ($seo_day ? '&' : '?') . 'lang=' . $lang : '' ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?= $iso_code ?>_<?= strtoupper($iso_code) ?>">
    
    <!-- PWA y favicons -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#cc3333">
    <link rel="icon" type="image/png" sizes="32x32" href="icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icon.png">
    
	<!-- PWA Meta Tags adicionales -->
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="Comparsa">
	<link rel="apple-touch-startup-image" href="icon.png">

	<!-- MS Tiles -->
	<meta name="msapplication-TileColor" content="#cc3333">
	<meta name="msapplication-TileImage" content="icon.png">

    <!-- CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    
    <style>
        #map { height: 80vh; min-height: 400px; }
        body { height: 100vh; margin: 0; font-family: 'BlinkMacSystemFont', -apple-system, 'Segoe UI', 'Roboto', sans-serif; }
        
        .live-indicator { color: #ffeb3b; animation: pulse 2s infinite; font-weight: bold; }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } }
        
        .day-button { text-decoration: none !important; transition: all 0.2s ease; }
        .day-button:hover, .day-button:focus {
            background: rgba(255,255,255,0.25) !important; border-color: white !important;
            color: white !important; transform: translateY(-1px); text-decoration: none !important;
        }
        
        .button.is-white.is-outlined {
            background: rgba(255,255,255,0.15) !important; border-color: rgba(255,255,255,0.7) !important;
            color: white !important;
        }
        
        .hero-body { padding: 1rem 1.5rem !important; }
        
        @media (max-width: 768px) {
            .hero-body { padding: 0.75rem 1rem !important; }
            .title { font-size: 1.25rem !important; }
            .subtitle { font-size: 0.8rem !important; }
            .day-buttons-mobile { display: flex; flex-wrap: wrap; gap: 0.25rem; justify-content: center; margin-top: 0.5rem; }
            .day-buttons-mobile .button { flex: 0 0 auto; min-width: 2rem; }
        }
        
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .footer { padding: 1rem 1.5rem; }
    </style>
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RPTYE7WZQL"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-RPTYE7WZQL');
    </script>
</head>
<body>
    
    <!-- Skip link -->
    <a href="#main-content" class="sr-only">Saltar al contenido principal</a>

    <!-- Header -->
    <header>
        <section class="hero" style="background: linear-gradient(135deg, #d63031 0%, #cc3333 100%);">
            <div class="hero-body py-3">
                <div class="container">
                    
                    <!-- Controles superiores -->
                    <div class="level mb-3">
                        <div class="level-left"></div>
                        <div class="level-right">
                            <!-- Desktop controls -->
                            <!-- <div class="level-item is-hidden-mobile">
                                <button class="button is-white is-outlined is-small mr-2" onclick="requestNotifications()" style="border-color: rgba(255,255,255,0.8); color: white; background: rgba(255,255,255,0.1);">
                                    <span class="icon is-small"><span style="filter: brightness(0) invert(1);">🔔</span></span>
                                </button>
                            </div> -->
                            <div class="level-item is-hidden-mobile">
                                <div class="buttons has-addons is-small">
                                    <a href="<?= buildLangUrl('cas') ?>" class="button is-small day-button <?= $lang === 'cas' ? 'active' : '' ?>" title="Castellano" style="background: <?= $lang === 'cas' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $lang === 'cas' ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $lang === 'cas' ? 'bold' : 'normal' ?>;">CAS</a>
                                    <a href="<?= buildLangUrl('eus') ?>" class="button is-small day-button <?= $lang === 'eus' ? 'active' : '' ?>" title="Euskera" style="background: <?= $lang === 'eus' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $lang === 'eus' ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $lang === 'eus' ? 'bold' : 'normal' ?>;">EUS</a>
                                    <a href="<?= buildLangUrl('eng') ?>" class="button is-small day-button <?= $lang === 'eng' ? 'active' : '' ?>" title="English" style="background: <?= $lang === 'eng' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $lang === 'eng' ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $lang === 'eng' ? 'bold' : 'normal' ?>;">ENG</a>
                                </div>
                            </div>
                            
                            <!-- Mobile controls -->
                            <div class="is-hidden-tablet">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <!-- <button class="button is-white is-outlined is-small" onclick="requestNotifications()" style="border-color: rgba(255,255,255,0.8); color: white; background: rgba(255,255,255,0.1);">
                                        <span class="icon is-small"><span style="filter: brightness(0) invert(1);">🔔</span></span>
                                    </button> -->
                                    <div class="buttons has-addons is-small">
                                        <a href="<?= buildLangUrl('cas') ?>" class="button is-small day-button <?= $lang === 'cas' ? 'active' : '' ?>" style="background: <?= $lang === 'cas' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $lang === 'cas' ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $lang === 'cas' ? 'bold' : 'normal' ?>;">CAS</a>
                                        <a href="<?= buildLangUrl('eus') ?>" class="button is-small day-button <?= $lang === 'eus' ? 'active' : '' ?>" style="background: <?= $lang === 'eus' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $lang === 'eus' ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $lang === 'eus' ? 'bold' : 'normal' ?>;">EUS</a>
                                        <a href="<?= buildLangUrl('eng') ?>" class="button is-small day-button <?= $lang === 'eng' ? 'active' : '' ?>" style="background: <?= $lang === 'eng' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $lang === 'eng' ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $lang === 'eng' ? 'bold' : 'normal' ?>;">ENG</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Título principal -->
                    <div class="has-text-centered mb-3">
                        <h1 id="title" class="title is-5 has-text-white mb-1" style="font-weight: 600;">
                            <?= $seo_day ? str_replace('{day}', $seo_day, $current_lang['day_title']) : $current_lang['title'] ?>
                        </h1>
                        <h2 id="subtitle" class="subtitle is-7 has-text-white mb-2" style="opacity: 0.95;">
                            <?= $current_lang['subtitle'] ?>
                        </h2>
                    </div>
                    
                    <!-- Selector de días -->
                    <div class="has-text-centered">
                        <p id="path" class="is-size-7 has-text-white mb-2" style="opacity: 0.9;"><?= $current_lang['select_day'] ?></p>
                        
                        <!-- Desktop day buttons -->
                        <div class="buttons has-addons is-small is-centered is-hidden-mobile">
                            <?php for ($d = 6; $d <= 14; $d++): ?>
                            <a href="?dia=<?= $d ?><?= $lang !== 'cas' ? '&lang=' . $lang : '' ?>" class="button is-small day-button <?= $seo_day == $d ? 'active' : '' ?>" style="background: <?= $seo_day == $d ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $seo_day == $d ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $seo_day == $d ? 'bold' : 'normal' ?>;"><?= $d ?></a>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- Mobile day buttons -->
                        <div class="day-buttons-mobile is-hidden-tablet">
                            <?php for ($d = 6; $d <= 14; $d++): ?>
                            <a href="?dia=<?= $d ?><?= $lang !== 'cas' ? '&lang=' . $lang : '' ?>" class="button is-small day-button <?= $seo_day == $d ? 'active' : '' ?>" style="background: <?= $seo_day == $d ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.1)' ?>; border-color: rgba(255,255,255,0.5); color: <?= $seo_day == $d ? '#cc3333' : 'white' ?>; font-size: 0.7rem; padding: 0.25rem 0.5rem; font-weight: <?= $seo_day == $d ? 'bold' : 'normal' ?>;"><?= $d ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </header>

    <!-- Contenido principal -->
    <main id="main-content">
        <!-- Información contextual de ruta -->
        <?php if ($seo_day): ?>
        <section class="section py-3" style="background: #f8f9fa;" id="route-info">
            <div class="container">
                <div class="content is-small">
                    <h3 class="title is-6" style="color: #cc3333; margin-bottom: 1rem;" id="route-title">
                        <?= str_replace('{day}', $seo_day, $current_lang['route_info']) ?>
                    </h3>
                    
                    <div class="box" style="background: white; border-left: 4px solid #cc3333; padding: 1.5rem; line-height: 1.6;">

						<div style="margin-bottom: 1rem;">
                            <span id="route-route-label"><strong style="color: #666;"><?= $current_lang['departure_label'] ?></strong></span>
							<p style="margin-bottom: 0.5rem;">
								<span style="color: #444; line-height: 1.8; font-size: 0.95em;" id="route-time"><?= $current_lang['routes'][$seo_day]['time'] ?? '--' ?></span>							 
								<span style="color: #444; line-height: 1.8; font-size: 0.95em;" id="route-from"><?= $current_lang['from'] ?></span> 
								<span style="color: #444; line-height: 1.8; font-size: 0.95em;" id="route-start"><?= $current_lang['routes'][$seo_day]['start'] ?? '--' ?></span>
							</p>
                        </div>

                        <div>
                            <p style="margin-bottom: 0.5rem;" id="route-route-label"><strong style="color: #666;"><?= $current_lang['route_label'] ?></strong></p>
                            <p style="color: #444; line-height: 1.8; font-size: 0.95em;" id="route-description"><?= $current_lang['routes'][$seo_day]['route'] ?? 'Cargando recorrido...' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Mapa -->
        <div id="map">
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer" style="background: #f8f9fa;">
        <div class="content has-text-centered">
            <p class="is-size-7 mb-1" style="color: #666;">
                <strong style="color: #333;" id="footer-days"><?= $current_lang['days_label'] ?></strong>
                <?php for ($d = 6; $d <= 14; $d++): ?>
                    <a href="?dia=<?= $d ?><?= $lang !== 'cas' ? '&lang=' . $lang : '' ?>" style="color: #cc3333;"><?= $d ?></a><?= $d < 14 ? ' |' : '' ?>
                <?php endfor; ?>
            </p>
            <p class="is-size-7" style="color: #666;">
                <a href="mailto:contacto@dondeestalacomparsa.click" style="color: #cc3333;">contacto@dondeestalacomparsa.click</a>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="translations.js"></script>
    <script src="script.js"></script>
    
    <!-- Tracking manual -->
    <?php if (isset($_GET['track'])): ?>
    <script>
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(pos => {
                fetch('api.php/track', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`
                });
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>