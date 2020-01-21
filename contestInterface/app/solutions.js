import UI from './components';
import questionIframe from './common/QuestionIframe';
import questions from './questions';
import fetch from './common/Fetch';
import logError from './common/LogError';

function loadSolutions(data) {
    var sortedQuestionIDs = questions.getSortedQuestionIDs(app.questionsData);
    for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
        var questionID = sortedQuestionIDs[iQuestionID];
        var questionData = app.questionsData[questionID];
        UI.GridView.appendQuestionData(questionData.key);
    }

    UI.OldContestHeader.updateDivQuestionsVisibility(false);
    UI.TaskFrame.hideQuestionIframe();
    UI.LoadingPage.load();
    UI.MainHeader.load();

    // The callback will be used by the task
    if (questionIframe.iframe.contentWindow.preloadSolImages) {
        questionIframe.iframe.contentWindow.preloadSolImages();
    }

    var onLoadCallback = function() {
        UI.MainHeader.unload();
        UI.OldContestHeader.updateDivQuestionsVisibility(true);
        UI.TaskFrame.showQuestionIframe();
        UI.ContestEndPage.unload();
        UI.TaskFrame.updateContainerCss();
        UI.LoadingPage.unload();
        if (!app.currentQuestionKey) {
            return;
        }
        questionIframe.updateHeight(function() {
            if (questionIframe.loaded) {
                questionIframe.task.unload(
                    function() {
                        questionIframe.loadQuestion(
                            {
                                task: true,
                                solution: true,
                                grader: true
                            },
                            app.currentQuestionKey,
                            function() {}
                        );
                    },
                    function() {
                        logError(arguments);
                        questionIframe.loadQuestion(
                            {
                                task: true,
                                solution: true,
                                grader: true
                            },
                            app.currentQuestionKey,
                            function() {}
                        );
                    }
                );
            } else {
                questionIframe.loadQuestion(
                    { task: true, solution: true, grader: true },
                    app.currentQuestionKey,
                    function() {}
                );
            }
            alert(i18n.t("check_score_detail"));
        },
        logError);
    }


    if(questionIframe.iframe.contentWindow.ImagesLoader) {
        setTimeout(function() {
            questionIframe.iframe.contentWindow.ImagesLoader.setCallback(onLoadCallback);
            questionIframe.iframe.contentWindow.ImagesLoader.preload(app.contestFolder);
        }, 50);
    } else {
        onLoadCallback();
    }

}



function loadSolutionsHat() {
    fetch(
        "data.php",
        { SID: app.SID, ieMode: window.ieMode, controller: "Solutions", action: "get" },
        function(data) {
            if (!data.error) {
                if (data.solutions) {
                    UI.GridView.updateSolutionsContent(data.solutions);
                    loadSolutions(data);
                } else {
                    $.get(data.solutionsUrl, function(content) {
                        UI.GridView.updateSolutionsContent(content);
                        loadSolutions(data);
                    }).fail(function() {
                        logError("a problem occured while fetching the solutions, please report to the administrators.");
                        UI.OldContestHeader.updateDivQuestionsVisibility(true);
                    });
                }
            }
        }
    );
}

export default {
    loadSolutionsHat
}