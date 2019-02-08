/*
   Notes about pdfmake:

   Margins go in this order: Left, Top, Right and Bottom.
   The unit used is pt (points).
*/

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
/*      if (contestant.round == "1") {
         continue;
      }*/
      var groupID = contestant.groupID;
      if (contestantPerGroup[groupID] == undefined) {
        contestantPerGroup[groupID] = [];
      }
      var diplomaContestant = {
        lastName: contestant.lastName,
        firstName: contestant.firstName,
        genre: contestant.genre,
        grade: contestant.grade,
        algoreaCode: contestant.qualificationCode,
        nbContestants: contestant.nbContestants,
        score: parseInt(contestant.score),
        rank: contestant.rank,
        schoolRank: contestant.schoolRank,
        level: contestant.level
      };
      if (params.contestID == "algorea") {
        diplomaContestant.category = contestant.category;
        diplomaContestant.round = contestant.round;
        diplomaContestant.rankDemi2018 = contestant.rankDemi2018;
      }
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

function isDiplomaToPrint(diploma) {
   if (qualifiedOnly && !diploma.qualified) {
      return false;
   }
   if (parseInt(diploma.rank) / parseInt(diploma.contestParticipants) > rankPercentile) {
      return false;
   }
   return true;
}

function countDiplomas(params) {
   var contestantPerGroup = fillDataDiplomas(params);
   var nbTotal = 0;
   var nbToPrint = 0;
   for (var groupID in contestantPerGroup) {
      var group = allData.group[groupID];
      var contest = allData.contest[group.contestID];
      var user = allData.user[group.userID];
      if (user == undefined) {
        user = allData.colleagues[group.userID];
      }
      var school = allData.school[group.schoolID];

      for (var iDiploma in contestantPerGroup[groupID]) {
         nbTotal++;
         var diploma = contestantPerGroup[groupID][iDiploma];
         if (isDiplomaToPrint(diploma)) {
            nbToPrint++;
         }
      }
   }
   return {
      total: nbTotal,
      toPrint: nbToPrint
   }
}

var qualifiedOnly = false;
var rankPercentile = 1;
var diplomasPerPart = 100;

function updateNbDiplomas() {
   qualifiedOnly = $("#qualifiedOnly").prop("checked");
   rankPercentile = 1;
   if ($("#topRankedOnly").prop("checked")) {
      rankPercentile = parseInt($("#minRankPercentile").val()) / 100;
   }
   diplomasPerPart = parseInt($("#diplomasPerPart").val());
   genDocumentParts(params);
   var counts = countDiplomas(params);
   $("#printedCertificates").html(counts.toPrint);
   $("#totalCertificates").html(counts.total);
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
      genDocumentParts(params);
      $("#preload").hide();
      $("#loaded").show();
      $("#qualificationText").html(qualificationText);
      updateNbDiplomas();
   });
}

function getFullPdfDocument(content) {
// Start PDF Template
   var docDefinition = {
      pageOrientation: 'landscape',
      pageSize: 'A4',
      content: content,
      defaultStyle: {
         font: defaultFont
      },
      styles: {
         mainColor: { color: mainColor },
         accentColor: { color: accentColor },
         documentTitle: {
            bold: true,
            fontSize: 22,
            alignment: 'center'
         },
         contestName: {
            bold: true,
            fontSize: titleFontSize,
            alignment: 'center'
         },
         contestSubtitle: {
            fontSize:24,
            alignment:'center'
         },
         contestantName: {
            fontSize:36,
            alignment:'center',
            bold: true
         },
         diplomaScore: {
            alignment:'center',
            fontSize:18
         }
      },
      images: allImages
   };
   return docDefinition;
}

function getCoordName(user) {
   return i18n.t('user_gender_' + (user.gender == 'F' ? 'female' : 'male')) + 
          ' ' + user.firstName + ' ' + user.lastName;
}

function addHeaderForGroup(content, group, contest, user, school, isFirst) {
   // The string diploma_group_title is split in 2 strings : diploma_group_title and diploma_coordinator_title
   var diploma_group_title = i18n.t('certificates_group');
   var diploma_coordinator_title = i18n.t('certificates_coordinator');

   var contentTitle = {
     stack: [
       school.name,
       {
         text: [
            diploma_group_title,
            {text: ' « '},
            group.name,
            {text: ' »'}
         ]
       }
     ],
     style: ['documentTitle', 'mainColor'],
     margin: [0, 0, 0, 20]
   };

   if (!isFirst) {
      contentTitle.pageBreak = 'before';
   }
   content.push(contentTitle);

   var coordName = getCoordName(user);
   content.push(
      {
        text: [
         diploma_coordinator_title,
         ' ',
         coordName
        ],
        style: 'mainColor',
        alignment: 'center',
        margin: [0, 0, 0, 20]
      }
   );
}

function addContestantTableForGroup(content, contestantsData) {
   var columnTitle = [
      i18n.t('contestant_firstName_label'),
      i18n.t('contestant_lastName_label'),
      i18n.t('contestant_genre_label'),
      i18n.t('contestant_grade_label'),
      i18n.t('contestant_qualificationCode_label'),
      i18n.t('contestant_nbContestants_label'),
      i18n.t('contestant_score_label'),
      i18n.t('contestant_rank_label'),
      i18n.t('contestant_schoolRank_label'),
      i18n.t('contestant_contestID_label')
   ];
   var contestantsTableHead = columnTitle.map(function(x) {
      return {text: x,bold:true};
   });
   var students = [];

   for (var iDiploma in contestantsData) {
      var diploma = contestantsData[iDiploma];
      if (!isDiplomaToPrint(diploma)) {
         continue;
      }
      var qualificationStr = '';
      if (diploma.algoreaCode) {
         qualificationStr = diploma.algoreaCode;
      } else if (diploma.qualified === true) {
         qualificationStr = i18n.t('option_yes');
      } else if (diploma.qualified === false) {
         qualificationStr = i18n.t('option_no');
      }
      students.push([
         diploma.firstName,
         diploma.lastName,
         diploma.genre,
         i18n.t('grade_' + diploma.grade),
         qualificationStr,
         i18n.t('nbContestants_'+diploma.nbContestants),
         diploma.score + "/" + diploma.contest.maxScore,
         diploma.rank + "/" + diploma.contestParticipants,
         diploma.schoolRank + "/" + diploma.schoolParticipants,
         diploma.contest.name
      ]);
   }


   content.push({
      table: {
         headerRows: 1,
         body: [contestantsTableHead].concat(students)
      },
      style: 'mainColor'
   });
}

function getDisplayedScoreAndRank(diploma) {
   var minScoreDisplayed = parseInt($("#minScoreDisplayed").val());
   var maxRankPercentileDisplayed = parseInt($("#maxRankPercentileDisplayed").val()) / 100;
   var maxSchoolRankPercentileDisplayed = parseInt($("#maxSchoolRankPercentileDisplayed").val()) / 100;
   var scoreAndRank = [
   ];
   if (diploma.score >= minScoreDisplayed) {
      scoreAndRank.push(i18n.t("certificates_points_obtained", { points: diploma.score }));
   }

   if ((diploma.category != undefined) && (diploma.category != "blanche")) {
/*      if (diploma.round == "1") {
         scoreAndRank.push("la qualification en catégorie " + diploma.category + " et en demi-finale");
      } else {
*/         
         scoreAndRank.push(i18n.t("certificates_qualification_to_category", {category: diploma.category}));
/*      }*/
   }
   if (diploma.rank <= diploma.contestParticipants * maxRankPercentileDisplayed) {
      scoreAndRank.push(i18n.t("certificates_global_rank", {rank: toOrdinal(diploma.rank), total: diploma.contestParticipants}));
   }
   if (diploma.schoolRank <= diploma.schoolParticipants * maxSchoolRankPercentileDisplayed) {
      scoreAndRank.push(i18n.t("certificates_school_rank", {rank: toOrdinal(diploma.schoolRank), total: diploma.schoolParticipants}));
   }
   if (diploma.rankDemi2018 != null) {
      scoreAndRank.push(i18n.t("certificates_semifinals_rank", {rank: toOrdinal(diploma.rankDemi2018)}));
   }
   if (diploma.qualified) {
      scoreAndRank.push(qualificationText);
   }
   var str = "";
   for (var iPart = 0; iPart < scoreAndRank.length; iPart++) {
      if ((iPart == scoreAndRank.length - 1) && (iPart > 0)) {
         str += i18n.t("certificates_and") + " ";
      }
      str += scoreAndRank[iPart];
      if (iPart == scoreAndRank.length - 1) {
         str += ".";
      } else if (iPart < scoreAndRank.length - 2) {
         str += ",";
      }
      str += "\n";
   }
   return str;
}

function addDiploma(content, diploma, contest, school, user) {
   var contestLogo = {image: allImages.logo, width:150};
   if (contest.ID == "algorea") {
      contestLogo.width = 75;
   }
   var yearBackground = {image: allImages.yearBackground, width:150};

   var grade = i18n.t('grade_' + diploma.grade);
   var levelNbContestants = "";
   if (contest.rankNbContestants == '1') {
      levelNbContestants = " - " + i18n.t('nbContestants_' + diploma.nbContestants);
   }

   // New strings
   var certifiedOn = i18n.t("certificates_certified_on");
   var certifiedBy = i18n.t("certificates_certified_by");

   var contestSubtitle = i18n.t('certificates_category') + grade + levelNbContestants;
   var coordName =  getCoordName(user);
   var today = dateFormat(new Date());


/*
   STYLES
   ======

The styles depend on the contest.

*/

   var scoreAndRank = getDisplayedScoreAndRank(diploma);

   var contentStack = [
      {image: 'background', absolutePosition: {x:40, y:45}, width:750},
      {stack: [contestLogo], absolutePosition: {x:20, y:40}},//this is an image
      {text: contestName, style: ['contestName', 'accentColor'], margin: [0, 30, 0, 20]},
      {text: contestSubtitle, style: ['contestSubtitle', 'mainColor']},
      {text: [diploma.firstName, ' ', diploma.lastName], style: ['accentColor', 'contestantName'], margin: [0, 40, 0, 0]},
      {text: scoreAndRank, style: 'diplomaScore', margin: [0, 20, 0, 0]},
      {
         table: {
            widths: [300],
            body: [
               [{text: contestUrl, fontSize:16, alignment: 'center'}]
            ]
         },
         layout: 'noBorders',
         absolutePosition: {x: 20, y: partnersStartY - 30}
      },
      {
         stack: [partnerLogos] // this is an array of images
      },
      {
        stack: [
          {text: [certifiedOn, ' ' , today]},
          {text: [certifiedBy, coordName]},
          {text: [school.name, ', ', school.city]}
        ],
        absolutePosition: {x: 600, y: 490},
        width: 250
      },
      {
         columns: [
           { width: '*', text: '' },
           { width: 'auto', text: footer },
           { width: '*', text: '' }
         ],
         absolutePosition: {x: 20, y: 530},
         width: 750
      }
   ];
   if (showYear) {
      contentStack.push(
         {stack: [yearBackground], absolutePosition: {x:650, y:40}},//this is an image
         {text: contest.year, absolutePosition: {x:680, y:85}, fontSize:36, bold: true, color:'#7B3E26'}
      );
   }
   content.push({
      stack: contentStack,
      style: 'mainColor',
      pageBreak: 'before'
    });
}

function newGenerateDiplomas(params, iPart) {
   $("#buttonPdf" + iPart).prop("disabled", true);
   var content = [];
   var contestantPerGroup = fillDataDiplomas(params);
   for (var iGroup = 0; iGroup < partsGroupsIDs[iPart].length; iGroup++) {
      var groupID = partsGroupsIDs[iPart][iGroup];
      var group = allData.group[groupID];
      var contest = allData.contest[group.contestID];
      var user = allData.user[group.userID];
      if (user == undefined) {
        user = allData.colleagues[group.userID];
      }
      var school = allData.school[group.schoolID];

      addHeaderForGroup(content, group, contest, user, school, (iGroup == 0));
      addContestantTableForGroup(content, contestantPerGroup[groupID]);

      for (var iDiploma in contestantPerGroup[groupID]) {
         var diploma = contestantPerGroup[groupID][iDiploma];
         if (isDiplomaToPrint(diploma)) {
            addDiploma(content, diploma, contest, school, user);
         }
      }

   }
   pdfMake.fonts = {
        Roboto: {
                normal: 'Roboto-Regular.ttf',
                bold: 'Roboto-Medium.ttf',
                italics: 'Roboto-Italic.ttf',
                bolditalics: 'Roboto-MediumItalic.ttf'
        },
        NotoSansArabic: {
                normal: 'NotoSansArabicUI-Regular.ttf',
                bold: 'NotoSansArabic-Bold.ttf',
                italics: 'NotoSansArabicUI-Medium.ttf',
                bolditalics: 'NotoSansArabic-Bold.ttf'
        },
        Coranica: {
                normal: 'coranica_allerseelen2012_09.ttf',
                bold: 'coranica_allerseelen2012_09.ttf',
                italics: 'coranica_allerseelen2012_09.ttf',
                bolditalics: 'coranica_allerseelen2012_09.ttf'
        },
   };
   var docDefinition = getFullPdfDocument(content);
   pdfMake.createPdf(docDefinition).download("diplomes_" + (iPart + 1) + ".pdf")
}

var partsGroupsIDs = [];

function genDocumentParts(params) {
   partsGroupsIDs = [];
   var curNbContestants = 0;
   var curPart = [];
   var contestantPerGroup = fillDataDiplomas(params);
   for (var groupID in contestantPerGroup) {
      var group = allData.group[groupID];
      var nb = 0;
      for (var iDiploma in contestantPerGroup[groupID]) {
         var diploma = contestantPerGroup[groupID][iDiploma];
         if (isDiplomaToPrint(diploma)) {
            nb++;
         }
      }
      if (nb == 0) {
         continue;
      }
      if ((curNbContestants + nb > diplomasPerPart) && curPart.length > 0) {
         partsGroupsIDs.push(curPart);
         curPart = [];
         curNbContestants = 0;
      }
      curPart.push(groupID);
      curNbContestants += nb;
   }
   partsGroupsIDs.push(curPart);
   $("#buttons").html("");
   if (partsGroupsIDs.length == 0) {
      $("#buttons").html(i18n.t("certificates_nothing_to_print"));
   }
   if (partsGroupsIDs.length == 1) {
      $("#buttons").append('<p><button type="button" id="buttonPdf0" onclick="newGenerateDiplomas(params, 0)" style="display: block;margin: 0 auto">' + i18n.t("certificates_generate_pdf") + '</button></p>');
   } else {
      for (var iPart = 0; iPart < partsGroupsIDs.length; iPart++) {
         $("#buttons").append('<p><button type="button" id="buttonPdf' + iPart + '" onclick="newGenerateDiplomas(params, ' + iPart + ')" style="display: block;margin: 0 auto">' + i18n.t("certificates_generate_pdf_x", {number: (iPart + 1), total: partsGroupsIDs.length}) + '</button></p>');
      }
   }
}

function loadAllDataAlgorea(params) {
   loadData("school", function() {
      loadData("colleagues", function() { 
         loadData("user", function() {
            loadData("algorea_registration", function() {
               mergeUsers();
               allData["contestant"] = allData["algorea_registration"];
               var userID = 0;
               for (var iContestant in allData["contestant"]) {
                  allData["contestant"][iContestant]["groupID"] = 0;
                  allData["contestant"][iContestant]["contestID"] = "algorea";
                  allData["contestant"][iContestant]["nbContestants"] = 1;
                  userID = allData["contestant"][iContestant]["userID"];
               }
               allData["group"] = [{ schoolID: params["schoolID"], "userID": userID, "contestID": "algorea", name: "Participants Algoréa"} ];
               allData["contest"] = {"algorea": {"ID": "algorea", "rankGrades": 1, "rankNbContestants": 0, maxScore: 630, name: "Algoréa 2018", year: 2018}};
               getTotalContestants(params, function() {
                  getStrings(params);
               });
            }, params);
         }, params);
      }, params);
   }, params);               
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

var params = {participationType: 'Official'}

function init() {
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
   var teamID = getParameterByName('teamID');
   if (teamID) {
      params['teamID'] = teamID;
   }
   params['official'] = true;
   if (contestID == "algorea") {
      loadAllDataAlgorea(params);
   } else {
      loadAllData(params);
   }
 }
 init();

