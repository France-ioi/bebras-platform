import UI from './components';
import Utils from './common/Utils';
import contest from './contest';

function groupWasChecked(data, curStep, groupCode, getTeams, isPublic, contestID) {
    contest.initContestData(data, contestID);
    UI.MainHeader.updateSubTitle(data.name);
    UI.MainHeader.updateLoginLinkVisibility(false);
    if (data.teamID !== undefined) {
        // The password of the team was provided directly
        $("#div" + curStep).hide();
        app.teamID = data.teamID;
        app.teamPassword = groupCode;
        contest.loadContestData(contestID, app.contestFolder);
    } else {
        if (data.nbMinutesElapsed > 30 && !data.isPublic && !data.isGenerated && !getTeams) {
            if (parseInt(data.bRecovered)) {
                alert(i18n.t("group_session_expired"));
                //window.location = i18n.t("contest_url");
                return false;
            } else {
                UI.TrainingContestSelection.load();
                UI.GroupUsedForm.load();
                return false;
            }
        }
        $("#div" + curStep).hide();
        UI.PersonalDataForm.hideLoginFields(data);
        if (curStep === "CheckGroup") {
            UI.PersonalDataForm.updateLoginForm(isPublic, data);
            UI.NavigationTabs.unload();
        } else {
            /*
             * Fills a select field with all the names of the teams (of a given group)
             * Used to continue a contest if the students didn't write down the team password
             */
            UI.RestartContestForm.fillListTeams(data.teams);
            UI.NavigationTabs.load();
            UI.RestartContestForm.updateDivReloginVisibility(true);
        }
    }
};




/*
 * Checks if a group is valid and loads information about the group and corresponding contest,
 * curStep: indicates which step of the login process the students are currently at :
 *   - "CheckGroup" if loading directly from the main page (public contest or group code)
 *   - "Interrupted" if loading from the interface used when continuing an interupted contest
 * groupCode: a group code, or a team password
 * isPublic: is this a public group ?
 */


function checkGroupFromCode(curStep, groupCode, getTeams, isPublic, language, startOfficial) {
    Utils.disableButton("button" + curStep);
    UI.GroupUsedForm.unload();
    UI.TrainingContestSelection.hideBrowserAlert();
    UI.TrainingContestSelection.updateCurStepResult(curStep, "");
    $.post(
        "data.php",
        {
            SID: app.SID,
            action: "checkPassword",
            password: groupCode,
            getTeams: getTeams,
            language: language,
            startOfficial: startOfficial,
            commonJsVersion: app.commonJsVersion,
            timestamp: window.timestamp,
            commonJsTimestamp: app.commonJsTimestamp
        },
        function(data) {
            if (!data.success) {
                if (data.message) {
                    UI.TrainingContestSelection.updateCurStepResult(curStep, data.message);
                } else {
                    UI.TrainingContestSelection.updateCurStepResult(curStep, i18n.t("invalid_code"));
                }
                return;
            }
            UI.StartContestForm.slideUp();
            UI.NavigationTabs.unload();
            UI.MainHeader.updateLoginLinkVisibility(false);
            $("#div" + curStep).hide();

            UI.SubcontestSelectionInterface.childrenContests = data.childrenContests;
            app.groupCheckedData = {
                data: data,
                curStep: curStep,
                groupCode: groupCode,
                getTeams: getTeams,
                isPublic: data.isPublic
            };

            if (data.registrationData != undefined && !data.isOfficialContest) {
                UI.PersonalData.showPersonalPage(data);
                return;
            }
            UI.MainHeader.updateTitle(data.contestName);

            UI.SubcontestSelectionInterface.groupMinCategory = data.minCategory;
            UI.SubcontestSelectionInterface.groupMaxCategory = data.maxCategory;
            UI.SubcontestSelectionInterface.groupLanguage = data.language;

            if (data.allContestsDone) {
                $("#" + curStep).hide();
                UI.AllContestsDone.load();
                return;
            }

            if (!getTeams && data.childrenContests != undefined && data.childrenContests.length != 0) {
                $("#" + curStep).hide();
                UI.SubcontestSelectionInterface.load();
                UI.SubcontestSelectionInterface.offerCategories(data);
            } else {
                groupWasChecked(
                    data,
                    curStep,
                    groupCode,
                    getTeams,
                    data.isPublic
                );
            }
        },
        "json"
    ).done(function() {
        Utils.enableButton("button" + curStep);
    });
};



export default {
    groupWasChecked,
    checkGroupFromCode
}