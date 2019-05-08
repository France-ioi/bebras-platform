import url from './ParseURL';
import fetch from './Fetch';

/**
 * Fetch configuration
 */
function get(callback) {
    if (window.config) {
        if (callback) {
            callback();
        }
        return;
    }

    fetch(
        "data.php",
        { controller: "Config", action: "get", p: url.getParameterByName("p") },
        function(data) {
            window.config = data.config;
            if (callback) {
                callback();
            }
        }
    );
}

export default {
    get
}