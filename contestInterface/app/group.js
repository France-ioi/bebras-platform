import UI from './components';
import Utils from './common/Utils';
import contest from './contest';
import fetch from './common/Fetch';


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
    fetch(
        "data.php",
        {
            SID: app.SID,
            controller: "Auth",
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
        }
    ).done(function() {
        Utils.enableButton("button" + curStep);
    });
};


function loadPublicGroups() {
    fetch(
        "data.php",
        {controller: "Group", action: 'loadPublicGroups'},
        function(data) {
            //$("#classroomGroups").show();
            if ((data.groups.length !== 0) && (data.groups.length < 10)) { // Temporary limit for fr platform
                $("#listPublicGroups").html(getPublicGroupsList(data.groups));
            }
            $("#contentPublicGroups").show();
            $("#loadPublicGroups").hide();
        }
    );
}

/*
 * Generates the html for the list of public groups
*/
function getPublicGroupsList(groups) {
    var arrGroups = {};
    var years = {};
    var categories = {};
    var year, group,category;
    var maxYear = 0;
    for (var iGroup = 0 ; iGroup < groups.length ; iGroup ++) {
       group = groups[iGroup];
       if (!arrGroups[group.level]) {
          arrGroups[group.level] = {};
       }
       year = group.year % 10000;
       arrGroups[group.level][group.category] = group;
       years[year] = true;
       if (!categories[year]) {
         categories[year] = {};
       }
       categories[year][group.category] = true;
       maxYear = Math.max(maxYear, year);
    }
    var levels = [
       {name: t("level_1_name"), i18name: "level_1_name", id: 1},
       {name: t("level_2_name"), i18name: "level_2_name", id: 2},
       {name: t("level_3_name"), i18name: "level_3_name", id: 3},
       {name: t("level_4_name"), i18name: "level_4_name", id: 4},
       {name: t("level_all_questions_name"), i18name: "level_all_questions_name", id: 0}
    ];
    var strGroups = "<table style='border:solid 1px black; border-collapse:collapse;' cellspacing=0 cellpadding=5>";
    for (year = maxYear; years[year] === true; year--) {
       for (category in categories[year]) {
          var nbGroupsInCategory = 0;
          var thisCategoryStrGroup = '';
          strGroups += "<tr class='groupRow'><td style='width:100px;border:solid 1px black;text-align:center'><b>" + category + "</b></td>";
          for (var iLevel = 0; iLevel < levels.length; iLevel++) {
             var level = levels[iLevel];
             group = undefined;
             if (arrGroups[level.id]) {
                group = arrGroups[level.id][category];
             }
             if (group) {
                thisCategoryStrGroup += "<td style='width:100px;border:solid 1px black;text-align:center'>" +
                   "<a href='#' onclick='checkGroupFromCode(\"CheckGroup\", \"" + group.code + "\", false, true)' data-i18n=\"[html]"+level.i18name+"\"> " + level.name + "</a></td>";
                   nbGroupsInCategory = nbGroupsInCategory + 1;
             } else {
                thisCategoryStrGroup += "<td width=20%></td>";
             }
          }
          if (nbGroupsInCategory == 1 && arrGroups[0] && arrGroups[0][category]) {
             group = arrGroups[0][category];
             thisCategoryStrGroup = "<td colspan=\"5\" style='width:500px;border:solid 1px black;text-align:center'>" +
                   "<a href='#' onclick='checkGroupFromCode(\"CheckGroup\", \"" + group.code + "\", false, true)' data-i18n=\"[html]level_all_levels_name\"> " + t("level_all_levels_name") + "</a></td>";
          }
          strGroups = strGroups + thisCategoryStrGroup;
          strGroups += "</tr>";
       }
    }
    strGroups += "</table>";
    return strGroups;
 }


export default {
    groupWasChecked,
    checkGroupFromCode
}