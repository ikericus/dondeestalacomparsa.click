			 
              var map;
			  var pathPoints;
              var giantMarker;
              var centerMarker;
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
			  var markers = [];
			  
              var centro = [42.81690537406873, -1.6432940644729581 ];

              function changeLan(lan){
			  
				  var titulo = '';
				  var subtitulo = '';
				  var recorrido = '';
				  					
				  var day = dateSelect.value;
				  if(!day) { day = getDay();}
				  
				  //alert(day);
				  dateSelect.innerHTML = '';
				  if(lan=='cas'){
					titulo = '¿Dónde está la comparsa?';
					subtitulo = 'Recorridos de la comparsa de gigantes y cabezudos en San Fermin 2024';
					recorrido = 'Recorrido del día';
					startTimePopup = 'Salida ';
					yourPositionPopup = 'Tu posición';
					giantsPositionPopup = 'Posición estimada de la comparsa';
					dateSelect.add(new Option('6 de julio','6'));
					dateSelect.add(new Option('7 de julio','7'));
					dateSelect.add(new Option('8 de julio','8'));
					dateSelect.add(new Option('9 de julio','9'));
					dateSelect.add(new Option('10 de julio','10'));
					dateSelect.add(new Option('11 de julio','11'));
					dateSelect.add(new Option('12 de julio','12'));
					dateSelect.add(new Option('13 de julio','13'));
					dateSelect.add(new Option('14 de julio','14'));
					restingMessage = 'Ahora mismo los gigantes y cabezudos están descansando!';
				  }
				  if(lan=='eus'){
					titulo = 'Non dago konpartsa?';
					subtitulo = 'Erraldoi eta buruhandien konpartsaren ibilbidea 2024ko San Ferminetan';
					recorrido = 'Eguneko ibilbidea';
					startTimePopup = 'Irteera ';
					yourPositionPopup = 'Zure posizioa';
					giantsPositionPopup = 'Konpartsaren posizio estimatua';
					dateSelect.add(new Option('Uztailak 6','6'));	
					dateSelect.add(new Option('Uztailak 7','7'));
					dateSelect.add(new Option('Uztailak 8','8'));
					dateSelect.add(new Option('Uztailak 9','9'));
					dateSelect.add(new Option('Uztailak 10','10'));
					dateSelect.add(new Option('Uztailak 11','11'));
					dateSelect.add(new Option('Uztailak 12','12'));
					dateSelect.add(new Option('Uztailak 13','13'));
					dateSelect.add(new Option('Uztailak 14','14'));
					restingMessage = 'Oraintxe erraldoiak eta buru handiak atseden hartzen ari dira!';
				  }
				  if(lan=='eng'){
					titulo = 'Where are the "comparsa"';
					subtitulo = 'Tour of the troupe of giants and big heads in San Fermin 2024';	
					recorrido = 'Tour of the day';		
					startTimePopup = 'Departure ';
					yourPositionPopup = 'Your position';		
					giantsPositionPopup = 'Estimated position of the troupe';
					dateSelect.add(new Option('July 6th','6'));					
					dateSelect.add(new Option('July 7th','7'));
					dateSelect.add(new Option('July 8th','8'));
					dateSelect.add(new Option('July 9th','9'));
					dateSelect.add(new Option('July 10th','10'));
					dateSelect.add(new Option('July 11th','12'));
					dateSelect.add(new Option('July 12th','12'));
					dateSelect.add(new Option('July 13th','13'));
					dateSelect.add(new Option('July 14th','14'));
					restingMessage = 'Right now the giants and big heads are resting!';
				  }
				  dateSelect.value = day;
				  
                  document.getElementById('subtitle').innerText = subtitulo;
                  document.getElementById('title').innerText = titulo;
                  document.getElementById('path').innerText = recorrido;
				  
				  if(userMarker != null) {
					userMarker.bindPopup(yourPositionPopup);
				  }
				  if(startMarker != null) {
					setStartTime(getDay());
					startMarker.bindPopup(startTimePopup);
				  }
              }    
                
              function request(url, method, data, fnSuccess, fnError) {
                  
                    $.ajax({
                        url: url,
                        type: method,
                        data: data,
                        //contentType: 'application/json',
                        success: function(data){
                            fnSuccess(data);
                        },
                        error: function(error){
                            fnError(error);
                        }
                    });
              }
              
              function calcDistance(lat1, lon1, lat2, lon2) {
                  const r = 6371000; // km
                  const p = Math.PI / 180;
                
                  const a = 0.5 - Math.cos((lat2 - lat1) * p) / 2
                                + Math.cos(lat1 * p) * Math.cos(lat2 * p) *
                                  (1 - Math.cos((lon2 - lon1) * p)) / 2;
                
                  var d =  2 * r * Math.asin(Math.sqrt(a));
                  
                  //console.log(d);
                  
                  return d;
                }
                  
              function calcGapPointsFromPath(path, gap) {
                 
                  var points = [];
                  var lastGap = 0;
				  
				  if(markers)
				  {
					for (var i=0; i < markers.length; i++) { 
						map.removeLayer(markers[i]);					
					}
				  }
				  
                  for (var i=1; i < 18; i++) { //path.length
                    
                    var p1= path[i-1];
                    var p2= path[i];
                    var d = calcDistance(p1.lat, p1.lng, p2.lat, p2.lng);
                    
                    var n = (d-lastGap)/gap;
                    var j = 1;
                    
					points.push(p1);
					points.push(p2);
					
					var m1 = L.marker([p1.lat, p1.lng]).addTo(map);
					var m2 = L.marker([p2.lat, p2.lng]).addTo(map);
					markers.push(m1);
					markers.push(m2);
					
                    while (d > gap * j) {
                      var newPoint = { lat : p1.lat+(p2.lat-p1.lat)/n*j, lng: p1.lng+(p2.lng-p1.lng)/n*j};
                      points.push(newPoint);
					  
					  var m = L.marker([newPoint.lat, newPoint.lng]).addTo(map);					  
					  markers.push(m); 
					  
                      j++;
                    }
                    lastGap = calcDistance(points[points.length-1].lat, points[points.length-1].lng, p2.lat, p2.lng);
                  }
				   
					var csv = [];
					var baseDate = new Date(2024,6,14,9,30);
					for (var i=0; i < points.length; i++) {
						csv.push([baseDate.getDate(),i,points[i].lat,points[i].lng,baseDate.toISOString()]);	
						baseDate = new Date(baseDate.getTime() + 12030); 
					}
					var csvContent='';
					csv.forEach(function(rowArray) {
						let row = rowArray.join(",");
						csvContent += row + "\r\n";
					});
					console.log(csvContent);
				                                     
                  return points;
                }
					
              function drawTodayPath(day){
                  request('index.php/path', 'POST', { day : day} ,function(points) {
                      
					  if(dayPath != null) {
						map.removeLayer(dayPath);
						map.removeLayer(startMarker);
						map.removeLayer(finishMarker);
                      }					  
					  
					  if(!points || points.length == 0 )
					  {
						 return;
					  }					  
					  
					  pathPoints = points;
					   
                      calcGapPointsFromPath(pathPoints, 5);
                      
					  dayPath = L.polyline(pathPoints, {color: '#cc3333', weight: 5}).addTo(map);
					                        
                      var start = pathPoints[0];
                      var finish = pathPoints[pathPoints.length - 1];                      
					  
                      startMarker  = L.marker([start.lat, start.lng], {icon: L.icon({ iconUrl: 'inicio.png', iconSize: [32, 32], popupAnchor: [0, -10]})}).addTo(map);
                      finishMarker  = L.marker([finish.lat, finish.lng], {icon: L.icon({ iconUrl: 'final.png', iconSize: [32, 32]})}).addTo(map);
					  
					  setStartTime(day);
					  
					  if(startTimePopup) {
						startMarker.bindPopup(startTimePopup).openPopup();
					  }
					  
					  setCenterAndZoom();
                  },
                  function(error) { console.log(error); });
              }
              
              function moveGiantMarker(lat, lon) {
                  if(giantMarker == null)
                  {				  
					  var giantIcon = L.icon({ iconUrl: 'king.png', iconSize: [64, 64], className: 'blinking', popupAnchor: [0, -30]});
					  giantMarker = L.marker([lat, lon], {icon: giantIcon}).addTo(map);                      
					  giantMarker.bindPopup(giantsPositionPopup).openPopup();;
					  
                      setCenterAndZoom();
                  }
                  else
                  {
                     giantMarker.setLatLng([lat, lon]);
                  }
              };
             
              function subscribeGiantPosition(){
				if(gigantesBailando()) {
					if(giantMarker != null) {
						map.removeLayer(giantMarker);
						giantMarker = null;
						setCenterAndZoom();
					}
					else {
						getGiantPosition();
						setInterval(function() { getGiantPosition(); }, 10000);
					}
				}
				else{
					alert(restingMessage);
				}
              }
              
              function getGiantPosition(){
				  //var date = new Date();
                  //request('index.php/position', 'POST', { day : 6, date: new Date(2024,6,6,date.getHours(),date.getMinutes()).toISOString()}, function(data) { console.log(data); moveGiantMarker(Number(data.lat), Number(data.lon)); }, function(error) { console.log(error); });
				  request('index.php/position', 'POST', { day : getDay(), date: new Date().toISOString()}, function(data) { moveGiantMarker(Number(data.lat), Number(data.lon)); }, function(error) { console.log(error); });
              }
              
              function subscribeUserPosition(){
                  
                   if ("geolocation" in navigator) {
                       
					   if(watchID) {
							navigator.geolocation.clearWatch(watchID);
							map.removeLayer(userMarker);
							userMarker = null;
							watchID = null;
							setCenterAndZoom();
					   }
					   else {
							watchID = navigator.geolocation.watchPosition((position) => {
					
								var userLatLon = [position.coords.latitude,  position.coords.longitude];
																					
								if(userMarker == null) {
								
									var userIcon = L.icon({ iconUrl: 'user.png', iconSize: [64, 64], className: 'blinking', popupAnchor: [0, -30]});
									userMarker = L.marker(userLatLon, {icon: userIcon}).addTo(map);
									userMarker.bindPopup(yourPositionPopup).openPopup();
									
									setCenterAndZoom();                                
								}
								else {
									userMarker.setLatLng(userLatLon);
								}							
							});
						}
                    } else { alert('Geolocalicacion no disponible'); }
              }
              
              function setCenterAndZoom(){
                  
				  var bounds = [];
                  
                  if(giantMarker != null)    { bounds.push(giantMarker.getLatLng()); }
                  if(userMarker != null)   	 { bounds.push(userMarker.getLatLng()); }
				  if(centerMarker != null)   { bounds.push(centerMarker.getLatLng()); }
				  if(startMarker != null)    { bounds.push(startMarker.getLatLng()); }
				  if(finishMarker != null)   { bounds.push(finishMarker.getLatLng()); }
                  if(pathPoints != null)	 { bounds.push(pathPoints); }				  
				  
                  map.fitBounds(bounds);
              }
              
              function privacidadModal() {}
              
			  function gigantesBailando() {
			  			  
				var now = new Date().getTime();
				
				if	(	(now > new Date(2024, 6, 6, 17, 0) && now < new Date(2024, 6, 6, 21, 0) ) ||
						(now > new Date(2024, 6, 7, 9, 30) && now < new Date(2024, 6, 7, 15, 0) ) ||
						(now > new Date(2024, 6, 8, 9, 30) && now < new Date(2024, 6, 8, 15, 0) ) ||
						(now > new Date(2024, 6, 9, 9, 30) && now < new Date(2024, 6, 9, 15, 0) ) ||
						(now > new Date(2024, 6, 10, 9, 30) && now < new Date(2024, 6, 10, 15, 0) ) ||
						(now > new Date(2024, 6, 11, 9, 30) && now < new Date(2024, 6, 11, 15, 0) ) ||
						(now > new Date(2024, 6, 12, 9, 30) && now < new Date(2024, 6, 12, 15, 0) ) ||
						(now > new Date(2024, 6, 13, 9, 30) && now < new Date(2024, 6, 13, 15, 0) ) ||
						(now > new Date(2024, 6, 14, 9, 30) && now < new Date(2024, 6, 14, 15, 0) )		)
					return true;
				
				return false;				
			  }
			  
			  function setStartTime(day){				
				if(day == 6) {
					startTimePopup = startTimePopup.split(' ')[0] + ' 17:00';
				}
				else {
					startTimePopup = startTimePopup.split(' ')[0] + ' 9:30';
				}
			  }
			  
			  function getDay() {
				var now = new Date().getTime();
				if (now < new Date('07/07/2024')){
					return 6;
				}
				if (now > new Date('14/07/2024'))
					return 14;
					
				return new Date().getDate();
			  }
			  
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
							L.DomEvent.disableClickPropagation(button);
							L.DomEvent.on(button, 'click', function(){
								subscribeUserPosition();
							});

							return container;
						},
						onRemove: function(map) {},
					});
					var control = new L.Control.Button()
					control.addTo(map);
			  }
			  
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
							L.DomEvent.disableClickPropagation(button);
							L.DomEvent.on(button, 'click', function(){
								subscribeGiantPosition();
							});

							return container;
						},
						onRemove: function(map) {},
					});
					var control = new L.Control.Button()
					control.addTo(map);
			  }
			  
              function init() {
                 
				 map = L.map('map');						  
				 L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 20, attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);				
				 				 
				 dateSelect = document.getElementById("dateSelect");
                 dateSelect.addEventListener('change',function(){
                     drawTodayPath(Number(this.value));
                 });                 
				 
                 changeLan('cas');
                 drawTodayPath(getDay());
				 addUserLocalizationControl();
				 addGiantsLocalizationControl();
                 
                 //privacidadModal();
				 
			  };
			
			  window.onload = init; 