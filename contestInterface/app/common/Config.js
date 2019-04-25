import url from './ParseURL';

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

    $.post(
        "data.php",
        { action: "getConfig", p: url.getParameterByName("p") },
        function(data) {
            window.config = data.config;
            if (callback) {
                callback();
            }
        },
        "json"
    );
}

export default {
    get
}