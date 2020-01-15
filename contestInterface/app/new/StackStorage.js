export default function(name) {

    var data;

    try {
        var data = window.localStorage.getItem(name);
        data = JSON.parse(data);
    } catch(e) {}

    if(!Array.isArray(data)) {
        data = []
    }


    function sync() {
        window.localStorage.setItem(name, JSON.stringify(data));
    }


    function clone(value) {
        return JSON.parse(JSON.stringify(value))
    }

    return {

        push: function(value) {
            data.push(clone(value));
            sync();
        },

        pop: function() {
            var res = data.pop();
            sync();
            return res;
        },

        exist: function(value) {
            return data.includes(value);
        },

        all: function() {
            return data;
        },

        clear: function() {
            data = [];
            sync();
        }
    }
}