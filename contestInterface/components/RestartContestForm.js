

export default {
	load (data, eventListeners) {

	},
	unload () {

	},
	updateReloginResult (html) {
		$("#ReloginResult").html(html);
	},
	updateDivReloginVisibility (isShow) {
		if (isShow) {
			$("#divRelogin").show();
		} else {
			$("#divRelogin").hide();
		}
	},
	updateSelectTeam (html, isAppend=false) {
		if (!isAppend) {
			$("#selectTeam").html(html);
		} else {
			$("#selectTeam").append(html);
		}
	},
	confirmUnsupportedBrowser () {
		$("#submitParticipationCode").removeClass('needBrowserConfirm');
	},
	getGroupCode () {
		return $("#groupCode").val();
	},
	getSelectTeam () {
		return $("#selectTeam").val();
	},
	getGroupPassword () {
		return $("#groupPassword").val();
	},
	getInterruptedPassword () {
		return $("#interruptedPassword").val();
	}
};