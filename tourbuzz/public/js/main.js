// Globals
var leafletMap;
var leafletLayers = {};
var leafletLayerControl;

function initLayers() {
  Object.keys(layers).forEach(function (key) {
    console.log(key, layers[key]);
    switch (key) {
      case 'layer.messages':
        addMessages(layers[key]);
        break;
      case 'layer.stops':
        addStops(layers[key]);
        break;
      case 'layer.parking':
        addParking(layers[key]);
        break;
      case 'layer.clearance_height':
        addDoorrijhoogtes(layers[key]);
        break;
      case 'layer.destination_traffic':
        addBestemmingsverkeer(layers[key]);
        break;
      case 'layer.recommended_routes':
        addAanbevolenroutes(layers[key]);
        break;
      case 'layer.mandatory_routes':
        addVerplichteroutes(layers[key]);
        break;
      case 'layer.traffic':
        //addVerkeersdrukte(tbmap);
        break;
      case 'layer.environmental_zone':
        addMilieuzone(layers[key]);
        break;
      default:
    }
  });


}

function removeAllLayers() {
  leafletLayerControl.removeLayer('layer.messages');
  leafletLayerControl.removeLayer('layer.stops');
  leafletLayerControl.removeLayer('layer.parking');
  leafletLayerControl.removeLayer('layer.clearance_height');
  leafletLayerControl.removeLayer('layer.destination_traffic');
  leafletLayerControl.removeLayer('layer.recommended_routes');
  leafletLayerControl.removeLayer('layer.mandatory_routes');
  leafletLayerControl.removeLayer('layer.traffic');
  leafletLayerControl.removeLayer('layer.environmental_zone');
}

//////////////////////
// Current location //
//////////////////////

function getLocation(callback) {
  if (navigator.getLocation) {
    navigator.geolocation.getCurrentPosition(function (data) {
      var jsonLocation = [data.coords.latitude, data.coords.longitude];
      callback(jsonLocation);
    });
  }
}

////////////////
// Layer data //
////////////////

function addCurrentLocation() {
  var lat = 52.3616339;
  var lon = 4.905583;

  getLocation(function (position) {
    lat = position[0];
    lng = position[1];
    var customIcon = new L.divIcon({
      iconSize: [36, 39],
      iconAnchor: [18, 39],
      popupAnchor: [0, -40],
      className: 'custom-icon-whereami'
    });

    var popupHTML = '<p>U bent hier</p>';

    L.marker([lat, lng], { icon: customIcon }).bindPopup(popupHTML).addTo(leafletMap);
  });
}

function addMessages(layer) {
  console.log("addMessages");
  var dayEl = document.querySelector('[data-day]');
  var day = dayEl.getAttribute('data-day');
  var mapviewEl = document.querySelector('[data-mapview]');
  var embedded = mapviewEl.getAttribute('data-embedded');
  var dataUrl = '/json/message-overview' + day;

  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      var markerArray = [];
      for (var i in res.berichten) {
        if (res.berichten[i].location_lat) {
          var customIcon = new L.divIcon({
            iconSize: [36, 39],
            iconAnchor: [18, 39],
            popupAnchor: [0, -40],
            className: 'custom-icon-bericht',
            html: '<span>' + res.berichten[i].sort_order + '</span>'
          });
          popupHTML = "<h3 class='custom-marker-title'>" + res.berichten[i].title + "</h3>";
          if (embedded == 1) {
            popupHTML += "<a href='https://www.tourbuzz.nl/bericht/" + res.berichten[i].id + "' class='custom-marker-link' target='_blank'>details</a>";
          } else {
            popupHTML += "<a href='/bericht/" + res.berichten[i].id + "' data-js-click='loadBericht' data-bericht-id='" + res.berichten[i].id + "' class='custom-marker-link'>details</a>";
          }
          markerArray.push(L.marker([res.berichten[i].location_lat, res.berichten[i].location_lng], { icon: customIcon }).bindPopup(popupHTML, { minWidth: 240, maxWidth: 240 }));
        }
      }
      leafletLayers[layer.id] = L.featureGroup(markerArray);
      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }

      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function messageInfo(data) {
  var content = "<h3 class='custom-marker-title'>" + data.title + "</h3>";
  return content;
}

function stopInfo(feature) {
  var popupHTML = "<h3 class='custom-marker-title'>" + feature.properties.title + "</h3>";
  popupHTML += "<div class='custom-marker-badge'>" + feature.properties.spots + " plaatsen</div>";
  popupHTML += "<p class='custom-marker-text'>" + feature.properties.description + "</p>";
  return popupHTML;
}

function parkingInfo(feature) {
  var popupHTML = "<h3 class='custom-marker-title'>" + feature.properties.title + "</h3>";
  popupHTML += "<div class='custom-marker-badge'>" + feature.properties.spots + " plaatsen</div>";
  popupHTML += "<p class='custom-marker-text'>" + feature.properties.description + "</p>";
  return popupHTML;
}

function addStops(layer) {
  var dataUrl = tourbuzz_api_base_uri + '/api/v2/stops.geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      leafletLayers[layer.id] = L.geoJSON(res, {
        pointToLayer: function (feature, latlng) {
          var customIcon = new L.divIcon({
            iconSize: [36, 39],
            iconAnchor: [18, 39],
            popupAnchor: [0, -40],
            className: 'custom-icon-halte blue',
            html: '<span>' + feature.properties.source.id + '</span>'
          });
          var marker = L.marker(latlng, {icon: customIcon}).bindPopup(stopInfo(feature));
          marker.on('mouseover', function (e) {
            document.getElementById('hoverpanel').innerHTML = stopInfo(feature);
          });
          marker.on('mouseout', function (e) {
            document.getElementById('hoverpanel').innerHTML = '';
          });
          return marker;
        }
      });

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }

      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function addParking(layer) {
  var dataUrl = tourbuzz_api_base_uri + '/api/v2/parking.geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      leafletLayers[layer.id] = L.geoJSON(res, {
        pointToLayer: function (feature, latlng) {
          var customIcon = new L.divIcon({
            iconSize: [36, 39],
            iconAnchor: [18, 39],
            popupAnchor: [0, -40],
            className: 'custom-icon-parkeren black',
            html: '<span>' + feature.properties.source.id + '</span>'
          });
          
          var marker = L.marker(latlng, {icon: customIcon}).bindPopup(parkingInfo(feature));
          marker.on('mouseover', function (e) {
            document.getElementById('hoverpanel').innerHTML = parkingInfo(feature);
          });
          marker.on('mouseout', function (e) {
            document.getElementById('hoverpanel').innerHTML = '';
          });
          return marker;
        }
      });

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }
      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function addDoorrijhoogtes(layer) {
  var dataUrl = 'https://tourbuzz-api.tiltshiftapps.nl/api/v2/max_height.geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      leafletLayers[layer.id] = L.geoJSON(res, {
        pointToLayer: function (feature, latlng) {
          var customIcon = new L.divIcon({
            iconSize: [36, 43],
            iconAnchor: [18, 39],
            popupAnchor: [0, -40],
            className: 'custom-icon-doorrijhoogte',
            html: '<span>' + feature.properties.maxheight + '</span>'
          });
          return L.marker(latlng, {icon: customIcon})
        }
      });

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }

      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function addBestemmingsverkeer(layer) {
  var dataUrl = tourbuzz_api_base_uri + '/routes/roadwork/geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      var styles = {
        weight: 6,
        opacity: 1,
        color: '#FF9100'
      };
      var popupHTML = '<p>' + layer.name + '</p>';
      leafletLayers[layer.id] = L.geoJSON(res, { style: styles }).bindPopup(popupHTML);

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }

      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function addAanbevolenroutes(layer) {
  var dataUrl = tourbuzz_api_base_uri + '/routes/recommended/geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      var styles = {
        weight: 6,
        opacity: 1,
        color: '#BED200'
      };
      var popupHTML = '<p>' + layer.name + '</p>';
      leafletLayers[layer.id] = L.geoJSON(res, { style: styles }).bindPopup(popupHTML);

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }

      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function addVerplichteroutes(layer) {
  var dataUrl = tourbuzz_api_base_uri + '/routes/mandatory/geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      var styles = {
        weight: 6,
        opacity: 1,
        color: '#00A03C'
      };

      var popupHTML = '<p>' + layer.name + '</p>';
      leafletLayers[layer.id] = L.geoJSON(res, { style: styles }).bindPopup(popupHTML);

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }

      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
}

function addVerkeersdrukte() {
  return true;
}

function addMilieuzone(layer) {
  var dataUrl = 'https://tourbuzz-api.tiltshiftapps.nl/api/v2/environmental_zones.geojson';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      var styles = {
        weight: 1,
        opacity: 1,
        fill: "#E50082",
        fillOpacity: .2,
        color: '#E50082',  //Outline color
      };
      leafletLayers[layer.id] = L.geoJSON(res, { style: styles });

      if (layer.visible) {
        leafletLayers[layer.id].addTo(leafletMap);
      }
      leafletLayerControl.addOverlay(leafletLayers[layer.id], layer.name);
    });
  return true;
}


/////////
// Map //
/////////

function createMap(el, lat, lon, zoom) {
  leafletMap = L.map(el).setView([lat, lon], zoom);
  leafletMap.zoomControl.setPosition('bottomright');
  var mapboxstreets = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v11/tiles/{z}/{x}/{y}?access_token=' + mapbox_access_token, {
    tms: false,
    minZoom: 3,
    maxZoom: 18,
    attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(leafletMap);
  var mapboxgrayscale = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/light-v9/tiles/{z}/{x}/{y}?access_token=' + mapbox_access_token, {
    tms: false,
    minZoom: 3,
    maxZoom: 18,
    attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  });
  var mapboxsatellite = L.tileLayer('https://api.mapbox.com/v4/mapbox.satellite/{z}/{x}/{y}@2x.png?access_token=' + mapbox_access_token, {
    tms: false,
    minZoom: 3,
    maxZoom: 18,
    attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  });

  routinglayer = L.Routing.control({
    waypoints: [],
    routeWhileDragging: true,
    router: L.Routing.osrmv1({
      serviceUrl: 'http://localhost:5000/route/v1'
    })
  }).addTo(leafletMap);

  leafletLayerControl = L.control.layers({ "Streets": mapboxstreets, "Grayscale": mapboxgrayscale, "Satellite": mapboxsatellite }).addTo(leafletMap);
  leafletLayerControl.setPosition('bottomright');


}

function repositionMap(lat, lng) {
  var markerBounds = L.latLngBounds([lat, lng]);
  leafletMap.fitBounds(markerBounds);
}

function updateMap(el) {

  var centerLat = el.getAttribute('data-center-lat');
  var centerLng = el.getAttribute('data-center-lng');
  var zoom = el.getAttribute('data-zoom');

  if (!leafletMap) {
    createMap(el, centerLat, centerLng, zoom);
  }

  var activateLayersString = el.getAttribute('data-activate-layers');
  if (activateLayersString) {
    var activateLayers = activateLayersString.split(",");
    for (var i = 0; i < activateLayers.length; i++) {
      layers[activateLayers[i]].visible = true;
    }
  }
  initLayers();
}


/////////////////////
// Main navigation //
/////////////////////

function navBerichten(el) {
  console.log("navBerichten");
  var currentNav = document.querySelector('[data-navigation-bar] .active');
  currentNav.classList.remove('active');
  el.classList.add('active');
  history.pushState(null, 'Berichten', '/');

  var pageContentOrder = document.querySelector('[data-page-content-order]');
  pageContentOrder.classList.remove('-reverse');

  var mapView = document.querySelector('[data-mapview]');
  mapView.setAttribute('data-activate-layers', 'layer.messages');
  updateMap(mapView);

  var infoPanel = document.querySelector('[data-infopanel]');

  var dayEl = document.querySelector('[data-day]');
  var day = dayEl.getAttribute('data-day');

  infoPanel.setAttribute('data-infopanel-url', day + '?partial=panel&lang=' + language);
  loadInfopanel(infoPanel);
}

function navHaltesParkeren(el) {
  console.log("navHaltesParkeren")
  var currentNav = document.querySelector('[data-navigation-bar] .active');
  currentNav.classList.remove('active');
  el.classList.add('active');
  history.pushState(null, 'Haltes & Parkeren', '/haltes-parkeerplaatsen');

  var mapView = document.querySelector('[data-mapview]');
  mapView.setAttribute('data-activate-layers', 'layer.stops,layer.parking');
  console.log(mapView);
  updateMap(mapView);

  var infoPanel = document.querySelector('[data-infopanel]');
  infoPanel.setAttribute('data-infopanel-url', '/haltes-parkeerplaatsen?partial=panel&lang=' + language);
  loadInfopanel(infoPanel);
}

function navRoutes(el) {
  var currentNav = document.querySelector('[data-navigation-bar] .active');
  currentNav.classList.remove('active');
  el.classList.add('active');
  history.pushState(null, 'Route informatie', '/routes');

  var pageContentOrder = document.querySelector('[data-page-content-order]');
  pageContentOrder.classList.add('-reverse');

  var mapView = document.querySelector('[data-mapview]');
  mapView.setAttribute('data-activate-layers', 'layer.clearance_height,layer.recommended_routes,layer.mandatory_routes,layer.destination_traffic');
  updateMap(mapView);

  var infoPanel = document.querySelector('[data-infopanel]');
  infoPanel.setAttribute('data-infopanel-url', '/routes?partial=panel&lang=' + language);
  loadInfopanel(infoPanel);
}

////////////////
// Load Panel //
////////////////

function loadInfopanel(el) {
  var dataUrl = el.getAttribute('data-infopanel-url');
  var contentContainer = document.querySelector('[data-infopanel-content]');
  contentContainer.innerHTML = '';

  var loading = document.querySelector('[data-infopanel-loading]');
  loading.classList.add('active');

  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      var loading = document.querySelector('[data-infopanel-loading]');
      loading.classList.remove('active');
      var contentContainer = document.querySelector('[data-infopanel-content]');
      contentContainer.innerHTML = res;

      // perform all ready functions
      var hooks = document.querySelectorAll('[data-infopanel-content] [data-js-ready]');
      for (i = 0; i < hooks.length; i++) {
        controller = hooks[i].getAttribute('data-js-ready');
        if (controller && undefined !== window[controller]) {
          window[controller](hooks[i]);
        } else {
          console.log(controller + " is not available");
        }
      }
    });

}

function loadBericht(el) {
  var infoPanel = document.querySelector('[data-infopanel]');
  var bericht = el.getAttribute('data-bericht-id');

  var dayEl = document.querySelector('[data-day]');
  var day = dayEl.getAttribute('data-day');

  infoPanel.setAttribute('data-infopanel-url', '/bericht/' + bericht + day);
  loadInfopanel(infoPanel);
}

function loadStop(el) {
  var infoPanel = document.querySelector('[data-infopanel]');
  var stop = el.getAttribute('data-halte');
  infoPanel.setAttribute('data-infopanel-url', '/haltes/' + stop);
  loadInfopanel(infoPanel);
}

function loadParkeerplaats(el) {
  var infoPanel = document.querySelector('[data-infopanel]');
  var parkeerplaats = el.getAttribute('data-parkeerplaats');
  infoPanel.setAttribute('data-infopanel-url', '/parkeerplaatsen/' + parkeerplaats);
  loadInfopanel(infoPanel);
}

function loadPanoThumbnail(el) {
  var lat = el.getAttribute('data-lat');
  var lon = el.getAttribute('data-lon');
  var dataUrl = 'https://api.data.amsterdam.nl/panorama/thumbnail/?lat=' + lat + '&lon=' + lon + '&width=600&radius=180';
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      el.src = res.url;
    });
}

//////////////
// Language //
//////////////

function toggleLanguageSwitch(el) {
  var langSwitch = document.querySelector('[data-lang-switch]');
  langSwitch.classList.toggle('active');
}

/////////////////////////////////////
// Prevent unformatted date input //
////////////////////////////////////

function preventUnformattedDateInput(el) {
  el.addEventListener('keypress', function (e) {
    e.preventDefault();
  });
}

///////////////////////
// Load availability //
///////////////////////

function loadAvailability(el) {
  var parkID = el.getAttribute('data-park-id');
  var dataUrl = '/async/parkeerplaats-status/' + parkID;
  axios.get(dataUrl)
    .then(function (response) {
      res = response.data;
      el.innerHTML = res;
    });
}

//////////
// Run //
//////////

function run() {

  // Catch all clicks
  document.addEventListener('click', function (e) {
    var el = e.target;
    var controller = el.getAttribute('data-js-click');
    if (controller) {
      e.preventDefault();
      window[controller](el);
    }
  });

  // DOM ready
  document.addEventListener('DOMContentLoaded', function (e) {
    var options = {
      defaultDate: new Date(),
      setDefaultDate: true
    };

    var dropdowns = document.querySelectorAll('.dropdown-trigger');
    var modals = document.querySelectorAll('.modal');
    var datepickers = document.querySelector('.datepicker');
    var datepicker = M.Datepicker.init(datepickers, options);
    datepicker.setDate(new Date());
    M.Dropdown.init(dropdowns, {});
    M.Modal.init(modals, {});
    var hooks = document.querySelectorAll('[data-js-ready]');
    for (i = 0; i < hooks.length; i++) {
      controller = hooks[i].getAttribute('data-js-ready');
      if (controller && undefined !== window[controller]) {
        window[controller](hooks[i]);
      } else {
        console.log(controller + " is not available");
      }
    }
  });

  // Refresh on back button
  window.onpopstate = function (e) {
    window.location = document.location;
  };

}

run();