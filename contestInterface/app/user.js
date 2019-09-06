import UI from './components';
import fetch from './common/Fetch';

function createRegular(user, callback) {
    fetch(
        'data.php',
        {
            SID: app.SID,
            controller: 'User',
            action: 'createRegular',
            user: user
        },
        function(data) {
            callback && callback(data);
        }
    );
}


function createGuest(callback) {
    fetch(
        'data.php',
        {
            SID: app.SID,
            controller: 'User',
            action: 'createGuest'
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
    createRegular,
    createGuest,
    update
};