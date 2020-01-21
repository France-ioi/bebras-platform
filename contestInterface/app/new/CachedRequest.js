import fetch from '../common/Fetch'
import KeyValueStorage from './KeyValueStorage'


var makeCachedRequest = function(name) {

    var storage = KeyValueStorage(name)

    return {
        send: function(params, callback) {
            if('commonJsTimestamp' in params) {
                delete params.commonJsTimestamp;
            }
            if('SID' in params) {
                delete params.commonJsTimestamp;
            }

            var key = JSON.stringify(params);

            if(storage.exist(key)) {
                //return callback(storage.get(key));
            }

            fetch('data.php', params, function(data) {
                storage.put(key, data);
                return callback(data);
            });
        },

        clear: function() {
            storage.clear();
        }
    }

}


var requests = {}

export default function(name) {
    if(!(name in requests)) {
        requests[name] = makeCachedRequest(name)
    }
    return requests[name];
}