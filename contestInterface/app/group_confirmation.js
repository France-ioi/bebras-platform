import UI from './components';
import fetch from './common/Fetch';


function check(code, callback) {
    fetch(
        "data.php",
        {
            SID: app.SID,
            controller: "Group",
            action: "checkConfirmationInterval",
            code: code
        },
        function(data) {
            if(!data.group) {
                callback();
                return;
            }
            UI.GroupConfirmation.show(data.group, function(confirmed) {
                if(confirmed) {
                    callback();
                } else {
                    UI.StartContestForm.unload();
                }
            });
        }
    );
}



export default {
    check
}