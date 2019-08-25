import { saveAs } from 'file-saver';

export default {
	init() {
		var isFileSaverSupported = false;
		try {
			isFileSaverSupported = !!new Blob;
		} catch (e) {}
		$("#encodedAnswersDownload").toggle(isFileSaverSupported);
		window.saveEncodedAnswers = this.saveEncodedAnswers.bind(this);
	},

	load (data, eventListeners) {
		$("#divClosedEncodedAnswers").show();
	},

	unload () {
		$("#divClosedEncodedAnswers").hide();
		$("#encodedAnswers").html('');
	},

	updateEncodedAnswers (encodedAnswers) {
		$("#encodedAnswers").html(encodedAnswers);
	},

	saveEncodedAnswers () {
		var blob = new Blob($("#encodedAnswers").html(), {type: "text/plain;charset=utf-8"});
		var postfix = "";
		if(app.user) {
			postfix =
				"_" +
				$.trim(app.user.firstName).replace(/ /g,"_") +
				"_" +
				$.trim(app.user.lastName).replace(/ /g,"_");
		}
		saveAs(blob, "answers" + postfix + ".dat");
	}
};