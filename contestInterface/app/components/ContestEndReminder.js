import Session from '../common/Session'
import PersonalData from './PersonalData'

export default {

	init () {
		window.navPersonalPage = this.navPersonalPage.bind(this);
		window.navLogout = this.navLogout.bind(this);
	},

	load (data, eventListeners) {
		$("#divClosedRemindPassword").show();
	},

	unload () {
		$("#divClosedRemindPassword").hide();
	},

	updateTeamPassword (teamPassword) {
		$("closedReminderPassword").show();
		$("closedReminderNav").hide();
		$("#remindTeamPassword").html(teamPassword);
	},

	showNav () {
		$("closedReminderPassword").hide();
		$("closedReminderNav").show();
	},

	updateTeamScore (ffTeamScore) {
		$("#remindScore").html(ffTeamScore);
	},

	showScoreReminder () {
		$("#scoreReminder").show();
	},

	navPersonalPage () {
		this.unload();
		PersonalData.load();
	},

	navLogout () {
		Session.destroy(function() {
			location.href = location.href;
		})
	}
};