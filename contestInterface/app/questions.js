import UI from './components';
import Utils from './common/Utils';
import tracker from './common/Tracker';
import timeManager from './common/TimeManager';
import questionIframe from './common/QuestionIframe';
import drawStars from './common/DrawStars';
import logError from './common/LogError';

// Questions tools
function getSortedQuestionIDs(questionsData) {
    var questionsByOrder = {};
    var orders = [];
    var order;
    for (var questionID in questionsData) {
        var questionData = questionsData[questionID];
        order = parseInt(questionData.order, 10);
        if (questionsByOrder[order] === undefined) {
            questionsByOrder[order] = [];
            orders.push(order);
        }
        questionsByOrder[order].push(questionID);
    }
    orders.sort(function(order1, order2) {
        if (order1 < order2) {
            return -1;
        }
        return 1;
    });
    var sortedQuestionsIDs = [];
    // teamID is a string representing a very long integer, let's take only the 5 last digits:
    var baseOrderKey = parseInt(app.teamID.slice(-5), 10);
    for (var iOrder = 0; iOrder < orders.length; iOrder++) {
        order = orders[iOrder];
        questionsByOrder[order].sort(function(id1, id2) {
            if (id1 < id2) return -1;
            return 1;
        });
        var shuffledOrder = Utils.getShuffledOrder(
            questionsByOrder[order].length,
            baseOrderKey + iOrder
        );
        for (var iSubOrder = 0; iSubOrder < shuffledOrder.length; iSubOrder++) {
            var subOrder = shuffledOrder[iSubOrder];
            sortedQuestionsIDs.push(questionsByOrder[order][subOrder]);
        }
    }
    fillNextQuestionID(sortedQuestionsIDs);
    return sortedQuestionsIDs;
}


function fillNextQuestionID(sortedQuestionsIDs) {
    var prevQuestionID = "0";
    for (var iQuestion = 0; iQuestion < sortedQuestionsIDs.length; iQuestion++ ) {
        var questionID = sortedQuestionsIDs[iQuestion];
        if (prevQuestionID !== "0") {
            app.questionsData[prevQuestionID].nextQuestionID = questionID;
        }
        prevQuestionID = questionID;
    }
    app.questionsData[prevQuestionID].nextQuestionID = "0";
}





function selectQuestion(questionID, clicked, noLoad) {
    // Prevent double-click until we fix the issue with timeouts
    var curTime = new Date().getTime();
    if (curTime - app.lastSelectQuestionTime < 1000) {
        if (curTime - app.lastSelectQuestionTime < 0) {
            // in case the computer time changes during the contest, we reset lastSelectQuestionTime, to make sure the user doesn't get stuck
            app.lastSelectQuestionTime = curTime;
        } else {
            return;
        }
    }
    app.lastSelectQuestionTime = curTime;
    $("body").scrollTop(0);
    try {
        if (document.getSelection) {
            var selection = document.getSelection();
            if (selection && selection.removeAllRanges) {
                selection.removeAllRanges();
            }
        }
    } catch (err) {}
    var questionData = app.questionsData[questionID];
    questionData.visited = true;
    var questionKey = questionData.key;

    if (app.newInterface) {
        UI.GridView.unload();
        UI.OldListView.updateButtonCloseVisibility(false);
        UI.TaskFrame.load();
        // $(".questionIframeLoading").show();
        UI.ContestHeader.updateButtonReturnListEnability(false);
    }

    var nextStep = function() {
        tracker.trackData({
            dataType: "selectQuestion",
            teamID: app.teamID,
            questionKey: questionKey,
            clicked: clicked
        });
        var questionName = questionData.name.replace("'", "&rsquo;").split("[")[0];
        var minScore = questionData.minScore;
        var maxScore = questionData.maxScore;
        var noAnswerScore = questionData.noAnswerScore;
        UI.GridView.updateNextStep(questionKey, app.currentQuestionKey);
        if (!app.fullFeedback) {
            UI.OldListView.updateQuestionPoints(
                "<table class='questionScores' cellspacing=0><tr><td>" +
                    i18n.t("no_answer") +
                    "</td><td>" +
                    i18n.t("bad_answer") +
                    "</td><td>" +
                    i18n.t("good_answer") +
                    "</td></tr>" +
                    "<tr><td><span class='scoreNothing'>" +
                    noAnswerScore +
                    "</span></td>" +
                    "<td><span class='scoreBad'>" +
                    minScore +
                    "</span></td>" +
                    "<td><span class='scoreGood'>+" +
                    maxScore +
                    "</span></td></tr></table>"
            );
        }
        UI.OldContestHeader.updateQuestionTitle(questionName);
        if (app.newInterface) {
            drawStars(
                "questionStars",
                4,
                24,
                getQuestionScoreRate(questionData),
                "normal",
                getNbLockedStars(questionData)
            ); // stars under icon on main page
            drawStars(
                "questionIframeStars",
                4,
                24,
                getQuestionScoreRate(questionData),
                "normal",
                getNbLockedStars(questionData)
            ); // stars under icon on main page
        }
        app.currentQuestionKey = questionKey;

        if (!questionIframe.initialized) {
            questionIframe.initialize();
        }
        var taskViews = { task: true };
        if (questionIframe.gradersLoaded || app.fullFeedback) {
            taskViews.grader = true;
        }
        if (timeManager.isContestOver()) {
            taskViews.solution = true;
        }

        if (!noLoad) {
            questionIframe.load(taskViews, questionKey, function() {});
        }
    };

    if (questionIframe.task) {
        questionIframe.task.getAnswer(
            function(answer) {
                if (!timeManager.isContestOver() &&
                    (answer !==
                        app.defaultAnswers[questionIframe.questionKey] ||
                        typeof app.answers[questionIframe.questionKey] !=
                            "undefined")
                ) {
                    if (app.fullFeedback) {
                        platform.validate(
                            "stay",
                            function() {
                                nextStep();
                            },
                            function() {
                                logError(arguments);
                            }
                        );
                    } else if (
                        (typeof app.answers[questionIframe.questionKey] == "undefined" ||
                            app.answers[questionIframe.questionKey] != answer) &&
                        !confirm(i18n.t("confirm_leave_question"))
                    ) {
                        return;
                    } else {
                        nextStep();
                    }
                } else {
                    nextStep();
                }
            },
            function() {
                logError(arguments);
                nextStep();
            }
        );
    } else {
        nextStep();
    }
}



/*
* Generates the html that displays the list of questions on the left side of the page
*/

// local
function fillListQuestions(sortedQuestionIDs, questionsData) {
    UI.OldListView.fillListQuestions(
        sortedQuestionIDs,
        questionsData,
        app.fullFeedback,
        app.scores
    );
    if (app.fullFeedback) {
        UI.OldContestHeader.updateCssFullfeedback();
        UI.OldListView.updateCss();
    }
}



// setupContest
function fillListQuestionsNew(sortedQuestionIDs, questionsData) {
    UI.OldListView.fillListQuestionsNew(
        sortedQuestionIDs,
        questionsData,
        window.contestsRoot,
        app.contestFolder
    );
    updateUnlockedLevels(sortedQuestionIDs);
    for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
        var questionData = questionsData[sortedQuestionIDs[iQuestionID]];
        drawStars(
            "score_" + questionData.key,
            4,
            20,
            getQuestionScoreRate(questionData),
            "normal",
            getNbLockedStars(questionData)
        ); // stars under question icon
    }
    UI.ContestEndPage.showFooter();
}


function getQuestionScoreRate(questionData) {
    if (app.scores[questionData.key] !== undefined) {
        return app.scores[questionData.key].score / questionData.maxScore;
    }
    return 0;
}


function getNbLockedStars(questionData) {
    // TODO (here and everywhere in the code) : support variable number of
    // levels and hence of unlockedLevels
    if (app.questionUnlockedLevels[questionData.key] != 0) {
        return 3 - app.questionUnlockedLevels[questionData.key];
    }
    return 4;
}



function updateUnlockedLevels(sortedQuestionIDs, updatedQuestionKey, contestEnded) {
    if (!app.newInterface) {
        return;
    }
    var epsilon = 0.001;
    var nbTasksUnlocked = [app.nbUnlockedTasksInitial, 0, 0];
    var prevQuestionUnlockedLevels = {};
    var iQuestionID, questionKey;
    for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
        questionKey = app.questionsData[sortedQuestionIDs[iQuestionID]].key;
        prevQuestionUnlockedLevels[questionKey] = app.questionUnlockedLevels[questionKey];
        app.questionUnlockedLevels[questionKey] = 4;
        nbTasksUnlocked[2]++;
    }

    for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
        var questionData = app.questionsData[sortedQuestionIDs[iQuestionID]];
        questionKey = questionData.key;
        for (var iLevel = 0; iLevel < 3; iLevel++) {
            if (nbTasksUnlocked[iLevel] > 0) {
                if (app.questionUnlockedLevels[questionKey] < iLevel + 1) {
                    app.questionUnlockedLevels[questionKey] = iLevel + 1;
                }
                nbTasksUnlocked[iLevel]--;
            }
        }
        UI.GridView.unlockLevel(questionKey, app.questionUnlockedLevels[questionKey] == 0);

        if (questionKey == updatedQuestionKey || prevQuestionUnlockedLevels[questionKey] != app.questionUnlockedLevels[questionKey]) {
            var nbLocked = getNbLockedStars(questionData);
            var scoreRate = getQuestionScoreRate(questionData);
            drawStars(
                "score_" + questionData.key,
                4,
                20,
                scoreRate,
                "normal",
                nbLocked
            ); // stars under icon on main page
            if (questionKey == updatedQuestionKey) {
                drawStars(
                    "questionStars",
                    4,
                    24,
                    scoreRate,
                    "normal",
                    nbLocked
                ); // stars in question title
                drawStars(
                    "questionIframeStars",
                    4,
                    24,
                    scoreRate,
                    "normal",
                    nbLocked
                ); // stars in question title
            }
        }
    }
}




export default {
    getSortedQuestionIDs,
    selectQuestion,
    fillListQuestions,
    fillListQuestionsNew,
    getQuestionScoreRate,
    getNbLockedStars,
    updateUnlockedLevels
}