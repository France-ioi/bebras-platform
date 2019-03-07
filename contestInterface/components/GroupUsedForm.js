

export default {
	init () {
		window.recoverGroup = this.recoverGroup.bind(this);
	},
	load (data, eventListeners) {
		$("#recoverGroup").show();
	},
	unload () {
		$("#recoverGroup").hide();
	},
	recoverGroup () {
		const groupCode = UI.StartContestForm.getGroupCode();
		const groupPass = this.getGroupPass();
		if (!groupCode || !groupPass) {return false;}
		this.updateRecoverGroupResult('');
		Utils.disableButton("buttonRecoverGroup");
		$.post("data.php", {SID: window.SID, action: "recoverGroup", groupCode: groupCode, groupPass: groupPass},
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
		   },
		   'json').done(function () {Utils.enableButton("buttonRecoverGroup");});
	 },
	getGroupPass () {
		return $('#recoverGroupPass').val();
	},
	updateRecoverGroupResult (html='') {
		$('#recoverGroupResult').html(html);
	}
};