import UI from '../components';
import user from '../user';
//import group_confirmation from '../group_confirmation'
import group from '../group';

export default {


	init () {
        window.registerUser = this.registerUser.bind(this);
        window.createGuest = this.createGuest.bind(this);
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
        user.createRegular(user_data, function(res) {
            console.log(res)
            UI.HomePage.unload();
            UI.PersonalPage.show(res.registrationData);
        })
    },


    createGuest() {
        user.createGuest(function(res) {
            UI.HomePage.unload();
            UI.PersonalPage.show(res.registrationData);
        });
    }

};