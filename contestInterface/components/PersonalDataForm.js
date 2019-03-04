

export default {
	nbContestants: 1,
	teamMateHasRegistration: {1: false, 2: false},
	fieldsHidden: {},
	contestants: {},

	init () {
		const that = this;
		$(".nbContestants").click(function (event) {
			const target = $(event.currentTarget);
			const nbContestants = target.data('nbcontestants');
			that.setNbContestants(nbContestants);
			target.addClass('selected');
		});

		window.hasRegistration = this.hasRegistration.bind(this);
		window.validateRegistrationCode = this.validateRegistrationCode.bind(this);
		window.validateLoginForm = this.validateLoginForm.bind(this);
	},
	load (data, eventListeners) {
		$("#divDescribeTeam").show();
	},
	unload () {
		$("#divDescribeTeam").hide();
		this.updateLoginVisibility(false);
		this.updateCheckNbContestantsVisibility(false);
	},
	updateLoginForm (isPublic, data) {
		if (isPublic) {
			this.setNbContestants(1);
			window.createTeam([{lastName: "Anonymous", firstName: "Anonymous", genre: 2, email: null, zipCode: null}]);
		} else {
			UI.Breadcrumbs.updateBreadcrumb();
			this.load();
			$("#divAccessContest").show();
			if (data.askParticipationCode == 0) {
				$("#askRegistrationCode1").hide();
				$("#askRegistrationCode2").hide();
				this.hasRegistration(1, false);
				this.hasRegistration(2, false);
			}
			if (data.allowTeamsOfTwo == 1) {
				this.updateCheckNbContestantsVisibility(true);
				this.updateLoginVisibility(false);
			} else {
				this.setNbContestants(1);
				this.updateCheckNbContestantsVisibility(false);
				this.updateLoginVisibility(true);
			}
		}
		if ((data.registrationData != undefined) && (data.registrationData.code != undefined)) {
			this.contestants[1] = {registrationCode: data.registrationData.code};
			this.updateRegCodeForTeamMate("1", data.registrationData.code);
			this.hasRegistration(1, true, true);
			this.updateErrorRegCodeForTeamMate("1", "Bienvenue " + data.registrationData.firstName + " " + data.registrationData.lastName);
		}
	},
	/*
	 * Validates student's information form
	 * then creates team
	*/
	validateLoginForm () {
		this.updateLoginResult("");
		for (let iContestant = 1; iContestant <= this.nbContestants; iContestant++) {
			const strTeamMate = "Équipier " + iContestant + " : ";
			if (this.teamMateHasRegistration[iContestant]) {
				if ((this.contestants[iContestant] == undefined) || (this.contestants[iContestant].registrationCode == undefined)) {
					this.updateLoginResult(strTeamMate + "entrez et validez le code");
					return;
				}
				if ((this.contestants[3 - iContestant] != undefined) &&
					(this.contestants[3 - iContestant].registrationCode == this.contestants[iContestant].registrationCode)) {
					this.updateLoginResult("Les deux codes ne peuvent pas être identiques !");
				}
			} else {
				const contestant = getContestant(iContestant);
				this.contestants[iContestant] = contestant;
				if (!contestant.lastName && !this.fieldsHidden.lastName) {
					this.updateLoginResult(strTeamMate + i18n.t("lastname_missing"));
					return;
				} else if (!contestant.firstName && !this.fieldsHidden.firstName) {
					this.updateLoginResult(strTeamMate + i18n.t("firstname_missing"));
					return;
				} else if (!contestant.genre && !this.fieldsHidden.genre) {
					this.updateLoginResult(strTeamMate + i18n.t("genre_missing"));
					return;
				} else if (!contestant.email && !this.fieldsHidden.email) {
					this.updateLoginResult(strTeamMate + i18n.t("email_missing"));
					return;
				} else if (!contestant.zipCode && !this.fieldsHidden.zipCode) {
					this.updateLoginResult(strTeamMate + i18n.t("zipCode_missing"));
					return;
				} else if (!contestant.studentId && !this.fieldsHidden.studentId) {
					this.updateLoginResult(strTeamMate + i18n.t("studentId_missing"));
					return;
				} else if (!contestant.grade && !this.fieldsHidden.grade) {
					this.updateLoginResult(strTeamMate + i18n.t("grade_missing"));
					return;
				}
			}
		}
		Utils.disableButton("buttonLogin"); // do not re-enable
		window.createTeam(this.contestants);
	},
	validateRegistrationCode (teamMate) {
		this.updateLoginResult("");
		const code = this.getTeamMateRegCode(teamMate);
		this.updateErrorRegCodeForTeamMate(teamMate, '');
		const that = this;
		$.post("data.php", {SID: window.SID, action: "checkRegistration", code: code},
			function (data) {
				if (data.success) {
					const contestant = {
						"registrationCode": code,
						"firstName": data.firstName,
						"lastName": data.lastName
					};
					that.contestants[teamMate] = contestant;
					that.updateErrorRegCodeForTeamMate(teamMate, "Bienvenue " + data.firstName + " " + data.lastName);
				} else {
					that.updateErrorRegCodeForTeamMate(teamMate, "code inconnu");
				}
			}, "json");
	},
	hasRegistration (teamMate, hasReg, lock) {
		this.updateLoginResult("");
		this.teamMateHasRegistration[teamMate] = hasReg;
		this.updateRegisterTeamMate(teamMate, hasReg, lock);
	},
	getContestant (iContestant) {
		return {
			"lastName": $.trim($("#lastName" + iContestant).val()),
			"firstName": $.trim($("#firstName" + iContestant).val()),
			"genre": $("input[name='genre" + iContestant + "']:checked").val(),
			"grade": $("#grade" + iContestant).val(),
			"email": $.trim($("#email" + iContestant).val()),
			"zipCode": $.trim($("#zipCode" + iContestant).val()),
			"studentId": $.trim($("#studentId" + iContestant).val())
		};
	},
	hideLoginFields (postData) {
		var contestFieldMapping = {
			askEmail: 'email',
			askGrade: 'grade',
			askStudentId: 'studentId',
			askZip: 'zipCode',
			askGenre: 'genre'
		};
		for (var contestFieldName in contestFieldMapping) {
			var loginFieldName = contestFieldMapping[contestFieldName];
			if (postData[contestFieldName]) {
				this.fieldsHidden[loginFieldName] = false;
				$('#login-input-' + loginFieldName + '-1').show();
				$('#login-input-' + loginFieldName + '-2').show();

			} else {
				this.fieldsHidden[loginFieldName] = true;
				$('#login-input-' + loginFieldName + '-1').hide();
				$('#login-input-' + loginFieldName + '-2').hide();
			}
		}
	},
	updateCheckNbContestantsVisibility (isShow) {
		if (isShow) {
			$('#divCheckNbContestants').show();
		} else {
			$('#divCheckNbContestants').hide();
		}
	},
	updateLoginVisibility (isShow) {
		if (isShow) {
			$("#divLogin").show();
		} else {
			$("#divLogin").hide();
		}
	},
	initContestant (contestant) {
		$("#firstName" + contestant).val("");
		$("#lastName" + contestant).val("");
		$("#genre" + contestant + "_female").attr('checked', null);
		$("#genre" + contestant + "_male").attr('checked', null);
	},
	/*
	 * Called when students validate the form that asks them if they participate
	 * alone or in a team of two students.
	*/
	setNbContestants (nbContestants) {
		this.nbContestants = nbContestants;
		$(".nbContestants").removeClass('selected');
		if (nbContestants === 2) {
			$("#contestant2").show();
		}
		if (nbContestants !== 2) {
			$("#contestant2").hide();
		}
		this.updateLoginVisibility(true);
	},
	getTeamMateRegCode (teamMate) {
		return $("#registrationCode" + teamMate).val().trim().toLowerCase();
	},
	updateLoginFieldVisibility (loginFieldName, isShow) {
		if (isShow) {
			$('#login-input-' + loginFieldName + '-1').show();
			$('#login-input-' + loginFieldName + '-2').show();
		} else {
			$('#login-input-' + loginFieldName + '-1').hide();
			$('#login-input-' + loginFieldName + '-2').hide();
		}
	},
	updateLoginResult (html) {
		$("#LoginResult").html(html);
	},
	updateRegisterTeamMate (teamMate, hasReg, lock) {
		$("#hasReg" + teamMate + "Yes").removeClass("selected");
		$("#hasReg" + teamMate + "No").removeClass("selected");
		$("#yesRegistrationCode" + teamMate).hide();
		$("#noRegistrationCode" + teamMate).hide();
		if (hasReg) {
			$("#hasReg" + teamMate + "Yes").addClass("selected");
			$("#yesRegistrationCode" + teamMate).show();
			if (lock) {
				$("#hasReg" + teamMate + "No").attr("disabled", "disabled");
				$("#registrationCode" + teamMate).attr("readonly", "readonly");
				$("#validateRegCode" + teamMate).hide();
			}
		} else {
			$("#hasReg" + teamMate + "No").addClass("selected");
			$("#noRegistrationCode" + teamMate).show();
		}
	},
	updateErrorRegCodeForTeamMate (teamMate, html) {
		$("#errorRegistrationCode" + teamMate).html(html);
	},
	updateRegCodeForTeamMate (teamMate, val) {
		$("#registrationCode" + teamMate).val(val);
	},
};