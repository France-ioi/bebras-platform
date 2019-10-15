function ContestPreloader(params) {

    var dir = window.location.pathname || '/';
    var ready = false;
    var debug = false;

    function browserCapatible() {
        if (!('serviceWorker' in navigator)) {
            console.error('serviceWorker not supported');
            return false;
        }
        if (!('MessageChannel' in window)) {
            console.error('MessageChannel not supported');
            return false;
        }
        return true;
    }

    if(!browserCapatible()) {
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
        navigator.serviceWorker
            .register(dir + 'sw.js', { scope: dir })
            .then(function(reg) {
                if (reg.installing) {
                    debug && console.log('preloader installing');
                } else if (reg.waiting) {
                    debug && console.log('preloader installed');
                    ready = true;
                } else if (reg.active) {
                    debug && console.log('preloader active');
                    ready = true;
                }
            })
            .catch(function(error) {
                console.error('Service worker registration failed', error);
            });
    }


    function unregister(callback) {
        console.log('preloader unregister');
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



    return {


        send: function(data, callback) {
            var chan = new MessageChannel();
            chan.port1.onmessage = function(event) {
                callback(event.data);
            };
            navigator.serviceWorker.controller.postMessage(data, [ chan.port2 ]);
        },

        add: function(list, callback) {
            for(var i=0; i<list.length; i++) {
                list[i] = dir + 'contests/' + list[i];
            }
            var data = {
                cmd: 'add',
                list: list
            }
            this.send(data, function(res) {
                callback(res.success);
            });
        },

        check: function(contest_folder, callback) {
            var data = {
                cmd: 'check',
                path: dir + 'contests/' + contest_folder + '/index.txt'
            }
            this.send(data, function(res) {
                callback(res.exists);
            });
        },
    };
}

export default ContestPreloader;