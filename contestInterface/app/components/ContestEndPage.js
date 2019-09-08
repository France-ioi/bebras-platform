import contest from '../contest'

export default {
	init () {
		$('#buttonCloseNew').click(function() {
			contest.tryCloseContest();
		})
	},
	load (data, eventListeners) {
		$("#divClosed").show();
		$('#divClosedReminder').show();
		$('#closedReminderNav').show();
	},
	unload () {
		$("#divClosed").hide();
	},
	updateClosedMessage (html) {
		$("#divClosedMessage").html(html);
	},
	showError (error) {
		$("#contentError").html(error);
		$("#divError").show();
	},
	showFooter () {
		$("#divFooter").show();
	}
};