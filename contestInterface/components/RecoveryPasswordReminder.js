

export default {
	load (data, eventListeners) {
		$("#divClosedRemindPassword").show();
	},
	unload () {
		$("#divClosedRemindPassword").hide();
	},
	updateTeamPassword (teamPassword) {
		$("#remindTeamPassword").html(teamPassword);
	},
	updateTeamScore (ffTeamScore) {
		$("#remindScore").html(ffTeamScore);
	},
	showScoreReminder () {
		$("#scoreReminder").show();
	}
};