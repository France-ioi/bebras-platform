import UI from './components';
import fetch from './common/Fetch';

function create(user, callback) {
    fetch(
        'data.php',
        {
            SID: app.SID,
            controller: 'User',
            action: 'create',
            user: user
        },
        function(data) {
            callback && callback(data);
        }
    );
}


function update(user, callback) {
    fetch(
        'data.php',
        {
            SID: app.SID,
            controller: 'User',
            action: 'update',
            user: user
        },
        function(data) {
            callback && callback(data);
        }
    );
}

export default {
    create,
    update
};