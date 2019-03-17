

export default {
	load (data, eventListeners) {
		$(".questionListIntro").show();
		$(".questionList").show();
	},
	unload () {
		$(".questionListIntro").hide();
		$(".questionList").hide();
	},
	unlockAllLevels (getSortedQuestionIDs, questionsData, questionUnlockedLevels) {
		var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
		for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
			var questionKey = questionsData[sortedQuestionIDs[iQuestionID]].key;
			questionUnlockedLevels[questionKey] = 4;
			this.unlockLevel(questionKey, false);
		}
	},
	unlockLevel (questionKey, isLock = false) {

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
	updateGradersContent (data) {
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
	},
	updateCss () {
		$(".questionScore").css("width", "50px");
		$(".question, #divClosed").css("left", "272px");
	},
	updateNextStep (questionKey, currentQuestionKey) {
		$("#question-" + currentQuestionKey).hide();
		$("#question-" + questionKey).show();
		$("#link_" + currentQuestionKey).attr("class", "questionLink");
		$("#link_" + questionKey).attr("class", "questionLinkSelected");
	},
	appendQuestionData (key) {
		$("#question-" + key).append("<hr>" + $("#solution-" + key).html());
	},
	updateNGetCustomIntro (customIntro) {
		return $("<textarea/>").html(customIntro).text();
	}
};