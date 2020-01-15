import fetch from '../common/Fetch'


function defferedFetch(url, params, callback) {
    //TODO
    return $.post(url, params, callback, 'json');

    var callbacks = {};

    return {
        done: function(callback) {
            callbacks.done = callback;
        },
        fail: function(callback) {
            callbacks.fail = callback;
        }
    }
}



export default defferedFetch;