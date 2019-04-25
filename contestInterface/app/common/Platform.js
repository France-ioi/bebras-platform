import UI from '../components';
import TimeManager from "./TimeManager";
import logActivity from './LogActivity';
import logError from './LogError';
import questionIframe from './QuestionIframe';
import answers from '../answers';
import scores from '../scores';
import questions from '../questions';

/**
 * The platform object as defined in the Bebras API specifications
 *
 * @type type
 */
var platform = {

    updateHeight: function(height, success, error) {
        this.updateDisplay({ height: height }, success, error);
    },

    updateDisplay: function(data, success, error) {
        if (data.height) {
            questionIframe.setHeight(data.height);
        }
        if (success) {
            success();
        }
    },

    openUrl: function(url) {
        // not used here
    },

    showView: function(views) {
        // not used here
    },

    askHint: function(numHint) {
        // not used here
    },

    getTaskParams: function(key, defaultValue, success, error) {
        var questionData =
            app.questionsData[app.questionsKeyToID[questionIframe.questionKey]];
        var unlockedLevels = 1;
        if (app.questionUnlockedLevels[questionIframe.questionKey] != null) {
            unlockedLevels =
                app.questionUnlockedLevels[questionIframe.questionKey];
        }
        var res = {
            minScore: questionData.minScore,
            maxScore: questionData.maxScore,
            noScore: questionData.noAnswerScore,
            randomSeed: app.teamID,
            options: questionData.options,
            pointsAsStars: app.newInterface,
            unlockedLevels: unlockedLevels
        };
        if (key) {
            if (key !== "options" && key in res) {
                res = res[key];
            } else if (res.options && key in res.options) {
                res = res.options[key];
            } else {
                res = typeof defaultValue !== "undefined" ? defaultValue : null;
            }
        }
        success(res);
    },

    validate: function(mode, success, error) {
        this.validateWithQuestionKey(
            mode,
            success,
            error,
            questionIframe.questionKey
        );
    },

    validateWithQuestionKey: function(mode, success, error, questionKey) {
        if (TimeManager.isContestOver()) {
            alert(i18n.t("contest_closed_answers_readonly"));
            if (error) {
                error();
            } else if (success) {
                success();
            }
            return;
        }

        if (mode == "nextImmediate") {
            platform.nextQuestion(0);
        }

        // Store the answer
        questionIframe.task.getAnswer(function(answer) {
            if (mode == "cancel") {
                answer = "";
            }
            var questionID = app.questionsKeyToID[questionKey];

            if (mode == "log") {
                logActivity(app.teamID, questionID, "attempt", answer);
                return;
            }

            var questionData = app.questionsData[questionID];
            if (app.fullFeedback) {
                questionIframe.task.gradeAnswer(
                    answer,
                    null,
                    function(score, message) {
                        logActivity(
                            app.teamID,
                            questionID,
                            "submission",
                            answer,
                            score
                        );
                        if (score < questionData.maxScore) {
                            mode = "stay";
                        }
                        if (answer != app.defaultAnswers[questionKey] || typeof app.answers[questionKey] != "undefined") {
                            var prevScore = 0;
                            if (typeof app.scores[questionKey] != "undefined") {
                                prevScore = app.scores[questionKey].score;
                            }
                            if (typeof app.answers[questionKey] == "undefined" ||
                                (answer != app.answers[questionKey] && score >= prevScore)
                            ) {
                                app.scores[questionKey] = {
                                    score: score,
                                    maxScore: questionData.maxScore
                                };
                                answers.submitAnswer(questionKey, answer, score);
                                app.answers[questionKey] = answer;

                                questions.updateUnlockedLevels(
                                    questions.getSortedQuestionIDs(app.questionsData),
                                    questionKey
                                );
                                if (!app.newInterface) {
                                    UI.OldListView.updateScore(
                                        questionData.key,
                                        score,
                                        questionData.maxScore
                                    );
                                }
                            }
                        }
                        scores.computeFullFeedbackScore();
                        platform.continueValidate(mode);
                        if (success) {
                            success();
                        }
                    },
                    logError
                );
            } else {
                answers.submitAnswer(questionKey, answer, null);
                app.answers[questionKey] = answer;
                platform.continueValidate(mode);
                if (success) {
                    success();
                }
            }
            //         if (success) {success();}
        }, logError);
    },

    firstNonVisitedQuestion: function(delay) {
        function timeoutFunFactory(questionID) {
            return function() {
                questions.selectQuestion(questionID, false);
            };
        }
        var sortedQuestionIDs = questions.getSortedQuestionIDs(app.questionsData);
        for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
            var questionID = sortedQuestionIDs[iQuestionID];
            var questionData = app.questionsData[questionID];
            if (app.questionUnlockedLevels[questionData.key] > 0 && !questionData.visited ) {
                setTimeout(timeoutFunFactory(questionID), delay);
                return;
            }
        }
        window.backToList();
    },

    nextQuestion: function(delay) {
        if (app.newInterface) {
            this.firstNonVisitedQuestion(delay);
            return;
        }
        var questionData = app.questionsData[app.questionsKeyToID[questionIframe.questionKey]];
        var nextQuestionID = questionData.nextQuestionID;
        // Next question
        if (nextQuestionID !== "0") {
            setTimeout(function() {
                questions.selectQuestion(nextQuestionID, false);
            }, delay);
        } else {
            setTimeout(function() {
                alert(i18n.t("last_question_message"));
            }, delay);
        }
    },

    continueValidate: function(mode) {
        if (!app.nextQuestionAuto) {
            return;
        }
        var questionData =
            app.questionsData[app.questionsKeyToID[questionIframe.questionKey]];
        var nextQuestionID = questionData.nextQuestionID;
        if (!app.hasAnsweredQuestion && nextQuestionID !== "0") {
            if (mode != "stay" && mode != "cancel") {
                if (app.fullFeedback) {
                    alert(i18n.t("first_question_message_full_feedback"));
                } else {
                    alert(i18n.t("first_question_message"));
                }
            }
            app.hasAnsweredQuestion = true;
        }

        var delay = 2300;
        switch (mode) {
            case "stay":
            case "cancel":
                break;
            case "next":
            case "done":
                delay = 400;
                platform.nextQuestion(delay);
                break;
            default:
                // problem!
                break;
        }
    }
};


window.platform = platform;

export default platform;