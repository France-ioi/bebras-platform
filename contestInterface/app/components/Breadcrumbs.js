import UI from '../components';

export default {
	load (data, eventListeners) {

	},
	unload () {
		$('#selection-breadcrumb').html('');
	},
	updateBreadcrumb () {
		const {
			preSelectedCategory,
			selectedCategory,
			preSelectedLanguage,
			selectedLanguage,
			preSelectedContest
		} = UI.SubcontestSelectionInterface;
		let contestBreadcrumb = "";

		if (preSelectedCategory != "") {
			contestBreadcrumb = '<span class="breadcrumb-item"><span class="breadcrumb-link" onclick="goToCategory()">Catégorie ' + selectedCategory + '</span></span>';
		}
		if (preSelectedLanguage != "") {
			contestBreadcrumb += '<span class="breadcrumb-item"><span class="breadcrumb-separator">/</span><span class="breadcrumb-link" onclick="goToLanguage()">Langage ' + selectedLanguage + '</span></span>';
		}
		if (preSelectedContest != "") {
			var contest = window.getContest(preSelectedContest);
			contestBreadcrumb += '<span class="breadcrumb-item"><span class="breadcrumb-separator">/</span><span class="breadcrumb-link" onclick="goToSequence()">' + contest.name + '</span></span>';
		}
		$('#selection-breadcrumb').html(contestBreadcrumb);
	}
};