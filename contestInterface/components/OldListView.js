

export default {
	load (data, eventListeners) {

	},
	unload () {

	},
	updateScore (key, score, maxScore) {
		$('#score_' + key).html(score + " / " + maxScore);
	},
	updateQuestionList (sortedQuestionIDs, questionsDataAll, fullFeedback, scores) {
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