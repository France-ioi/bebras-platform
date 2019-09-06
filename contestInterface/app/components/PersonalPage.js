import UI from '../components';
import user from '../user';

export default {

    registrationData: null,


    init() {
        window.personalPageDataEdit = this.personalPageDataEdit.bind(this);
        window.personalPageCreateAccount = this.personalPageCreateAccount.bind(this);
    },


    load(data, eventListeners) {
        $('#divPersonalPage').show();
        UI.Contests.show(this.registrationData.guest == 1);
    },

    unload() {
        $('#divPersonalPage').hide();
        UI.Contests.unload();
    },

    show(registrationData) {
        if (registrationData) {
            this.registrationData = registrationData;
            if(registrationData.guest == 1) {
                this.showGuestMode();
            } else {
                this.showRegularMode();
            }
        }
        this.load();
    },


    showRegularMode() {
        $('#pp_regular_mode').show();
        $('#pp_guest_mode').hide();

        $('#persoLastName').html(this.registrationData.lastName);
        $('#persoFirstName').html(this.registrationData.firstName);
        if(this.registrationData.grade != '') {
            $('#persoGrade').html(i18n.t('grade_' + this.registrationData.grade).toLowerCase());
            $('#pp_row_grade').show();
        } else {
            $('#pp_row_grade').hide();
        }
        if(this.registrationData.qualifiedCategory != '') {
            $('#persoCategory').html(this.registrationData.qualifiedCategory);
            $('#pp_row_category').show();
        } else {
            $('#pp_row_category').hide();
        }
        if(this.registrationData.code != '') {
            $('#persoCode').html(this.registrationData.code);
            $('#pp_row_code').show();
        } else {
            $('#pp_row_code').hide();
        }
    },

    showGuestMode() {
        $('#pp_regular_mode').hide();
        $('#pp_guest_mode').show();
    },


    personalPageDataEdit() {
		this.unload();
        UI.Breadcrumbs.updateBreadcrumb();
        UI.PersonalDataEditor.edit(this.registrationData, {
            onEdit: this.onUserDataChange.bind(this),
            onCancel: this.load.bind(this)
        });
    },


    personalPageCreateAccount() {
        this.unload();
        UI.PersonalDataEditor.edit(null, {
            onEdit: this.onUserDataChange.bind(this),
            onCancel: this.load.bind(this)
        });
    },


    onUserDataChange(new_data) {
        var user_data = Object.assign({}, this.registrationData, new_data);
        var self = this;
        user.update(user_data, function(data) {
            var registrationData = Object.assign({}, self.registrationData, data.user);
            self.show(registrationData);
        })
    }

};