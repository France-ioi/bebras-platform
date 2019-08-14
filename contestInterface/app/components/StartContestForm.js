import UI from '../components';
import group from '../group';

export default {
	init() {
		/*
		 * Called when confirming a participation from an unsupported browser
		 */
		window.confirmUnsupportedBrowser = this.confirmUnsupportedBrowser.bind(this);
		/*
		* Called when starting a contest by providing a group code on the main page.
		*/
		window.checkGroup = this.checkGroup.bind(this);
	},
	load (data, eventListeners) {

	},
	unload () {
		$('#groupCode').val('');
	},
	checkGroup () {
		const groupCode = this.getGroupCode();
		return group.checkGroupFromCode("CheckGroup", groupCode, false, false);
	},
	slideUp () {
		$("#submitParticipationCode").delay(250).slideUp(400);
	},
	confirmUnsupportedBrowser () {
		$("#submitParticipationCode").removeClass('needBrowserConfirm');
	},
	getGroupCode () {
		return $('#groupCode').val();
	},
};