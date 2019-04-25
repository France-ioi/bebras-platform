

export default {
	init () {
		/**
		 * Update the number of preloaded images
		 * Called by the task
		 *
		 * @param {string} content
		 */
		window.setNbImagesLoaded = this.updateImagesLoaded.bind(this);
	},
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