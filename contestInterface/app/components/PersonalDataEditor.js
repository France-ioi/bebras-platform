export default {

    init() {
        window.personalDataEditorSubmit = this.personalDataEditorSubmit.bind(this);
        window.personalDataEditorCancel = this.personalDataEditorCancel.bind(this);
    },

    load(data, eventListeners) {
        $('#buttonPersonalDataEditorSubmit').prop('disabled', false);
        $('#buttonPersonalDataEditorCancel').prop('disabled', false);
        $('#divPersonalDataEditor').show();
    },

    unload() {
        $('#divPersonalDataEditor').hide();
    },

    edit(user, callbacks) {
        $('#pde_caption').html(user ? i18n.t("personal_data_edit") : i18n.t("personal_data_register"));
        this.user_data = user || {};
        $('#pde_firstName').val(this.user_data.firstName || '');
        $('#pde_lastName').val(this.user_data.lastName || '');
        $('#pde_grade').val(this.user_data.grade || '');
        $('input[name="pde_genre"]').val(this.user_data.genre ? [this.user_data.genre] : []);
        $('#pde_email').val(this.user_data.email || '');
        $('#pde_zipCode').val(this.user_data.zipCode || '');
        $('#pde_studentID').val(this.user_data.studentID);
        this.refreshTooltips(this.user_data);
        this.load();
        this.callbacks = callbacks;
    },

    refreshTooltips(user) {
        $('#divPersonalDataEditor .confirmed_value').hide();

        if(!user.ID) return;
        if(parseInt(user.confirmed, 10) == 1) return;

        function show(key, value) {
            $('#pde_' + key + '_confirmed').show().attr('title', i18n.t('personal_data_confirmed_value') + value);
        }

        if(user.firstName != user.original.firstName) {
            show('firstName', user.original.firstName);
        }
        if(user.lastName != user.original.lastName) {
            show('lastName', user.original.lastName);
        }
        if(user.grade != user.original.grade) {
            show('grade', i18n.t('grade_' + user.original.grade));
        }
        if(user.genre != user.original.genre) {
            var t = '';
            if(user.original.genre == "1") {
                t = i18n.t('login_female')
            } else if(user.original.genre == "2") {
                t = i18n.t('login_male')
            }
            show('genre', t);
        }
        if(user.email != user.original.email) {
            show('email', user.original.email);
        }
        if(user.zipCode != user.original.zipCode) {
            show('zipCode', user.original.zipCode);
        }
        if(user.studentID != user.original.studentID) {
            show('studentID', user.original.studentID);
        }
    },

    personalDataEditorCancel() {
        this.unload();
        this.callbacks.onCancel();
    },

    personalDataEditorSubmit() {
        var user_data = this.validate();
        if (!user_data) return;
        $('#buttonPersonalDataEditorSubmit').prop('disabled', true);
        $('#buttonPersonalDataEditorCancel').prop('disabled', true);
        user_data = Object.assign({}, this.user_data, user_data)
        this.unload();
        this.callbacks.onEdit(user_data);
    },


    getUserData() {
        return {
            lastName: $.trim($('#pde_lastName').val()),
            firstName: $.trim($('#pde_firstName').val()),
            genre: $("input[name='pde_genre']:checked").val(),
            grade: $('#pde_grade').val(),
            email: $.trim($('#pde_email').val()),
            zipCode: $.trim($('#pde_zipCode').val()),
            studentID: $.trim($('#pde_studentID').val())
        };
    },


    validate() {
        var user= this.getUserData();
        if (!user.lastName) {
            this.showError('lastname_missing');
            return false;
        } else if (!user.firstName) {
            this.showError('firstname_missing');
            return false;
        } else if (!user.genre) {
            this.showError('genre_missing');
            return false;
        } else if (!user.grade) {
            this.showError('grade_missing');
            return false;
        } else if (!user.email) {
            this.showError('email_missing');
            return false;
        } else if (!user.zipCode) {
            this.showError('zipCode_missing');
            return false;
        } else if (!user.studentID) {
            this.showError('studentId_missing');
            return false;
        }
        return user;
    },


    showError(str_key) {
        $('#personalDataEditorResult').html(str_key ? i18n.t(str_key) : '');
    }
};