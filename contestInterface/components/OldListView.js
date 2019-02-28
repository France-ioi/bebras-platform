

export default {
	load (data, eventListeners) {

	},
	unload () {

	},
	updateScore (key, score, maxScore) {
		$('#score_' + key).html(score + " / " + maxScore);
	},
	updateQuestionList (strListQuestions) {
		$(".questionList").html(strListQuestions);
	},
	updateButtonCloseVisibility (isShow) {
		if (isShow) {
			$(".buttonClose").show();
		} else {
			$(".buttonClose").hide();
		}
	},
	updateQuestionPoints (html) {
		$("#questionPoints").html(html);
	}
};