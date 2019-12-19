var gradePackSize = 20;
var curGradingData = null;
var curGradingBebras = null;
var curGradingScoreCache = {};

function loopGradeContest(curContestID, curGroupID, onlyMarked) {
   // Retrieve the list of questions of the contest
   $.post('questions.php', { contestID: curContestID, groupID: curGroupID }, function(data) {
      if (data.status === 'success') {
         var selectorState = curGroupID ? '#gradeGroupState' : '#gradeContestState';
         $(selectorState).show();
         $(selectorState).html(i18n.t('grading_in_progress')+'<span class="nbCurrent">0</span> / <span class="nbTotal">' + data.questionKeys.length + '</span> - ' + i18n.t("grading_current_question") + ' : <span class="current"></span> <span class="gradeprogressing"></span>');
         grade(curContestID, curGroupID, data.questionKeys, data.questionPaths, 0, onlyMarked);
      }
      else {
         jqAlert(data.message);
         return;
      }
   }, 'json');
}

function grade(curContestID, curGroupID, questionKeys, questionPaths, curIndex, onlyMarked)
{
   var selectorState = curGroupID ? '#gradeGroupState' : '#gradeContestState';
   if (curIndex >= questionKeys.length) {
      $(selectorState).show();
      if (curGroupID) {
         $(selectorState).html(i18n.t("grading_compute_total_scores") + '<span class="gradeprogressing"></span>');
         computeScores(curContestID, curGroupID, 0);
      } else {
         $('#buttonGradeContest').attr("disabled", false);
         $('#gradeContestState').html('');
      }
      return;
   }
   
   $(selectorState+' .nbCurrent').text(parseInt(curIndex) + 1);
   $(selectorState+' .current').text(questionKeys[curIndex]);
   
   // Retrieve the bebras/grader of the current question
   $.post('grader.php', { contestID: curContestID, groupID: curGroupID, questionKey: questionKeys[curIndex], onlyMarked: onlyMarked },function(data) {
      if (data.status === 'success') {
         var url = "bebras-tasks/" + questionPaths[curIndex];
         $("#preview_question").attr("src", url);
         
         // Retrieve bebras
         generating = true;
         $('#preview_question').load(function() {
            $('#preview_question').unbind('load');
            generating = false;
            curGradingData = data;
            curGradingData.noScore = curGradingData.noAnswerScore;
            // will be filled later
            curGradingData.randomSeed = 0;

            // Reset answers score cache
            curGradingScoreCache = {};
            try {
               TaskProxyManager.getTaskProxy('preview_question', function(task) {
                  var platform = new Platform(task);
                  platform.updateDisplay = function(data, success, error) { success(); };
                  platform.getTaskParams = function(key, defaultValue, success, error) {
                     var res = {};
                     if (key) {
                        if (key !== 'options' && key in curGradingData) {
                           res = curGradingData[key];
                        } else if (curGradingData.options && key in curGradingData.options) {
                           res = curGradingData.options[key];
                        } else {
                           res = (typeof defaultValue !== 'undefined') ? defaultValue : null;
                        }
                     } else {
                        res = {
                           randomSeed: curGradingData.randomSeed,
                           maxScore: curGradingData.maxScore,
                           minScore: curGradingData.minScore,
                           noAnswerScore: curGradingData.noAnswerScore,
                           noScore: curGradingData.noScore,
                           options: curGradingData.options
                        };
                     }
                     if (success) {
                        success(res);
                     } else {
                        return res;
                     }
                  };
                  TaskProxyManager.setPlatform(task, platform);
                  task.getResources(function(bebras) {
                     curGradingBebras = bebras;
                     task.load({'task': true, 'grader': true}, function() {
                        gradeQuestion(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, onlyMarked);
                     });
                  });
               }, true);
            } catch (e) {
               console.log('Task loading error catched : questionKey='+questionKeys[curIndex]);
               console.log(e);
            }
         });
      }
      else {
         jqAlert(data.message);
         return;
      }
   }, 'json');
}

function gradeQuestionPack(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, curPackIndex, onlyMarked) {
   var selectorState = curGroupID ? '#gradeGroupState' : '#gradeContestState';
   // Compute scores of a pack
   if (curPackIndex >= curGradingData.teamQuestions.length) {
      grade(curContestID, curGroupID, questionKeys, questionPaths, curIndex + 1, onlyMarked);
      return;
   }
   
   var packEndIndex = curPackIndex + gradePackSize;
   if (packEndIndex > curGradingData.teamQuestions.length) {
      packEndIndex = curGradingData.teamQuestions.length;
   }
   
   var scores = {};
   var i = 0;
   var answersToGrade = {};
   for (var curTeamQuestion = curPackIndex; curTeamQuestion < packEndIndex; curTeamQuestion++) {
      var teamQuestion = curGradingData.teamQuestions[curTeamQuestion];

      // XXX : must be in sync with common.js!!!
      curGradingData.randomSeed = teamQuestion.teamID;
      var usesRandomSeed = (('usesRandomSeed' in curGradingBebras) && curGradingBebras.usesRandomSeed);
      // If the answer is in cache and the task doesn't use randomSeed, the server side will update it
      // but only in the case of a contest global evaluation
      if ((!curGroupID) && (!usesRandomSeed) && 'cache_'+teamQuestion.answer in curGradingScoreCache) {
         continue;
      }
      
      scores[i] = {};
      // in some cases, score cannot be computed because the answer is invalid, so we have this default score
      // that will output "NULL" in the database
      scores[i].score = '';
      scores[i].questionID = teamQuestion.questionID;
      scores[i].teamID = teamQuestion.teamID;
      if (teamQuestion.answer.length < 100) {
         scores[i].answer = teamQuestion.answer;
      }
      scores[i].contestID = curContestID;
      scores[i].groupID = curGroupID;
      scores[i].usesRandomSeed = usesRandomSeed;
      if (curGroupID && (!usesRandomSeed) && teamQuestion.answer.length < 100 && 'cache_'+teamQuestion.answer in curGradingScoreCache) {
         scores[i].score = curGradingScoreCache['cache_'+teamQuestion.answer];
      }
      else if (teamQuestion.answer == '') {
         scores[i].score = parseInt(curGradingData.noAnswerScore);
      }
      else if (curGradingBebras.acceptedAnswers && curGradingBebras.acceptedAnswers[0]) {
         if (curGradingBebras.acceptedAnswers.indexOf($.trim(teamQuestion.answer)) > -1) {
            scores[i].score = curGradingData.maxScore;
         }
         else {
            scores[i].score = curGradingData.minScore;
         }
      }
      else {
         try {
            if (teamQuestion.answer.length > 200000) {
               scores[i].score = 0;
               console.log('Answer too long scored 0 : questionID='+teamQuestion.questionID+' teamID='+teamQuestion.teamID);
            }
            else {
               answersToGrade[i] = $.trim(teamQuestion.answer);
            }
         }
         catch (e) {
            console.log('Grading error catched : questionID='+teamQuestion.questionID+' teamID='+teamQuestion.teamID);
            console.log(e);
            console.log(teamQuestion.answer);
         }
      }

      // Cache the current answer's score
      if (!usesRandomSeed && teamQuestion.answer.length < 100) {
         curGradingScoreCache['cache_'+teamQuestion.answer] = scores[i].score;
      }

      i++;
   }
   // If not score need to be send, go to the next packet directly
   if (!i) {
      $(selectorState+' .gradeprogressing').text($(selectorState+' .gradeprogressing').text()+'.');
      gradeQuestionPack(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, curPackIndex + gradePackSize, onlyMarked);
      return;
   }
   
   gradeOneAnswer(task, answersToGrade, 0, scores, function() {
      gradeQuestionPackEnd(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, curPackIndex, scores, selectorState, onlyMarked);
   });
}

function gradeOneAnswer(task, answers, i, scores, finalCallback) {
   if (!scores[i]) {
      finalCallback();
      return;
   }
   answer = answers[i];
   if (!answer) {
      gradeOneAnswer(task, answers, i+1, scores, finalCallback);
      return;
   }
   curGradingData.randomSeed = scores[i].teamID;
   task.gradeAnswer(answer, null, function(score) {
      scores[i].score = score;
      scores[i].checkStatus = 'computed';
      if (answer.length < 100 && curGradingScoreCache['cache_'+answer] === '') {
         curGradingScoreCache['cache_'+answer] = score;
      }
      setTimeout(function() {
         gradeOneAnswer(task, answers, i+1, scores, finalCallback);
      },0);
   }, function() {
      scores[i].score = -2;
      scores[i].checkStatus = 'error';
      setTimeout(function() {
         gradeOneAnswer(task, answers, i+1, scores, finalCallback);
      },0);
   });
}

function gradeQuestionPackEnd(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, curPackIndex, scores, selectorState, onlyMarked) {
   var usesRandomSeed = (('usesRandomSeed' in curGradingBebras) && curGradingBebras.usesRandomSeed);
   // If the answer is in cache and the task doesn't use randomSeed, the server side will update it
   // but only in the case of a contest global evaluation
   if (curGroupID && (!usesRandomSeed)) {
      for (var i in scores) {
         var score = scores[i];
         if (score.score === '' && score.answer.length < 100 && 'cache_'+score.answer in curGradingScoreCache) {
            score.score = curGradingScoreCache['cache_'+score.answer];
         }
      }
   }
   // Send the computed scores to the platform
   $.post('scores.php', { scores: scores, questionKey: curGradingData.questionKey, groupMode: (typeof curGroupID !== 'undefined') },function(data) {
      if (data.status !== 'success') {
         jqAlert('Something went wrong while sending those scores : '+JSON.stringify(scores));
         return false;
      }
      /**
       * Timeout each 25 packs to make the garbage collector work
       */
      if (parseInt(curPackIndex / gradePackSize) % 25 === 0) {
         setTimeout(function() {
            $(selectorState+' .gradeprogressing').text($(selectorState+' .gradeprogressing').text()+'.');
            gradeQuestionPack(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, curPackIndex + gradePackSize, onlyMarked);
         }, 5000);
      }
      else {
         $(selectorState+' .gradeprogressing').text($(selectorState+' .gradeprogressing').text()+'.');
         gradeQuestionPack(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, curPackIndex + gradePackSize, onlyMarked);
      }
   }, 'json').fail(function() {
      jqAlert('Something went wrong while sending scores...');
      return false;
   });
}

function gradeQuestion(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, onlyMarked) {
   // Compute all scores by pack
   if (curGradingBebras.grader[0] && curGradingBebras.grader[0].content) {
      $('#preview_question')[0].contentWindow.eval($('#preview_question')[0].contentWindow.eval(curGradingBebras.grader[0].content));
   }
   
   gradeQuestionPack(task, curContestID, curGroupID, questionKeys, questionPaths, curIndex, 0, onlyMarked);
}

/**
 * Compute the scores of a contest packet
 * 
 * @param {int} curContestID
 * @param {int} packetNumber
 */
function computeScores(curContestID, curGroupID, packetNumber)
{
   // Compute teams total score
   $.post('totalScores.php', { contestID: curContestID, groupID: curGroupID, begin: packetNumber },function(data) {
      var selectorButton = curGroupID ? '#buttonGradeSelected_group' : '#buttonComputeScoresContest';
      var selectorState = curGroupID ? '#gradeGroupState' : '#gradeContestState';
      if (data.status === 'success') {
         if (data.finished) {
            $(selectorState).hide();
            $(selectorState).html('');
            var button = $(selectorButton);
            var msg = i18n.t("grading_scores_computed");
            if (typeof data.differences !== 'undefined') {
               var differences = parseInt(data.differences);
               if (!differences) {
                  msg += " " + i18n.t("grading_scores_no_difference");
               } else if (differences === 1) {
                  msg += " " + i18n.t("grading_scores_one_difference");
               } else if (differences > 1) {
                  msg += " " + i18n.t("grading_scores_with_differences", {nbDifferences: differences});
               }
            }
            jqAlert(msg);
            button.attr("disabled", false);
         }
         else {
            $(selectorState+' .gradeprogressing').html($(selectorState+' .gradeprogressing').html()+'.');
            computeScores(curContestID, curGroupID, packetNumber + 1);
         }
      }
      else {
         jqAlert(data.message);
         return;
      }
   }, 'json');
}

function gradeContestWithRefresh(contestID, onlyMarked) {
   setTimeout(function() {
      location.reload();
   }, 5*60*1000);
   loopGradeContest(contestID, null, onlyMarked);
}
