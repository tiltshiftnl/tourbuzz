var paths = [
    '/img/buzzlogo-w.png',
    '/img/tour-buzz.png',
    '/img/taal/nl.png',
    '/img/taal/de.png',
    '/img/taal/en.png',
    '/img/down-w.png',
    '/img/markers/1.png',
    '/img/markers/2.png',
    '/img/markers/3.png',
    '/img/markers/4.png',
    '/img/markers/5.png',
    '/img/markers/6.png',
    '/img/markers/7.png',
    '/img/markers/8.png',
    '/img/markers/9.png',
    '/img/markers/10.png',
    '/img/markers/11.png',
    '/img/markers/12.png',
    '/img/markers/13.png',
    '/img/markers/14.png',
    '/img/markers/15.png',
    '/offline',
    '/css/scss/main.scss'
];

var refreshed = 0;

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open('offline')
            .then(function(cache) {
                return cache.addAll(paths);
            })
    );

    refreshed = Date.now();
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('fetch', function(event) {
    var requestURL = new URL(event.request.url);

    /**
     * Wanneer de pagina niet in de cache array zit, kijken of deze online beschikbaar is.
     * Is deze niet online beschikbaar de fallback pagina teruggeven
     */
    if (-1 == paths.indexOf(requestURL.pathname)) {
        event.respondWith(
            fetch(event.request).catch(function() {
                return caches.match('/offline');
            })
        );
        var now = Date.now();
        if (refreshed+60000 < now) {
            caches.open('offline').then(function(cache) {
                cache.delete('/offline');
                cache.add('/offline');
            });
            console.log('Refreshed offline');
            refreshed = Date.now();
        }
        return;
    }

    /**
     * Kijk of het onderdeel al gecached is, zo niet haal het op van het internet
     */
    event.respondWith(
        caches.match(event.request).then(function(response) {
            return response || fetch(event.request);
        })
    );
});