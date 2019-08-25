export default {

    load (data, eventListeners) {
        $("#divGroupConfirmation").show();
        $("#divGroupConfirmation button").on('click', this.onButtonClick.bind(this));
	},

    unload () {
        $("#divGroupConfirmation").hide();
        $("#divGroupConfirmation").unbind('click');
    },

    show (data, callback) {
        $('#group_confirmation_group').html(data.group);
        $('#group_confirmation_grade').html(i18n.t("grade_" + data.grade));
        $('#group_confirmation_contest').html(data.contest);
        $('#group_confirmation_time').html(data.expectedStartTime);
        this.callback = callback;
        this.load();
    },

    onButtonClick (e) {
        this.unload();
        var action = $(e.target).data('callback');
        this.callback(action == 'yes');
    }

}