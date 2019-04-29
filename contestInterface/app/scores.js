import url from './common/ParseURL';
import UI from './components';
import questionIframe from './common/QuestionIframe';
import questions from './questions';
import fetch from './common/Fetch';
import logError from './common/LogError';

var ffMaxTeamScore = 0;
var teamScore = 0;
var maxTeamScore = 0;

// Grade the i'est question, then call the (i+1)'est or send the score
// local
function gradeQuestion(i) {
    if (i >= app.questionsToGrade.length) {
        sendScores();
        return;
    }

    var curQuestion = app.questionsToGrade[i];

    questionIframe.load(
        { task: true, grader: true },
        curQuestion.questionKey,
        function() {
            questionIframe.task.gradeAnswer(
                curQuestion.answer,
                null,
                function(newScore, message) {
                    app.scores[curQuestion.questionKey] = {
                        score: newScore,
                        maxScore: curQuestion.maxScore
                    };
                    teamScore += parseInt(
                        app.scores[curQuestion.questionKey].score
                    );
                    gradeQuestion(i + 1);
                }
            );
        }
    );
}

// Send the computed scores, then load the solutions
// local
function sendScores() {
    fetch(
        "scores.php",
        { scores: app.scores, SID: app.SID },
        function(data) {
            if (data.status === "success") {
                solutions.loadSolutionsHat();
                if (app.bonusScore) {
                    UI.OldContestHeader.updateBonusScore(app.bonusScore);
                }
                UI.GridView.updateCss();
                UI.OldContestHeader.updateCssLoadSolutions();
                var sortedQuestionIDs = questions.getSortedQuestionIDs(app.questionsData);
                for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
                    var questionID = sortedQuestionIDs[iQuestionID];
                    var questionKey = app.questionsData[questionID].key;
                    var image = "";
                    var score = 0;
                    var maxScore = 0;
                    if (app.scores[questionKey] !== undefined) {
                        score = app.scores[questionKey].score;
                        maxScore = app.scores[questionKey].maxScore;
                        if (score < 0) {
                            image = "<img src='images/35.png'>";
                        } else if (score == maxScore) {
                            image = '<span class="check">✓</span>';
                        } else if (parseInt(score) > 0) {
                            image = "<img src='images/check.png'>";
                        } else {
                            image = "";
                        }
                    }
                    if (!app.newInterface) {
                        UI.OldContestHeader.updateBulletAndScores(
                            questionKey,
                            image,
                            score,
                            maxScore
                        );
                    }
                }
                UI.OldContestHeader.hideScoreTotal();
                UI.OldContestHeader.updateTeamScore(
                    teamScore,
                    maxTeamScore
                );
                //      questions.selectQuestion(sortedQuestionIDs[0], false);
            }
        }
    );
}

/*
    * Called when the team's contest participation is over, and it's not
    * a "restricted" contest.
    * Computes the scores for each question using the task's graders
    * the score for each question as well as the total score.
    * Send the scores to the server, then display the solutions
    */
function showScoresHat () {
    // in case of fullFeedback, we don't need other graders
    if (app.fullFeedback) {
        showScores({bonusScore: bonusScore});
        return;
    }
    fetch(
        "graders.php",
        {
            SID: SID,
            ieMode: window.ieMode,
            p: url.getParameterByName("p")
        },
        function(data) {
            if (data.status === "success" && (data.graders || data.gradersUrl)) {
                questionIframe.gradersLoaded = true;
                if (data.graders) {
                    UI.GridView.updateGradersContent(data.graders);
                    showScores(data);
                } else {
                    $.get(data.gradersUrl, function(content) {
                        UI.GridView.updateGradersContent(content);
                        showScores(data);
                    }).fail(function() {
                        logError("cannot find " + data.gradersUrl);
                        showScores({ bonusScore: app.bonusScore });
                    });
                }
            }
        }
    );
}

function showScores(data) {
    UI.OldContestHeader.hideScoreTotal();
    // Compute scores
    teamScore = parseInt(data.bonusScore);
    maxTeamScore = parseInt(data.bonusScore);
    for (var questionID in app.questionsData) {
        var questionData = app.questionsData[questionID];
        var questionKey = questionData.key;
        var answer = app.answers[questionKey];
        var minScore = questionData.minScore;
        var noAnswerScore = questionData.noAnswerScore;
        var maxScore = questionData.maxScore;
        if (answer) {
            // Execute the grader in the question context
            app.questionsToGrade.push({
                answer: answer,
                minScore: minScore,
                maxScore: maxScore,
                noScore: questionData.noAnswerScore,
                options: questionData.options,
                questionKey: questionKey
            });
        } else {
            // No answer given
            app.scores[questionKey] = {
                score: noAnswerScore,
                maxScore: maxScore
            };
            teamScore += parseInt(app.scores[questionKey].score);
        }
        maxTeamScore += parseInt(maxScore);
    }
    gradeQuestion(0);
}


function computeFullFeedbackScore() {
    app.ffTeamScore = app.bonusScore ? app.bonusScore : 0;
    ffMaxTeamScore = 0;
    for (var questionID in app.questionsData) {
        var questionKey = app.questionsData[questionID].key;
        ffMaxTeamScore += app.questionsData[questionID].maxScore;
        if (app.scores[questionKey]) {
            app.ffTeamScore += parseInt(app.scores[questionKey].score);
        } else {
            app.ffTeamScore += app.questionsData[questionID].noAnswerScore;
        }
    }
    if (app.newInterface) {
        var strScore = app.ffTeamScore + " ";
        if (app.ffTeamScore > 1) {
            strScore += i18n.t("points");
        } else {
            strScore += i18n.t("point");
        }
        UI.ContestHeader.updateScoreTotalFullFeedback(strScore);
    } else {
        UI.ContestHeader.updateScoreTotalFullFeedback(
            app.ffTeamScore + " / " + ffMaxTeamScore
        );
    }
}



export default {
    showScoresHat,
    computeFullFeedbackScore
}