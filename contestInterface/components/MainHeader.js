

export default {
	load (data, eventListeners) {
		$("#divHeader").show();
	},

	unload () {
		$("#divHeader").hide();
	},

	updateTitle (title) {
		$('#headerH1').html(title);
		//$('title').html(contestName); doesn't work on old IEs
	},

	updateSubTitle (title) {
		$("#headerH2").html(title);
	}
};