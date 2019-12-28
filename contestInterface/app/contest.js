import UI from './components';
import questionIframe from './common/QuestionIframe';
import Utils from './common/Utils';
import scores from './scores';
import group from './group';
import answers from './answers';
import questions from './questions';
import timeManager from './common/TimeManager';
import tracker from './common/Tracker';
import Loader from './common/Loader';
import metaViewport from "./common/MetaViewport";
import fetch from './common/Fetch';
import logError from './common/LogError';
import base64 from './common/Base64';

var contestShowSolutions;
var contestVisibility; // TODO: value not used
var contestOpen; // TODO: value not used

// local
function startContestTime(data) {
    fetch(
        "data.php",
        { SID: app.SID, controller: "Timer", action: "start", teamID: app.teamID },
        function(dataStartTimer) {
            var contestData = {
                ended: dataStartTimer.ended,
                remainingSeconds: dataStartTimer.remainingSeconds,
                questionsData: data.questionsData,
                scores: data.scores,
                answers: data.answers,
                isTimed: data.isTimed,
                teamPassword: data.teamPassword
            };
            setupContest(contestData);
        }
    );
}

/*
 * Setup of the contest when the group has been selected, contestants identified,
 * the team's password given to the students, and the images preloaded
 */
// local
function setupContest(data) {
    //console.log(data);
    app.teamPassword = data.teamPassword;
    app.questionsData = data.questionsData;

    var questionKey;
    // Reloads previous scores to every question
    app.scores = {};
    for (var questionID in data.scores) {
        if (questionID in app.questionsData) {
            questionKey = app.questionsData[questionID].key;
            app.scores[questionKey] = {
                score: data.scores[questionID],
                maxScore: app.questionsData[questionID].maxScore
            };
        }
    }
    if (app.fullFeedback) {
        scores.computeFullFeedbackScore();
    }

    // Determines the order of the questions, and displays them on the left
    var sortedQuestionIDs = questions.getSortedQuestionIDs(app.questionsData);
    if (app.newInterface) {
        questions.fillListQuestionsNew(sortedQuestionIDs, app.questionsData);
        if (app.customIntro != null && app.customIntro != "") {
            UI.GridView.updateQuestionListIntro(app.customIntro);
        }
    } else {
        questions.fillListQuestions(sortedQuestionIDs, app.questionsData);
    }
    questions.updateUnlockedLevels(sortedQuestionIDs, null, data.ended);

    // Defines function to call if students try to close their browser or tab
    window.onbeforeunload = function() {
        return i18n.t("warning_confirm_close_contest");
    };

    // Map question key to question id array
    for (questionID in app.questionsData) {
        app.questionsKeyToID[app.questionsData[questionID].key] = questionID;
    }

    // Displays the first question
    var questionData = app.questionsData[sortedQuestionIDs[0]];
    // We don't want to start the process of selecting a question, if the grading is going to start !

    if (!app.newInterface) {
        questions.selectQuestion(
            sortedQuestionIDs[0],
            false,
            data.ended && !app.fullFeedback
        );
    }

    // Reloads previous answers to every question
    app.answers = {};
    for (questionID in data.answers) {
        if (questionID in app.questionsData) {
            questionKey = app.questionsData[questionID].key;
            app.answers[questionKey] = data.answers[questionID];
            answers.markAnswered(questionKey, app.answers[questionKey]);
            app.hasAnsweredQuestion = true;
        }
    }
    UI.OldListView.showButtonClose();

    // Starts the timer
    timeManager.init(
        data.isTimed,
        data.remainingSeconds,
        data.ended,
        function() {
            closeContest(i18n.t("contest_is_over"));
        },
        function() {
            closeContest("<b>" + i18n.t("time_is_up") + "</b>");
        }
    );
}

/*
 * Loads contest's css and js files,
 * then preloads all contest images
 * then gets questions data from the server if groupPassword and teamID are valid,
 * then loads contest html file
 * then calls setupContest
 * if temID/password are incorrect, this means we're in the middle of re-login after an interruption
 * and the password provided is incorrect
 */
function loadContestData(_contestID, _contestFolder, _groupPassword) {
    if (_contestID === null) {
        _contestID = app.contestID;
    }
    if (_contestFolder === null) {
        _contestFolder = app.contestFolder;
    }
    if (_groupPassword === null) {
        _groupPassword = groupPassword;
    }
    $("#browserAlert").hide();
    UI.LoadingPage.load();
    questionIframe.initialize(function() {
        if (app.fullFeedback) {
            fetch(
                "graders.php",
                {
                    SID: SID,
                    ieMode: window.ieMode,
                    teamID: app.teamID,
                    groupPassword: _groupPassword,
                    p: url.getParameterByName("p")
                },
                function(data) {
                    if (data.status === "success" &&(data.graders || data.gradersUrl)) {
                        questionIframe.gradersLoaded = true;
                        UI.GridView.updateGradersContent(data);
                    }
                    if (data.status == "success") {
                        app.bonusScore = parseInt(data.bonusScore);
                    }
                }
            );
        }


        // The callback will be used by the task

        function loadData() {
            UI.MainHeader.unload();
            UI.OldContestHeader.updateDivQuestionsVisibility(true);
            if (app.fullFeedback) {
                UI.OldContestHeader.updateFeedbackVisibility(true);
            }
            UI.TaskFrame.showQuestionIframe();
            UI.LoadingPage.unload();

            fetch(
                "data.php",
                {
                    SID: app.SID,
                    controller: "Contest",
                    action: "loadData",
                    groupPassword: _groupPassword,
                    teamID: app.teamID
                },
                function(data) {
                    if (!data.success) {
                        UI.MainHeader.load();
                        UI.TrainingContestSelection.load();
                        UI.RestartContestForm.updateReloginResult(i18n.t("invalid_password"));
                        UI.OldContestHeader.updateDivQuestionsVisibility(false);
                        UI.OldContestHeader.updateFeedbackVisibility(false);
                        //UI.NavigationTabs.load();
                        UI.HomePage.load();
                        Utils.enableButton("buttonRelogin");
                        return;
                    }
                    UI.TrainingContestSelection.unload();
                    //UI.NavigationTabs.unload();
                    UI.HomePage.unload();


                    function oldLoader() {
                        $.get(
                            window.contestsRoot + "/" + _contestFolder + "/contest_" + _contestID + ".html",
                            function(content) {
                                UI.GridView.updateQuestionContent(content);
                                startContestTime(data);
                            }
                        );
                    }


                    function newLoader() {
                        var loader = new Loader(
                            window.contestsRoot + "/" + _contestFolder + "/",
                            UI.OldListView.log_fn
                        );
                        loader
                            .run()
                            .done(function(content) {
                                UI.GridView.updateQuestionContent(content);
                                startContestTime(data);
                            })
                            .fail(function() {
                                oldLoader();
                            });
                    }

                    // XXX: select loader here
                    if(window.config.contestLoaderVersion === '2') {
                        //UI.GridView.updateQuestionContent('');
                        startContestTime(data);
                    } else {
                        newLoader();
                    }

                    //UI.GridView.updateQuestionContent('updateQuestionContent');
                    //startContestTime(data);
                }
            );
        }

        if(questionIframe.iframe && questionIframe.iframe.contentWindow.ImagesLoader) {
            questionIframe.iframe.contentWindow.ImagesLoader.setCallback(loadData);
            questionIframe.iframe.contentWindow.ImagesLoader.preload(_contestFolder);
        } else {
            loadData();
        }

    });
};


//related to UI.SubcontestSelectionInterface
function selectContest(ID) {
    UI.SubcontestSelectionInterface.selectContest(function() {
        $(this).dequeue();
        if (window.config.browserIsMobile && typeof app.scratchToBlocklyContestID[ID] != "undefined") {
            alert(i18n.t("browser_redirect_scratch_to_blockly"));
            ID = app.scratchToBlocklyContestID[ID];
            UI.SubcontestSelectionInterface.selectedLanguage = "blockly";
            UI.Breadcrumbs.updateBreadcrumb();
        }
        var contest = window.getContest(ID);
        app.contestID = ID;
        app.contestFolder = contest.folder;
        app.customIntro = contest.customIntro;
        app.groupCheckedData.data.allowTeamsOfTwo = contest.allowTeamsOfTwo;
        app.groupCheckedData.data.askParticipationCode = contest.askParticipationCode;
        group.groupWasChecked(
            app.groupCheckedData.data,
            app.groupCheckedData.curStep,
            app.groupCheckedData.groupCode,
            app.groupCheckedData.getTeams,
            app.groupCheckedData.isPublic,
            app.contestID
        );
    });
};





function initContestData(data, newContestID) {
    if (newContestID == null) {
        app.contestID = data.contestID;
        app.contestFolder = data.contestFolder;
        app.customIntro = UI.GridView.updateNGetCustomIntro(data.customIntro);
    }
    UI.MainHeader.updateTitle(data.contestName);
    app.fullFeedback = parseInt(data.fullFeedback);
    app.nextQuestionAuto = parseInt(data.nextQuestionAuto);
    app.nbUnlockedTasksInitial = parseInt(data.nbUnlockedTasksInitial);
    app.newInterface = !!parseInt(data.newInterface);
    app.customIntro = UI.GridView.updateNGetCustomIntro(data.customIntro);
    contestOpen = !!parseInt(data.contestOpen);
    contestVisibility = data.contestVisibility;
    contestShowSolutions = !!parseInt(data.contestShowSolutions);
    if (app.newInterface) {
        UI.TaskFrame.showNewInterface();
        UI.OldContestHeader.unload();
        UI.ContestHeader.load();
        window.backToList(true);
    } else {
        UI.TaskFrame.showOldInterface();
        UI.ContestHeader.unload();
        UI.OldContestHeader.load();
    }
}



/*
* Called when a student clicks on the button to stop before the timer ends
*/
function tryCloseContest() {
    var remainingSeconds = timeManager.getRemainingSeconds();
    var nbMinutes = Math.floor(remainingSeconds / 60);
    if (nbMinutes > 1) {
        if (!confirm(i18n.t("time_remaining_1") + nbMinutes + i18n.t("time_remaining_2"))) {
            return;
        }
        if (!confirm(i18n.t("confirm_stop_early"))) {
            return;
        }
    }
    closeContest(i18n.t("thanks_for_participating"));
}




/*
    * Called when the contest is over, whether from the student's action,
    * or the timer is expired (either right now or was expired before being loaded
    *
    * If some answers are still waiting to be sent to the server, displays a message that
    * says to wait for 20 seconds. If the answers could still not be send, end the contest
    * anyway. finalCloseContest will offer a backup solution, but the app will keep trying
    * to send them automatically as long as the page is stays opened.
    */
function closeContest(message) {
    app.hasDisplayedContestStats = true;
    Utils.disableButton("buttonClose");
    Utils.disableButton("buttonCloseNew");
    $("body").removeClass("autoHeight");
    metaViewport.toggle(false);
    UI.OldContestHeader.updateDivQuestionsVisibility(false);
    UI.TaskFrame.hideQuestionIframe();
    if (questionIframe.task) {
        questionIframe.task.unload(
            function() {
                doCloseContest(message);
            },
            function() {
                logError(arguments);
                doCloseContest(message);
            }
        );
    } else {
        doCloseContest(message);
    }
}

function doCloseContest(message) {
    UI.MainHeader.load();
    UI.ContestEndPage.load();
    if ($.isEmptyObject(app.answersToSend)) {
        tracker.trackData({ send: true });
        tracker.disabled = true;
        finalCloseContest(message);
    } else {
        UI.ContestEndWaitingPage.load();
        app.delaySendingAttempts = 10000;
        answers.sendAnswers();
        setTimeout(function() {
            finalCloseContest(message);
        }, 22000);
    }
}

/*
    * Called when a team's participation is over
    * For a restricted contest, if shows a message reminding the students of
    * their team password, and suggesting them to go learn more on france-ioi.org;
    * if some answers have not been sent due to connexion problem, displays an
    * encoded version of the answers, and asks students to send that text to us
    * by email whenever they can.
    * If the contest is not resticted, show the team's scores
    */
function finalCloseContest(message) {
    timeManager.stopNow();
    fetch(
        "data.php",
        {
            SID: app.SID,
            controller: "Contest",
            action: "close",
            teamID: app.teamID,
            teamPassword: app.teamPassword
        },
        function() {}
    ).always(function() {
        window.onbeforeunload = function() {};
        if (!contestShowSolutions) {
            UI.ContestEndWaitingPage.unload();
            UI.ContestEndPage.updateClosedMessage(message);
            var listAnswers = [];
            for (var questionID in app.answersToSend) {
                var answerObj = app.answersToSend[questionID];
                listAnswers.push([questionID, answerObj.answer]);
            }
            if (listAnswers.length !== 0) {
                var encodedAnswers = base64.encode(
                    JSON.stringify({ pwd: app.teamPassword, ans: listAnswers })
                );
                UI.ContestQuestionRecoveryPage.updateEncodedAnswers(encodedAnswers);
                UI.ContestQuestionRecoveryPage.load();
                // Attempt to send the answers payload to a backup server by adding
                // an image to the DOM.
                var img = document.createElement("img");
                $("body").append(
                    $("<img>", {
                        width: 1,
                        height: 1,
                        class: "hidden",
                        src: "http://castor.epixode.fr/?q=" + encodeURIComponent(encodedAnswers)
                    })
                );
            }
            if(app.user) {
                UI.ContestEndReminder.showNav();
            } else {
                UI.ContestEndReminder.updateTeamPassword(app.teamPassword);
            }
            if (app.fullFeedback) {
                UI.ContestEndReminder.updateTeamScore(app.ffTeamScore);
                UI.ContestEndReminder.showScoreReminder();
            }
            UI.ContestEndReminder.load();
        } else {
            UI.OldContestHeader.updateDivQuestionsVisibility(false);
            UI.TaskFrame.hideQuestionIframe();
            UI.LoadingPage.load();
            UI.MainHeader.load();

            scores.showScoresHat();
            if (app.newInterface) {
                var sortedQuestionIDs = questions.getSortedQuestionIDs(app.questionsData);
                questions.updateUnlockedLevels(sortedQuestionIDs, null, true);
                UI.GridView.updateQuestionListIntro("<p>" + i18n.t("check_score_detail") + "</p>");
                UI.GridView.resetHeaderTime();
            }
        }
    });
}



function get(ID, callback) {
    fetch(
        "data.php",
        {
            controller: "Contest",
            action: "get",
            SID: app.SID,
            ID: ID
        },
        function(data) {
            callback && callback(data.contest);
        }
    );
}


function getFilesListOld(folder, callback) {
    fetch(
        "data.php",
        {
            SID: app.SID,
            controller: "Contest",
            action: "getFilesList",
            folder: folder
        },
        function(data) {
            callback && callback(data.list);
        }
    );
}

function getFilesListNew(folder, callback) {
    var url = window.contestsRoot + '/' + folder + '.v2/index.json';
    $.getJSON(url, callback);
}


function getFilesList(folder, callback) {
    if(window.config.contestLoaderVersion === '2') {
        getFilesListNew(folder, callback);
    } else {
        // support for old local stored contests only, do we need s3 support here?
        getFilesListOld(folder, callback);
    }
}


export default {
    selectContest,
    initContestData,
    loadContestData,
    closeContest,
    tryCloseContest,
    get,
    getFilesList
}