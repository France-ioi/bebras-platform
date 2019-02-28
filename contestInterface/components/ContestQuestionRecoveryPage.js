

export default {
	load (data, eventListeners) {
		$("#divClosedEncodedAnswers").show();
	},
	unload () {
		$("#divClosedEncodedAnswers").hide();
	},
	updateEncodedAnswers (encodedAnswers) {
		$("#encodedAnswers").html(encodedAnswers);
	}
};