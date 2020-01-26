import StackStorage from './StackStorage';


var storage = StackStorage('dataRequest');
var online = true;


function post(data, onSuccess, onTimeout) {
    return $.ajax({
        url: 'data.php',
        timeout: window.config.requestTimeout,
        data: data,
        method: 'POST'
    }).done(function(res) {
        onSuccess && onSuccess(res);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        if(textStatus === 'timeout') {
            onTimeout(data);
        } else {
            console.error('Server error', data, textStatus, errorThrown);
        }
    });​
}


function getData(data) {
    if(!storage.size()) {
        return data;
    }
    console.log('Deffered request storage size: ', storage.size())
    return {
        requestType: 'deffered',
        data: ([data]).concat(storage.all())
    }
}


export default {
    post: function(data, callback) {
        post(
            data,
            function(data) {
                online = true;
                callback(data);
            },
            function(data) {
                storage.push(data);
                online = false;
            }
        );
    },

    online: function() {
        return online;
    }
};