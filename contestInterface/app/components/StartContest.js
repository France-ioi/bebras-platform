import UI from '../components';
import group from '../group';
import PersonalData from './PersonalData'

export default {
	init () {
		window.cancelStartContest = this.cancelStartContest.bind(this);
		window.reallyStartContest = this.reallyStartContest.bind(this);

	},
	load (data, eventListeners) {
		$("#divStartContest").show();
	},
	unload () {
		$("#divStartContest").hide();
	},
	cancelStartContest () {
		UI.AllContestsDone.unload();
		this.unload();
		UI.PersonalData.load();
	},
	reallyStartContest () {
		this.unload();
		group.checkGroupFromCode("CheckGroup", PersonalData.personalPageData.registrationData.code, false, false, null, true);
	}

}