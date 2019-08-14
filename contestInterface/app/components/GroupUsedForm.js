import UI from '../components';
import fetch from '../common/Fetch';
import Utils from '../common/Utils';

export default {
	init () {
		window.recoverGroup = this.recoverGroup.bind(this);
	},
	load (data, eventListeners) {
		$("#recoverGroup").show();
	},
	unload () {
		$("#recoverGroup").hide();
		$('#recoverGroupPass').val('');
	},
	recoverGroup () {
		const groupCode = UI.StartContestForm.getGroupCode();
		const groupPass = this.getGroupPass();
		if (!groupCode || !groupPass) {return false;}
		this.updateRecoverGroupResult('');
		Utils.disableButton("buttonRecoverGroup");
		fetch(
			"data.php",
			{
				SID: app.SID,
				controller: "Group",
				action: "recover",
				groupCode: groupCode,
				groupPass: groupPass
			},
		   	function (data) {
			  	if (!data.success) {
				 	if (data.message) {
						this.updateRecoverGroupResult(data.message);
				 	} else {
						this.updateRecoverGroupResult(i18n.t("invalid_code"));
				 	}
				 	return;
				}
				window.checkGroup();
		   	}
		).done(function () {
			Utils.enableButton("buttonRecoverGroup");
		});
	 },
	getGroupPass () {
		return $('#recoverGroupPass').val();
	},
	updateRecoverGroupResult (html='') {
		$('#recoverGroupResult').html(html);
	}
};