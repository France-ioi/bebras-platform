var data = {};

function fetch(url, params, callback) {

    var callbacks = {};

    $.getJSON('app/debug/data/' + url + '.json', function(json) {
        data[url] = json;
        if(params.action && !data[url][params.action]) {
            console.error('Fetch debug: ' + url + ' action=' + params.action + ' data not exists.')
        } else {
            callback(params.action ? data[url][params.action] : data[url]);
            callbacks['done'] && callbacks['done']();
        }
        callbacks['always'] && callbacks['always']();
    }).fail(function() {
        console.error('Fetch debug: ' + url + ' related data not found.')
        callbacks['fail'] && callbacks['fail']();
        callbacks['always'] && callbacks['always']();
    });


    return {
        done: function(callback) {
            callbacks['done'] = callback;
        },
        fail: function(callback) {
            callbacks['fail'] = callback;
        },
        always: function(callback) {
            callbacks['always'] = callback;
        }
    }
}

export default fetch;