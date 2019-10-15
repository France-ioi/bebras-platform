var version = 'v1';
var dir = '/contests/'

self.addEventListener('install', function(event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
    event.waitUntil(clients.claim());
});


// cache

function fromCache(request) {
    return caches.open(version).then((cache) =>
        cache.match(request).then((matching) =>
            matching || fetch(request)
        ));
}

function update(request) {
    return caches.open(version).then((cache) =>
        fetch(request).then((response) =>
            cache.put(request, response)
        )
    );
}

self.addEventListener('fetch', function(event) {
    if(event.request.url.includes(dir)) {
        event.respondWith(fromCache(event.request));
        //event.waitUntil(update(event.request));
    } else {
        event.respondWith(fetch(event.request));
    }
});



// interface

self.addEventListener('message', function(event) {

    switch(event.data.cmd) {

        case 'add':
            caches
                .open(version)
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
            caches
                .open(version)
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
