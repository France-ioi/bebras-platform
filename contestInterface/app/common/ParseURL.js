export default {

    getParameterByName: function(name) {
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null
            ? ""
            : decodeURIComponent(results[1].replace(/\+/g, " "));
    },

    // Obtain an association array describing the parameters passed to page
    getPageParameters: function() {
        var str = window.location.search.substr(1);
        var params = {};
        if (str) {
            var items = str.split("&");
            for (var idItem = 0; idItem < items.length; idItem++) {
                var tmp = items[idItem].split("=");
                params[tmp[0]] = decodeURIComponent(tmp[1]);
            }
        }
        return params;
    }

}