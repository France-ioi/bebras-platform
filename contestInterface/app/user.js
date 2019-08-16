import UI from './components';
import fetch from './common/Fetch';

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
            callback && callback();
        }
    );
}

export default {
    update
};