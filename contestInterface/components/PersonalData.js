export default {
	load (data, eventListeners) {
		$("#divPersonalPage").show();
		if (data) {
			$("#persoLastName").html(data.registrationData.lastName);
			$("#persoFirstName").html(data.registrationData.firstName);
			$("#persoGrade").html(data.persoGrade);
			$("#persoCategory").html(data.registrationData.qualifiedCategory);
			if (data.registrationData.allowContestAtHome == "0") {
				$("#buttonStartContest").attr("disabled", "disabled");
				$("#contestAtHomePrevented").show();
			}
		}
	},
	unload () {
		$("#divPersonalPage").hide();
	},
	updatePastParticipations (html) {
		$("#pastParticipations").append(html);
	},
	updateTeamPassword (html) {
		$("#teamPassword").html(html);
	},
	updateVisibilityPassword (isShow) {
		if (isShow) {
			$("#divPassword").show();
		} else {
			$("#divPassword").hide();
		}
	}
}