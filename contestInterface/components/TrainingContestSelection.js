

export default {
	load (data, eventListeners) {
		$("#divCheckGroup").show();
	},
	unload () {
		$("#divCheckGroup").hide();
	},
	showPublicGroups (t) {
		$("#publicContestExplanation").html(t("tab_public_contests_score_explanation"));
		//loadPublicGroups(); We don't use this feature anymore, we create this page manually.
		$("#loadPublicGroups").hide();
		$("#contentPublicGroups").show();
	},
	showHideTab (tabName, isShow) {
		if (isShow) {
			$("#tab-" + tabName).show();
			$("#button-" + tabName).addClass("selected");
		} else {
			$("#tab-" + tabName).hide();
			$("#button-" + tabName).removeClass("selected");
		}
	},
	confirmPublicGroup () {
		$("#warningPublicGroups").hide();
		$("#publicGroups").show();
	},

};