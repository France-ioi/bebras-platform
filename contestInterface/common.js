 /* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */
!function () {

var contestID;
var contestFolder;
var contestVisibility;
var contestShowSolutions;
var contestOpen;
var fullFeedback;
var nextQuestionAuto;
var nbUnlockedTasksInitial;
var newInterface;
var customIntro;
var solutionsLoaded;
var teamID = 0;
var teamPassword = "";
var questionsData = {};
var questionsKeyToID = {};
var questionsToGrade = [];
var scores = {};
var questionUnlockedLevels = {};
var bonusScore = 0;
var ffTeamScore = 0;
var ffMaxTeamScore = 0; // fullFeedback versions
var teamScore = 0;
var maxTeamScore = 0;
var sending = false;
var answersToSend = {};
var answers = {};
var defaultAnswers = {};
var lastSelectQuestionTime = 0;
var currentQuestionKey = "";
// SID is initialized to the empty string so that its encoding in an AJAX query
// is predictable (rather than being either '' or 'null').
var SID = '';
var hasAnsweredQuestion = false;
var hasDisplayedContestStats = false;
var delaySendingAttempts = 60000;
var nbSubmissions = 0;
var t = i18n.t;

var logToConsole = function(logStr) {
  if (window.console) {
    console.error(logStr);
  }
};

window.unlockAllLevels = function() {
   var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
   for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      var questionKey = questionsData[sortedQuestionIDs[iQuestionID]].key;
      questionUnlockedLevels[questionKey] = 3;
      $("#place_" + questionKey).hide();
      $("#row_" + questionKey).show();
   }
};

var nbErrorsSent = 0;
var logError = function(error, errormsg) {
  var logStr;
  if (typeof error != "string" && !error.stack) {
     logStr = JSON.stringify(error);
  } else {
     logStr = error;
  }
  if (errormsg) {
     logStr += ' ' + errormsg;
  }
  if (error.stack) {
    logStr += ' ' + error.stack;
  }
  logToConsole((currentQuestionKey ? currentQuestionKey+': ' : '')+logStr);
  nbErrorsSent = nbErrorsSent + 1;
  if (nbErrorsSent > 10) {
    return;
  }
  $.post('logError.php', {errormsg: logStr, questionKey: currentQuestionKey}, function(data) {
    if (!data || !data.success) {
      logToConsole('error from logError.php');
    }
  }, 'json').fail(function() {
    logToConsole('error calling logError.php');
  });
};

window.onerror = function () {
   logError({
      message: 'global error handler',
      details: Array.prototype.slice.call(arguments)});
};

window.logError = logError;

var updateContestName = function(contestName) {
  $('#headerH1').html(contestName);
  $('title').html(contestName);
};

/**
 * Old IE versions does not implement the Array.indexOf function
 * Setting it in Array.prototype.indexOf makes IE crash
 * So the graders are using this inArray function
 *
 * TODO: is it still used?
 *
 * @param {array} arr
 * @param {type} value
 * @returns {int}
 */
function inArray(arr, value) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i] == value) {
            return i;
        }
    }
    return -1;
}

/**
 * The platform object as defined in the Bebras API specifications
 *
 * @type type
 */
var platform = {
   updateHeight: function(height, success, error) {
      if (height < 700) {
        height = 700;
      }
      questionIframe.setHeight(height);
      if (success) {success();}
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
      var questionData = questionsData[questionsKeyToID[questionIframe.questionKey]];
      var unlockedLevels = 1;
      if (questionUnlockedLevels[questionIframe.questionKey] != null) {
         unlockedLevels = questionUnlockedLevels[questionIframe.questionKey];
      }
      var res = {
         'minScore': questionData.minScore,
         'maxScore': questionData.maxScore,
         'noScore': questionData.noAnswerScore,
         'randomSeed': teamID,
         'options': questionData.options,
         'pointsAsStars': newInterface,
         'unlockedLevels': unlockedLevels
      };
      if (key) {
         if (key !== 'options' && key in res) {
            res = res[key];
         } else if (res.options && key in res.options) {
            res = res.options[key];
         } else {
            res = (typeof defaultValue !== 'undefined') ? defaultValue : null;
         }
      }
      success(res);
   },
   validate: function(mode, success, error) {
      if (TimeManager.isContestOver()) {
         alert(t("contest_closed_answers_readonly"));
         if (error) {error();} else if (success) {success();}
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
         var questionData = questionsData[questionsKeyToID[questionIframe.questionKey]];
         if (fullFeedback) {
            questionIframe.task.gradeAnswer(answer, null, function(score, message) {
               if (score < questionData.maxScore) {
                  mode = "stay";
               }
               if ((answer != defaultAnswers[questionIframe.questionKey]) || (typeof answers[questionIframe.questionKey] != 'undefined')) {
                  var prevScore = 0;
                  if (typeof  scores[questionIframe.questionKey] != 'undefined') {
                     prevScore = scores[questionIframe.questionKey].score;
                  }
                  if ((typeof answers[questionIframe.questionKey] == 'undefined') ||
                      ((answer != answers[questionIframe.questionKey]) && (score >= prevScore))) {
                     scores[questionIframe.questionKey] = {score: score, maxScore: questionData.maxScore};
                     submitAnswer(questionIframe.questionKey, answer, score);
                     answers[questionIframe.questionKey] = answer;

                     updateUnlockedLevels(getSortedQuestionIDs(questionsData), questionIframe.questionKey);
                     if (!newInterface) {
                        $('#score_' + questionData.key).html(score + " / " + questionData.maxScore);
                     }
                  }
               }
               computeFullFeedbackScore();
               platform.continueValidate(mode);
            }, logError);
         } else {
            submitAnswer(questionIframe.questionKey, answer, null);
            answers[questionIframe.questionKey] = answer;
            platform.continueValidate(mode);
         }
         if (success) {success();}
      }, logError);
   },
   firstNonVisitedQuestion: function(delay) {
      function timeoutFunFactory(questionID) {
         return function() {
            window.selectQuestion(questionID, false); 
         };
      }
      var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
      for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
         var questionID = sortedQuestionIDs[iQuestionID];
         var questionData = questionsData[questionID];
         if ((questionUnlockedLevels[questionData.key] > 0) && (!questionData.visited)) {
            setTimeout(timeoutFunFactory(questionID), delay);
            return;
         }
      }
      window.backToList();
   },
   nextQuestion: function(delay) {
      if (newInterface) {
         this.firstNonVisitedQuestion(delay);
         return;
      }
      var questionData = questionsData[questionsKeyToID[questionIframe.questionKey]];
      var nextQuestionID = questionData.nextQuestionID;
      // Next question
      if (nextQuestionID !== "0") {
         setTimeout(function() {
            window.selectQuestion(nextQuestionID, false);
         }, delay);
      }
      else {
         setTimeout(function() {
            alert(t("last_question_message"));
         }, delay);
      }
   },
   continueValidate: function(mode) {
      if (!nextQuestionAuto) {
         return;
      }
      var questionData = questionsData[questionsKeyToID[questionIframe.questionKey]];
      var nextQuestionID = questionData.nextQuestionID;
      if ((!hasAnsweredQuestion) && (nextQuestionID !== "0")) {
         if ((mode != "stay") && (mode != "cancel")) {
            if (fullFeedback) {
               // TODO : translate
               alert("Vous avez répondu à votre première question, et la suivante va s'afficher automatiquement. Dans la liste à gauche, vous pouvez voir si la question a été résolue (symbole vert) et le nombre de points obtenus, vous pouvez aussi revenir sur une question en cliquant sur son nom.");
            } else {
               alert(t("first_question_message"));
            }
         }
         hasAnsweredQuestion = true;
      }

      var delay = 2300;
      switch (mode) {
         case 'stay':
         case 'cancel':
            break;
         case 'next':
         case 'done':
            delay = 400;
            platform.nextQuestion(delay);
            break;
         default:
            // problem!
            break;
      }
   }
};

/**
 * Task iframe
 */
var questionIframe = {
   iframe: null,
   doc: null,
   body: null,
   tbody: null,
   initialized: false,
   loaded: false,
   questionKey: null,
   task: null,
   gradersLoaded: false,

   /**
    * Load a javascript file inside the iframe
    *
    * @param string filename
    * @param {function} callback
    */
   addJsFile: function(filename, callback) {
      var script = this.doc.createElement('script');
      script.src = filename;
      if (script.addEventListener) {
         script.addEventListener('load', callback, false);
      }
      else if (script.readyState) {
         script.onreadystatechange = function () {
            if (script.readyState === 'complete' || script.readyState === 'loaded') {
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
   addCssFile: function(filename) {
      var css = this.doc.createElement('link');
      css.rel = 'stylesheet';
      css.type = 'text/css';
      css.href = filename;
      this.doc.getElementsByTagName('head')[0].appendChild(css);
   },

   /**
    * Add some css inside the iframe
    *
    * @param {string} content Css content
    */
   addCssContent: function(content) {
      var style = this.doc.createElement('style');
      style.type = 'text/css';
      var iframeWin = this.iframe.contentWindow;
      if (iframeWin.addEventListener) {
         style.appendChild(this.doc.createTextNode(content));
      } else { // IE
          // http://stackoverflow.com/questions/5618742/ie-8-and-7-bug-when-dynamically-adding-a-stylesheet
          style.styleSheet.cssText = content;
      }
      // We can put it in #jsContent as it makes no difference
      this.doc.getElementById('jsContent').appendChild(style);
   },

   /**
    * Add some javascript inside the iframe
    *
    * @param {string} content Javascript content
    */
   addJsContent: function(content) {
      var script = this.doc.createElement('script');
      var iframeWin = this.iframe.contentWindow;
      if (iframeWin.addEventListener) {
         script.appendChild(this.doc.createTextNode(content));
      } else {
         script.text = content;
      }
      this.doc.getElementById('jsContent').appendChild(script);
   },

   /**
    * Remove the JS added by the addJsContent method
    */
   removeJsContent: function() {
      this.body.find('#jsContent').empty();
   },

   /**
    * Inject Javascript code in iframe
    */
   inject: function(jsContent) {
      var iframeWin = this.iframe.contentWindow;
      if (!iframeWin.eval && iframeWin.execScript) {
         iframeWin.execScript("null");
      }
      if (iframeWin.eval) {
         iframeWin.eval(jsContent);
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
   evaluate: function(expr) {
      return this.iframe.contentWindow.eval(expr);
   },

   /**
    * Initialize the question iframe, must be run before anything else.
    * Acts somewhat like a constructor
    *
    * @param {function} callback when everything is loaded
    */
   initialize: function(callback) {
      // The iframe is removed then recreated. It is the only way to add a Doctype in it
      $('#question-iframe').remove();

      var iframe = document.createElement('iframe');
      iframe.setAttribute('id', 'question-iframe');
      iframe.setAttribute('scrolling', 'no');
      iframe.setAttribute('src', 'about:blank');

      var content = '<!DOCTYPE html>' +
         '<html><head><meta http-equiv="X-UA-Compatible" content="IE=edge"></head>' +
         '<body></body></html>';
      var ctnr = document.getElementById('question-iframe-container');
      ctnr.appendChild(iframe);

      iframe.contentWindow.document.open('text/html', 'replace');
      iframe.contentWindow.document.write(content);
      if (typeof iframe.contentWindow.document.close === 'function')
         iframe.contentWindow.document.close();

      this.iframe = $('#question-iframe')[0];
      this.doc = $('#question-iframe')[0].contentWindow.document;
      this.body = $('body', this.doc);
      this.tbody = this.doc.getElementsByTagName('body')[0];

      this.setHeight(0);
      this.body.css('width', '782px');
      this.body.css('margin', '0');
      this.body.css('padding', '0');

      // users shouldn't reload iframes
      this.inject('window.onbeforeunload = function() {return "Désolé, il est impossible de recharger l\'iframe. Si un problème est survenu, sélectionnez une autre question et revenez sur celle-ci.";};');

      // Inject localized strings
      this.inject('var t = function(item) {return item;}; function setTranslate(translateFun) { t = translateFun; }');
      this.iframe.contentWindow.setTranslate(t);

      // Inject ImagesLoader
      this.inject('var ImagesLoader = { \n\
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
            if (that.switchToNonStatic) { \n\
                srcImage = srcImage.replace("static1.france-ioi.org", "concours1.castor-informatique.fr"); \n\
                srcImage = srcImage.replace("static2.france-ioi.org", "concours2.castor-informatique.fr"); \n\
                that.newUrlImages[that.imagesToPreload[that.nbImagesLoaded]] = srcImage; \n\
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
};');

      // No more global css file
      //this.addCssFile(contestsRoot + '/' + contestFolder + '/contest_' + contestID + '.css');

      // Call image preloading
      this.addJsFile(window.contestsRoot + '/' + contestFolder + '/contest_' + contestID + '.js', callback);
      
      var border = "border: 1px solid #000000;";
      if (newInterface) {
         border = "";
      }
      this.body.append('<div id="jsContent"></div><div id="container" style="' + border + 'padding: 5px;"><div class="question" style="font-size: 20px; font-weight: bold;">Le contenu du concours est en train d\'être téléchargé, merci de patienter le temps nécessaire.</div></div>');

      this.initialized = true;
   },

   /**
    * Run the task, should be called only by the loadQuestion function
    */
   run: function(taskViews, callback) {
      TaskProxyManager.getTaskProxy('question-iframe', withTask, true);
      function withTask (task) {
        questionIframe.task = task;
        TaskProxyManager.setPlatform(task, platform);
        task.load(taskViews, function() {
           task.showViews(taskViews, function() {
              if (typeof defaultAnswers[questionIframe.questionKey] == 'undefined') {
                 task.getAnswer(function(strAnswer) {
                    defaultAnswers[questionIframe.questionKey] = strAnswer;
                 }, logError);
              }
              task.getHeight(function(height) {
                 platform.updateHeight(height);
              }, logError);
           }, logError);
        }, logError);
        // Iframe height "hack" TODO: why two timers?
        setTimeout(function() {
           task.getHeight(function(height) {
              platform.updateHeight(height);
           }, logError);
        }, 500);
        setTimeout(function() {
           task.getHeight(function(height) {
              platform.updateHeight(height);
           }, logError);
        }, 1000);

        // TODO : test without timeout : should not be needed.
        setTimeout(function() {
           var nextStep = function() {
              setTimeout(function() {
                 if (!hasDisplayedContestStats) {
                    if (fullFeedback) {
                       if (!newInterface) {
                          alert("C'est parti ! Notez votre score en haut à gauche qui se met à jour au fur et à mesure de vos réponses !");
                       }
                    } else {
                       alert(t("contest_starts_now"));
                    }
                    hasDisplayedContestStats = true;
                 }
              }, 200);

              if (callback) {
                 callback();
              }
           };

           // Load the session's answer, if any
           if (answers[questionIframe.questionKey]) {
              var answer = answers[questionIframe.questionKey];
              task.reloadAnswer(answer, function() {
                 nextStep();
              }, logError);
           } else {
              nextStep();
           }
        }, 50);
      }
   },

   /**
    * Load the question, should be call only by the load function
    *
    * @param string questionKey
    */
   loadQuestion: function(taskViews, questionKey, callback) {
      this.body.find('#container > .question').remove();
      // We cannot just clone the element, because it'll result in an strange id conflict, even if we put the result in an iframe
      var questionContent = $('#question-' + questionKey).html();
      if (!questionContent) {
         questionContent = 'Il s\'est produit une anomalie lors du téléchargement du contenu du concours. Veuillez tenter de recharger la page avec Ctrl+R ou Ctrl+F5. Si cela ne fonctionne pas, essayez éventuellement avec un autre navigateur. En cas d\'échec répété, merci de contacter la hotline, pour que nous puissions rechercher la cause de ce problème.';
      }
      this.body.find('#container').append('<div id="question-'+questionKey+'" class="question">'+questionContent+'</div>');

      // Remove task-specific previous added JS, then add the new one
      this.removeJsContent();

      this.addJsContent('window.grader = null;');
      this.addJsContent('window.task = null;');

      // Load js modules
      $('.js-module-'+questionKey).each(function() {
         var jsModuleId = 'js-module-'+$(this).attr('data-content');
         questionIframe.addJsContent($('#'+jsModuleId).attr('data-content'));
      });

      this.addJsContent('window.contestsRoot = "'+window.contestsRoot+'";');
      this.addJsContent('window.sAbsoluteStaticPath = "'+window.sAbsoluteStaticPath+'";');
      this.addJsContent('window.sAssetsStaticPath = "'+window.sAssetsStaticPath+'";');
      this.addJsContent('window.contestFolder = "'+contestFolder+'";');

      // Load specific js
      this.addJsContent($('#javascript-' + questionKey).attr('data-content'));
      if ('solution' in taskViews) {
         this.addJsContent($('#javascript-solution-' + questionKey).attr('data-content'));
      }
      if ('grader' in taskViews) {
         this.addJsContent($('#javascript-grader-' + questionKey).attr('data-content'));
      }

      // Load css modules
      $('.css-module-'+questionKey).each(function() {
         var cssModuleId = 'css-module-'+$(this).attr('data-content');
         questionIframe.addCssContent($('#'+cssModuleId).attr('data-content'));
      });

      questionIframe.loaded = true;
      questionIframe.questionKey = questionKey;

      setTimeout(function() {
         questionIframe.run(taskViews, function() {
            loadSolutionChoices(questionKey);
            callback();
         });
      }, 100);

   },

   /**
    * Load the question when ready
    *
    * @param {string} questionKey
    */
   load: function(taskViews, questionKey, callback) {
      if (this.loaded) {
         var that = this;
         if (questionIframe.task && questionIframe.task.iframe_loaded) {
            questionIframe.task.unload(function() {
               that.loaded = false;
               that.loadQuestion(taskViews, questionKey, callback);
            }, function() {
               logError(arguments);
               that.loaded = false;
               that.loadQuestion(taskViews, questionKey, callback);
            });   
         }
         else {
            this.loaded = false;
            this.loadQuestion(taskViews, questionKey, callback);
         }
      }
      else {
         this.loadQuestion(taskViews, questionKey, callback);
      }
   },

   setHeight: function(height) {
       height = Math.max($(window).height() - 79, height + 25);
       $('#question-iframe').css('height', height + 'px');
   }
};

var Utils = {
   disableButton: function(buttonId) {
      var button = $("#" + buttonId);
      if (button.attr("disabled")) {
         return false;
      }
      button.attr("disabled", true);
      return true;
   },

   enableButton: function(buttonId) {
      var button = $("#" + buttonId);
      button.attr("disabled", false);
   },

   pad2: function(number) {
      if (number < 10) {
         return "0" + number;
      }
      return number;
   },

   /*
    * Returns an array with numbers 0 to nbValues -1.
    * Unless preventShuffle is true, the order is "random", but
    * is fully determined by the value of the integer ordeKey
   */
   getShuffledOrder: function (nbValues, orderKey, preventShuffle) {
      var order = [];
      for (var iValue = 0; iValue < nbValues; iValue++) {
         order.push(iValue);
      }
      if (preventShuffle) {
         return order;
      }
      for (iValue = 0; iValue < nbValues; iValue++) {
         var pos = iValue + (orderKey % (nbValues - iValue));
         var tmp = order[iValue];
         order[iValue] = order[pos];
         order[pos] = tmp;
      }
      return order;
   }
};

/*
 * TimeManager is in charge of checking and displaying how much time contestants
 * still have to answer questions.
 * all times are in seconds since 01/01/70
*/
var TimeManager = {
   endTime: null,  // is set once the contest is closed, to the closing time
   timeUsedBefore: null, // time used before the contest is loaded (in case of an interruption)
   timeStart: null, // when the contest was loaded (potentially after an interruption)
   totalTime: null, // time allocated to this contest
   endTimeCallback: null, // function to call when out of time
   interval: null,
   prevTime: null,
   synchronizing: false,
   syncCounter: 0,  // counter used to limit number of pending getRemainingTime requests

   setTotalTime: function(totalTime) {
      this.totalTime = totalTime;
   },

   init: function(timeUsed, endTime, contestOverCallback, endTimeCallback) {
      this.timeUsedBefore = parseInt(timeUsed);
      this.endTime = endTime;
      this.endTimeCallback = endTimeCallback;
      var curDate = new Date();
      this.timeStart = curDate.getTime() / 1000;
      if (this.endTime) {
         contestOverCallback();
      } else if (this.totalTime > 0) {
         this.prevTime = this.timeStart;
         this.updateTime();
         this.interval = setInterval(this.updateTime, 1000);
         this.minuteInterval = setInterval(this.minuteIntervalHandler.bind(this), 60000);
      } else {
         $(".chrono").hide();
      }
   },

   getRemainingTime: function() {
      var curDate = new Date();
      var curTime = curDate.getTime() / 1000;
      var usedTime = (curTime - this.timeStart) + this.timeUsedBefore;
      var remainingTime = this.totalTime - usedTime;
      if (remainingTime < 0) {
         remainingTime = 0;
      }
      return remainingTime;
   },

   // fallback when sync with server fails:
   simpleTimeAdjustment: function() {
      var curDate = new Date();
      var timeDiff = curDate.getTime() / 1000 - TimeManager.prevTime;
      TimeManager.timeStart += timeDiff - 1;
      setTimeout(function() {
         TimeManager.syncWithServer();
      }, 120000);
   },

   syncWithServer: function() {
      if (this.syncCounter >= 1) {
         //console.log('ignored spurious call to syncWithServer');
         return;
      }
      this.syncCounter += 1;
      TimeManager.synchronizing = true;
      $(".minutes").html('');
      $(".seconds").html('synchro...');
      var self = this;
      $.post('data.php', {SID: SID, action: 'getRemainingTime', teamID: teamID},
         function(data) {
            if (data.success) {
               var remainingTime = self.getRemainingTime();
               TimeManager.timeStart = TimeManager.timeStart + data.remainingTime - remainingTime;
            } else {
               TimeManager.simpleTimeAdjustment();
            }
         },
      'json').done(function() {
         var curDate = new Date();
         TimeManager.prevTime = curDate.getTime() / 1000;
         TimeManager.synchronizing = false;
      }).fail(function() {
         TimeManager.simpleTimeAdjustment();
         TimeManager.synchronizing = false;
      });
   },

   minuteIntervalHandler: function() {
      this.syncCounter = 0;
   },

   updateTime: function() {
      if (TimeManager.endTime || TimeManager.synchronizing) {
         return;
      }
      var curDate = new Date();
      var curTime = curDate.getTime() / 1000;
      var timeDiff = Math.abs(curTime - TimeManager.prevTime);
      // We traveled through time, more than 60s difference compared to 1 second ago !
      if (timeDiff > 60 || timeDiff < -60) {
         TimeManager.syncWithServer();
         return;
      }
      TimeManager.prevTime = curTime;
      var remainingTime = TimeManager.getRemainingTime();
      var minutes = Math.floor(remainingTime / 60);
      var seconds = Math.floor(remainingTime - 60 * minutes);
      $(".minutes").html(minutes);
      $(".seconds").html(Utils.pad2(seconds));
      if (remainingTime <= 0) {
         clearInterval(this.interval);
         clearInterval(this.minuteInterval);
         TimeManager.endTimeCallback();
      }
   },

   setEndTime: function(endTime) {
      this.endTime = endTime;
   },

   stopNow: function() {
      var curDate = new Date();
      this.endTime = curDate.getTime() / 1000;
   },

   isContestOver: function() {
      return this.endTime;
   }
};

// Main page

window.selectMainTab = function(tabName) {
   if (tabName == 'home') {
      loadPublicGroups();
   }
   var tabNames = ["school", "home", "continue"];
   for(var iTab = 0; iTab < tabNames.length; iTab++) {
      if (tabNames[iTab] === tabName) {
         $("#tab-" + tabNames[iTab]).show();
         $("#button-" + tabNames[iTab]).addClass("selected");
      } else {
         $("#tab-" + tabNames[iTab]).hide();
         $("#button-" + tabNames[iTab]).removeClass("selected");
      }
   }
};

window.confirmPublicGroup = function() {
   $("#warningPublicGroups").hide();
   $("#publicGroups").show();
};
// Contest startup

/*
 * Generates the html that displays the list of questions on the left side of the page
*/

function fillListQuestions(sortedQuestionIDs, questionsData)
{
   var strListQuestions = "";
   for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      var questionID = sortedQuestionIDs[iQuestionID];
      var questionData = questionsData[questionID];
      var encodedName = questionData.name.replace("'", "&rsquo;");

      var strScore = "";
      if (fullFeedback) {
         if (scores[questionData.key] !== undefined) {
            strScore = scores[questionData.key].score + " / " + questionData.maxScore;
         } else {
            strScore = questionData.noAnswerScore + " / " + questionData.maxScore;
         }
      }
      strListQuestions += "<tr id='row_" + questionData.key + "'><td class='questionBullet' id='bullet_" + questionData.key + "'></td>" +
         "<td class='questionLink' id='link_" + questionData.key + "' " + "onclick='selectQuestion(\"" + questionData.ID + "\", true)'>" +
            encodedName + 
         "</td>" + 
         "<td class='questionScore' id='score_" + questionData.key + "'>" +
            strScore +
         "</td></tr>";

   }
   $(".questionList").html("<table>" + strListQuestions + "</table>");
   if (fullFeedback) {
      $(".questionListHeader").css("width", "240px");
      $(".question, #divQuestionParams, #divClosed, .questionsTable, #question-iframe-container").css("left", "245px");
   }
}

function fillListQuestionsNew(sortedQuestionIDs, questionsData)
{
   var strListQuestions = "";
   var iQuestionID, questionData;
   for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      questionData = questionsData[sortedQuestionIDs[iQuestionID]];
      var encodedName = questionData.name.replace("'", "&rsquo;");

      strListQuestions += 
         "<span id='row_" + questionData.key + "' class='icon' onclick='selectQuestion(\"" + questionData.ID + "\", true)'>" +
            '<div class="icon_title"><span class="questionBullet" id="bullet_' + questionData.key + '"></span>&nbsp;' + encodedName + '&nbsp;&nbsp;</div>' +
            '<div class="icon_img">' +
               '<table>' +
                  '<tr>' +
                     '<td class="icon_img_td" style="vertical-align: middle;">' +
                        '<img src="' + window.contestsRoot + '/' + contestFolder + '/' + questionData.key + '/icon.png" />' +
                     '</td>' +
                  '</tr>' +
               '</table>' +
            '</div>' +
            '<div class="questionScore" style="margin:auto" id="score_' + questionData.key + '"></div>' +
         '</span>' +
         '<span id="place_' + questionData.key + '" class="icon">' +
            '<div class="icon_title" style="color:gray">'+t("question_locked")+'</div>' +
            '<div class="icon_img">' +
               '<table>' +
                  '<tr>' +
                     '<td class="icon_img_td" style="vertical-align: middle;">' +
                        '<img src="images/locked_task.png" />' +
                     '</td>' +
                  '</tr>' +
               '</table>' +
            '</div>' +
         '</span>';
   }
   $(".questionList").html(strListQuestions);
   updateUnlockedLevels(sortedQuestionIDs);
   for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      questionData = questionsData[sortedQuestionIDs[iQuestionID]];
      drawStars("score_" + questionData.key, 4, 20, getQuestionScoreRate(questionData), "normal", getNbLockedStars(questionData)); // stars under question icon
   }
   $("#divFooter").show();
}

function getQuestionScoreRate(questionData) {
   if (scores[questionData.key] !== undefined) {
      return scores[questionData.key].score / questionData.maxScore;
   }
   return 0;
}

function getNbLockedStars(questionData) {
   if (questionUnlockedLevels[questionData.key] != 0) {
      return 3 - questionUnlockedLevels[questionData.key];
   }
   return 4;
}

function updateUnlockedLevels(sortedQuestionIDs, updatedQuestionKey, contestEnded) {
   if (!newInterface) {
      return;
   }
   var epsilon = 0.001;
   var nbTasksUnlocked = [nbUnlockedTasksInitial, 0, 0];
   var prevQuestionUnlockedLevels = {};
   var iQuestionID, questionKey;
   for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      questionKey = questionsData[sortedQuestionIDs[iQuestionID]].key;
      prevQuestionUnlockedLevels[questionKey] = questionUnlockedLevels[questionKey];
      if (contestEnded) {
         questionUnlockedLevels[questionKey] = 3;
         nbTasksUnlocked[2]++;
         continue;
      }
      questionUnlockedLevels[questionKey] = 0;
      if (scores[questionKey] != null) {
         var score = scores[questionKey].score;
         var maxScore = scores[questionKey].maxScore;
         if (score >= (maxScore / 2) - epsilon) {
            nbTasksUnlocked[0]++;
            nbTasksUnlocked[1]++;
            questionUnlockedLevels[questionKey] = 2;
         }
         if (score >= (3 * maxScore / 4) - epsilon) {
            nbTasksUnlocked[1]++;
            nbTasksUnlocked[2]++;
            questionUnlockedLevels[questionKey] = 3;
         }
         if (score >= maxScore - epsilon) {
            nbTasksUnlocked[2]++;
         }
      }
   }
   for (iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      var questionData = questionsData[sortedQuestionIDs[iQuestionID]];
      questionKey = questionData.key;
      for (var iLevel = 0; iLevel < 3; iLevel++) {
         if (nbTasksUnlocked[iLevel] > 0) {
            if (questionUnlockedLevels[questionKey] < iLevel + 1) {
               questionUnlockedLevels[questionKey] = iLevel + 1;
            }
            nbTasksUnlocked[iLevel]--;
         }
      }
      if (questionUnlockedLevels[questionKey] == 0) {
         $("#row_" + questionKey).hide();
         $("#place_" + questionKey).show();
      } else {
         $("#place_" + questionKey).hide();
         $("#row_" + questionKey).show();
      }
      if ((questionKey == updatedQuestionKey) || 
          (prevQuestionUnlockedLevels[questionKey] != questionUnlockedLevels[questionKey])) {
         var nbLocked = getNbLockedStars(questionData);
         var scoreRate = getQuestionScoreRate(questionData);
         drawStars('score_' + questionData.key, 4, 20, scoreRate, "normal", nbLocked);  // stars under icon on main page
         if (questionKey == updatedQuestionKey) {
            drawStars('questionStars', 4, 24, scoreRate, "normal", nbLocked); // stars in question title
         }
      }
   }
}

/*
 * Setup of the contest when the group has been selected, contestants identified,
 * the team's password given to the students, and the images preloaded
*/
function setupContest(data) {
   teamPassword = data.teamPassword;
   questionsData = data.questionsData;

   var questionKey;
   // Reloads previous scores to every question
   scores = {};
   for (var questionID in data.scores) {
      if (questionID in questionsData) {
         questionKey = questionsData[questionID].key;
         scores[questionKey] = {score: data.scores[questionID], maxScore: questionsData[questionID].maxScore};
      }
   }
   if (fullFeedback) {
      computeFullFeedbackScore();
   }

   var contestEnded = (data.endTime != null);

   // Determines the order of the questions, and displays them on the left
   var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
   if (newInterface) {
      fillListQuestionsNew(sortedQuestionIDs, questionsData);
      if ((customIntro != null) && (customIntro != '')) {
         $("#questionListIntro").html(customIntro);
      }
   } else {
      fillListQuestions(sortedQuestionIDs, questionsData);
   }
   updateUnlockedLevels(sortedQuestionIDs, null, contestEnded);

   // Defines function to call if students try to close their browser or tab
   window.onbeforeunload = function() {
      return t("warning_confirm_close_contest");
   };

   // Map question key to question id array
   for (questionID in questionsData) {
      questionsKeyToID[questionsData[questionID].key] = questionID;
   }

   // Displays the first question
   var questionData = questionsData[sortedQuestionIDs[0]];
   // We don't want to start the process of selecting a question, if the grading is going to start !

   if (!newInterface) {
      window.selectQuestion(sortedQuestionIDs[0], false, contestEnded && !fullFeedback);
   }

   // Reloads previous answers to every question
   answers = {};
   for (questionID in data.answers) {
      if (questionID in questionsData) {
         questionKey = questionsData[questionID].key;
         answers[questionKey] = data.answers[questionID];
         markAnswered(questionKey, answers[questionKey]);
         hasAnsweredQuestion = true;
      }
   }
   $('.buttonClose').show();
   if (!contestEnded || !fullFeedback) {
      // Starts the timer
      TimeManager.init(
         data.timeUsed,
         data.endTime,
         function() {
            closeContest(t("contest_is_over"));
         },
         function() {
            closeContest("<b>" + t("time_is_up") + "</b>");
         }
      );
   } else {
      TimeManager.endTime = true;
      hasDisplayedContestStats = true;
      loadSolutionsHat();
   }
   if (contestEnded && newInterface) {
      $('#questionListIntro').html('<p>'+t('check_score_detail')+'</p>');
      $('#header_time').html('');
   }

   //questionIframe.iframe.contentWindow.ImagesLoader.refreshImages();
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
function loadContestData(contestID, contestFolder, groupPassword, teamID)
{
   $("#divImagesLoading").show();
   questionIframe.initialize(function() {
      if (fullFeedback) {
         $.post("graders.php", {SID: SID, ieMode: window.ieMode}, function(data) {
            if (data.status === 'success' && (data.graders || data.gradersUrl)) {
               questionIframe.gradersLoaded = true;
               if (data.graders) {
                  $('#divGradersContent').html(data.graders);
               } else {
                  $('#divGradersContent').load(data.gradersUrl);
               }
            }
            if (data.status == 'success') { bonusScore = parseInt(data.bonusScore); }
         }, 'json');
      }
      // The callback will be used by the task
      questionIframe.iframe.contentWindow.ImagesLoader.setCallback(function() {
         $("#divHeader").hide();
         $("#divQuestions").show();
         if (fullFeedback) {
            $('.chrono').css('font-size', '1.3em');
            $('.fullFeedback').show();
         }
         showQuestionIframe();
         $("#divImagesLoading").hide();

         $.post("data.php", {SID: SID, action: "loadContestData", groupPassword: groupPassword, teamID: teamID},
         function(data) {
            if (!data.success) {
               $("#ReloginResult").html(t("invalid_password"));
               Utils.enableButton("buttonRelogin");
               return;
            }
            $("#divCheckGroup").hide();

            function oldLoader() {
               $.get(window.contestsRoot + '/' + contestFolder + "/contest_" + contestID + ".html", function(content) {
                  $('#divQuestionsContent').html(content);
                  setupContest(data);
               });
            }

            function newLoader() {
               var log_fn = function(text) {
                  $('.questionList').html("<span style='font-size:2em;padding-left:10px'>" + text + "</span>");
               };
               var loader = new Loader(window.contestsRoot + '/' + contestFolder + '/', log_fn);
               loader.run().done(function(content) {
                  $('#divQuestionsContent').html(content);
                  setupContest(data);
               }).fail(function() {
                  oldLoader();
               });
            }

            // XXX: select loader here
            newLoader();

         }, "json");
      });

      questionIframe.iframe.contentWindow.ImagesLoader.preload(contestFolder);
   });
}

/**
 * Update the number of preloaded images
 * Called by the task
 *
 * @param {string} content
 */
window.setNbImagesLoaded = function(content) {
   $("#nbImagesLoaded").html(content);
};

// Team connexion

/*
 * Called when starting a contest by providing a group code on the main page.
*/
window.checkGroup = function() {
   var groupCode = $("#groupCode").val();
   return window.checkGroupFromCode("CheckGroup", groupCode, false, false);
};

window.recoverGroup = function() {
   var curStep = 'CheckGroup';
   var groupCode = $("#groupCode").val();
   var groupPass = $('#recoverGroupPass').val();
   if (!groupCode || !groupPass) {return false;}
   $('#recoverGroupResult').html('');
   Utils.disableButton("buttonRecoverGroup");
   $.post("data.php", {SID: SID, action: "recoverGroup", groupCode: groupCode, groupPass: groupPass},
      function(data) {
         if (!data.success) {
            if (data.message) {
               $('#recoverGroupResult').html(data.message);
            } else {
               $('#recoverGroupResult').html(t("invalid_code"));
            }
            return;
         }
         window.checkGroup();
      },
   'json').done(function() { Utils.enableButton("buttonRecoverGroup"); });
};

/*
 * Called when trying to continue a contest after an interruption
 * The password can either be a group password (leading to another page)
 * or directly a team password (to re-login directly)
*/
window.checkPasswordInterrupted = function() {
   var password = $("#interruptedPassword").val();
   return window.checkGroupFromCode("Interrupted", password, true, false);
};

/*
 * Fills a select field with all the names of the teams (of a given group)
 * Used to continue a contest if the students didn't write down the team password
*/
function fillListTeams(teams) {
   for (var curTeamID in teams) {
      var team = teams[curTeamID];
      var teamName = "";
      for (var iContestant in team.contestants) {
         var contestant = team.contestants[iContestant];
         if (iContestant == 1) {
            teamName += " et ";
         }
         teamName += contestant.firstName + " " + contestant.lastName;
      }
      $("#selectTeam").append("<option value='" + curTeamID + "'>" + teamName + "</option>");
   }
}

/*
 * Called when students validate the form that asks them if they participate
 * alone or in a team of two students.
*/
var nbContestants;
window.setNbContestants = function(nb) {
   nbContestants = nb;
   if (nbContestants === 2) {
      $("#contestant2").show();
   }
   $("#divLogin").show();
   $("#divCheckNbContestants").hide();
};

var fieldsHidden = {};

var hideLoginFields = function(postData) {
   var contestFieldMapping = {
      askEmail: 'email',
      askGrade: 'grade',
      askStudentId: 'studentId',
      askZip: 'zipCode',
      askGenre: 'genre'
   };
   for (var contestFieldName in contestFieldMapping) {
      var loginFieldName = contestFieldMapping[contestFieldName];
      if (postData[contestFieldName]) {
         fieldsHidden[loginFieldName] = false;
         $('#login-input-'+loginFieldName+'-1').show();
         $('#login-input-'+loginFieldName+'-2').show();
      } else {
         fieldsHidden[loginFieldName] = true;
         $('#login-input-'+loginFieldName+'-1').hide();
         $('#login-input-'+loginFieldName+'-2').hide();
      }
   }
};

/*
 * Checks if a group is valid and loads information about the group and corresponding contest,
 * curStep: indicates which step of the login process the students are currently at :
 *   - "CheckGroup" if loading directly from the main page (public contest or group code)
 *   - "Interrupted" if loading from the interface used when continuing an interupted contest
 * groupCode: a group code, or a team password
 * isPublic: is this a public group ?
*/
window.checkGroupFromCode = function(curStep, groupCode, getTeams, isPublic) {
   Utils.disableButton("button" + curStep);
   $('#recoverGroup').hide();
   $("#" + curStep + "Result").html('');
   $.post("data.php", {SID: SID, action: "checkPassword", password: groupCode, getTeams: getTeams},
      function(data) {
         if (!data.success) {
            if (data.message) {
               $("#" + curStep + "Result").html(data.message);
            } else {
               $("#" + curStep + "Result").html(t("invalid_code"));
            }
            return;
         }
         initContestData(data);
         $("#headerH2").html(data.name);
         if (data.teamID !== undefined) { // The password of the team was provided directly
            $("#div" + curStep).hide();
            teamID = data.teamID;
            teamPassword = groupCode;
            loadContestData(contestID, contestFolder);
         } else {
            if ((data.nbMinutesElapsed > 30) && (data.isPublic === "0") && (!getTeams)) {
               if (parseInt(data.bRecovered)) {
                  alert(t("group_session_expired"));
                  window.location = t("contest_url");
                  return false;
               } else {
                  $("#recoverGroup").show();
                  return false;
               }
            }
            $("#div" + curStep).hide();
            hideLoginFields(data);
            if (curStep === "CheckGroup") {
               if (isPublic) {
                  window.setNbContestants(1);
                  createTeam([{ lastName: "Anonymous", firstName: "Anonymous", genre: 2, email: null, zipCode: null}]);
               } else if (data.allowTeamsOfTwo == 1) {
                  $("#divCheckNbContestants").show();
               } else {
                  window.setNbContestants(1);
               }
            } else {
               fillListTeams(data.teams);
               $("#divRelogin").show();
            }
         }
      }, "json").done(function() { Utils.enableButton("button" + curStep); });
};

/*
 * Validates student's information form
 * then creates team
*/
window.validateLoginForm = function() {
   var contestants = {};
   for (var iContestant = 1; iContestant <= nbContestants; iContestant++) {
      var contestant = {
         "lastName" : $.trim($("#lastName" + iContestant).val()),
         "firstName" : $.trim($("#firstName" + iContestant).val()),
         "genre" : $("input[name='genre" + iContestant + "']:checked").val(),
         "grade" : $("#grade" + iContestant).val(),
         "email" : $.trim($("#email" + iContestant).val()),
         "zipCode" : $.trim($("#zipCode" + iContestant).val()),
         "studentId" : $.trim($("#studentId" + iContestant).val())
      };
      contestants[iContestant] = contestant;
      if (!contestant.lastName && !fieldsHidden.lastName) {
         $("#LoginResult").html(t("lastname_missing"));
         return;
      } else if (!contestant.firstName && !fieldsHidden.firstName) {
         $("#LoginResult").html(t("firstname_missing"));
         return;
      } else if (!contestant.genre && !fieldsHidden.genre) {
         $("#LoginResult").html(t("genre_missing"));
         return;
      } else if (!contestant.email && !fieldsHidden.email) {
         $("#LoginResult").html(t("email_missing"));
         return;
      } else if (!contestant.zipCode && !fieldsHidden.zipCode) {
         $("#LoginResult").html(t("zipCode_missing"));
         return;
      } else if (!contestant.studentId && !fieldsHidden.studentId) {
         $("#LoginResult").html(t("studentId_missing"));
         return;
      } else if (!contestant.grade && !fieldsHidden.grade) {
         $("#LoginResult").html(t("grade_missing"));
         return;
      }
   }
   Utils.disableButton("buttonLogin"); // do not re-enable
   createTeam(contestants);
};

/*
 * Creates a new team using contestants information
*/
function createTeam(contestants) {
   $.post("data.php", {SID: SID, action: "createTeam", contestants: contestants},
      function(data) {
         teamID = data.teamID;
         teamPassword = data.password;
         $("#divLogin").hide();
         $("#teamPassword").html(data.password);
         $("#divPassword").show();
      }, "json");
}

/*
 * Called when students acknowledge their new team password
 * hides password and loads contest
*/
window.confirmTeamPassword = function() {
   if (!Utils.disableButton("buttonConfirmTeamPassword")) { // Do not re-enable
      return;
   }
   $("#divPassword").hide();
   loadContestData(contestID, contestFolder);
};

/*
 * Called when students select their team in the list of teams of their group,
 * and the teacher enters the group password (to continue after an interruption)
 * Tries to load the corresponding contest.
*/
window.relogin = function() {
   teamID = $("#selectTeam").val();
   var groupPassword = $("#groupPassword").val();
   if (teamID == '0') {
      $("#ReloginResult").html(t("select_team"));
      return;
   }
   Utils.disableButton("buttonRelogin");
   loadContestData(contestID, contestFolder, groupPassword, teamID);
};

/*
 * Generates the html for the list of public groups
*/
function getPublicGroupsList(groups) {
   var arrGroups = {};
   var years = {};
   var categories = {};
   var year, group,category;
   var maxYear = 0;
   for (var iGroup = 0 ; iGroup < groups.length ; iGroup ++) {
      group = groups[iGroup];
      if (!arrGroups[group.level]) {
         arrGroups[group.level] = {};
      }
      year = group.year % 10000;
      arrGroups[group.level][group.category] = group;
      years[year] = true;
      if (!categories[year]) {
        categories[year] = [];
      }
      categories[year][group.category] = true;
      maxYear = Math.max(maxYear, year);
   }
   var levels = [
      {name: t("level_1_name"), i18name: "level_1_name", id: 1},
      {name: t("level_2_name"), i18name: "level_2_name", id: 2},
      {name: t("level_3_name"), i18name: "level_3_name", id: 3},
      {name: t("level_4_name"), i18name: "level_4_name", id: 4},
      {name: t("level_all_questions_name"), i18name: "level_all_questions_name", id: 0}
   ];
   var strGroups = "<table style='border:solid 1px black' cellspacing=0 cellpadding=5>";
   for (year = maxYear; years[year] === true; year--) {
      for (category in categories[year]) {
         var nbGroupsInCategory = 0;
         var thisCategoryStrGroup = '';
         strGroups += "<tr class='groupRow'><td style='width:100px;border:solid 1px black;text-align:center'><b>" + category + "</b></td>";
         for (var iLevel = 0; iLevel < levels.length; iLevel++) {
            var level = levels[iLevel];
            group = undefined;
            if (arrGroups[level.id]) {
               group = arrGroups[level.id][category];
            }
            if (group) {
               thisCategoryStrGroup += "<td style='width:100px;border:solid 1px black;text-align:center'>" +
                  "<a href='#' onclick='checkGroupFromCode(\"CheckGroup\", \"" + group.code + "\", false, true)' data-i18n=\"[html]"+level.i18name+"\"> " + level.name + "</a></td>";
                  nbGroupsInCategory = nbGroupsInCategory + 1;
            } else {
               thisCategoryStrGroup += "<td width=20%></td>";
            }
         }
         if (nbGroupsInCategory == 1 && arrGroups[0] && arrGroups[0][category]) {
            group = arrGroups[0][category];
            thisCategoryStrGroup = "<td colspan=\"5\" style='width:500px;border:solid 1px black;text-align:center'>" +
                  "<a href='#' onclick='checkGroupFromCode(\"CheckGroup\", \"" + group.code + "\", false, true)' data-i18n=\"[html]level_all_levels_name\"> " + t("level_all_levels_name") + "</a></td>";
         }
         strGroups = strGroups + thisCategoryStrGroup;
         strGroups += "</tr>";
      }
   }
   strGroups += "</table>";
   return strGroups;
}

function initContestData(data) {
   contestID = data.contestID;
   contestFolder = data.contestFolder;
   updateContestName(data.contestName);
   fullFeedback = parseInt(data.fullFeedback);
   nextQuestionAuto = parseInt(data.nextQuestionAuto);
   nbUnlockedTasksInitial = parseInt(data.nbUnlockedTasksInitial);
   newInterface = !!parseInt(data.newInterface);
   customIntro = $("<textarea/>").html(data.customIntro).text();
   contestOpen = !!parseInt(data.contestOpen);
   contestVisibility = data.contestVisibility;
   contestShowSolutions = !!parseInt(data.contestShowSolutions);
   TimeManager.setTotalTime(data.nbMinutes * 60);
   if (newInterface) {
      $("#question-iframe-container").addClass("newInterfaceIframeContainer");
      $(".oldInterface").html("").hide();
      $(".newInterface").show();
      window.backToList();
   } else {
      $("#question-iframe-container").addClass("oldInterfaceIframeContainer");
      $(".newInterface").html("").hide();
      $(".oldInterface").show();
   }
}

/*
 * Loads all the information about a session if a session is already opened
 * Otherwise, displays the list of public groups.
*/
function loadSession() {
   $.post("data.php", {SID: SID, action: 'loadSession'},
      function(data) {
         SID = data.SID;
         if (data.teamID) {
            if (!confirm("Voulez-vous reprendre l'épreuve commencée ?")) {
               destroySession();
               return;
            }
            teamID = data.teamID;
            initContestData(data);
            $("#divCheckGroup").hide();
            loadContestData(contestID, contestFolder);
            return;
         }
      }, "json");
}

function destroySession() {
   SID = null; // are we sure about that?
   $.post("data.php", {action: 'destroySession'},
      function(data) {
         SID = data.SID;
      }, "json");
}

function loadPublicGroups() {
   $.post("data.php", {action: 'loadPublicGroups'},
      function(data) {
           //$("#classroomGroups").show();
         if (data.groups.length !== 0) {
            $("#listPublicGroups").html(getPublicGroupsList(data.groups));
         }
         $("#contentPublicGroups").show();
         $("#loadPublicGroups").hide();
      }, 'json');
}

// Obtain an association array describing the parameters passed to page
function getPageParameters() {
   var str = window.location.search.substr(1);
   var params = {};
   if (str) {
      var items = str.split("&");
      for (var idItem = 0; idItem < items.length; idItem++) {
         var tmp = items[idItem].split("=");
         params[tmp[0]] = decodeURIComponent(tmp[1]);
      }
   }
   return params;
}

/*
 * Initialisation
 * Cleans up identification form (to avoid auto-fill for some browser)
 * Inits ajax error handler
 * Loads current session or list of public groups
*/
function init() {
   for (var contestant = 1; contestant <= 2; contestant++) {
      $("#firstName" + contestant).val("");
      $("#lastName" + contestant).val("");
      $("#genre" + contestant + "_female").attr('checked', null);
      $("#genre" + contestant + "_male").attr('checked', null);
   }
   initErrorHandler();
   loadSession();
   // Load initial tab according to parameters
   var params = getPageParameters();
   if (params.tab)
      window.selectMainTab(params.tab);
}

/*
 * Called when a student clicks on the button to stop before the timer ends
*/
window.tryCloseContest = function() {
   var remainingTime = TimeManager.getRemainingTime();
   var nbMinutes = Math.floor(remainingTime / 60);
   if (nbMinutes > 1) {
      if (!confirm(t("time_remaining_1") + nbMinutes + t("time_remaining_2"))) {
         return;
      }
      if (!confirm(t("confirm_stop_early"))) {
         return;
      }
   }
   closeContest(t("thanks_for_participating"));
};

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
   hasDisplayedContestStats = true;
   Utils.disableButton("buttonClose");
   Utils.disableButton("buttonCloseNew");
   $("#divQuestions").hide();
   hideQuestionIframe();
   if (questionIframe.task) {
      questionIframe.task.unload(function() {
         doCloseContest(message);
      }, function() {
         logError(arguments);
         doCloseContest(message);
      });
   } else {
      doCloseContest(message);
   }
}

function doCloseContest(message) {
   $("#divHeader").show();
   $("#divClosed").show();
   if ($.isEmptyObject(answersToSend)) {
      Tracker.trackData({send: true});
      Tracker.disabled = true;
      finalCloseContest(message);
   } else {
      $("#divClosedPleaseWait").show();
      delaySendingAttempts = 10000;
      sendAnswers();
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
   TimeManager.stopNow();
   $.post("data.php", {SID: SID, action: "closeContest", teamID: teamID, teamPassword: teamPassword},
      function() {}, "json"
   ).always(function() {
      window.onbeforeunload = function(){};
      if (!contestShowSolutions) {
         $("#divClosedPleaseWait").hide();
         $("#divClosedMessage").html(message);
         var listAnswers = [];
         for(var questionID in answersToSend) {
            var answerObj = answersToSend[questionID];
            listAnswers.push([questionID, answerObj.answer]);
         }
         if (listAnswers.length !== 0) {
            var encodedAnswers = base64_encode(JSON.stringify({pwd: teamPassword, ans: listAnswers}));
            $("#encodedAnswers").html(encodedAnswers);
            $("#divClosedEncodedAnswers").show();
         }
         $("#remindTeamPassword").html(teamPassword);
         $("#divClosedRemindPassword").show();
      } else {
         $("#divQuestions").hide();
         hideQuestionIframe();
         $("#divImagesLoading").show();
         $("#divHeader").show();

         showScoresHat();
         if (newInterface) {
            var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
            updateUnlockedLevels(sortedQuestionIDs, null, true);
            $('#questionListIntro').html('<p>'+t('check_score_detail')+'</p>');
            $('#header_time').html('');
         }
      }
   });
}


/*
 * Called when the team's contest participation is over, and it's not
 * a "restricted" contest.
 * Computes the scores for each question using the task's graders
 * the score for each question as well as the total score.
 * Send the scores to the server, then display the solutions
*/
function showScoresHat() {
   // in case of fullFeedback, we don't need other graders
   if (fullFeedback) {
      showScores({bonusScore: bonusScore});
      return;
   }
   $.post("graders.php", {SID: SID, ieMode: window.ieMode}, function(data) {
      if (data.status === 'success' && (data.graders || data.gradersUrl)) {
         questionIframe.gradersLoaded = true;
         if (data.graders) {
            $('#divGradersContent').html(data.graders);
            showScores(data);
         } else {
            $.get(data.gradersUrl, function(content) {
               $('#divGradersContent').html(content);
               showScores(data);
            }).fail(function() {
               logError('cannot find '+data.gradersUrl);
               showScores({bonusScore: bonusScore});
            });
         }
      }
   }, 'json');
}

function showScores(data) {
   $(".scoreTotal").hide();
   // Compute scores
   teamScore = parseInt(data.bonusScore);
   maxTeamScore = parseInt(data.bonusScore);
   for (var questionID in questionsData) {
      var questionData = questionsData[questionID];
      var questionKey = questionData.key;
      var answer = answers[questionKey];
      var minScore = questionData.minScore;
      var noAnswerScore = questionData.noAnswerScore;
      var maxScore = questionData.maxScore;
      if (answer) {
         // Execute the grader in the question context
         questionsToGrade.push({
            answer: answer,
            minScore: minScore,
            maxScore: maxScore,
            noScore: questionData.noAnswerScore,
            options: questionData.options,
            questionKey: questionKey
         });
      }
      else {
         // No answer given
         scores[questionKey] = {
             score: noAnswerScore,
             maxScore: maxScore
         };
         teamScore += parseInt(scores[questionKey].score);
      }
      maxTeamScore += parseInt(maxScore);
   }
   gradeQuestion(0);
}

// Grade the i'est question, then call the (i+1)'est or send the score
function gradeQuestion(i) {
   if (i >= questionsToGrade.length) {
      sendScores();
      return;
   }

   var curQuestion = questionsToGrade[i];

   questionIframe.load({'task': true, 'grader': true}, curQuestion.questionKey, function() {
      questionIframe.task.gradeAnswer(curQuestion.answer, null, function(newScore, message) {
         scores[curQuestion.questionKey] = {
            score: newScore,
            maxScore: curQuestion.maxScore
         };
         teamScore += parseInt(scores[curQuestion.questionKey].score);
         gradeQuestion(i + 1);
      });
   });
}

// Send the computed scores, then load the solutions
function sendScores() {
   $.post('scores.php', { scores: scores, SID: SID }, function(data) {
      if (data.status === 'success') {
         loadSolutionsHat();
         if (bonusScore) {
            $(".scoreBonus").html($(".scoreBonus").html().replace('50', bonusScore));
            $(".scoreBonus").show();
         }
         $(".questionScore").css("width", "50px");
         $(".questionListHeader").css("width", "265px");
         $(".question, #divQuestionParams, #divClosed, .questionsTable").css("left", "272px");
         var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
         for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
            var questionID = sortedQuestionIDs[iQuestionID];
            var questionKey = questionsData[questionID].key;
            var image = "";
            var score = 0;
            var maxScore = 0;
            if (scores[questionKey] !== undefined) {
               score = scores[questionKey].score;
               maxScore = scores[questionKey].maxScore;
               if (score < 0) {
                  image = "<img src='images/35.png'>";
               } else if (score == maxScore) {
                  image = '<span class="check">✓</span>';
               } else if (score !== "0") {
                  image = "<img src='images/check.png'>";
               } else {
                  image = "";
               }
            }
            if (!newInterface) {
               $("#bullet_" + questionKey).html(image);
               $("#score_" + questionKey).html("<b>" + score + "</b> / " + maxScore);
            }
         }
         $(".scoreTotal").hide();
         $(".chrono").html("<tr><td style='font-size:28px'> " + t("score") + ' ' + teamScore + " / " + maxTeamScore + "</td></tr>");
         $(".chrono").css("background-color", "#F66");
   //      window.selectQuestion(sortedQuestionIDs[0], false);
      }
   }, 'json');
}

// Questions tools

function getSortedQuestionIDs(questionsData) {
   var questionsByOrder = {};
   var orders = [];
   var order;
   for (var questionID in questionsData) {
      var questionData = questionsData[questionID];
      order = parseInt(questionData.order);
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
   for (var iOrder = 0; iOrder < orders.length; iOrder++) {
      order = orders[iOrder];
      questionsByOrder[order].sort(function(id1, id2) { if (id1 < id2) return -1; return 1; });
      var shuffledOrder = Utils.getShuffledOrder(questionsByOrder[order].length, teamID + iOrder);
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
   for (var iQuestion = 0; iQuestion < sortedQuestionsIDs.length; iQuestion++) {
      var questionID = sortedQuestionsIDs[iQuestion];
      if (prevQuestionID !== "0") {
         questionsData[prevQuestionID].nextQuestionID = questionID;
      }
      prevQuestionID = questionID;
   }
   questionsData[prevQuestionID].nextQuestionID = "0";
}

window.backToList = function() {
   $(".questionListIntro").show();
   $(".questionList").show();
   $(".buttonClose").show();
   $("#question-iframe-container").hide();
   $(".button_return_list").prop("disabled",true);
};

window.selectQuestion = function(questionID, clicked, noLoad) {
   // Prevent double-click until we fix the issue with timeouts
   var curTime = (new Date()).getTime();
   if (curTime - lastSelectQuestionTime < 1000) {
      if (curTime - lastSelectQuestionTime < 0) {
         // in case the computer time changes during the contest, we reset lastSelectQuestionTime, to make sure the user doesn't get stuck
         lastSelectQuestionTime = curTime; 
      } else {
         return;
      }
   }
   lastSelectQuestionTime = curTime;
   $("body").scrollTop(0);
   try {
      if (document.getSelection) {
         var selection = document.getSelection();
         if (selection && selection.removeAllRanges) {
            selection.removeAllRanges();
         }
      }
   } catch(err) {}
   var questionData = questionsData[questionID];
   questionData.visited = true;
   var questionKey = questionData.key;

   if (newInterface) {
      $(".questionListIntro").hide();
      $(".questionList").hide();
      $(".buttonClose").hide();
      $("#question-iframe-container").show();
      $(".button_return_list").prop("disabled", false);
   }

   var nextStep = function() {
      Tracker.trackData({dataType:"selectQuestion", teamID: teamID, questionKey: questionKey, clicked: clicked});
      var questionName = questionData.name.replace("'", "&rsquo;");
      var minScore = questionData.minScore;
      var maxScore = questionData.maxScore;
      var noAnswerScore = questionData.noAnswerScore;
      $("#question-" + currentQuestionKey).hide();
      $("#question-" + questionKey).show();
      $("#link_" + currentQuestionKey).attr("class", "questionLink");
      $("#link_" + questionKey).attr("class", "questionLinkSelected");
      if (! fullFeedback) {
         $("#questionPoints").html( "<table class='questionScores' cellspacing=0><tr><td>" + t("no_answer") + "</td><td>" + t("bad_answer") + "</td><td>" + t("good_answer") + "</td></tr>" +
            "<tr><td><span class='scoreNothing'>" + noAnswerScore + "</span></td>" +
            "<td><span class='scoreBad'>" + minScore + "</span></td>" +
            "<td><span class='scoreGood'>+" + maxScore + "</span></td></tr></table>");
      }
      $(".questionTitle").html(questionName);
      if (newInterface) {
         drawStars('questionStars', 4, 24, getQuestionScoreRate(questionData), "normal", getNbLockedStars(questionData)); // stars under icon on main page
      }
      currentQuestionKey = questionKey;

      if (!questionIframe.initialized) {
         questionIframe.initialize();
      }
      var taskViews = {"task": true};
      if (questionIframe.gradersLoaded) {
         taskViews.grader = true;
      }
      if (TimeManager.isContestOver()) {
         taskViews.solution = true;
      }
      if (!noLoad) {
         questionIframe.load(taskViews, questionKey, function() {});
      }
   };

   if (questionIframe.task) {
      questionIframe.task.getAnswer(function(answer) {
         if ( ! TimeManager.isContestOver() && ((answer !== defaultAnswers[questionIframe.questionKey]) || (typeof answers[questionIframe.questionKey] != 'undefined'))) {
            if (fullFeedback) {
               platform.validate("stay");
            } else if ((typeof answers[questionIframe.questionKey] == 'undefined') || (answers[questionIframe.questionKey] != answer)) {
               if (!confirm(" Êtes-vous sûr de vouloir changer de question ? Votre réponse n'a pas été enregistrée et va être perdue.")) {
                  return;
               }
            }
         }
         nextStep();
      }, function() {
         logError(arguments);
         nextStep();
      });
   } else {
      nextStep();
   }
};

function markAnswered(questionKey, answer) {
   if (newInterface) {
      return;
   }
   if (answer === "") {
      $("#bullet_" + questionKey).html("");
   } else {
      if (fullFeedback && typeof scores[questionKey] !== 'undefined' && scores[questionKey].score == scores[questionKey].maxScore) {
         $("#bullet_" + questionKey).html('<span class="check">✓</span>');
      } else {
         $("#bullet_" + questionKey).html("&diams;");
      }
   }
}

function submitAnswer(questionKey, answer, score) {
   if (typeof answer !== 'string') {
      logError('trying to submit non-string answer: '+answer);
      return;
   }
   if (!newInterface) {
      $("#bullet_" + questionKey).html("&loz;");
   }
   answersToSend[questionsKeyToID[questionKey]] = { answer: answer, sending:false, 'score': score };
   nbSubmissions++;
   Tracker.trackData({dataType:"answer", teamID: teamID, questionKey: questionKey, answer: answer});
   sendAnswers();
}

function computeFullFeedbackScore() {
   ffTeamScore = bonusScore ? bonusScore : 0;
   ffMaxTeamScore = 0;
   for (var questionID in questionsData) {
      var questionKey = questionsData[questionID].key;
      ffMaxTeamScore += questionsData[questionID].maxScore;
      if (scores[questionKey]) {
         ffTeamScore += parseInt(scores[questionKey].score);
      } else {
         ffTeamScore += questionsData[questionID].noAnswerScore;
      }
   }
   if (newInterface) {
      var strScore = ffTeamScore + " point";
      if (ffTeamScore > 1) {
         strScore += "s";
      }
      $(".scoreTotalFullFeedback").html(strScore);
   } else {
      $(".scoreTotalFullFeedback").html(ffTeamScore+' / '+ffMaxTeamScore);
   }
}

// Sending answers

function failedSendingAnswers() {
   Tracker.disabled = true;
   sending = false;
   for(var questionID in answersToSend) {
      answersToSend[questionID].sending = false;
   }
   setTimeout(sendAnswers, delaySendingAttempts);
}

function initErrorHandler() {
   // TODO: call on document for jquery 1.8+
   $( "body" ).ajaxError(function(e, jqxhr, settings, exception) {
     if ( settings.url == "answer.php" ) {
         failedSendingAnswers();
     } else {
        if ((exception === "") || (exception === "Unknown")) {
           if (confirm(t("server_not_responding_try_again"))) {
              $.ajax(settings);
           }
        } else if (exception === "timeout") {
           $("#contentError").html(t("exception") + exception + "<br/><br/>" + 'Le concours n\'a pas été correctement initialisé. Merci de recharger votre page.');
           $("#divError").show();
        } else {
           $("#contentError").html(t("exception") + exception + "<br/><br/>" + t("server_output") + "<br/>" + jqxhr.responseText);
           $("#divError").show();
        }
     }
   });
}

function base64_encode(str) {
   return btoa(utf8.encode(str));
}

function base64url_encode(str) {
	return base64_encode(str).replace('+', '-').replace('/', '_');
}

// TODO: is it still used?
function addAnswerPing(questionID, answer) {
   // add image ping
   var img = document.createElement('img');
   $('body').append($('<img>', { width: 1, height: 1, 'class': 'hidden',
      src: 'http://castor.armu.re/' + [
         encodeURIComponent(SID),
         teamID,
         questionID,
         base64url_encode(answer)
      ].join('/') }));
}

function sendAnswers() {
   if (sending) {
      return;
   }
   sending = true;
   var somethingToSend = false;
   for(var questionID in answersToSend) {
      var answerObj = answersToSend[questionID];
      answerObj.sending = true;
      somethingToSend = true;
      //addAnswerPing(questionID, answerObj.answer);
   }
   if (!somethingToSend) {
      sending = false;
      return;
   }
   try {
      $.post("answer.php", {SID: SID, "answers": answersToSend, teamID: teamID, teamPassword: teamPassword},
      function(data) {
         sending = false;
         if (!data.success) {
            if (confirm(t("response_transmission_error_1") + " " + data.message + t("response_transmission_error_2"))) {
               failedSendingAnswers();
            }
            return;
         }
         var answersRemaining = false;
         for(var questionID in answersToSend) {
            var answerToSend = answersToSend[questionID];
            if (answerToSend.sending) {
               var questionKey = questionsData[questionID].key;
               markAnswered(questionKey, answersToSend[questionID].answer);
               delete answersToSend[questionID];
            } else {
               answersRemaining = true;
            }
         }
         if (answersRemaining) {
            setTimeout(sendAnswers, 1000);
         }
      }, "json").fail(failedSendingAnswers);
   } catch(exception) {
      failedSendingAnswers();
   }
}

// Solutions

function loadSolutionChoices(questionKey) {
   for (var iChoice = 0; iChoice < 10; iChoice++) {
      questionIframe.body.find('#container .' + questionKey + "_choice_" + (iChoice + 1))
         .html(questionIframe.body.find('#container #answerButton_' + questionKey + "_" + (iChoice + 1) + " input").val());
   }
}

function loadSolutionsHat() {
   $.post('solutions.php', {SID: SID, ieMode: window.ieMode}, function(data) {
      if (data.success) {
         if (data.solutions) {
            $('#divSolutionsContent').html(data.solutions);
            loadSolutions(data);
         } else {
            $.get(data.solutionsUrl, function(content) {
               $('#divSolutionsContent').html(content);
               loadSolutions(data);
            }).fail(function() {
              logError('a problem occured while fetching the solutions, please report to the administrators.');
              $("#divQuestions").show();
            });
         }
      }
   }, 'json');
}

function loadSolutions(data) {
   var sortedQuestionIDs = getSortedQuestionIDs(questionsData);
   for (var iQuestionID = 0; iQuestionID < sortedQuestionIDs.length; iQuestionID++) {
      var questionID = sortedQuestionIDs[iQuestionID];
      var questionData = questionsData[questionID];
      $("#question-" + questionData.key).append("<hr>" + $("#solution-" + questionData.key).html());
   }

   $("#divQuestions").hide();
   hideQuestionIframe();
   $("#divImagesLoading").show();
   $("#divHeader").show();

   // The callback will be used by the task
   if (questionIframe.iframe.contentWindow.preloadSolImages) {
     questionIframe.iframe.contentWindow.preloadSolImages();
   }
   setTimeout(function() {
      questionIframe.iframe.contentWindow.ImagesLoader.setCallback(function() {
         $("#divHeader").hide();
         $("#divQuestions").show();
         showQuestionIframe();
         $("#divClosed").hide();
         $('#question-iframe-container').css('left', '273px');
         $("#divImagesLoading").hide();
         if (!currentQuestionKey) {
            return;
         }
         questionIframe.task.getHeight(function(height) {
            platform.updateHeight(height);
            if (questionIframe.loaded) {
               questionIframe.task.unload(function() {
                  questionIframe.loadQuestion({'task': true, 'solution': true, 'grader': true}, currentQuestionKey, function(){});
               }, function() {
                  logError(arguments);
                  questionIframe.loadQuestion({'task': true, 'solution': true, 'grader': true}, currentQuestionKey, function(){});
               });
            } else {
               questionIframe.loadQuestion({'task': true, 'solution': true, 'grader': true}, currentQuestionKey, function(){});
            }
            alert(t("check_score_detail"));
         }, logError);
     });

     questionIframe.iframe.contentWindow.ImagesLoader.preload(contestFolder);
   }, 50);
}

var Tracker = {
   disabled: true,
   trackData: function(data) {
      if (Tracker.disabled) {
         return;
      }
      if (($("#trackingFrame").length > 0)) {
         $.postMessage(
            JSON.stringify(data),
            "http://eval02.france-ioi.org/castor_tracking/index.html",
            $("#trackingFrame")[0].contentWindow
         );
      }
   }
};

// TODO: is it still used?
function htmlspecialchars_decode(string, quote_style) {
   var optTemp = 0;
   var i = 0;
   var noquotes = false;

   if (typeof quote_style === 'undefined') {
     quote_style = 2;
   }
   string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
   var OPTS = {
     'ENT_NOQUOTES': 0,
     'ENT_HTML_QUOTE_SINGLE': 1,
     'ENT_HTML_QUOTE_DOUBLE': 2,
     'ENT_COMPAT': 2,
     'ENT_QUOTES': 3,
     'ENT_IGNORE': 4
   };
   if (quote_style === 0) {
     noquotes = true;
   }
   if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
      quote_style = [].concat(quote_style);
      for (i = 0; i < quote_style.length; i++) {
         // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
         if (OPTS[quote_style[i]] === 0) {
            noquotes = true;
         }
         else if (OPTS[quote_style[i]]){
            optTemp = optTemp | OPTS[quote_style[i]];
         }
      }
      quote_style = optTemp;
   }
   if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
      string = string.replace(/&#0*39;/g, "'"); // PHP doesn't currently escape if more than one 0, but it should
      // string = string.replace(/&apos;|&#x0*27;/g, "'"); // This would also be useful here, but not a part of PHP
   }
   if (!noquotes) {
      string = string.replace(/&quot;/g, '"');
   }
   // Put this in last place to avoid escape being double-decoded
   string = string.replace(/&amp;/g, '&');

   return string;
}

function hideQuestionIframe()
{
   $('#question-iframe-container').css('width', '0');
   $('#question-iframe-container').css('height', '0');
   $('#question-iframe').css('width', '0');
   $('#question-iframe').css('height', '0');
}

function showQuestionIframe()
{
   $('#question-iframe-container').css('width', 'auto');
   $('#question-iframe-container').css('height', 'auto');
   $('#question-iframe').css('width', '782px');
   $('#question-iframe').css('height', 'auto');
}

//
// Loader
//

var Loader = function(base, log_fn) {
   this.log = log_fn;
   this.base = base;
   this.queue = [];
   this.parts = [];
   this.n_loaded = 0;
   this.n_total = 0;
};
Loader.prototype.version = 1.2;
Loader.prototype.add = function(items) {
   this.queue = this.queue.concat(items);
   this.n_total += items.length;
};
Loader.prototype.assemble = function() {
   var self = this;
   self.log('A');
   setTimeout(function() {
      var data = self.parts.join('');
      self.promise.resolve(data);
   }, 100);
};
Loader.prototype.load_next = function() {
   var self = this;
   if (self.queue.length === 0) {
      this.assemble();
   } else {
      var item = self.queue.shift();
      var url = self.base + item;
      self.start_time = new Date().getTime();
      $.ajax(self.base + item, { dataType: 'text', global: false }).done(function(data, textStatus, xhr) {
         try {
            var delta = new Date().getTime() - self.start_time;
            self.n_loaded += 1;
            // speed of last download in b/ms, or kb/s (data.length is approximately in bytes)
            var last_speed = data.length * 8 / delta;
            // factor so that delay is around 4s at 10kb/s, 0.4s at 100kb/s
            // multiplying by 1+rand() so that users in the same room don't wait the same time, causing bottlenecks
            var k = 30000 * (1 + Math.random());
            var delay = Math.round(k / last_speed);
            if (delay > 5000) { // no more than 5s waiting
               delay = 5000;
            }
            self.log(Math.round(self.n_loaded * 100 / self.n_total) + '%');
            self.parts.push(data);
            setTimeout(function() { self.load_next(); }, delay);
         } catch (e) {
            self.promise.reject(e);
         }
      }).fail(function(xhr, textStatus, err) {
         self.log(textStatus);
         self.promise.reject(textStatus);
      });
   }
};
Loader.prototype.run = function() {
   var self = this;
   self.log('v' + self.version);
   this.promise = jQuery.Deferred(function() {
      $.ajax(self.base + 'index.txt', { dataType: 'text', global: false }).done(function(data, textStatus, xhr) {
         var index = data.replace(/^\s+|\s+$/g, '').split(/\s+/);
         index = self.shuffleArray(index);
         self.add(index);
         self.log('I');
         self.load_next();
      }).fail(function(xhr, textStatus, err) {
         self.promise.reject(textStatus);
      });
   });
   return self.promise;
};
Loader.prototype.shuffleArray= function (values) {
   var nbValues = values.length;
   for (var iValue = 0; iValue < nbValues; iValue++) {
      var pos = iValue + (Math.round(1000 * Math.random()) % (nbValues - iValue));
      var tmp = values[iValue];
      values[iValue] = values[pos];
      values[pos] = tmp;
   }
   return values;
};

var drawStars = function(id, nbStars, starWidth, rate, mode, nbStarsLocked) {
   $('#' + id).addClass('stars');

   function clipPath(coords, xClip) {
      var result = [[coords[0][0], coords[0][1]]];
      var clipped = false;
      for (var iCoord = 1; iCoord <= coords.length; iCoord++) {
         var x1 = coords[iCoord - 1][0];
         var y1 = coords[iCoord - 1][1];
         var x2 = coords[iCoord % coords.length][0];
         var y2 = coords[iCoord % coords.length][1];
         if (x2 > xClip) {
            if (!clipped) {
               result.push([xClip, y1 + (y2 - y1) * (xClip - x1) / (x2 - x1)]);
               clipped = true;
            }
         } else {
            if (clipped) {
               result.push([xClip, y1 + (y2 - y1) * (xClip - x1) / (x2 - x1)]);
               clipped = false;
            }
            result.push([x2, y2]);
         }
      }
      result.pop();
      return result;
   }

   function pathFromCoords(coords) {
      var result = 'm' + coords[0][0] + ',' + coords[0][1];
      for (var iCoord = 1; iCoord < coords.length; iCoord++) {
         var x1 = coords[iCoord - 1][0];
         var y1 = coords[iCoord - 1][1];
         var x2 = coords[iCoord][0];
         var y2 = coords[iCoord][1];
         result += ' ' + (x2 - x1) + ',' + (y2 - y1);
      }
      result += 'z';
      return result;
   }

   var fillColors = { normal: 'white', locked: '#ddd', useless: '#ced' };
   var strokeColors = { normal: 'black', locked: '#ddd', useless: '#444' };
   var starCoords = [[25, 60], [5, 37], [35, 30], [50, 5], [65, 30], [95, 37], [75, 60], [78, 90], [50, 77], [22, 90]];
   var fullStarCoords = [
      [[5, 37], [35, 30], [50, 5], [65, 30], [95, 37], [75, 60], [25, 60]],
      [[22, 90], [50, 77], [78, 90], [75, 60], [25, 60]]
   ];

   $('#' + id).html('');
   var paper = new Raphael(id, starWidth * nbStars, starWidth * 0.95);
   for (var iStar = 0; iStar < nbStars; iStar++) {
      var scaleFactor = starWidth / 100;
      var deltaX = iStar * starWidth;
      var coordsStr = pathFromCoords(starCoords, iStar * 100);
      var starMode = mode;
      if (iStar >= nbStars - nbStarsLocked) {
         starMode = "locked";
      }

      paper.path(coordsStr).attr({
         fill: fillColors[starMode],
         stroke: 'none'
      }).transform('s' + scaleFactor + ',' + scaleFactor + ' 0,0 t' + (deltaX / scaleFactor) + ',0');
      
      var ratio = Math.min(1, Math.max(0, rate * nbStars  - iStar));
      var xClip = ratio * 100;
      if (xClip > 0) {
         for (var iPiece = 0; iPiece < fullStarCoords.length; iPiece++) {
            var coords = clipPath(fullStarCoords[iPiece], xClip);
            var star = paper.path(pathFromCoords(coords)).attr({
               fill: '#ffc90e',
               stroke: 'none'
            }).transform('s' + scaleFactor + ',' + scaleFactor + ' 0,0 t' + (deltaX / scaleFactor) + ",0");
         }
      }
      paper.path(coordsStr).attr({
         fill: 'none',
         stroke: strokeColors[starMode],
         'stroke-width': 5 * scaleFactor
      }).transform('s' + scaleFactor + ',' + scaleFactor + ' 0,0 t' + (deltaX / scaleFactor) + ',0');
   }
};

function getParameterByName(name) {
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

$(document).on('ready', function() {
   var teamParam = getParameterByName('team');
   if (teamParam !== '') {
      window.checkGroupFromCode("CheckGroup", teamParam, false, false);
   } else {
      init();
   }
});

}();
