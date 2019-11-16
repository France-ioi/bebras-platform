/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require('./polyfills/ObjectKeys.js');
import UI from './components';
import url from './common/ParseURL';
import questionIframe from './common/QuestionIframe';
import session from './common/Session';
import debugPanel from './debug/Panel';


window.app = {

    // SID is initialized to the empty string so that its encoding in an AJAX query
    // is predictable (rather than being either '' or 'null').
    SID: '',

    // *** Version of this file
    // It will be checked against config.php's minimumCommonJsVersion; increment
    // this version on each important change, and modify config.php accordingly.
    commonJsVersion: 2,

    // Timestamp of common.js initial loading, sent on checkPassword too
    commonJsTimestamp: Date(),

    // Redirections from Scratch contests to Blockly versions when user is on a
    // mobile device
    scratchToBlocklyContestID: {
        //  "223556559616198459": "40284639530086786", // 2019.1 white
        "604034698343183586": "503470753157869958", // 2019.1 yellow
        "719201791586950565": "727677046248069693", // 2019.1 orange
        "714570819714244963": "185545119426515177" // 2019.1 green
    },

    contestID: null,
    contestFolder: null,
    fullFeedback: null,
    nextQuestionAuto: null,
    nbUnlockedTasksInitial: null,
    newInterface: false,
    customIntro: null,
    teamID: 0,
    teamPassword: "",
    questionsData: {},
    questionsKeyToID: {},
    questionsToGrade: [],
    scores: {},
    questionUnlockedLevels: {},
    bonusScore: 0,
    ffTeamScore: 0,
    answersToSend: {},
    answers: {},
    defaultAnswers: {},
    lastSelectQuestionTime: 0,
    currentQuestionKey: "",
    hasAnsweredQuestion: false,
    hasDisplayedContestStats: false,
    delaySendingAttempts: 60000,
    groupCheckedData: null,
    t: i18n.t,
    user: false,

    /*
     * Initialisation
     * Cleans up identification form (to avoid auto-fill for some browser)
     * Inits ajax error handler
     * Loads current session or list of public groups
     */
    init: function() {
        for (var contestant = 1; contestant <= 2; contestant++) {
            UI.PersonalDataForm.initContestant(contestant);
        }
        this.initErrorHandler();
        session.load();
        // Load initial tab according to parameters
        var params = url.getPageParameters();
        if (params.tab) {
            window.selectMainTab(params.tab);
        }
    },


    initErrorHandler: function() {
        // TODO: call on document for jquery 1.8+
        $("body").ajaxError(function(e, jqxhr, settings, exception) {
            if (settings.url == "answer.php") {
                answers.failedSendingAnswers();
            } else {
                if (exception === "" || exception === "Unknown") {
                    if (confirm(i18n.t("server_not_responding_try_again"))) {
                        $.ajax(settings);
                    }
                } else if (exception === "timeout") {
                    UI.ContestEndPage.showError(
                        i18n.t("exception") +
                            exception +
                            "<br/><br/>" +
                            i18n.t("contest_load_failure")
                    );
                } else {
                    UI.ContestEndPage.showError(
                        i18n.t("exception") +
                            exception +
                            "<br/><br/>" +
                            i18n.t("server_output") +
                            "<br/>" +
                            jqxhr.responseText
                    );
                }
            }
        });
    },

    setUser: function(registrationData) {
        this.user = registrationData ? {
            firstName: registrationData.firstName,
            lastName: registrationData.lastName,
            guest: registrationData.guest
        } : false;
    }

}


$(document).on("ready", function() {
    var teamParam = url.getParameterByName("team");
    if (teamParam !== "") {
        /* remove team from url to avoid restarting after a reload */
        var oldUrl = document.location.href;
        var newUrl = oldUrl.replace(/(team=[^&]*)/, "");
        window.history.pushState("", document.title, newUrl);
        group.checkGroupFromCode("CheckGroup", teamParam, false, false);
    } else {
        app.init();
    }
    if(__DEBUG__) {
        debugPanel.init();
    }
    window.addEventListener("resize", questionIframe.onBodyResize);
    UI.ContestHeader.checkFullscreen();


    //TODO: debug code, remove
window.checkGroup()
/*
setTimeout(function() {
    window.startPracticeByID('135774099345825017')
}, 400);
*/
});