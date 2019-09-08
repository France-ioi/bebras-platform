

export default {

    load (data, eventListeners) {
        $("#divStartContestMenu").show();
	},

    unload () {
        $("#divStartContestMenu").hide();
    },

    show (options, callback) {
        var self = this;
        var el = $('#startContestMenuContent');
        el.empty();
        for(var i=0; i<options.length; i++) {
            el.append('<button type="button" class="btn btn-default" data-idx="' + i + '">' + options[i].text + '</button><br>');
        }
        el.append('<br><button type="button" class="btn btn-default" data-idx="cancel">' + i18n.t('cancel') + '</button>');

        el.find('button').click(function(e) {
            self.unload();
            var idx = $(e.target).data('idx');
            if(options[idx]) {
                callback && callback(options[idx]);
            }
        });

        this.load();
    }

}