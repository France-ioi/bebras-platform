

export default {
	load (data, eventListeners) {
		$("#divClosed").show();
	},
	unload () {
		$("#divClosed").hide();
	},
	updateClosedMessage (html) {
		$("#divClosedMessage").html(html);
	},
	showError (error) {
		$("#contentError").html(error);
		$("#divError").show();
	}
};