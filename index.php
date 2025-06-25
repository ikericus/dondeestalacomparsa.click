<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
		try {
			header("Content-Type:application/json");
			
			$mysqli = new mysqli('localhost','u329673490_info','O~JW:e8T7t','u329673490_erraldoiak');
			
			if($result === false) {
				echo "Error al conectar" . $mysqli->connect_error;
			}
			$query;

			if($_SERVER['PATH_INFO'] == '/position')
			{
				$query = "SELECT day, step, lat, lon, date FROM position WHERE day =" . $_POST["day"] . " AND date < '" . $_POST["date"] . "' ORDER BY id DESC LIMIT 1";
			}
			if($_SERVER['PATH_INFO'] == '/path')
			{
				$query = "SELECT lat, lon as lng FROM path WHERE day =" . $_POST["day"] . " ORDER BY step";
			}
			if($_SERVER['PATH_INFO'] == '/track')
			{
				$query = "INSERT INTO position (lat, lon) VALUES (" . $_POST["lat"] . ", " . $_POST["lon"] . ")";
			}		
			$result = $mysqli->query($query);
			
			if($result === false) {
				echo "Error query" . $mysqli->error;
			}
			else {
				if($result->num_rows == 0){
					echo 'ok';
				}
				else if($result->num_rows == 1){
					$row = $result->fetch_object();
					echo json_encode($row, JSON_NUMERIC_CHECK);
				}
				else {
					$myArray = array();;
					while($row = $result->fetch_assoc()) {
						$myArray[] = $row;
					}
					echo json_encode($myArray, JSON_NUMERIC_CHECK);
				}
			}
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	} 
    if ($_SERVER['REQUEST_METHOD'] == 'GET') { ?>
		<!DOCTYPE html>
        <html lang="es-ES">
          <head>
		  
            <meta name="google-site-verification" content="gKMPUASJJc0iRje3ThGIhNKepadorRVslSrzpXsNvqQ" />
			
			<meta name="description" content="Encuentra a la Comparsa de Gigantes y Cabezudos de Pamplona en sus recorrido en San Fermin. Mapa con los recorridos de la comparsa en Sanfermines con ubicación en directo.">
			
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta http-equiv="content-language" content="es-ES">
			
			<meta property="og:title" content="¿Dónde está la comparsa?" />
			<meta property="og:url" content="https://www.dondeestalacomparsa.click" />
			<meta property="og:description" content="Sanfermines 2024. Localiza los gigantes y cabezudos de Pamplona">
			<meta property="og:image" itemprop="image" content="https://www.dondeestalacomparsa.click/icon.png"/>
			
			<link rel="canonical" href="https://www.dondeestalacomparsa.click/">
			
            <title>Donde está la comparsa de Pamplona. Sanfermines 2024</title>
            					
			<!-- leaflet -->			
			<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
			<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
			
			<!--  jquery -->
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            
			<!--  analytics -->
			<script async src="https://www.googletagmanager.com/gtag/js?id=G-RPTYE7WZQL"></script>
			<script>
			  window.dataLayer = window.dataLayer || [];
			  function gtag(){dataLayer.push(arguments);}
			  gtag('js', new Date());

			  gtag('config', 'G-RPTYE7WZQL');
			</script>
			
            <!--  bulma -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
			            
            <script src="script.min.js"></script> 			    
			 
            <style>
			
			  #map {
                height: 75%;        /* Always set the map height explicitly to define the size of the div element that contains the map. */
				
              }
              html,
              body {
                height: 100%;
                margin: 0;
                padding: 0;
              }
			  
   			  @keyframes fade { 
			  	from { opacity: 0.2; } 
			  }
			  .blinking {
			  	animation: fade 1s infinite alternate;
   			  }
			  
			  @keyframes maxOff {
			   0% {
			  	 width: 100%;
			  	 height: 100%;
			   },
			   100% {
			  	 width: 110%;
			  	 height: 110%;
			   }
			  }
			  .bigger {
			  	animation: maxOff 1s ease infinite alternate;
   			  }
			  
			  @keyframes animate {
			  	0% {
			  		transform: translateY(0px);
			  	}
			  
			  	50% {
			  		transform: translateY(-10px);
			  	}
			  
			  	100% {
			  		transform: translateY(0px);
			  	}
			  }
			  .dancing {
			  	animation: animate 1s infinite;
   			  }


   			  </style>
			  
            <!-- popup ad -->
			<!-- <script type='text/javascript' src='//pl23666907.highrevenuenetwork.com/f5/0b/db/f50bdbf073fd4ad159e7dcedd40cbc51.js'></script>-->
			
          </head>
          <body>
		  
            <!-- header -->
            <section class="hero " style="background-color:#cc3333">
			            
              <div class="hero-body p-4">
				<div>
					<span class="is-pulled-right">
						<a lang="es" hreflang="es" class="has-text-white" onclick="changeLan('cas')"> CAS </a>
						<a lang="eu" hreflang="eu" class="has-text-white ml-3" onclick="changeLan('eus')"> EUS </a>
						<a lang="en" hreflang="en" class="has-text-white ml-3" onclick="changeLan('eng')"> ENG </a>
					</span>
				</div>
				<br>
                <div class="columns">
                  <div class="column ">
                    <h1 id="title" class="title is-4 has-text-white">¿Dónde está la comparsa?</h1>
                    <h2 id="subtitle" class="subtitle has-text-white">Recorridos de los gigantes y cabezudos en San Fermin 2024</h2>
                  </div>
				</div>
				<div class="columns is-mobile">
                  <div class="column">
					<span id="path" class="subtitle has-text-white mb-1">Recorrido del día</span>
                    <div class="select is-small">
                      <select id="dateSelect"></select>
                    </div>
				   </div>
                </div>

              </div>
            </section>
			
			<?php if(isset($_GET['track'])) { ?>
				<script>
					var trackMarker;
					alert('tracking');
				    if ("geolocation" in navigator) {
                       var trackID = navigator.geolocation.watchPosition((position) => {													
							request('index.php/track', 
							  'POST', 
							  { lat : position.coords.latitude, lon: position.coords.longitude },
							  function(ok) { console.log(ok); },
							  function(error) { console.log(error); });
							  
							  if(trackMarker) {
								map.removeLayer(trackMarker);
							  }
							  trackMarker = L.marker([position.coords.latitude, position.coords.longitude]).addTo(map);												
						});
					}
                    else { alert('Geolocalicacion no disponible'); }
				</script>
			<?php } ?>
		
            <!-- map --> 
			<div id="map" ></div>

            <!-- footer -->
            <footer class="footer">
              <div class="content has-text-centered">
			  
			  	<!-- ad -->
				<script type="text/javascript">
					atOptions = {
						'key' : '8e6b7844352b2029c853455378320864',
						'format' : 'iframe',
						'height' : 250,
						'width' : 300,
						'params' : {}
					};
				</script>
				<script type="text/javascript" src="//www.topcreativeformat.com/8e6b7844352b2029c853455378320864/invoke.js"></script>
				<br>
				<br>
                <p>
                  contacto@dondeestalacomparsa.click
                </p>
              </div>			  
            </footer>
			<script type="text/javascript"> var infolinks_pid = 3422169; var infolinks_wsid = 0; </script> <script type="text/javascript" src="http://resources.infolinks.com/js/infolinks_main.js"></script>    
          </body>
        </html>
		
<?php    }     
?>