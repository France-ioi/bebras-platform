import UI from './components';
import CachedRequest from './new/CachedRequest'

var request = CachedRequest('group_confirmation')

function check(code, callback) {
    request.send(
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