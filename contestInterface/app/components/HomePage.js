import UI from '../components';
import user from '../user';

export default {


	init () {
        window.registerUser = this.registerUser.bind(this);
        window.guestUser = this.guestUser.bind(this);
	},

    load(data, eventListeners) {

        $('#homePage').show();
    },

    unload() {
        $('#homePage').hide();
    },


    registerUser() {
		this.unload();
        UI.PersonalDataEditor.edit(null, {
            onEdit: this.onRegistrationData.bind(this),
            onCancel: this.load.bind(this)
        });
    },


    onRegistrationData(user_data) {
        user.create(user_data, function(res) {

        })
    },


    guestUser() {
        this.unload();
    }

};