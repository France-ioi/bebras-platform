

export default {
	load (data, eventListeners) {
		$(".newInterface").show();
	},
	unload () {
		$(".newInterface").html("").hide();
	},
	resetHeaderTime () {
		$('#header_time').html('');
	},
	updateButtonReturnListEnability (isEnabled) {
		$(".button_return_list").prop("disabled", isEnabled);
	},
	updateScoreTotalFullFeedback (html) {
		$(".scoreTotalFullFeedback").html(html);
	},
	hideHeaderButtonFullscreen () {
		$('.header_button_fullscreen').hide();
	}
};