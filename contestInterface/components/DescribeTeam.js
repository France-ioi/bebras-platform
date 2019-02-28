export default {
	load (data, eventListeners) {
		$("#divDescribeTeam").show();
	},
	unload () {
		$("#divDescribeTeam").hide();
	},
	unselect () {
		$(".nbContestants").removeClass('selected');
	},
	updateCheckNbContestantsVisibility (isShow) {
		if (isShow) {
			$('#divCheckNbContestants').show();
		} else {
			$('#divCheckNbContestants').hide();
		}
	}
};