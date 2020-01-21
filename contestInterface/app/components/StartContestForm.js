import UI from '../components';
import group from '../group';
import group_confirmation from '../group_confirmation';

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
		$('#CheckGroupResult').html('');
		const groupCode = this.getGroupCode();
		group_confirmation.check(groupCode, function() {
			return group.checkGroupFromCode("CheckGroup", groupCode, false, false);
		})
	},
	confirmUnsupportedBrowser () {
		$("#submitParticipationCode").removeClass('needBrowserConfirm');
	},
	getGroupCode () {
		return $('#groupCode').val();
	}
};