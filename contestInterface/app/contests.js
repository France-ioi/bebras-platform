import CachedRequest from './new/CachedRequest'

var request = CachedRequest('contests')

function getData(registrationData, callback) {
    request.send(
        {
			SID: app.SID,
			controller: "Contests",
            action: "getData",
            registrationID: registrationData['ID'],
            guest: registrationData['guest']
        },
        function(data) {
            callback && callback(data);
        }
    );
}


export default {
    getData
}