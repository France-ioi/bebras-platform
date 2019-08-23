import UI from './index';

export default {
	init () {
		window.selectMainTab = this.selectMainTab.bind(this);
	},
	load (data, eventListeners) {
		UI.HomePage.load();
	},
	unload () {
		UI.HomePage.unload();
	},
	selectMainTab (tabName) {
		alert(tabName)
		if (tabName == 'home') {
			$("#publicContestExplanation").html(i18n.t("tab_public_contests_score_explanation"));
			$("#contentPublicGroups").show();
		}
		var tabNames = ["school", "home", "continue", "contests"];
		for (var iTab = 0; iTab < tabNames.length; iTab++) {
			if (tabNames[iTab] === tabName) {
				$("#tab-" + tabNames[iTab]).show();
				$("#button-" + tabNames[iTab]).addClass("selected");
			} else {
				$("#tab-" + tabNames[iTab]).hide();
				$("#button-" + tabNames[iTab]).removeClass("selected");
			}
		}
	},
	updateCurStepResult (curStep, html) {
		$("#" + curStep + "Result").html(html);
	},
	hideBrowserAlert () {
		$('#browserAlert').hide();
	}

};