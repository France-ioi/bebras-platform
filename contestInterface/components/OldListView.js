

export default {
	load (data, eventListeners) {

	},
	unload () {

	},
	updateScore (key, score, maxScore) {
		$('#score_' + key).html(score + " / " + maxScore);
	},
	log_fn (text) {
		$(".questionList").html("<span style='font-size:2em;padding-left:10px'>" + text + "</span>");
	},
	fillListQuestionsNew (sortedQuestionIDs, questionsData, contestsRoot, contestFolder) {
		var strListQuestions = "";
		var iQuestionID, questionData;
		for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
			questionData = questionsData[sortedQuestionIDs[iQuestionID]];
			var encodedName = questionData.name.replace("'", "&rsquo;").split("[")[0];

			strListQuestions +=
				"<span id='row_" + questionData.key + "' class='icon' onclick='selectQuestion(\"" + questionData.ID + "\", true)'>" +
				'<div class="icon_title"><span class="questionBullet" id="bullet_' + questionData.key + '"></span>&nbsp;' + encodedName + '&nbsp;&nbsp;</div>' +
				'<div class="icon_img">' +
				'<table>' +
				'<tr>' +
				'<td class="icon_img_td" style="vertical-align: middle;">' +
				'<img src="' + contestsRoot + '/' + contestFolder + '/' + questionData.key + '/icon.png" />' +
				'</td>' +
				'</tr>' +
				'</table>' +
				'</div>' +
				'<div class="questionScore" style="margin:auto" id="score_' + questionData.key + '"></div>' +
				'</span>' +
				'<span id="place_' + questionData.key + '" class="icon">' +
				'<div class="icon_title" style="color:gray">' + i18n.t("question_locked") + '</div>' +
				'<div class="icon_img">' +
				'<table>' +
				'<tr>' +
				'<td class="icon_img_td" style="vertical-align: middle;">' +
				'<img src="images/locked_task.png" />' +
				'</td>' +
				'</tr>' +
				'</table>' +
				'</div>' +
				'</span>';
		}
		$(".questionList").html(strListQuestions);
	},
	fillListQuestions (sortedQuestionIDs, questionsDataAll, fullFeedback, scores) {
		var strListQuestions = "";
		for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
			var questionID = sortedQuestionIDs[iQuestionID];
			var questionData = questionsDataAll[questionID];
			var encodedName = questionData.name.replace("'", "&rsquo;").split("[")[0];

			var strScore = "";
			if (fullFeedback) {
				if (scores[questionData.key] !== undefined) {
					strScore = scores[questionData.key].score + " / " + questionData.maxScore;
				} else {
					strScore = questionData.noAnswerScore + " / " + questionData.maxScore;
				}
			}
			strListQuestions += "<tr id='row_" + questionData.key + "'><td class='questionBullet' id='bullet_" + questionData.key + "'></td>" +
				"<td class='questionLink' id='link_" + questionData.key + "' " + "onclick='selectQuestion(\"" + questionData.ID + "\", true)'>" +
				encodedName +
				"</td>" +
				"<td class='questionScore' id='score_" + questionData.key + "'>" +
				strScore +
				"</td></tr>";

		}
		$(".questionList").html("<table>" + strListQuestions + "</table>");
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