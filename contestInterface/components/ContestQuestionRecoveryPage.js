

export default {
	load (data, eventListeners) {
		$("#divClosedEncodedAnswers").show();
	},
	unload () {
		$("#divClosedEncodedAnswers").hide();
		$("#encodedAnswers").html('');
	},
	updateEncodedAnswers (encodedAnswers) {
		$("#encodedAnswers").html(encodedAnswers);
	}
};