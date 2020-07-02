////////////
// Legend //
////////////

function toggleLayersLegend (el) {
    var layersLegend = document.querySelector('[data-layers-legend]');
    layersLegend.classList.toggle('-active');
}

function toggleLayer (el) {
    var currentLayerId = el.getAttribute('data-layer');
    if (el.classList.contains('-active')) {
        el.classList.remove('-active');
        removeLayer(currentLayerId);
    } else {
        el.classList.add('-active');
        addLayer(currentLayerId);
    }
}


//////////////////////
// Layer activation //
//////////////////////

function addLayer (layerId) {
    switch (layerId) {
        case 'berichten':
            addBerichten(tbmap);
            break;
        case 'haltes':
            addHaltes(tbmap);
            break;
        case 'parkeren':
            addParkeren(tbmap);
            break;
        case 'doorrijhoogtes':
            addDoorrijhoogtes(tbmap);
            break;
        case 'bestemmingsverkeer':
            addBestemmingsverkeer(tbmap);
            break;
        case 'aanbevolenroutes':
            addAanbevolenroutes(tbmap);
            break;
        case 'verplichteroutes':
            addVerplichteroutes(tbmap);
            break;
        case 'verkeersdrukte':
            //addVerkeersdrukte(tbmap);
            break;
        case 'milieuzone':
            addMilieuzone(tbmap);
            break;
        default:
    }
}

function removeLayer (layerId) {
    if (undefined !== mapLayers[layerId]) {
        mapLayers[layerId].remove();
    }
}

function removeAllLayers () {
    removeLayer('berichten');
    removeLayer('haltes');
    removeLayer('parkeren');
    removeLayer('doorrijhoogtes');
    removeLayer('bestemmingsverkeer');
    removeLayer('aanbevolenroutes');
    removeLayer('verplichteroutes');
    removeLayer('verkeersdrukte');
    removeLayer('milieuzone');
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

function addCurrentLocation (targetMap) {
    var lat = 52.3616339;
    var lon = 4.905583;

    getLocation(function(position) {
        lat = position[0];
        lng = position[1];
        var customIcon = new L.divIcon({
            iconSize: [36, 39],
            iconAnchor: [18, 39],
            popupAnchor: [0, -40],
            className: 'custom-icon-whereami'
        });

        var popupHTML = '<p>U bent hier</p>';

        L.marker([lat, lng], {icon: customIcon}).bindPopup(popupHTML).addTo(targetMap);
    });
}

function addBerichten (targetMap) {
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
                        popupHTML += "<a href='/bericht/" + res.berichten[i].id + "' data-js-click='loadBericht' data-bericht-id='"+ res.berichten[i].id + "' class='custom-marker-link'>details</a>";
                    }
                    markerArray.push(L.marker([res.berichten[i].location_lat, res.berichten[i].location_lng], {icon: customIcon}).bindPopup(popupHTML, {minWidth: 240, maxWidth: 240}));
                }
            }
            mapLayers['berichten'] = L.featureGroup(markerArray).addTo(targetMap);
            if(markerArray.length > 0){
                targetMap.fitBounds(mapLayers['berichten'].getBounds(), {padding: [50,50]});
            }
        });
}

function addHaltes (targetMap) {
    var dataUrl = tourbuzz_api_base_uri + '/haltes';
    var mapviewEl = document.querySelector('[data-mapview]');
    var embedded = mapviewEl.getAttribute('data-embedded');
    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var markerArray = [];
            for (var i in res.haltes) {
                var customIcon = new L.divIcon({
                    iconSize: [36, 39],
                    iconAnchor: [18, 39],
                    popupAnchor: [0, -40],
                    className: 'custom-icon-halte',
                    html: '<span>'+ res.haltes[i].haltenummer +'</span>'
                });
                popupHTML = "<h3 class='custom-marker-title'>" + res.haltes[i].haltenummer + " " + res.haltes[i].straat + "</h3>";
                popupHTML += "<div class='custom-marker-badge'>" + res.haltes[i].capaciteit + " plaatsen</div>";
                popupHTML += "<p class='custom-marker-text'>" + res.haltes[i].locatie + "</p>";
                if (embedded == 1) {
                    popupHTML += "<a href='https://www.tourbuzz.nl/haltes/" + res.haltes[i].haltenummer + "' class='custom-marker-link' target='_blank'>details</a>";
                } else {
                    popupHTML += "<a href='#' class='custom-marker-link' data-js-click='loadHalte' data-halte='"+ res.haltes[i].haltenummer +"'>details</a>";
                }
                markerArray.push(L.marker([res.haltes[i].location.lat, res.haltes[i].location.lng], {icon: customIcon}).bindPopup(popupHTML, {minWidth: 240, maxWidth: 240}));
            }
            mapLayers['haltes'] = L.featureGroup(markerArray).addTo(targetMap);
            targetMap.fitBounds(mapLayers['haltes'].getBounds());
        });
}

function addParkeren (targetMap) {
    var dataUrl = tourbuzz_api_base_uri + '/parkeerplaatsen';
    var mapviewEl = document.querySelector('[data-mapview]');
    var embedded = mapviewEl.getAttribute('data-embedded');
    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var markerArray = [];
            for (var i in res.parkeerplaatsen) {
                var customIcon = new L.divIcon({
                    iconSize: [36, 39],
                    iconAnchor: [18, 39],
                    popupAnchor: [0, -40],
                    className: 'custom-icon-parkeren',
                    html: '<span>'+ res.parkeerplaatsen[i].nummer +'</span>'
                });
                popupHTML = "<h3 class='custom-marker-title'>" + res.parkeerplaatsen[i].nummer + " " + res.parkeerplaatsen[i].naam + "</h3>";
                popupHTML += "<div class='custom-marker-badge'>" + res.parkeerplaatsen[i].capaciteit + " plaatsen</div>";
                popupHTML += "<p class='custom-marker-text'>" + res.parkeerplaatsen[i]._origineel.Bijzonderheden + "</p>";
                if (embedded == 1) {
                    popupHTML += "<a href='https://www.tourbuzz.nl/parkeerplaatsen/" + res.parkeerplaatsen[i].nummer + "' class='custom-marker-link' target='_blank'>details</a>";
                } else {
                    popupHTML += "<a href='#' class='custom-marker-link' data-js-click='loadParkeerplaats' data-parkeerplaats='"+ res.parkeerplaatsen[i].nummer +"'>details</a>";
                }
                markerArray.push(L.marker([res.parkeerplaatsen[i].location.lat, res.parkeerplaatsen[i].location.lng], {icon: customIcon}).bindPopup(popupHTML, {minWidth: 240, maxWidth: 240}));
            }
            mapLayers['parkeren'] = L.featureGroup(markerArray).addTo(targetMap);
            targetMap.fitBounds(mapLayers['parkeren'].getBounds());
        });
}

function addDoorrijhoogtes (targetMap) {
    var dataUrl = 'https://open.data.amsterdam.nl/ivv/touringcar/max_doorrijhoogte.json';
    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var markerArray = [];
            for (var i in res.max_doorrijhoogtes) {
                var customIcon = new L.divIcon({
                    iconSize: [36, 39],
                    iconAnchor: [18, 39],
                    popupAnchor: [0, -40],
                    className: 'custom-icon-doorrijhoogte',
                    html: '<span>'+ res.max_doorrijhoogtes[i].max_doorrijhoogte.Maximale_doorrijhoogte +'</span>'
                });
                popupHTML = "<h3 class='custom-marker-title'>" + res.max_doorrijhoogtes[i].max_doorrijhoogte.title + " " + res.max_doorrijhoogtes[i].max_doorrijhoogte.Maximale_doorrijhoogte +"</h3>";
                var locationString = res.max_doorrijhoogtes[i].max_doorrijhoogte.Lokatie;
                var locationJSON = JSON.parse(locationString);
                markerArray.push(L.marker([locationJSON.coordinates[1], locationJSON.coordinates[0]], {icon: customIcon}).bindPopup(popupHTML));
            }
            mapLayers['doorrijhoogtes'] = L.featureGroup(markerArray).addTo(targetMap);
            targetMap.fitBounds(mapLayers['doorrijhoogtes'].getBounds());
        });
}

function addBestemmingsverkeer (targetMap) {
    var dataUrl = tourbuzz_api_base_uri + '/routes/roadwork/geojson';
    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var styles = {
                weight: 6,
                opacity: 1,
                color: '#FF9100'
            };
            var popupHTML = '<p>Bestemmingsverkeer</p>';
            mapLayers['bestemmingsverkeer'] = L.geoJSON(res, {style: styles} ).bindPopup(popupHTML).addTo(targetMap);
            targetMap.fitBounds(mapLayers['bestemmingsverkeer'].getBounds());
        });
}

function addAanbevolenroutes (targetMap) {
    var dataUrl = tourbuzz_api_base_uri + '/routes/recommended/geojson';
    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var styles = {
                weight: 6,
                opacity: 1,
                color: '#BED200'
            };
            var popupHTML = '<p>Aanbevolen route</p>';
            mapLayers['aanbevolenroutes'] = L.geoJSON(res, {style: styles} ).bindPopup(popupHTML).addTo(targetMap);
            targetMap.fitBounds(mapLayers['aanbevolenroutes'].getBounds());
        });
}

function addVerplichteroutes(targetMap) {
    var dataUrl = tourbuzz_api_base_uri + '/routes/mandatory/geojson';
    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var styles = {
                weight: 6,
                opacity: 1,
                color: '#00A03C'
            };

            var popupHTML = '<p>Verplichte route</p>';
            mapLayers['verplichteroutes'] = L.geoJSON(res, {style: styles} ).bindPopup(popupHTML).addTo(targetMap);
            targetMap.fitBounds(mapLayers['verplichteroutes'].getBounds());
        });
}

function addVerkeersdrukte (targetMap) {
    return true;
}

function addMilieuzone (targetMap) {
    var dataUrl = 'https://api.data.amsterdam.nl/dcatd/datasets/ot28M5SZu0h9PA/purls/1';
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
            mapLayers['milieuzone'] = L.geoJSON(res, {style: styles} ).addTo(targetMap);
            targetMap.fitBounds(mapLayers['milieuzone'].getBounds());
        });
    return true;
}


/////////
// Map //
/////////

function createMap (el, lat, lon, zoom) {
    var newMap = L.map(el).setView([lat, lon], zoom);
    newMap.zoomControl.setPosition('topright');
    L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v9/tiles/{z}/{x}/{y}?access_token=' + mapbox_access_token, {
        tms: false,
        minZoom: 3,
        maxZoom: 18,
        attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(newMap);
    return newMap;
}

function repositionMap (lat, lng) {
    var markerBounds = L.latLngBounds( [ lat, lng ]);
    tbmap.fitBounds(markerBounds);
}

function updateMap (el) {

    var centerLat = el.getAttribute('data-center-lat');
    var centerLng = el.getAttribute('data-center-lng');
    var zoom = el.getAttribute('data-zoom');

    if (!tbmap) {
        tbmap = createMap(el, centerLat, centerLng, zoom);
        //tbmap.scrollWheelZoom.disable();
        //addCurrentLocation(tbmap);
    }

    var activateLayersString = el.getAttribute('data-activate-layers');

    if ( activateLayersString ) {
        var activateLayers = activateLayersString.split(",");

        //mapLayers = []; // reset all layers
        var layers = document.querySelectorAll('[data-layer]');
        for (var i = 0; i < layers.length; i++) {
            currentLayerId = layers[i].getAttribute('data-layer');
            if (activateLayers.indexOf(currentLayerId) !== -1) {
                layers[i].classList.add('-active');
                addLayer(currentLayerId);
            } else {
                layers[i].classList.remove('-active');
                removeLayer(currentLayerId);
            }
        }
    }
}


/////////////////////
// Main navigation //
/////////////////////

function navBerichten (el) {
    var currentNav = document.querySelector('[data-navigation-bar] .-active');
    currentNav.classList.remove('-active');
    el.classList.add('-active');
    history.pushState(null, 'Berichten', '/');

    var pageContentOrder = document.querySelector('[data-page-content-order]');
    pageContentOrder.classList.remove('-reverse');

    var mapView = document.querySelector('[data-mapview]');
    mapView.setAttribute('data-activate-layers', 'berichten');
    updateMap(mapView);

    var infoPanel = document.querySelector('[data-infopanel]');

    var dayEl = document.querySelector('[data-day]');
    var day = dayEl.getAttribute('data-day');

    infoPanel.setAttribute('data-infopanel-url', day + '?partial=panel&lang=' + language);
    loadInfopanel(infoPanel);
}

function navHaltesParkeren (el) {
    var currentNav = document.querySelector('[data-navigation-bar] .-active');
    currentNav.classList.remove('-active');
    el.classList.add('-active');
    history.pushState(null, 'Haltes & Parkeren', '/haltes-parkeerplaatsen');

    var pageContentOrder = document.querySelector('[data-page-content-order]');
    pageContentOrder.classList.add('-reverse');

    var mapView = document.querySelector('[data-mapview]');
    mapView.setAttribute('data-activate-layers', 'haltes,parkeren');
    updateMap(mapView);

    var infoPanel = document.querySelector('[data-infopanel]');
    infoPanel.setAttribute('data-infopanel-url', '/haltes-parkeerplaatsen?partial=panel&lang=' + language);
    loadInfopanel(infoPanel);
}

function navRoutes (el) {
    var currentNav = document.querySelector('[data-navigation-bar] .-active');
    currentNav.classList.remove('-active');
    el.classList.add('-active');
    history.pushState(null, 'Route informatie', '/routes');

    var pageContentOrder = document.querySelector('[data-page-content-order]');
    pageContentOrder.classList.add('-reverse');

    var mapView = document.querySelector('[data-mapview]');
    mapView.setAttribute('data-activate-layers', 'doorrijhoogtes,aanbevolenroutes,verplichteroutes,bestemmingsverkeer');
    updateMap(mapView);

    var infoPanel = document.querySelector('[data-infopanel]');
    infoPanel.setAttribute('data-infopanel-url', '/routes?partial=panel&lang=' + language);
    loadInfopanel(infoPanel);
}

/////////////////
// Date picker //
/////////////////

// Date picker Input
function datePickerInput (el) {
    var datePickerInput = el;
    var datePicker = document.querySelector('[data-date-picker]');
    var calendarUrl = el.getAttribute('data-calendar-url');
    var calendarContainer = document.querySelector('[data-calendar-container]');
    datePicker.classList.toggle('-active');

    axios.get(calendarUrl)
        .then(function (response) {
            var htmlString = response.data;
            calendarContainer.innerHTML = htmlString;
            var calendarLoading = document.querySelector('[data-loading-calendar-block]');
            calendarLoading.parentNode.removeChild(calendarLoading);
        });
}

// Month select
function monthSelect (el) {
    var calendarUrl = el.getAttribute('data-month');
    var calendarContainer = document.querySelector('[data-calendar-container]');

    axios.get(calendarUrl)
        .then(function (response) {
            var htmlString = response.data;
            calendarContainer.innerHTML = htmlString;
            var calendarLoading = document.querySelector('[data-loading-calendar-block]');
            calendarLoading.parentNode.removeChild(calendarLoading);
        });
}

////////////////
// Load Panel //
////////////////

function loadInfopanel (el) {
    var dataUrl = el.getAttribute('data-infopanel-url');
    var contentContainer =  document.querySelector('[data-infopanel-content]');
    contentContainer.innerHTML = '';

    var loading = document.querySelector('[data-infopanel-loading]');
    loading.classList.add('-active');

    axios.get(dataUrl)
        .then(function (response) {
            res = response.data;
            var loading = document.querySelector('[data-infopanel-loading]');
            loading.classList.remove('-active');
            var contentContainer =  document.querySelector('[data-infopanel-content]');
            contentContainer.innerHTML = res;

            // perform all ready functions
            var hooks = document.querySelectorAll('[data-infopanel-content] [data-js-ready]');
            for(i = 0; i < hooks.length; i++) {
                controller = hooks[i].getAttribute('data-js-ready');
                if (controller && undefined !== window[controller]) {
                    window[controller](hooks[i]);
                } else {
                    console.log(controller + " is not available");
                }
            }
        });

}

function loadBericht (el) {
    var infoPanel = document.querySelector('[data-infopanel]');
    var bericht = el.getAttribute('data-bericht-id');

    var dayEl = document.querySelector('[data-day]');
    var day = dayEl.getAttribute('data-day');

    infoPanel.setAttribute('data-infopanel-url', '/bericht/' + bericht + day);
    loadInfopanel(infoPanel);
}

function loadHalte (el) {
    var infoPanel = document.querySelector('[data-infopanel]');
    var halte = el.getAttribute('data-halte');
    infoPanel.setAttribute('data-infopanel-url', '/haltes/' + halte);
    loadInfopanel(infoPanel);
}

function loadParkeerplaats (el) {
    var infoPanel = document.querySelector('[data-infopanel]');
    var parkeerplaats = el.getAttribute('data-parkeerplaats');
    infoPanel.setAttribute('data-infopanel-url', '/parkeerplaatsen/' + parkeerplaats);
    loadInfopanel(infoPanel);
}

function loadPanoThumbnail (el) {
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

function toggleLanguageSwitch (el) {
    var langSwitch = document.querySelector('[data-lang-switch]');
    langSwitch.classList.toggle('-active');
}

/////////////////////////////////////
// Prevent unformatted date input //
////////////////////////////////////

function preventUnformattedDateInput (el) {
    el.addEventListener('keypress', function(e) {
        e.preventDefault();
    });
}

///////////////////////
// Load availability //
///////////////////////

function loadAvailability (el) {
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

var tbmap = false; // Tourbuzz map
var mapLayers = {};

function run () {

    // Catch all clicks
    document.addEventListener('click', function(e) {
        var el = e.target;
        var controller = el.getAttribute('data-js-click');
        if (controller) {
            e.preventDefault();
            window[controller](el);
        }
    });

    // DOM ready
    document.addEventListener('DOMContentLoaded', function(e) {
        var hooks = document.querySelectorAll('[data-js-ready]');
        for(i = 0; i < hooks.length; i++) {
            controller = hooks[i].getAttribute('data-js-ready');
            if (controller && undefined !== window[controller]) {
                window[controller](hooks[i]);
            } else {
                console.log(controller + " is not available");
            }
        }
    });

    // Refresh on back button
    window.onpopstate = function(e) {
        window.location = document.location;
    };

}

run();