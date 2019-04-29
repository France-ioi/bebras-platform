if(__DEBUG__) {
    module.exports = require('../debug/Fetch.js')
} else {
    module.exports = function(url, params, callback) {
        return $.post(url, params, callback, 'json');
    }
}