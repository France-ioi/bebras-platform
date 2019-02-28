

export default {
	load (data, eventListeners) {
		$("#divLogin").show();
	},
	unload () {
		$("#divLogin").hide();
	},
	init (contestant) {
		$("#firstName" + contestant).val("");
		$("#lastName" + contestant).val("");
		$("#genre" + contestant + "_female").attr('checked', null);
		$("#genre" + contestant + "_male").attr('checked', null);
	},
	setNbContestants (nbContestants) {
		if (nbContestants === 2) {
			$("#contestant2").show();
		}
		if (nbContestants !== 2) {
			$("#contestant2").hide();
		}
	},
	getTeamMateRegCode (teamMate) {
		return $("#registrationCode" + teamMate).val().trim().toLowerCase();
	},
	hideAskRegCode () {
		$("#askRegistrationCode1").hide();
		$("#askRegistrationCode2").hide();
	},
	updateLoginFieldVisibility (loginFieldName, isShow) {
		if (isShow) {
			$('#login-input-' + loginFieldName + '-1').show();
			$('#login-input-' + loginFieldName + '-2').show();
		} else {
			$('#login-input-' + loginFieldName + '-1').hide();
			$('#login-input-' + loginFieldName + '-2').hide();
		}
	},
	updateLoginResult (html) {
		$("#LoginResult").html(html);
	},
	updateRegisterTeamMate (teamMate, hasReg, lock) {
		$("#hasReg" + teamMate + "Yes").removeClass("selected");
		$("#hasReg" + teamMate + "No").removeClass("selected");
		$("#yesRegistrationCode" + teamMate).hide();
		$("#noRegistrationCode" + teamMate).hide();
		if (hasReg) {
			$("#hasReg" + teamMate + "Yes").addClass("selected");
			$("#yesRegistrationCode" + teamMate).show();
			if (lock) {
				$("#hasReg" + teamMate + "No").attr("disabled", "disabled");
				$("#registrationCode" + teamMate).attr("readonly", "readonly");
				$("#validateRegCode" + teamMate).hide();
			}
		} else {
			$("#hasReg" + teamMate + "No").addClass("selected");
			$("#noRegistrationCode" + teamMate).show();
		}
	},
	updateErrorRegCodeForTeamMate (teamMate, html) {
		$("#errorRegistrationCode" + teamMate).html(html);
	},
	updateRegCodeForTeamMate (teamMate, val) {
		$("#registrationCode" + teamMate).val(val);
	},
};