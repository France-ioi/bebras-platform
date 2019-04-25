import UI from '../components';
import metaViewport from '../common/MetaViewport';

export default {
	fullscreenActive: false,
	fullscreenEvents: false,
	events: [],

	init () {
		window.backToList = this.backToList.bind(this);
		window.toggleFullscreen = this.toggleFullscreen.bind(this);
	},
	load (data, eventListeners) {
		$(".newInterface").show();
	},
	unload () {
		$(".newInterface").html("").hide();
		for(var i=0; i<this.events.length; i++) {
			document.removeEventListener(this.events[i], this.updateFullscreen);
		}
		this.events = [];
	},
	checkFullscreen () {
		// Checks whether fullscreen is available, else hides the button
		var el = document.documentElement;
		var available = false;
		try {
			available = el.requestFullscreen || el.mozRequestFullScreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
		} catch (e) {}
		if (!available) {
			this.hideHeaderButtonFullscreen();
		}
	},
	toggleFullscreen () {
		if (!this.fullscreenEvents) {
			// Register events to update fullscreen state
			var types = [
				'fullscreenchange',
				'webkitfullscreenchange',
				'mozfullscreenchange',
				'MSFullscreenChange'
			]
			for(var t in types) {
				document.addEventListener(t, this.updateFullscreen);
				this.events.push(t);
			}
			this.fullscreenEvents = true;
		}

		if (this.fullscreenActive) {
			// Exit fullscreen
			var el = document;
			if (el.exitFullscreen) {
				el.exitFullscreen();
			} else if (el.mozCancelFullScreen) {
				el.mozCancelFullScreen();
			} else if (el.webkitExitFullscreen) {
				el.webkitExitFullscreen();
			} else if (el.msExitFullscreen) {
				el.msExitFullscreen();
			}
			this.fullscreenActive = false;
		} else {
			var el = document.documentElement;
			if (el.requestFullscreen) {
				el.requestFullscreen();
			} else if (el.mozRequestFullScreen) {
				el.mozRequestFullScreen();
			} else if (el.webkitRequestFullscreen) {
				el.webkitRequestFullscreen();
			} else if (el.msRequestFullscreen) {
				el.msRequestFullscreen();
			}
			this.fullscreenActive = true;
		}
	},
	updateFullscreen () {
		// Update fullscreen state when receiving event
		if (document.fullscreenElement || document.msFullscreenElement || document.mozFullScreen || document.webkitIsFullScreen) {
			this.fullscreenActive = true;
		} else {
			this.fullscreenActive = false;
		}
	},
	backToList (initial) {
		$('body').removeClass('autoHeight');
		metaViewport.toggle(false);
		UI.GridView.load();
		UI.OldListView.updateButtonCloseVisibility(true);
		UI.TaskFrame.unload();
		this.updateButtonReturnListEnability(true);
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
	},
	addClassStars (id) {
		$('#' + id).addClass('stars');
	},
	setStar (id) {
		$('#' + id).html('');
	}
};