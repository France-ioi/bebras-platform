

export default {
	load (data, eventListeners) {
		$("#recoverGroup").show();
	},
	unload () {
		$("#recoverGroup").hide();
	},
	getGroupPass () {
		return $('#recoverGroupPass').val();
	},
	updateRecoverGroupResult (html='') {
		$('#recoverGroupResult').html(html);
	}
};