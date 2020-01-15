export default function(name) {

    var data;

    try {
        var data = window.localStorage.getItem(name);
        data = JSON.parse(data);
    } catch(e) {}

    if(!data || typeof data !== 'object') {
        data = {}
    }


    function sync() {
        window.localStorage.setItem(name, JSON.stringify(data));
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value))
    }


    return {

        get: function(key) {
            if(key in data) {
                return data[key];
            }
            return undefined;
        },

        put: function(key, value) {
            data[key] = clone(value);
            sync();
        },

        exist: function(key) {
            return key in data;
        },

        clear: function() {
            data = {};
            sync();
        }
    }
}