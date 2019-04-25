import UI from '../components';
import Utils from '../common/Utils';
import contest from '../contest';
import group from '../group';

export default {
	init () {
		/*
		 * Called when trying to continue a contest after an interruption
		 * The password can either be a group password (leading to another page)
		 * or directly a team password (to re-login directly)
		*/
		window.checkPasswordInterrupted = this.checkPasswordInterrupted.bind(this);
		/*
		* Called when students select their team in the list of teams of their group,
		* and the teacher enters the group password (to continue after an interruption)
		* Tries to load the corresponding contest.
		*/
		window.relogin = this.relogin.bind(this);
	},
	load (data, eventListeners) {

	},
	unload () {
		$("#selectTeam").val('');
		$("#groupPassword").val('');
		$("#interruptedPassword").val('');
	},
	relogin () {
		const teamID = this.getSelectTeam();
		app.teamID = teamID;
		const groupPassword = this.getGroupPassword();
		if (teamID == '0') {
			this.updateReloginResult(i18n.t("select_team"));
			return;
		}
		Utils.disableButton("buttonRelogin");
		UI.TrainingContestSelection.unload();
		contest.loadContestData(null, null, groupPassword);
	},
	checkPasswordInterrupted () {
		const password = this.getInterruptedPassword()
		return group.checkGroupFromCode("Interrupted", password, true, false);
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
	fillListTeams (teams) {
		$("#selectTeam").html("<option value='0'>" + i18n.t("tab_view_select_team"));
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