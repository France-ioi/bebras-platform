import UI from '.'
import contests from '../contests';
import contest from '../contest';
import StackStorage from '../new/StackStorage'
import CachedRequest from '../new/CachedRequest'
import preloader from '../common/Preloader';

var storage = StackStorage('preloaded_codes')
var GroupRequest = CachedRequest('group')
var GroupConfirmationRequest = CachedRequest('group_confirmation')
var ContestsRequest = CachedRequest('contests')

export default {


	init () {
        window.preloadPageAddCode = this.preloadPageAddCode.bind(this);
        window.preloadPageNavBack = this.preloadPageNavBack.bind(this);
        window.preloadPageClear = this.clear.bind(this);
	},

    load(data, eventListeners) {
        this.refresh();
        $('#preloadPage').show();
    },

    unload() {
        $('#preloadPage').hide();
    },


    preloadPageNavBack() {
        this.unload();
        UI.HomePage.load();
    },

    preloadPageAddCode() {
        var code = $('#preloadPageCode').val().trim();
        $('#preloadPageCode').val('');
        if(code == '' || storage.exist(code)) return;

        $('#preloadPageCodeBtn').attr('disabled', true);
        var params = {
            controller: "Auth",
            action: "checkPassword",
            password: code,
            getTeams: false,
            commonJsVersion: app.commonJsVersion,
            timestamp: window.config.timestamp,
            commonJsTimestamp: app.commonJsTimestamp
        };
        var self = this;
        GroupRequest.send(
            params,
            function(data) {
                if(!data.success) {
                    $('#preloadCodeResult').text(data['message'] || i18n.t("invalid_code"));
                    $('#preloadPageCodeBtn').attr('disabled', false);
                    return;
                }

                var params = {
                    controller: "Group",
                    action: "checkConfirmationInterval",
                    code: code
                }
                GroupConfirmationRequest.send(params, function(data) {
                    var params = {
                        controller: "Contests",
                        action: "getData"
                    }
                    ContestsRequest.send(params, function(data) {
                        self.preloadContestsFiles(data.contests);
                        self.preloadContests(data.results);
                        $('#preloadPageCodeBtn').attr('disabled', false);
                        storage.push(code);
                        self.refresh();
                    })
                });
            }
        );
    },


    preloadContestsFiles(contests) {
        function preloadFolder(folder) {
            preloader.check(folder, function(exists) {
                if(exists) return;
                contest.getFilesList(folder, function(list) {
                    preloader.add(list, function(success) {})
                })
            });
        }
        for(var i=0; i<contests.practice.length; i++) {
            preloadFolder(contests.practice[i].folder);
        }
        for(var i=0; i<contests.open.length; i++) {
            preloadFolder(contests.open[i].folder);
        }
    },


    preloadContests(results) {
        for(var ID in results) {
            if(!('password' in results[ID])) {
                continue;
            }
            var params = {
                controller: "Auth",
                action: "checkPassword",
                password: results[ID].password,
                getTeams: false,
                commonJsVersion: app.commonJsVersion,
                timestamp: window.config.timestamp,
                commonJsTimestamp: app.commonJsTimestamp
            };
            GroupRequest.send(
                params,
                function(data) {}
            );
        }
    },


    refresh() {
        var codes = storage.all();
        if(!codes.length) {
            $('#preloadPageData').hide();
            $('#preloadedCodesList').empty();
            return;
        }
        $('#preloadedCodesList').text(codes.join(', '));
        $('#preloadPageData').show();
    },


    clear() {
        if(!confirm(i18n.t('are_you_sure'))) {
            return;
        }
        GroupRequest.clear();
        GroupConfirmationRequest.clear();
        ContestsRequest.clear();
        storage.clear();
        this.refresh();
    }

};