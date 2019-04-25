var Tracker = {
    disabled: true,

    trackData: function(data) {
        if (this.disabled) {
            return;
        }
        if ($("#trackingFrame").length > 0) {
            $.postMessage(
                JSON.stringify(data),
                "http://eval02.france-ioi.org/castor_tracking/index.html",
                $("#trackingFrame")[0].contentWindow
            );
        }
    }
}


export default Tracker;