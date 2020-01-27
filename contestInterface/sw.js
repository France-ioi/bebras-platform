var config = {
    version: 'v1',
    path: '/'
}

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches
            .open(config.version)
            .then((cache) => cache.addAll(['/']))
    );


    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
    event.waitUntil(clients.claim());
});




function cacheThenNetwork(cache, event) {
    return caches.match(event.request).then(response => {
        function fetchAndCache () {
           return fetch(event.request).then(response => {
                cache.put(event.request, response.clone());
                return response;
            });
        }
        if (!response) {
            return fetchAndCache();
        }
        fetchAndCache();
        return response;
    });
}

self.addEventListener('fetch', function(event) {
    if(event.request.method !== 'GET') {
        return;
    }
    if(event.request.cache === 'only-if-cached' && event.request.mode !== 'same-origin') {
        return;
    }
    event.respondWith(
        caches.open(config.version).then((cache) => cacheThenNetwork(cache, event))
    );
});



// interface

self.addEventListener('message', function(event) {

    switch(event.data.cmd) {

        case 'add':
            //console.log('SW add: ', event.data.list)
            caches
                .open(config.version)
                .then(function(cache) {
                    return cache.addAll(event.data.list);
                })
                .then(function() {
                    event.ports[0].postMessage({
                        success: true
                    });
                })
                .catch(function(e) {
                    event.ports[0].postMessage({
                        success: false
                    });
                })
            break;

        case 'check':
            //console.log('SW check: ', event.data.path)
            caches
                .open(config.version)
                .then(function(cache) {
                    return cache.match(event.data.path);
                })
                .then(function(res) {
                    event.ports[0].postMessage({
                        exists: !!res
                    });
                })
            break;
    }

});
