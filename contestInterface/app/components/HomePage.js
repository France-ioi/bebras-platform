import UI from '../components';
import user from '../user';
import group from '../group';
import preloader from '../common/Preloader';


export default {


	init () {
        window.registerUser = this.registerUser.bind(this);
        window.createGuest = this.createGuest.bind(this);
        window.showPreloadPage = this.showPreloadPage.bind(this);
        $('#homePagePreloadSection').toggle(!!preloader);
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
            UI.HomePage.unload();
            app.setUser(res.registrationData);
            UI.PersonalPage.show(res.registrationData);
        })
    },


    createGuest() {
        user.createGuest(function(res) {
            UI.HomePage.unload();
            app.setUser(res.registrationData);
            UI.PersonalPage.show(res.registrationData);
        });
    },

    showPreloadPage() {
        this.unload();
        UI.PreloadPage.load();
    }

};