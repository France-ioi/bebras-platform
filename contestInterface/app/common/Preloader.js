function ContestPreloader(params) {

    var ready = false;
    var debug = true;

    function browserCapatible() {
        if (!('serviceWorker' in navigator)) {
            console.warn('serviceWorker not supported');
            return false;
        }
        if (!('MessageChannel' in window)) {
            console.warn('MessageChannel not supported');
            return false;
        }
        return true;
    }

    if(!browserCapatible()) {
        console.warn('contest preloader disabled');
        return false;
    }


    function register() {
        /*
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            var scriptURL = navigator.serviceWorker.controller.scriptURL;
            console.log('serviceWorker.onControllerchange', scriptURL);
        });

        navigator.serviceWorker.ready.then(function(registration) {
            console.log('navigator.serviceWorker.ready', registration)
        });
        */
       debug && console.log('SW install')
        navigator.serviceWorker
            //.register(dir + 'sw.js', { scope: dir })
            .register('sw.js')
            .then(function(reg) {
                debug && console.log('SW registration successful, scope is:', reg.scope);
                if (reg.installing) {
                    debug && console.log('SW preloader installing');
                } else if (reg.waiting) {
                    debug && console.log('SW preloader installed');
                    ready = true;
                } else if (reg.active) {
                    debug && console.log('SW preloader active');
                    ready = true;
                }
            })
            .catch(function(error) {
                console.error('SW registration failed', error);
            });
    }


    function unregister(callback) {
        console.log('SW preloader unregister');
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for (let registration of registrations) {
                //console.log(registration);
                registration.unregister();
            }
            callback && callback();
        });
    }

    if(debug) {
        unregister(register);
    } else {
        register();
    }


    function send(data, callback) {
        var chan = new MessageChannel();
        chan.port1.onmessage = function(event) {
            callback(event.data);
        };
        navigator.serviceWorker.controller.postMessage(
            data,
            [ chan.port2 ]
        );
    }



    return {

        add: function(list, callback) {
            for(var i=0; i<list.length; i++) {
                list[i] = window.contestsRoot + '/' + list[i];
            }
            var data = {
                cmd: 'add',
                list: list
            }
            send(data, function(res) {
                callback(res.success);
            });
        },

        check: function(contest_folder, callback) {
            var path = window.contestsRoot + '/' + contest_folder;
            if(window.config.contestLoaderVersion === '2') {
                path += '.v2/index.json';
            } else {
                path += '/index.txt';
            }

            var data = {
                cmd: 'check',
                path: path
            }
            send(data, function(res) {
                callback(res.exists);
            });
        },
    };
}

var preloader = ContestPreloader({});

export default preloader;