import user from '../user';

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
        this.user_data = user;
        $('#pde_firstName').val(user.firstName);
        $('#pde_lastName').val(user.lastName);
        $('#pde_grade').val(user.grade);
        $('input[name="pde_genre"]').val([user.genre]);
        $('#pde_email').val(user.email);
        $('#pde_zipCode').val(user.zipCode);
        $('#pde_studentId').val(user.studentId);
        this.load();
        this.callbacks = callbacks;
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
        var self = this;
        user_data = Object.assign({}, this.user_data, user_data)
        user.update(user_data, function () {
            self.unload();
            self.callbacks.onEdit(user_data);
        });
    },


    getUserData() {
        return {
            lastName: $.trim($('#pde_lastName').val()),
            firstName: $.trim($('#pde_firstName').val()),
            genre: $("input[name='pde_genre']:checked").val(),
            grade: $('#pde_grade').val(),
            email: $.trim($('#pde_email').val()),
            zipCode: $.trim($('#pde_zipCode').val()),
            studentId: $.trim($('#pde_studentId').val())
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
        } else if (!user.studentId) {
            this.showError('studentId_missing');
            return false;
        }
        return user;
    },


    showError(str_key) {
        $('#personalDataEditorResult').html(str_key ? i18n.t(str_key) : '');
    }
};