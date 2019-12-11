import UI from "../components";
import logActivity from "./LogActivity";
import logError from "./LogError";
import metaViewport from "./MetaViewport";
import config from './Config';
import platform from './Platform';

/**
 * Task iframe
 */
var questionIframe = {

    version: '1',

    iframe: null,
    doc: null,
    body: null,
    tbody: null,
    initialized: false,
    loaded: false,
    questionKey: null,
    task: null,
    gradersLoaded: false,
    autoHeight: false,
    contestImagePreload: {},

    /**
     * Load a javascript file inside the iframe
     *
     * @param string filename
     * @param {function} callback
     */
    // private, never used
    addJsFile: function(filename, callback) {
        var script = this.doc.createElement("script");
        script.src = filename;
        if (script.addEventListener) {
            script.addEventListener("load", callback, false);
        } else if (script.readyState) {
            script.onreadystatechange = function() {
                if (
                    script.readyState === "complete" ||
                    script.readyState === "loaded"
                ) {
                    callback();
                }
            };
        }

        this.tbody.appendChild(script);
    },

    /**
     * Load a css file inside the iframe
     *
     * @param string filename
     */
    //private, never used
    addCssFile: function(filename) {
        var css = this.doc.createElement("link");
        css.rel = "stylesheet";
        css.type = "text/css";
        css.href = filename;
        this.doc.getElementsByTagName("head")[0].appendChild(css);
    },

    /**
     * Add some css inside the iframe
     *
     * @param {string} content Css content
     */
    // used in TaskFrame
    addCssContent: function(content) {
        var style = this.doc.createElement("style");
        style.type = "text/css";
        var iframeWin = this.iframe.contentWindow;
        if (iframeWin.addEventListener) {
            style.appendChild(this.doc.createTextNode(content));
        } else {
            // IE
            // http://stackoverflow.com/questions/5618742/ie-8-and-7-bug-when-dynamically-adding-a-stylesheet
            style.styleSheet.cssText = content;
        }
        // We can put it in #jsContent as it makes no difference
        this.doc.getElementById("jsContent").appendChild(style);
    },

    /**
     * Add some javascript inside the iframe
     *
     * @param {string} content Javascript content
     */
    // public
    addJsContent: function(content) {
        var script = this.doc.createElement("script");
        var iframeWin = this.iframe.contentWindow;
        if (iframeWin.addEventListener) {
            script.appendChild(this.doc.createTextNode(content));
        } else {
            script.text = content;
        }
        this.doc.getElementById("jsContent").appendChild(script);
    },

    /**
     * Remove the JS added by the addJsContent method
     */
    // private, never used
    removeJsContent: function() {
        this.body.find("#jsContent").empty();
    },

    /**
     * Inject Javascript code in iframe
     */
    // private
    inject: function(jsContent) {
        var iframeWin = this.iframe.contentWindow;
        if (!iframeWin.eval && iframeWin.execScript) {
            iframeWin.execScript("null");
        }
        if (iframeWin.eval) {
            try {
                iframeWin.eval(jsContent);
            } catch (e) {
                console.error(e);
            }
        } else {
            alert("No eval!");
        }
    },

    /**
     * Evaluate something in the iframe context
     *
     * @param {string} expr
     * @returns result
     */
    // private, never used
    evaluate: function(expr) {
        return this.iframe.contentWindow.eval(expr);
    },

    /**
     * Initialize the question iframe, must be run before anything else.
     * Acts somewhat like a constructor
     *
     * @param {function} callback when everything is loaded
     */
    // public
    initialize: function(callback) {
        UI.TaskFrame.updateIFrame();

        this.iframe = UI.TaskFrame.getIframe();
        this.doc = this.iframe.contentWindow.document;
        this.body = $("body", this.doc);
        this.tbody = this.doc.getElementsByTagName("body")[0];
        this.autoHeight = false;
        $("body").removeClass("autoHeight");
        metaViewport.toggle(false);

        this.setHeight(0);
        this.body.css("width", "782px");
        this.body.css("margin", "0");
        this.body.css("padding", "0");

        // users shouldn't reload iframes.
        this.inject(
            'window.onbeforeunload = function() {return "' +
                i18n.t("error_reloading_iframe") +
            '";};'
        );

        this.inject("window.onerror = window.parent.onerror;");

        // Inject localized strings
        this.inject(
            "var t = function(item) {return item;}; function setTranslate(translateFun) { t = translateFun; }"
        );
        this.iframe.contentWindow.setTranslate(i18n.t);

        // Inject ImagesLoader
        this.inject(
            'var ImagesLoader = { \n\
  newUrlImages: {}, \n\
  loadingImages: new Array(), \n\
  imagesToPreload: null, \n\
  contestFolder: null, \n\
  nbImagesToLoad: 0, \n\
  nbImagesLoaded: 0, \n\
  nbPreloadErrors: 0, \n\
  switchToNonStatic: false, \n\
  preloadCallback: null, \n\
  preloadAllImages: null, \n\
  /* Defines what function to call once the preload phase is over */ setCallback: function (callback) { \n\
      this.preloadCallback = callback; \n\
  }, \n\
  /* Called by the generated contest .js file with the list of images to preload */ setImagesToPreload: function (imagesToPreload) { \n\
      this.imagesToPreload = imagesToPreload; \n\
  }, \n\
  addImagesToPreload: function (imagesToPreload) { \n\
      this.imagesToPreload = this.imagesToPreload.concat(imagesToPreload); \n\
  }, \n\
  errorHandler: function () { \n\
      var that = ImagesLoader;\n\
      that.loadingImages[that.nbImagesLoaded].onload = null; \n\
      that.loadingImages[that.nbImagesLoaded].onerror = null; \n\
      that.nbPreloadErrors++;  \n\
      if (that.nbPreloadErrors == 4){ \n\
          alert(t("error_connexion_server")); \n\
      } \n\
      if (that.nbPreloadErrors == 20) { \n\
          alert(t("error_connexion_server_bis")); \n\
          that.nbImagesLoaded = that.nbImagesToLoad; \n\
      } \n\
      setTimeout(that.loadNextImage, 2000); \n\
  }, \n\
  /* * Called after each successful load of an image. Update the interface and starts * loading the next image. */ loadHandler: function () { \n\
      var that = ImagesLoader; \n\
      that.loadingImages[that.nbImagesLoaded].onload = null; \n\
      that.loadingImages[that.nbImagesLoaded].onerror = null; \n\
      that.nbImagesLoaded++; \n\
      that.nbPreloadErrors = 0;  \n\
      parent.setNbImagesLoaded("" + that.nbImagesLoaded + "/" + that.nbImagesToLoad); \n\
      setTimeout(function() { that.loadNextImage(); }, 1); \n\
  }, \n\
  loadNextImage: function () { \n\
      var that = ImagesLoader; \n\
      if (that.nbImagesLoaded === that.nbImagesToLoad) { \n\
          that.preloadCallback(); \n\
          return; \n\
      } \n\
      if (that.loadingImages[that.nbImagesLoaded] == undefined) { \n\
          that.loadingImages[that.nbImagesLoaded] = new Image(); \n\
          that.loadingImages[that.nbImagesLoaded].onerror = that.errorHandler; \n\
          that.loadingImages[that.nbImagesLoaded].onload = that.loadHandler; \n\
          var srcImage = that.imagesToPreload[that.nbImagesLoaded]; \n\
          if (srcImage == "") { \n\
              that.loadHandler(); \n\
              return; \n\
          } \n\
          if (that.nbPreloadErrors > 0) { \n\
              var oldSrcImage = srcImage; \n\
              srcImage += "?v=" + that.nbPreloadErrors + "_" + Parameters.teamID; \n\
              that.newUrlImages[oldSrcImage] = srcImage; \n\
              if (that.nbPreloadErrors > 2) { \n\
                  that.switchToNonStatic = true; \n\
              } \n\
          } \n\
          for(var i=0; i<window.config.imagesURLReplacements.length; i++) { \n\
              srcImage = srcImage.replace(window.config.imagesURLReplacements[i][0], window.config.imagesURLReplacements[i][1]); \n\
          } \n\
          if (that.switchToNonStatic) { \n\
              srcImage = srcImage.replace("static1.france-ioi.org", "concours1.castor-informatique.fr"); \n\
              srcImage = srcImage.replace("static2.france-ioi.org", "concours2.castor-informatique.fr"); \n\
              for(var i=0; i<window.config.imagesURLReplacementsNonStatic.length; i++) { \n\
                  srcImage = srcImage.replace(window.config.imagesURLReplacementsNonStatic[i][0], window.config.imagesURLReplacements[i][1]); \n\
              } \n\
              that.newUrlImages[that.imagesToPreload[that.nbImagesLoaded]] = srcImage; \n\
          } \n\
          if(window.config.upgradeToHTTPS) { \n\
              srcImage = srcImage.replace(/^http:/, "https:"); \n\
          } \n\
          that.loadingImages[that.nbImagesLoaded].src = srcImage; \n\
      } else { \n\
          ImagesLoader.loadHandler(); \n\
      } \n\
  }, \n\
  preload: function (contestFolder) { \n\
      ImagesLoader.contestFolder = contestFolder; \n\
      ImagesLoader.nbImagesToLoad = ImagesLoader.imagesToPreload.length; \n\
      ImagesLoader.loadNextImage(); \n\
  }, \n\
  /* Updates the src attribute of images that couldnt be pre-loaded with the original url. */ refreshImages: function () { \n\
      $.each($("img"), function (i, elem) { \n\
          var posContest = this.src.indexOf("contest"); \n\
          if (posContest < 0) { \n\
              return; \n\
          } \n\
          if (ImagesLoader.newUrlImages[this.src] != undefined) { \n\
              this.src = ImagesLoader.newUrlImages[this.src]; \n\
          } \n\
      }); \n\
  } \n\
};'
        );

        UI.TaskFrame.updateBorder(this.body, app.newInterface);

        this.initialized = true;

        // Get configuration and image preloader
        var that = this;
        config.get(function() {
            that.inject("window.config = window.parent.config;");
            // Call image preloading
            if (that.contestImagePreload[app.contestID]) {
                that.inject(that.contestImagePreload[app.contestID]);
                callback();
            } else {
                // Load image preload lists
                $.get(
                    window.contestsRoot +
                        "/" +
                        app.contestFolder +
                        "/contest_" +
                        app.contestID +
                        ".js?origin=" +
                        window.location.protocol +
                        window.location.hostname,
                    function(content) {
                        that.contestImagePreload[app.contestID] = content;
                        that.inject(content);
                        callback();
                    },
                    "text"
                ).fail(function() {
                    // Continue anyway
                    callback();
                });
            }
        });
    },

    /**
     * Run the task, should be called only by the loadQuestion function
     */
    // private
    run: function(taskViews, callback) {
        // Reset autoHeight-related styles
        $("body").removeClass("autoHeight");
        UI.TaskFrame.updatePadding(questionIframe.doc, "5px");

        TaskProxyManager.getTaskProxy("question-iframe", withTask, true);
        function withTask(task) {
            questionIframe.task = task;
            TaskProxyManager.setPlatform(task, platform);
            task.getMetaData(function(metaData) {
                questionIframe.autoHeight = !!metaData.autoHeight;
                if (questionIframe.autoHeight) {
                    $("body").addClass("autoHeight");
                    UI.TaskFrame.updatePadding(questionIframe.doc, "");
                    metaViewport.toggle(true);
                    questionIframe.updateHeight();
                }
            });
            task.load(
                taskViews,
                function() {
                    //$('.questionIframeLoading').hide();
                    task.showViews(
                        taskViews,
                        function() {
                            if (
                                typeof app.defaultAnswers[
                                    questionIframe.questionKey
                                ] == "undefined"
                            ) {
                                task.getAnswer(function(strAnswer) {
                                    app.defaultAnswers[
                                        questionIframe.questionKey
                                    ] = strAnswer;
                                }, logError);
                            }
                            questionIframe.updateHeight();
                        },
                        logError
                    );
                },
                logError
            );
            // Iframe height "hack" TODO: why two timers?
            setTimeout(questionIframe.updateHeight, 500);
            setTimeout(questionIframe.updateHeight, 1000);

            // TODO : test without timeout : should not be needed.
            setTimeout(function() {
                var nextStep = function() {
                    setTimeout(function() {
                        if (!app.hasDisplayedContestStats) {
                            if (app.fullFeedback) {
                                if (!app.newInterface) {
                                    alert(
                                        i18n.t("contest_starts_now_full_feedback")
                                    );
                                }
                            } else {
                                alert(i18n.t("contest_starts_now"));
                            }
                            app.hasDisplayedContestStats = true;
                        }
                    }, 200);

                    if (callback) {
                        callback();
                    }
                };

                // Load the session's answer, if any
                if (app.answers[questionIframe.questionKey]) {
                    var answer = app.answers[questionIframe.questionKey];
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
            }, 50);
        }
    },

    /**
     * Update the iframe height depending on the task parameters
     */
    // public
    updateHeight: function(callback) {
        if (!questionIframe.loaded || !questionIframe.task) {
            if (callback) {
                callback();
            }
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
            platform.updateDisplay({ height: fullHeight });
            if (callback) {
                callback();
            }
        } else {
            questionIframe.task.getHeight(function(height) {
                height = Math.max(fullHeight, height + 25);
                platform.updateDisplay({ height: height });
                if (callback) {
                    callback();
                }
            }, logError);
        }
    },

    /**
     * body resize event handler
     */
    // public
    onBodyResize: function() {
        // We only need to update if the iframe is on auto-height
        if (questionIframe.autoHeight) {
            questionIframe.updateHeight();
        }
    },

    /**
     * Load the question, should be call only by the load function
     *
     * @param string questionKey
     */
    // public
    loadQuestion: function(taskViews, questionKey, callback) {
        var questionID = app.questionsKeyToID[questionKey];
        logActivity(app.teamID, questionID, "load");

        UI.TaskFrame.loadQuestion(this.body, questionKey);

        // Remove task-specific previous added JS, then add the new one
        this.removeJsContent();

        this.addJsContent("window.grader = null;");
        this.addJsContent("window.task = null;");

        // Load js modules
        UI.TaskFrame.loadQuestionJS(questionIframe, questionKey);

        this.addJsContent(
            'window.contestsRoot = "' + window.contestsRoot + '";'
        );
        this.addJsContent(
            'window.sAbsoluteStaticPath = "' + window.sAbsoluteStaticPath + '";'
        );
        this.addJsContent(
            'window.sAssetsStaticPath = "' + window.sAssetsStaticPath + '";'
        );
        this.addJsContent(
            'window.contestFolder = "' + app.contestFolder + '";'
        );

        // Load specific js
        this.addJsContent($("#javascript-" + questionKey).attr("data-content"));
        if ("solution" in taskViews) {
            this.addJsContent(
                $("#javascript-solution-" + questionKey).attr("data-content")
            );
        }
        if ("grader" in taskViews) {
            this.addJsContent(
                $("#javascript-grader-" + questionKey).attr("data-content")
            );
        }

        // Load css modules
        UI.TaskFrame.loadQuestionCSS(questionIframe, questionKey);

        questionIframe.loaded = true;
        questionIframe.questionKey = questionKey;

        setTimeout(function() {
            questionIframe.run(taskViews, function() {
                questionIframe.updateSolutionChoices(questionIframe, questionKey);
                callback();
            });
        }, 100);
    },

	updateSolutionChoices (questionIframe, questionKey) {
		for (var iChoice = 0; iChoice < 10; iChoice++) {
			this.body.find('#container .' + questionKey + "_choice_" + (iChoice + 1))
				.html(this.body.find('#container #answerButton_' + questionKey + "_" + (iChoice + 1) + " input").val());
		}
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
        if (this.loaded) {
            if (questionIframe.task && questionIframe.task.iframe_loaded) {
                questionIframe.task.unload(
                    function() {
                        that.loaded = false;
                        questionIframe.initialize(cb);
                    },
                    function() {
                        logError(arguments);
                        that.loaded = false;
                        questionIframe.initialize(cb);
                    }
                );
            } else {
                this.loaded = false;
                questionIframe.initialize(cb);
            }
        } else {
            this.loadQuestion(taskViews, questionKey, callback);
        }
    },


    // public
    setHeight: function(height) {
        UI.TaskFrame.updateHeight(height, questionIframe);
    }
};


export default questionIframe;