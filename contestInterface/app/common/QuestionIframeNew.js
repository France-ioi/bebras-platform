import UI from "../components";
import logActivity from "./LogActivity";
import logError from "./LogError";
import metaViewport from "./MetaViewport";
import config from './Config';
import platform from './Platform';


var questionIframe = {

    version: '2',

    iframe: null, // public
    initialized: false, // public
    loaded: false, // public
    questionKey: null, // public
    task: null, // public
    gradersLoaded: false, // public
    autoHeight: false, // public

    addCssContent: function(content) {},
    addJsContent: function(content) {},


    initialize: function(callback) {
        UI.TaskFrame.updateIFrame();
        this.iframe = UI.TaskFrame.getIframe();
        this.iframe.src = 'about:blank';
        this.autoHeight = false;
        $("body").removeClass("autoHeight");
        this.initialized = true;
        callback && callback();
    },


    /**
     * Run the task, should be called only by the loadQuestion function
     */
    // private
    run: function(taskViews, callback) {
        $("body").removeClass("autoHeight");
        TaskProxyManager.getTaskProxy("question-iframe", withTask, true);
        function withTask(task) {
            console.log('TaskProxyManager.getTaskProxy callback')
            questionIframe.task = task;
            TaskProxyManager.setPlatform(task, platform);
            task.getMetaData(function(metaData) {
                questionIframe.autoHeight = !!metaData.autoHeight;
                if (questionIframe.autoHeight) {
                    $("body").addClass("autoHeight");
                    metaViewport.toggle(true);
                    questionIframe.updateHeight();
                }
            });
            questionIframe.loadTask(task, taskViews, function() {
                questionIframe.onTaskLoad(task, callback);
            });
        }
    },



    loadTask: function(task, taskViews, callback) {
        console.log('QI TASK loadTask', taskViews)
        task.load(
            taskViews,
            function() {
                console.log('QI TASK loadTask callback')
                task.showViews(
                    taskViews,
                    function() {
                        console.log('QI TASK showViews callback')
                        if (typeof app.defaultAnswers[questionIframe.questionKey] == "undefined") {
                            task.getAnswer(function(strAnswer) {
                                app.defaultAnswers[questionIframe.questionKey] = strAnswer;
                            }, logError);
                        }
                        questionIframe.updateHeight();
                        callback();
                    },
                    logError
                );
            },
            function(error) {
                console.error('task.load error:', error)
            },
            //logError
        );
    },


    onTaskLoad: function(task, callback) {
        var nextStep = function() {
            setTimeout(function() {
                if (!app.hasDisplayedContestStats) {
                    if (app.fullFeedback) {
                        if (!app.newInterface) {
                            alert(i18n.t("contest_starts_now_full_feedback"));
                        }
                    } else {
                        alert(i18n.t("contest_starts_now"));
                    }
                    app.hasDisplayedContestStats = true;
                }
            }, 200);
            callback && callback();
        };


        // Load the session's answer, if any
        if (app.answers[questionIframe.questionKey]) {
            var answer = app.answers[questionIframe.questionKey];
            //alert('answer')
            //alert(task.reloadAnswer)
            task.reloadAnswer(
                answer,
                function() {
                    nextStep();
                },
                logError
            );
        } else {
            nextStep();
        }
    },

    /**
     * Update the iframe height depending on the task parameters
     */
    // public
    updateHeight: function(callback) {
        console.log('QI updateHeight')
        if (!questionIframe.loaded || !questionIframe.task) {
            callback && callback();
            return;
        }
        var fullHeight =
            UI.TaskFrame.getHeight() -
            $("html").height() +
            document.documentElement.clientHeight;
        if (questionIframe.autoHeight) {
            // Because the layout can vary, we simply take the height of the html
            // and compare to the desired height, hence finding how much the
            // iframe's height needs to change
            platform.updateDisplay({
                height: fullHeight
            });
            callback && callback();
            return;
        }

        questionIframe.task && questionIframe.task.getHeight(function(height) {
            height = Math.max(fullHeight, height + 25);
            platform.updateDisplay({
                height: height
            });
            callback && callback();
        }, logError);
    },


    onBodyResize: function() {
        if (questionIframe.autoHeight) {
            questionIframe.updateHeight();
        }
    },


    // public
    loadQuestion: function(taskViews, questionKey, callback) {
        console.log('QI loadQuestion')
        var questionID = app.questionsKeyToID[questionKey];
        logActivity(app.teamID, questionID, "load");
        questionIframe.loaded = false;
        UI.TaskFrame.loadQuestion(null, questionKey, function() {
            console.log('QI loadQuestion')
            questionIframe.loaded = true;
            questionIframe.questionKey = questionKey;
            questionIframe.run(taskViews, function() {
                questionIframe.updateSolutionChoices(questionIframe, questionKey);
                callback && callback();
            });
            questionIframe.updateHeight();
        });

    },


    updateSolutionChoices: function(questionIframe, questionKey) {
        return;
	},

    /**
     * Load the question when ready
     *
     * @param {string} questionKey
     */
    // public
    load: function(taskViews, questionKey, callback) {
        var that = this;
        var cb = function() {
            UI.TaskFrame.showQuestionIframe();
            that.loadQuestion(taskViews, questionKey, callback);
        };
        console.log('QI task load')
        if (this.loaded) {
            if (this.task && this.task.iframe_loaded) {
                console.log('QI task already loaded')
                this.task.unload(
                    function() {
                        that.loaded = false;
                        that.initialize(cb);
                    },
                    function() {
                        logError(arguments);
                        that.loaded = false;
                        that.initialize(cb);
                    }
                );
            } else {
                console.log('QI task not loaded')
                this.loaded = false;
                this.initialize(cb);
            }
        } else {
            console.log('QI load fist task')
            this.loadQuestion(taskViews, questionKey, callback);
        }
    },


    // public
    setHeight: function(height) {
        console.log('QI setHeight')
        UI.TaskFrame.updateHeight(height, questionIframe);
    }
};


export default questionIframe;