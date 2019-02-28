

export default {
	load (data, eventListeners) {
		$(".questionListIntro").show();
		$(".questionList").show();
	},
	unload () {
		$(".questionListIntro").hide();
		$(".questionList").hide();
	},
	unlockAllLevels (questionKey, isLock = false) {
		if (isLock) {
			$("#row_" + questionKey).hide();
			$("#place_" + questionKey).show();
		} else {
			$("#place_" + questionKey).hide();
			$("#row_" + questionKey).show();
		}
	},
	updateQuestionListIntro (html) {
		$("#questionListIntro").html(html);
	},
	updateQuestionContent (html) {
		$("#divQuestionsContent").html(html);
	},
	getGradersContent (data) {
		if (data.graders) {
			$('#divGradersContent').html(data.graders);
		 } else {
			$('#divGradersContent').load(data.gradersUrl);
		 }
	},
	updateGradersContent (html) {
		$('#divGradersContent').html(html);
	},
	updateBullet (questionKey, html) {
		$("#bullet_" + questionKey).html(html);
	},
	updateSolutionsContent (html) {
		$('#divSolutionsContent').html(html);
	}

};