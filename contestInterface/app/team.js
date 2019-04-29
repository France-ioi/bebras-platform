import UI from './components';
import fetch from './common/Fetch';
/*
    * Creates a new team using contestants information
    */
function createTeam(contestants) {
    if (window.browserIsMobile && typeof app.scratchToBlocklyContestID[contestID] != "undefined") {
        alert(i18n.t("browser_redirect_scratch_to_blockly"));
        app.contestID = app.scratchToBlocklyContestID[contestID];
        var contest = window.getContest(app.contestID);
        app.contestFolder = contest.folder;
        app.customIntro = contest.customIntro;
    }
    fetch(
        "data.php",
        {
            SID: app.SID,
            action: "createTeam",
            contestants: contestants,
            contestID: app.contestID
        },
        function(data) {
            app.teamID = data.teamID;
            app.teamPassword = data.password;
            UI.PersonalDataForm.unload();
            UI.SubcontestSelectionInterface.unload();
            UI.PersonalData.updateTeamPassword(data.password);
            UI.PersonalData.updateVisibilityPassword(true);
        }
    );
};


export default {
    createTeam
}