import UI from './components';
import tracker from './common/Tracker';
import fetch from './common/Fetch';
import logError from './common/LogError';

var sending = false;
//var nbSubmissions = 0; // TODO: value not used

function failedSendingAnswers() {
    tracker.disabled = true;
    sending = false;
    for (var questionID in app.answersToSend) {
        app.answersToSend[questionID].sending = false;
    }
    setTimeout(sendAnswers, app.delaySendingAttempts);
}



function sendAnswers() {
    if (sending) {
        return;
    }
    sending = true;
    var somethingToSend = false;
    for (var questionID in app.answersToSend) {
        var answerObj = app.answersToSend[questionID];
        answerObj.sending = true;
        somethingToSend = true;
    }
    if (!somethingToSend) {
        sending = false;
        return;
    }
    try {
        fetch(
            "data.php",
            {
                controller: 'Answers',
                action: 'add',
                SID: app.SID,
                answers: app.answersToSend,
                teamID: app.teamID,
                teamPassword: app.teamPassword
            },
            function(data) {
                sending = false;
                if (!data.success) {
                    if (
                        confirm(
                            i18n.t("response_transmission_error_1") +
                                " " +
                                data.message +
                                i18n.t("response_transmission_error_2")
                        )
                    ) {
                        failedSendingAnswers();
                    }
                    return;
                }
                var answersRemaining = false;
                for (var questionID in app.answersToSend) {
                    var answerToSend = app.answersToSend[questionID];
                    if (answerToSend.sending) {
                        var questionKey = app.questionsData[questionID].key;
                        markAnswered(questionKey, app.answersToSend[questionID].answer);
                        delete app.answersToSend[questionID];
                    } else {
                        answersRemaining = true;
                    }
                }
                if (answersRemaining) {
                    setTimeout(sendAnswers, 1000);
                }
            }
        ).fail(failedSendingAnswers);
    } catch (exception) {
        failedSendingAnswers();
    }
}



function submitAnswer(questionKey, answer, score) {
    if (typeof answer !== "string") {
        logError("trying to submit non-string answer: " + answer);
        return;
    }
    if (!app.newInterface) {
        UI.GridView.updateBullet(questionKey, "&loz;");
    }
    app.answersToSend[app.questionsKeyToID[questionKey]] = {
        answer: answer,
        sending: false,
        score: score
    };
    //nbSubmissions++;
    tracker.trackData({
        dataType: "answer",
        teamID: app.teamID,
        questionKey: questionKey,
        answer: answer
    });
    sendAnswers();
}


function markAnswered(questionKey, answer) {
    if (app.newInterface) {
        return;
    }
    if (answer === "") {
        UI.GridView.updateBullet(questionKey, "");
    } else {
        if (app.fullFeedback &&
            typeof app.scores[questionKey] !== "undefined" &&
            app.scores[questionKey].score == app.scores[questionKey].maxScore
        ) {
            UI.GridView.updateBullet(
                questionKey,
                '<span class="check">✓</span>'
            );
        } else {
            UI.GridView.updateBullet(questionKey, "&diams;");
        }
    }
}



export default {
    failedSendingAnswers,
    submitAnswer,
    sendAnswers,
    markAnswered
}