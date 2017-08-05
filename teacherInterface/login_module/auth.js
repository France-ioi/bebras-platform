function initAuth(config) {

    // Native auth wrapper
    var native_auth =  {

        login: function() {
            window.login();
        },

        logout: function() {
            window.logout();
        },

        profile: function() {
            window.editUser();
        }
    }


    // Login module auth
    var  login_module_auth =  {

        login: function() {
            //disableButton("buttonLogin");
            this.popup.open('login');
        },

        callbackLogin: function(result) {
            this.popup.close();
            if(result.success) {
                //enableButton("buttonLogin");
                logUser(result.user);
                warningUsers(result.schoolUsers);
                return;
            }
            $("#login_error").html(i18n.t(result.message));
        },

        logout: function() {
            this.popup.open('logout');
        },

        callbackLogout: function() {
            this.popup.close();
            window.location.reload();
        },

        profile: function() {
            //disableButton("buttonEditUser");
            this.popup.open('profile');
        },

        callbackProfile: function(result) {
            //enableButton("buttonEditUser");
            this.popup.close();
            if(result.success) {
                endEditUser(result.user.ID, result.user);
            }
        }
    }


    login_module_auth.popup = {

        win: null,

        open: function(action) {
            var url = config.base_url + 'login_module/popup_redirect.php?action=' + action;
            this.win = window.open(url, "LoginModule", "menubar=no, status=no, scrollbars=yes, menubar=no, width=800, height=600");
            this.win.focus();
        },

        close: function() {
            this.win && this.win.close();
            this.win = null;
        }
    }

    window.auth = config.native_auth ? native_auth : login_module_auth;
}