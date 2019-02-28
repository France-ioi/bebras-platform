

export default {
	load (data, eventListeners) {

	},
	unload () {

	},
	goToCategory () {
		$('#selectLanguage').slideUp();
		$('#selectContest').slideUp();
		$('#divCheckNbContestants').slideUp();
		$('#selectCategory').slideDown();
	},
	goToLanguage () {
		$('#selectCategory').slideUp();
		$('#selectContest').slideUp();
		$('#divCheckNbContestants').slideUp();
		$('#selectLanguage').slideDown();
	},
	goToSequence () {
		$('#selectCategory').slideUp();
		$('#selectLanguage').slideUp();
		$('#divCheckNbContestants').slideUp();
		$('#selectContest').slideDown();
	},
	initListeners () {
		// Select contest category
		$('.categorySelector').click(function (event) {
			var target = $(event.currentTarget);
			var category = target.data('category');
			if (selectedCategory.length && selectedCategory !== preSelectedCategory) {
				selectedLanguage = "";
				selectedContest = "";
			}
			preSelectedCategory = category;
			$('.categorySelector').removeClass('selected');
			target.addClass('selected');
			selectCategory(preSelectedCategory);
		});

		function selectCategory (category) {
			selectedCategory = category;
			$("#selectCategory").delay(250).slideUp(400);
			preSelectedLanguage = "";
			preSelectedContest = "";
			offerLanguages();
		}

		// Select contest language
		$('.languageSelector').click(function (event) {
			var target = $(event.currentTarget);
			var language = target.data('language');
			preSelectedLanguage = language;
			$('.languageSelector').removeClass('selected');
			$('.languageSelector[data-language="' + language + '"]').addClass('selected');
			selectLanguage(preSelectedLanguage);
		});

		function selectLanguage (language) {
			selectedLanguage = language;
			$("#selectLanguage").delay(250).slideUp(400);
			preSelectedContest = "";
			offerContests();
		}
	},
	setContestSelector () {
		$('.contestSelector').click(function (event) {
			var target = $(event.currentTarget);
			preSelectedContest = target.data('contestid').toString();
			$('.contestSelector').removeClass('selected');
			target.addClass('selected');
			selectContest(preSelectedContest);
		});
	},
	scrollToTop (el) {
		$('html, body').animate({
			scrollTop: $(el).offset().top
		}, 250);
	},
	hideCategoryChoice () {
		$(".categoryChoice").hide();
	},
	showCategory (category) {
		$("#cat_" + category).show();
	},
	showSelectCategory () {
		$("#selectCategory").show();
	},
	updateVisibilityCategoryWarning (isShow) {
		if (isShow) {
			$(".categoryWarning").show();
		} else {
			$(".categoryWarning").hide();
		}
	},
	hideLanguageSelector () {
		$(".languageSelector").hide();
	},
	showLanguage (language) {
		$(".languageSelector[data-language='" + language + "']").show();
	},
	showSelectLanguage () {
		$("#selectLanguage").show();
	},
	updateSelectContestItems (html) {
		$("#selectContestItems").html(html);
		$("#selectContest").show();
	}
};