

export default {
	load (data, eventListeners) {
		$("#divImagesLoading").show();
	},
	unload () {
		$("#divImagesLoading").hide();
	},
	updateImagesLoaded (content) {
		$("#nbImagesLoaded").html(content);
	},

};