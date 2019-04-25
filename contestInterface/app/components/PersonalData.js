import UI from '../components';
import Utils from '../common/Utils';
import DateFormat from '../common/DateFormat';
import contest from '../contest';
import group from '../group';

export default {
	personalPageData: null,


	init () {
		window.startContest = this.startContest.bind(this);
		window.startPreparation = this.startPreparation.bind(this);
		/*
		* Called when students acknowledge their new team password
		* hides password and loads contest
		*/
		window.confirmTeamPassword = this.confirmTeamPassword.bind(this);
	},
	load (data, eventListeners) {
		$("#divPersonalPage").show();
		if (data) {
			$("#persoLastName").html(data.registrationData.lastName);
			$("#persoFirstName").html(data.registrationData.firstName);
			$("#persoGrade").html(data.persoGrade);
			$("#persoCategory").html(data.registrationData.qualifiedCategory);
			if (data.registrationData.allowContestAtHome == "0") {
				$("#buttonStartContest").attr("disabled", "disabled");
				$("#contestAtHomePrevented").show();
			}
		}
	},
	unload () {
		$("#divPersonalPage").hide();
	},
	confirmTeamPassword () {
		if (!Utils.disableButton("buttonConfirmTeamPassword")) { // Do not re-enable
			return;
		}
		this.updateVisibilityPassword(false);
		contest.loadContestData(null, null);
	},
	startPreparation () {
		UI.MainHeader.updateTitle(this.personalPageData.contestName);
		UI.SubcontestSelectionInterface.groupMinCategory = this.personalPageData.minCategory;
		UI.SubcontestSelectionInterface.groupMaxCategory = this.personalPageData.maxCategory;
		UI.SubcontestSelectionInterface.groupLanguage = this.personalPageData.language;
		if (this.personalPageData.childrenContests.length > 0) {
			this.unload();
			UI.Breadcrumbs.updateBreadcrumb();
			UI.SubcontestSelectionInterface.offerCategories(this.personalPageData);
			$('#divAccessContest').show();
		} else {
			group.groupWasChecked(this.personalPageData, "PersonalPage", this.personalPageData.registrationData.code, false, false);
		}
	},
	startContest () {
		this.unload();
		UI.StartContest.load();
	},
	showPersonalPage (data) {
		this.personalPageData = data;
		const $nameGrade = i18n.t("grade_" + data.registrationData.grade).toLowerCase();
		data.persoGrade = $nameGrade;
		this.load(data);
		let htmlParticipations = "";
		for (let iParticipation = 0; iParticipation < data.registrationData.participations.length; iParticipation++) {
			const participation = data.registrationData.participations[iParticipation];
			let status;
			if (participation.startTime == null) {
				status = "Non démarrée";
			} else if ((parseInt(participation.nbMinutes) == 0) || (parseInt(participation.remainingSeconds) > 0)) {
				status = "En cours";
			} else {
				status = "Terminé";
			}
			let score = "-";
			if (participation.sumScores !== null) {
				score = parseInt(participation.sumScores);
				if (participation.score !== null) {
					score = Math.max(score, parseInt(participation.score));
				}
			} else if (participation.score !== null) {
				score = parseInt(participation.score);
			}
			const rank = this.rankToStr(participation.rank, $nameGrade, participation.nbContestants);
			const schoolRank = this.rankToStr(participation.schoolRank, $nameGrade, participation.nbContestants);

			htmlParticipations += "<tr><td>" + participation.contestName + "</td>" +
				"<td>" + DateFormat.utc(participation.startTime) + "</td>" +
				"<td>" + participation.contestants + "</td>" +
				"<td>" + status + "</td>" +
				"<td>" + score + "</td>" +
				"<td>" + rank + "</td>" +
				"<td>" + schoolRank + "</td>" +
				"<td><a href='" + location.pathname + "?team=" + participation.password + "' target='_blank'>ouvrir</a></td></tr>";
		}
		$("#pastParticipations").append(htmlParticipations);
	},
	rankToStr (rank, $nameGrade, nbContestants) {
		let strRank = "-";
		if (rank !== null) {
			strRank = rank;
			rank = parseInt(rank);
			if (rank == 1) {
				strRank += "er";
			} else {
				strRank += "e";
			}
			strRank += "<br/>" + $nameGrade + " ";
			if (nbContestants == 1) {
				strRank += "individuels";
			} else {
				strRank += "binômes";
			}
		}
		return strRank;
	},
	updateTeamPassword (html) {
		$("#teamPassword").html(html);
	},
	updateVisibilityPassword (isShow) {
		if (isShow) {
			$("#divPassword").show();
		} else {
			$("#divPassword").hide();
		}
	}
}