var versions = 2;

var paths = [
    '/img/buzzlogo-w.png',
    '/img/tour-buzz.png',
    '/img/taal/nl.png',
    '/img/taal/de.png',
    '/img/taal/en.png',
    '/img/down-w.png',
    '/offline',
    '/css/scss/main.scss',
    '/js/jquery-2.2.4.min.js'
];

var refreshed = 0;

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open('offline')
            .then(function(cache) {

                cache.keys().then(function(keys) {
                    keys.forEach(function(request, index, array) {
                        cache.delete(request);
                    });
                });

                return cache.addAll(paths);
            })
    );

    refreshed = Date.now();
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('fetch', function(event) {
    if ('GET' != event.request.method) {
        return;
    }

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