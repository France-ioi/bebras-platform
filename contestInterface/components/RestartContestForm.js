

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
	fillListTeams (teams, t) {
		$("#selectTeam").html("<option value='0'>" + t("tab_view_select_team"));
		for (var curTeamID in teams) {
			var team = teams[curTeamID];
			var teamName = "";
			for (var iContestant in team.contestants) {
			   var contestant = team.contestants[iContestant];
			   if (iContestant == 1) {
				  teamName += " et "; // XXX: translate
			   }
			   teamName += contestant.firstName + " " + contestant.lastName;
			}
			$("#selectTeam").append("<option value='" + curTeamID + "'>" + teamName + "</option>");
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