

export default {
	load (data, eventListeners) {
		$("#divClosedPleaseWait").show();
	},
	unload () {
		$("#divClosedPleaseWait").hide();
	}
};