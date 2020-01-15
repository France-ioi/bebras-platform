import CachedRequest from './new/CachedRequest'

var request = CachedRequest('contests')

function getData(callback) {
    request.send(
        {
			SID: app.SID,
			controller: "Contests",
            action: "getData",
        },
        function(data) {
            callback && callback(data);
        }
    );
}


export default {
    getData
}