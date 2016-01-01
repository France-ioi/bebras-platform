<?php
  include('./config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
   <head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title data-i18n="page_title"></title>
   <?php 
      stylesheet_tag('/bower_components/jqgrid/css/ui.jqgrid.css');
      stylesheet_tag('/admin.css');
      script_tag('/bower_components/json3/lib/json3.min.js');
      // jquery 1.9 is required for IE6+ compatibility.
      script_tag('/bower_components/jquery/jquery.min.js');
      script_tag('/bower_components/i18next/i18next.min.js');
      script_tag('/config.js.php');
   ?>
      <style>
         @media print{@page {size: a4 landscape}}
         @media print{.dontprint { display: none; }}
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

         function getTotalContestants(params, callback) {
            return $.post("dataCertificates.php", params,
               function(data) {
                  var countContestants = {};
                  allData["schoolContestants"] = countContestants;
                  for (var iResult in data.perSchool) {
                     var result = data.perSchool[iResult];
                     countContestants[result.grade + "_" + result.nbContestants] = result.totalContestants;
                  }
                  var countContestants = {};
                  allData["contestContestants"] = countContestants;
                  for (var iResult in data.perContest) {
                     var result = data.perContest[iResult];
                     countContestants[result.grade + "_" + result.nbContestants] = result.totalContestants;
                  }                  
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

         function fillDataDiplomas() {
            var contestantPerGroup = {}
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
                  score: contestant.score,
                  rank: contestant.rank,
                  schoolRank: contestant.schoolRank,
                  level: contestant.level
               };
               diplomaContestant.contest = allData.contest[contestant.contestID];
               diplomaContestant.user = allData.users[contestant.userID];
               diplomaContestant.school = allData.school[contestant.schoolID];
               diplomaContestant.schoolParticipants = allData.schoolContestants[contestant.grade + "_" + contestant.nbContestants];
               diplomaContestant.contestParticipants = allData.contestContestants[contestant.grade + "_" + contestant.nbContestants];
               contestantPerGroup[groupID].push(diplomaContestant);
            }
            return contestantPerGroup;
         }

         function toOrdinal(i) {
            // TODO: adapt to English, using numeral.js?
            return i == 1 ? '1ère' : i+'e';
         }

         function displayDiplomas() {
            var contestantPerGroup = fillDataDiplomas();
            var tableHeader = "<table class=\"bordered\"><tr>" +
               "<td>"+i18n.t('contestant_lastName_label')+"</td>" +
               "<td>"+i18n.t('contestant_firstName_label')+"</td>" +
               "<td>"+i18n.t('contestant_genre_label')+"</td>" +
               "<td>"+i18n.t('contestant_grade_label')+"</td>" +
               "<td>"+i18n.t('contestant_qualificationCode_label')+"</td>" +
               "<td>"+i18n.t('contestant_nbContestants_label')+"</td>" +
               "<td>"+i18n.t('contestant_score_label')+"</td>" +
               "<td>"+i18n.t('contestant_rank_label')+"</td>" +
               "<td>"+i18n.t('contestant_schoolRank_label')+"</td>" +
               "<td>"+i18n.t('contestant_contestID_label')+"</td>" +
               "<tr>";

            var s = "<p class=\"dontprint\">Une fois les images chargées, vous pouvez <button onclick=\"window.print()\">imprimer</button></p>";
            var today = new Date().toJSON().slice(0,10);
            for (var groupID in contestantPerGroup) {
               var group = allData.group[groupID];
               var contest = allData.contest[group.contestID];
               var user = allData.user[group.userID];
               var coordName = i18n.t('user_gender_'+(user.gender == 1 ? 'female' : 'male'));
               coordName += ' '+user.firstName+' '+user.lastName;
               s += "<div style=\"page-break-after:always\"><center>";
               s += i18n.t('diploma_group_title', {schoolName: allData.school[group.schoolID].name, groupName: group.name, coordName: coordName, interpolation: {prefix: '__', suffix: '__'}});
               s += tableHeader;
               var iDiploma, diploma;
               for (iDiploma in contestantPerGroup[groupID]) {
                  diploma = contestantPerGroup[groupID][iDiploma];
                  s += "<tr>" +
                     "<td>" + diploma.lastName + "</td>" +
                     "<td>" + diploma.firstName + "</td>" +
                     "<td>" + diploma.genre + "</td>" +
                     "<td>" + i18n.t('grade_'+diploma.grade) + "</td>" +
                     "<td>" + (diploma.algoreaCode ? diploma.algoreaCode : '') + "</td>" +
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
                  var levelNbContestants = i18n.t('grade_'+diploma.grade);
                  if (allData.contest[group.contestID].rankNbContestants) {
                     levelNbContestants += " - " + i18n.t('nbContestants_'+diploma.nbContestants);
                  }
                  var scoreRankContext = {
                     score: diploma.score,
                     maxScore: diploma.contest.maxScore,
                     rankOrdinal: toOrdinal(diploma.rank),
                     schoolRankOrdinal: toOrdinal(diploma.schoolRank),
                     maxRank: diploma.contestParticipants,
                     maxSchoolRank: diploma.schoolParticipants
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
                  var qualificationCode = '';
                  if (diploma.algoreaCode) {
                     var context = (diploma.genre == '1' ? 'female' : 'male');
                     qualificationCode = i18n.t('diploma_code', {code: diploma.algoreaCode, context: context});
                  }
                  s += i18n.t('diploma_template', {
                     coordName: coordName,
                     date: today,
                     schoolName: allData.school[group.schoolID].name,
                     schoolCity: allData.school[group.schoolID].city,
                     levelNbContestants: levelNbContestants,
                     name: diploma.lastName+' '+diploma.firstName,
                     scoreRank: i18n.t('diploma_score', scoreRankContext),
                     qualificationCode: qualificationCode,
                     context: 'test',
                     contestYear: contest.year,
                     interpolation: {prefix: '__', suffix: '__'}
                  });
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
                           loadData("contest", function() {
                              getTotalContestants(params, function() {
                                 displayDiplomas();
                              });
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
            loadAllData(params);
         }

         i18n.init(<?= json_encode([
           'lng' => $config->defaultLanguage,
           'fallbackLng' => [$config->defaultLanguage],
           'fallbackNS' => 'translation',
           'ns' => [
             'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
             'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
           ],
           'getAsync' => true,
           'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
         ]); ?>, function () {
           init();
         });
      </script>
   </head>
   <body>
      Chargement...
   </body>
</html>