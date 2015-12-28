<?php
  include('./config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
   <head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title data-i18n="page_title"></title>
   <?php 
      stylesheet_tag('/bower_components/jquery-ui/themes/base/jquery-ui.min.css');
      stylesheet_tag('/bower_components/jqgrid/css/ui.jqgrid.css');
      stylesheet_tag('/admin.css');
      script_tag('/bower_components/json3/lib/json3.min.js');
      // jquery 1.9 is required for IE6+ compatibility.
      script_tag('/bower_components/jquery/jquery.min.js');
   ?>
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

         function displayDiplomas() {
            var contestantPerGroup = fillDataDiplomas();
            var tableHeader = "<table><tr>" +
               "<td>nom</td>" +
               "<td>prenom</td>" +
               "<td>genre</td>" +
               "<td>classe</td>" +
               "<td>algoreaCode</td>" +
               "<td>nbContestants</td>" +
               "<td>score</td>" +
               "<td>rank</td>" +
               "<td>schoolRank</td>" +
               "<td>level</td>" +
               "<td>contest</td>" +
               "<td>user_lastName</td>" +
               "<td>user_firstName</td>" +
               "<td>school_name</td>" +
               "<tr>";

            var s = "";
            for (var groupID in contestantPerGroup) {
               var group = allData.group[groupID];
               s += "<h1>" + group.name + "</h1>";
               s += tableHeader;
               for (var iDiploma in contestantPerGroup[groupID]) {
                  var diploma = contestantPerGroup[groupID][iDiploma];
                  s += "<tr>" +
                     "<td>" + diploma.lastName + "</td>" +
                     "<td>" + diploma.firstName + "</td>" +
                     "<td>" + diploma.genre + "</td>" +
                     "<td>" + diploma.grade + "</td>" +
                     "<td>" + diploma.algoreaCode + "</td>" +
                     "<td>" + diploma.nbContestants + "</td>" +
                     "<td>" + diploma.score + "/" + diploma.contest.maxScore + "</td>" +
                     "<td>" + diploma.rank + "/" + diploma.contestParticipants + "</td>" +
                     "<td>" + diploma.schoolRank + "/" + diploma.schoolParticipants + "</td>" +
                     "<td>" + diploma.level + "</td>" +
                     "<td>" + diploma.contest.name + "</td>" +
                     "<td>" + diploma.user.lastName + "</td>" +
                     "<td>" + diploma.user.firstName + "</td>" +
                     "<td>" + diploma.school.name + "</td>" +
                     "</tr>";
               }
               s += "</table>";
            }
            document.write(s);
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
                           });
                        });
                     });
                  });               
               }, params);
            }, params);
         }
      </script>
   </head>
   <body>
      <input type="button" value="load" onclick="loadAllData({contestID: 54, schoolID: 1249})">
   </body>
</html>