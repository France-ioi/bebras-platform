<?php
  include('./config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
   <head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title data-i18n="page_title"></title>
   <?php 
      stylesheet_tag('/admin.css');
      script_tag('/bower_components/json3/lib/json3.min.js');
      // jquery 1.9 is required for IE6+ compatibility.
      script_tag('/bower_components/jquery/jquery.min.js');
      script_tag('/bower_components/jquery-ui/jquery-ui.min.js'); // for $.datepicker.formatDate
      script_tag('/bower_components/i18next/i18next.min.js');
   ?>
      <style>
         @import url(https://fonts.googleapis.com/css?family=Varela+Round); /* needed for Alkindi... TODO: find a better system */
         @media print{@page {size: a4 landscape}}
         @media print{.dontprint { display: none; }}
         .bigmessage {
            text-align:center;
            font-size:22pt;
            color:red;
         }
         body {
            font-family: arial;
            color: #4A5785;
         }
         .bordered {
             border-spacing:0;
             border-collapse:collapse;
         }
         .bordered td {
            border: solid black 1px;
            padding: 2px;
         }
      </style>
      <script>
         var allData = {};
         function loadData(tableName, callback, filters) {
            var params = {oper: "select", tableName: tableName};
            for (var filterName in filters) {
               params[filterName] = filters[filterName];
            }
            return $.post("jqGridData.php", params,
               function(data) {
                  if (data.success) {
                     allData[tableName] = data.items;
                     callback();
                  } else {
                     alert("error");
                  }
               }, "json"
            ).fail(
               function(xhr, textStatus, errorThrown) {
                  alert(xhr.responseText);
               }
            );
         };

         var thresholdsByGrade = {};
         function getThresholds() {
            for (var iThreshold in allData.award_threshold) {
               threshold = allData.award_threshold[iThreshold];
               if (!thresholdsByGrade[threshold.gradeID]) {
                  thresholdsByGrade[threshold.gradeID] = {};
               }
               thresholdsByGrade[threshold.gradeID][threshold.nbContestants] = parseInt(threshold.minScore);
            } 
         }

         function getTotalContestants(params, callback) {
            return $.post("dataCertificates.php", params,
               function(data) {
                  var contest = allData.contest[params.contestID];
                  var countContestants = {};
                  for (var iResult in data.perSchool) {
                     var result = data.perSchool[iResult];
                     if (contest.rankGrades == '1' && contest.rankNbContestants == '1') {
                        countContestants[result.grade + "_" + result.nbContestants] = result.totalContestants;
                     } else if (contest.rankGrades == '1') {
                        countContestants[result.grade] = result.totalContestants;
                     } else if (contest.rankNbContestants == '1') {
                        countContestants[result.nbContestants] = result.totalContestants;
                     } else {
                        countContestants = result.totalContestants;
                     }
                  }
                  allData["schoolContestants"] = countContestants;
                  countContestants = {};
                  for (iResult in data.perContest) {
                     result = data.perContest[iResult];
                     if (contest.rankGrades == '1' && contest.rankNbContestants == '1') {
                        countContestants[result.grade + "_" + result.nbContestants] = result.totalContestants;
                     } else if (contest.rankGrades == '1') {
                        countContestants[result.grade] = result.totalContestants;
                     } else if (contest.rankNbContestants == '1') {
                        countContestants[result.nbContestants] = result.totalContestants;
                     } else {
                        countContestants = result.totalContestants;
                     }
                  }
                  allData["contestContestants"] = countContestants;              
                  callback();
               }, "json"
            );
         }

         function mergeUsers() {
            var users = {};
            for (var recordID in allData.colleagues) {
               var colleague = allData.colleagues[recordID];
               users[recordID] = {
                  gender: colleague.gender,
                  lastName: colleague.lastName,
                  firstName: colleague.firstName
               }
            }
            for (var recordID in allData.user) {
               var user = allData.user[recordID];
               users[recordID] = {
                  gender: user.gender,
                  lastName: user.lastName,
                  firstName: user.firstName
               }
            }
            allData.users = users;
         };

         function fillDataDiplomas(params) {
            var contestantPerGroup = {}
            var contest = allData.contest[params.contestID];
            for (var contestantID in allData.contestant) {
               var contestant = allData.contestant[contestantID];
               var groupID = contestant.groupID;
               if (contestantPerGroup[groupID] == undefined) {
                  contestantPerGroup[groupID] = [];
               }
               var diplomaContestant = {
                  lastName: contestant.lastName,
                  firstName: contestant.firstName,
                  genre: contestant.genre,
                  grade: contestant.grade,
                  algoreaCode: contestant.algoreaCode,
                  nbContestants: contestant.nbContestants,
                  score: parseInt(contestant.score),
                  rank: contestant.rank,
                  schoolRank: contestant.schoolRank,
                  level: contestant.level
               };
               diplomaContestant.contest = allData.contest[contestant.contestID];
               diplomaContestant.user = allData.users[contestant.userID];
               diplomaContestant.school = allData.school[contestant.schoolID];
               if (thresholdsByGrade[contestant.grade] && thresholdsByGrade[contestant.grade][contestant.nbContestants]) {
                  if (diplomaContestant.score >= thresholdsByGrade[contestant.grade][contestant.nbContestants]) {
                     diplomaContestant.qualified = true;
                  } else {
                     diplomaContestant.qualified = false;
                  }
               }
               if (contest.rankGrades == '1' && contest.rankNbContestants == '1') {
                  diplomaContestant.schoolParticipants = allData.schoolContestants[contestant.grade + "_" + contestant.nbContestants];
                  diplomaContestant.contestParticipants = allData.contestContestants[contestant.grade + "_" + contestant.nbContestants];
               } else if (contest.rankGrades == '1') {
                  diplomaContestant.schoolParticipants = allData.schoolContestants[contestant.grade];
                  diplomaContestant.contestParticipants = allData.contestContestants[contestant.grade];
               } else if (contest.rankNbContestants == '1') {
                  diplomaContestant.schoolParticipants = allData.schoolContestants[contestant.nbContestants];
                  diplomaContestant.contestParticipants = allData.contestContestants[contestant.nbContestants];
               } else {
                  diplomaContestant.schoolParticipants = allData.schoolContestants;
                  diplomaContestant.contestParticipants = allData.contestContestants;
               }
               contestantPerGroup[groupID].push(diplomaContestant);
            }
            return contestantPerGroup;
         }

         function toOrdinal(i) {
            if (i == 1) {
               return i + i18n.t('certificates_rank_1_suffix');
            } else {
               return i + i18n.t('certificates_rank_n_suffix');
            }
         }

         function dateFormat(d) {
            // TODO: adapt to English, using moment.js?
            return $.datepicker.formatDate("dd/mm/yy", d);
         }

         function getStrings(params) {
            var contest = allData.contest[params.contestID];
            if (!contest) {
               return;
            }
            var stringsName = contest.certificateStringsName;
            if (stringsName) {
               window.i18nconfig.ns.defaultNs = stringsName;
               window.i18nconfig.ns.namespaces = [stringsName, 'translation'];
            }
            i18n.init(window.i18nconfig, function () {
              displayDiplomas(params);
            });   
         }
         function displayDiplomas(params) {
            var contestantPerGroup = fillDataDiplomas(params);
            var tableHeader = "<table class=\"bordered\"><tr>" +
               "<td>"+i18n.t('contestant_firstName_label')+"</td>" +
               "<td>"+i18n.t('contestant_lastName_label')+"</td>" +
               "<td>"+i18n.t('contestant_genre_label')+"</td>" +
               "<td>"+i18n.t('contestant_grade_label')+"</td>" +
               "<td>"+i18n.t('contestant_qualificationCode_label')+"</td>" +
               "<td>"+i18n.t('contestant_nbContestants_label')+"</td>" +
               "<td>"+i18n.t('contestant_score_label')+"</td>" +
               "<td>"+i18n.t('contestant_rank_label')+"</td>" +
               "<td>"+i18n.t('contestant_schoolRank_label')+"</td>" +
               "<td>"+i18n.t('contestant_contestID_label')+"</td>" +
               "<tr>";

            var s = "<p class=\"dontprint bigmessage\">Une fois les images chargées, vous pouvez <button onclick=\"window.print()\">imprimer</button></p><p class=\"dontprint bigmessage\"><strong>Attention:</strong> vous devez imprimer en A4, orientation paysage, sans en-tête ni pied de page.</p>";
            var today = dateFormat(new Date());
            for (var groupID in contestantPerGroup) {
               var group = allData.group[groupID];
               var contest = allData.contest[group.contestID];
               var user = allData.user[group.userID];
               if (user == undefined) {
                  user = allData.colleagues[group.userID];
               }
               var coordName = i18n.t('user_gender_'+(user.gender == 'F' ? 'female' : 'male'));
               coordName += ' '+user.firstName+' '+user.lastName;
               s += "<div style=\"page-break-after:always\"><center>";
               s += i18n.t('diploma_group_title', {schoolName: allData.school[group.schoolID].name, groupName: group.name, coordName: coordName, interpolation: {prefix: '__', suffix: '__'}});
               s += tableHeader;
               var iDiploma, diploma;
               for (iDiploma in contestantPerGroup[groupID]) {
                  diploma = contestantPerGroup[groupID][iDiploma];
                  qualificationStr = '';
                  if (diploma.algoreaCode) {
                     qualificationStr = diploma.algoreaCode;
                  } else if (diploma.qualified === true) {
                     qualificationStr = i18n.t('option_yes');
                  } else if (diploma.qualified === false) {
                     qualificationStr = i18n.t('option_no');
                  }
                  s += "<tr>" +
                     "<td>" + diploma.firstName + "</td>" +
                     "<td>" + diploma.lastName + "</td>" +
                     "<td>" + diploma.genre + "</td>" +
                     "<td>" + i18n.t('grade_'+diploma.grade) + "</td>" +
                     "<td>" + qualificationStr + "</td>" +
                     "<td>" + i18n.t('nbContestants_'+diploma.nbContestants) + "</td>" +
                     "<td>" + diploma.score + "/" + diploma.contest.maxScore + "</td>" +
                     "<td>" + diploma.rank + "/" + diploma.contestParticipants + "</td>" +
                     "<td>" + diploma.schoolRank + "/" + diploma.schoolParticipants + "</td>" +
                     "<td>" + diploma.contest.name + "</td>" +
                     "</tr>";
               }
               s += "</table></center></div>";
               for (iDiploma in contestantPerGroup[groupID]) {
                  diploma = contestantPerGroup[groupID][iDiploma];
                  var levelNbContestants = i18n.t('translations_category_label')+' '+i18n.t('grade_'+diploma.grade);
                  if (allData.contest[group.contestID].rankNbContestants == '1') {
                     levelNbContestants += " - " + i18n.t('nbContestants_'+diploma.nbContestants);
                  }
                  var scoreRankContext = {
                     score: diploma.score,
                     maxScore: diploma.contest.maxScore,
                     rankOrdinal: toOrdinal(diploma.rank),
                     schoolRankOrdinal: toOrdinal(diploma.schoolRank),
                     maxRank: diploma.contestParticipants,
                     maxSchoolRank: diploma.schoolParticipants,
                     name: diploma.firstName+' 'diploma.lastName,
                     grade: i18n.t("grade_"+diploma.grade)
                  };
                  if (diploma.rank <= diploma.contestParticipants / 2) {
                     if (diploma.schoolRank <= diploma.schoolParticipants / 2) {
                        scoreRankContext.context = 'rankSchoolRank';
                     } else {
                        scoreRankContext.context = 'rank';
                     }
                  } else if (diploma.schoolRank <= diploma.schoolParticipants / 2) {
                     scoreRankContext.context = 'schoolRank';
                  }
                  translateParameters = {
                     coordName: coordName,
                     date: today,
                     schoolName: allData.school[group.schoolID].name,
                     schoolCity: allData.school[group.schoolID].city,
                     levelNbContestants: levelNbContestants,
                     scoreRank: i18n.t('diploma_score', scoreRankContext),
                     contestYear: contest.year,
                     name: diploma.firstName+' '+diploma.lastName,
                     interpolation: {prefix: '__', suffix: '__'}
                  };
                  var qualificationCode = '';
                  var context = (diploma.genre == '1' ? 'female' : 'male');
                  if (diploma.algoreaCode) {
                     qualificationCode = i18n.t('diploma_code', {code: diploma.algoreaCode, context: context});
                     translateParameters.context = 'withQualificationCode';
                  } else if (diploma.qualified === true) {
                     qualificationCode = i18n.t('diploma_code', {context: context});
                     translateParameters.context = 'withQualificationCode';
                  }
                  translateParameters.qualificationCode = qualificationCode;
                  s += i18n.t('diploma_template', translateParameters);
               }
            }
            $('body').html(s);
         }

         function loadAllData(params) {
            loadData("group", function() {
               loadData("contestant", function() {
                  loadData("school", function() {
                     loadData("colleagues", function() {
                        loadData("user", function() {
                           mergeUsers();
                           loadData("award_threshold", function() {
                              getThresholds();
                              loadData("contest", function() {
                                 getTotalContestants(params, function() {
                                    getStrings(params);
                                 });
                              }, params);
                           }, params);
                        });
                     });
                  }, params);               
               }, params);
            }, params);
         }

         function getParameterByName(name) {
             var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                 results = regex.exec(location.search);
             return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
         }

         function init() {
            var params = {participationType: 'Official'};
            var contestID = getParameterByName('contestID');
            if (!contestID) return;
            var schoolID = getParameterByName('schoolID');
            if (!schoolID) return;
            params['schoolID'] = schoolID;
            params['contestID'] = contestID;
            var groupID = getParameterByName('groupID');
            if (groupID) {
               params['groupID'] = groupID;
            }
            params['official'] = true;
            loadAllData(params);
         }
         window.i18nconfig = <?= json_encode([
           'lng' => $config->defaultLanguage,
           'fallbackLng' => [$config->defaultLanguage],
           'fallbackNS' => 'translation',
           'ns' => [
             'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
             'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
           ],
           'getAsync' => true,
           'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
         ]); ?>;
         init();
      </script>
   </head>
   <body>
      Chargement...
   </body>
</html>