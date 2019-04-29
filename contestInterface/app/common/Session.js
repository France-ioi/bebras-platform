import fetch from './Fetch';
import UI from '../components';
import contest from '../contest';

/*
    * Loads all the information about a session if a session is already opened
    * Otherwise, displays the list of public groups.
    */
function load() {
    fetch(
        "data.php",
        { SID: app.SID, action: "loadSession" },
        function(data) {
            app.SID = data.SID;
            if (data.teamID) {
                if (!confirm(data.message)) {
                    // i18n.t("restart_previous_contest") json not loaded yet!
                    destroy();
                    return;
                }
                app.teamID = data.teamID;
                contest.initContestData(data);
                UI.TrainingContestSelection.unload();
                contest.loadContestData(app.contestID, app.contestFolder);
                return;
            }
        }
    );
}

function destroy() {
    app.SID = null; // are we sure about that?
    fetch(
        "data.php",
        { action: "destroySession" },
        function(data) {
            app.SID = data.SID;
        }
    );
}

export default {
    load,
    destroy
}