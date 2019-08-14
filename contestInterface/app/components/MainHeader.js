import fetch from '../common/Fetch';

export default {

	init() {
		window.changeInterfaceLanguage = this.onLanguageChange.bind(this);
	},

	load (data, eventListeners) {
		$("#divHeader").show();
	},

	unload () {
		$("#divHeader").hide();
	},

	updateTitle (title) {
		$('#headerH1').html(title);
		//$('title').html(contestName); doesn't work on old IEs
	},

	updateSubTitle (title) {
		$("#headerH2").html(title);
	},

	updateLoginLinkVisibility (isShow) {
		if (isShow) {
			$("#login_link_to_home").show();
		} else {
			$("#login_link_to_home").hide();
		}
	},

	onLanguageChange() {
		fetch(
            'data.php',
            {
                SID: app.SID,
                controller: 'Language',
                action: 'set',
                language: $('#interface_language').val()
            },
            function(data) {
                window.location.href = window.location.href;
            }
        );
	}
};