/**
 * Add or remove the meta viewport tag
 *
 * @param {bool} toggle
 */

function toggle(toggle) {
    if (toggle) {
        if ($("meta[name=viewport]").length) {
            return;
        }
        // Add
        var metaViewport = document.createElement("meta");
        metaViewport.name = "viewport";
        metaViewport.content =
            "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no";
        document.getElementsByTagName("head")[0].appendChild(metaViewport);
    } else {
        // Remove
        $("meta[name=viewport]").remove();
    }
};

export default {
    toggle
};