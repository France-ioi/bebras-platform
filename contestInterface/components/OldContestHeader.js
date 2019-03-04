

export default {
	load (data, eventListeners) {
		$(".oldInterface").show();
	},
	unload () {
		$(".oldInterface").hide();
	},
	updateBonusScore (score) {
		$(".scoreBonus").html($(".scoreBonus").html().replace('50', score));
		$(".scoreBonus").show();
	},
	updateCssLoadSolutions () {
		$(".questionListHeader").css("width", "265px");
		$("#divQuestionParams, .questionsTable").css("left", "272px");
	},
	updateCssFullfeedback () {
		$(".questionListHeader").css("width", "240px");
	},
	updateBulletAndScores (questionKey, image, score, maxScore) {
		$("#bullet_" + questionKey).html(image);
		$("#score_" + questionKey).html("<b>" + score + "</b> / " + maxScore);
	},
	updateTeamScore (teamScore, maxTeamScore) {
		$(".chrono").html("<tr><td style='font-size:28px'> " + i18n.t("score") + ' ' + teamScore + " / " + maxTeamScore + "</td></tr>");
        $(".chrono").css("background-color", "#F66");
	},
	updateFeedbackVisibility (isShow) {
		if (isShow) {
			$('.chrono').css('font-size', '1.3em');
            $('.fullFeedback').show();
		} else {
			$('.fullFeedback').hide();
		}
	}
};