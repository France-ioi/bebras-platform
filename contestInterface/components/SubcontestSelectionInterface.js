

export default {
	preSelectedCategory: "",
	selectedCategory: "",
	preSelectedLanguage: "",
	selectedLanguage: "",
	preSelectedContest: "",
	selectedContest: "",
	childrenContests: [],
	groupMinCategory: "",
	groupMaxCategory: "",
	groupLanguage: "",

	init () {
		const that = this;
		// Select contest category
		$('.categorySelector').click(function (event) {
			var target = $(event.currentTarget);
			var category = target.data('category');
			if (that.selectedCategory.length && that.selectedCategory !== that.preSelectedCategory) {
				that.selectedLanguage = "";
				that.selectedContest = "";
			}
			that.preSelectedCategory = category;
			$('.categorySelector').removeClass('selected');
			target.addClass('selected');
			that.selectCategory(that.preSelectedCategory);
		});

		// Select contest language
		$('.languageSelector').click(function (event) {
			var target = $(event.currentTarget);
			var language = target.data('language');
			that.preSelectedLanguage = language;
			$('.languageSelector').removeClass('selected');
			$('.languageSelector[data-language="' + language + '"]').addClass('selected');
			that.selectLanguage(preSelectedLanguage);
		});

		window.getContest = this.getContest;
		window.goToCategory = this.goToCategory;
		window.goToLanguage = this.goToLanguage;
		window.goToSequence = this.goToSequence;

	},
	load (data, eventListeners) {

	},
	unload () {

	},
	selectLanguage (language) {
		this.selectedLanguage = language;
		$("#selectLanguage").delay(250).slideUp(400);
		this.preSelectedContest = "";
		this.offerContests();
	},
	selectCategory (category) {
		this.selectedCategory = category;
		$("#selectCategory").delay(250).slideUp(400);
		this.preSelectedLanguage = "";
		this.preSelectedContest = "";
		this.offerLanguages();
	},
	getContest (ID) {
		for (var iChild = 0; iChild < this.childrenContests.length; iChild++) {
			var child = this.childrenContests[iChild];
			if (child.contestID == ID) {
				return child;
			}
		}
	},
	goToCategory () {
		$('#selectLanguage').slideUp();
		$('#selectContest').slideUp();
		$('#divCheckNbContestants').slideUp();
		$('#selectCategory').slideDown();
		this.offerCategories();
	},
	goToLanguage () {
		$('#selectCategory').slideUp();
		$('#selectContest').slideUp();
		$('#divCheckNbContestants').slideUp();
		$('#selectLanguage').slideDown();
		this.offerLanguages();
	},
	goToSequence () {
		$('#selectCategory').slideUp();
		$('#selectLanguage').slideUp();
		$('#divCheckNbContestants').slideUp();
		$('#selectContest').slideDown();
		this.offerContests();
	},
	offerCategories (data) {
		var categories = {};
		$(".categoryChoice").hide();
		for (var iChild = 0; iChild < this.childrenContests.length; iChild++) {
			var child = this.childrenContests[iChild];
			if (categories[child.categoryColor] == undefined) {
				categories[child.categoryColor] = true;
			}
		}
		var allCategories = ["blanche", "jaune", "orange", "verte", "bleue", "cm1cm2", "6e5e", "4e3e", "2depro", "2de", "1reTalepro", "1reTale", "all"]; // TODO: do not hardcode
		var minReached = (this.groupMinCategory == "");
		var maxReached = false;
		var nbCategories = 0;
		var lastCategory;
		for (var iCategory = 0; iCategory < allCategories.length; iCategory++) {
			var category = allCategories[iCategory];
			if (category == this.groupMinCategory) {
				minReached = true;
			}
			if ((!minReached) || maxReached) {
				categories[category] = false;
			}
			if (category == this.groupMaxCategory) {
				maxReached = true;
			}
			if (categories[category]) {
				nbCategories++;
				lastCategory = category;
				$("#cat_" + category).show();
			}
		}
		if (nbCategories > 1) {
			$("#selectCategory").show();
			if (data.isOfficialContest) {
				$(".categoryWarning").show();
			} else {
				$(".categoryWarning").hide();
			}
		} else {
			this.selectCategory(lastCategory);
		}
		this.scrollToTop('#tab-school .tabTitle');
	},
	offerLanguages () {
		var languages = {};
		var nbLanguages = 0;
		$(".languageSelector").hide();
		var lastLanguage = "";
		for (var iChild = 0; iChild < this.childrenContests.length; iChild++) {
			var child = this.childrenContests[iChild];
			if (this.groupLanguage != "" && this.groupLanguage != child.language) {
				continue;
			}
			if (languages[child.language] == undefined) {
				languages[child.language] = true;
				nbLanguages++;
				lastLanguage = child.language;
				$(".languageSelector[data-language='" + language + "']").show();
			}
		}
		if (nbLanguages > 1) {
			$("#selectLanguage").show();
		} else {
			this.selectLanguage(lastLanguage);
		}
		UI.Breadcrumbs.updateBreadcrumb();
		this.scrollToTop('#tab-school .tabTitle');
	},
	offerContests () {
		var selectHtml = "";
		var lastContestID = "";
		var nbContests = 0;
		for (var iChild = 0; iChild < this.childrenContests.length; iChild++) {
			var child = this.childrenContests[iChild];
			if ((this.selectedCategory == child.categoryColor) &&
				(this.selectedLanguage == child.language)) {
				lastContestID = child.contestID;
				var contestImage = "";
				if (child.imageURL != "") {
					contestImage = '<img src="' + child.imageURL + '"/>';
				}
				var trClasses = "contestSelector";
				/* use of == because contestID is a number, preSelectedContest a string */
				if (child.contestID == this.preSelectedContest) {
					trClasses = trClasses + ' selected';
				}
				selectHtml += '<tr data-contestid="' + child.contestID + '" class="' + trClasses + '">' +
					'<td class="selectorCell">' +
					'<div class="selector_arrowForward" ><span> </span></div>' +
					'</td>' +
					'<td class="selectorTitle"><button type="button" class="btn btn-default">' + child.name + ' →</button></td>' +
					'<td class="contestDescription">' +
					child.description +
					'</td><td class="contestImage">' +
					contestImage +
					'</td></tr>';
				nbContests++;
			}
		}
		if (nbContests > 1) {
			$("#selectContestItems").html(selectHtml);
			$("#selectContest").show();
			this.setContestSelector();
		}
		else {
			window.selectContest(lastContestID);
		}
		UI.Breadcrumbs.updateBreadcrumb();
	},
	setContestSelector () {
		const that = this;
		$('.contestSelector').click(function (event) {
			var target = $(event.currentTarget);
			that.preSelectedContest = target.data('contestid').toString();
			$('.contestSelector').removeClass('selected');
			target.addClass('selected');
			window.selectContest(that.preSelectedContest);
		});
	},
	scrollToTop (el) {
		$('html, body').animate({
			scrollTop: $(el).offset().top
		}, 250);
	},
};