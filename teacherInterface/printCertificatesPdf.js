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
      if(filterName == 'groupID' && (tableName == 'contestant' || tableName == 'group')) {
         params['relatedGroupID'] = filters[filterName];
         continue;
      }
      if(filterName == 'contestID' && (tableName == 'contestant' || tableName == 'group')) {
         params['relatedContestID'] = filters[filterName];
         continue;
      }
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
      var threshold = allData.award_threshold[iThreshold];
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

function getContestantsPerTeam() {
   var contestantPerTeam = {};
   for (var contestantID in allData.contestant) {
      var contestant = allData.contestant[contestantID];
      var teamID = contestant.teamID;
      if (contestantPerTeam[teamID] == undefined) {
         contestantPerTeam[teamID] = [];
      }
      contestantPerTeam[teamID].push(contestant);
   }
   return contestantPerTeam;
}

function fillDataDiplomas(params) {
   var contestantPerGroup = {}
   var contestantPerTeam = getContestantsPerTeam();
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
      diplomaContestant.certificateIsIntermediate = contest.certificateIsIntermediate;
      diplomaContestant.certificateAllNames = contest.certificateAllNames;
      if(contest.certificateAllNames == 1 && contestant.nbContestants > 1) {
         var teamContestants = contestantPerTeam[contestant.teamID];
         for(var i = 0; i < teamContestants.length; i++) {
            if(teamContestants[i] !== contestant) {
               if(!diplomaContestant.otherContestants) {
                  diplomaContestant.otherContestants = [];
               }
               diplomaContestant.otherContestants.push(teamContestants[i]);
            }
         }
      }
      contestantPerGroup[groupID].push(diplomaContestant);
   }
   return contestantPerGroup;
}

function isDiplomaToPrint(diploma) {
   if (qualifiedOnly && !diploma.qualified) {
      return false;
   }
   if (rankPercentile < 1 && parseInt(diploma.rank) / parseInt(diploma.contestParticipants) > rankPercentile) {
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

function filterContestantTableRow(row) {
   var baseIdx = 0;
   if(hideCode) {
      row.splice(baseIdx + 4, 1);
      baseIdx--;
   }
   if(hideType) {
      row.splice(baseIdx + 5, 1);
   }
   return row;
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
   columnTitle = filterContestantTableRow(columnTitle);
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
      students.push(filterContestantTableRow([
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
      ]));
   }


   content.push({
      table: {
         headerRows: 1,
         body: [contestantsTableHead].concat(students)
      },
      style: 'mainColor'
   });
}

function joinDiplomaLines(parts) {
   var str = "";
   for (var iPart = 0; iPart < parts.length; iPart++) {
      if (iPart == parts.length - 1 && iPart > 0) {
         str += i18n.t("certificates_and") + " ";
      }
      str += parts[iPart];
      if (iPart == parts.length - 1) {
         str += ".";
      } else if (iPart < parts.length - 2) {
         str += ",";
      }
      str += "\n";
   }
   return str;
}

function getDisplayedScoreAndRank(diploma, contest, gradeAndLevel) {
   var minScoreDisplayed = parseInt($("#minScoreDisplayed").val());
   var maxRankPercentileDisplayed = parseInt($("#maxRankPercentileDisplayed").val()) / 100;
   var maxSchoolRankPercentileDisplayed = parseInt($("#maxSchoolRankPercentileDisplayed").val()) / 100;
   var scoreAndRank = [];
   var displayStr = "";

   // Belongs to a team with multiple contestants
   var inTeam = parseInt(diploma.nbContestants) > 1;
   if(diploma.certificateAllNames != 1 && inTeam) {
      displayStr += i18n.t("certificates_in_team") + "\n";
   }
   var intermediateI18nKey = diploma.certificateIsIntermediate == 1 ? "certificates_ranking_intermediate" : "certificates_ranking";
   var withIntermediate = i18n.exists(intermediateI18nKey); // Are there cases where it's not?
   function getI18nTeam(key) {
      var key2 = key + (withIntermediate ? '_short' : '');
      key = i18n.exists(key2) ? key2 : key;
      var key2 = key + (inTeam ? '_team' : '');
      key = i18n.exists(key2) ? key2 : key;
      return key;
   }

   // Score
   if (diploma.score >= minScoreDisplayed) {
      var scoreI18nKey = getI18nTeam("certificates_points_obtained");
      if(contest.categoryColor && i18n.exists(scoreI18nKey + "_color")) {
         scoreAndRank.push(i18n.t(scoreI18nKey + "_color", { points: diploma.score, color: contest.categoryColor }));
      } else {
         scoreAndRank.push(i18n.t(scoreI18nKey, { points: diploma.score }));
      }
   }

   // Qualification to category
   if ((diploma.category != undefined) && (diploma.category != "blanche")) {
      scoreAndRank.push(i18n.t("certificates_qualification_to_category", {category: diploma.category}));
   }

   var intermediateStr = "";
   if(withIntermediate) {
      displayStr += joinDiplomaLines(scoreAndRank);
      intermediateStr += i18n.t(intermediateI18nKey);
      if(gradeAndLevel) {
         if(i18n.exists("certificates_among")) {
            intermediateStr += i18n.t("certificates_among") + gradeAndLevel;
         } else {
            intermediateStr += " (" + gradeAndLevel + ")";
         }
      }
      intermediateStr += ' :\n';
      scoreAndRank = [];
   }

   // Teacher setting : display global rank for top X%
   if (diploma.rank) {
      if(!diploma.contestParticipants) {
         scoreAndRank.push(i18n.t(getI18nTeam("certificates_global_rank_nototal"), {rank: toOrdinal(diploma.rank)}));
      } else if(diploma.rank <= diploma.contestParticipants * maxRankPercentileDisplayed) {
         scoreAndRank.push(i18n.t(getI18nTeam("certificates_global_rank"), {rank: toOrdinal(diploma.rank), total: diploma.contestParticipants}));
      }
   }
   // Teacher setting : display school rank for top X%
   if (diploma.schoolRank && diploma.schoolRank <= diploma.schoolParticipants * maxSchoolRankPercentileDisplayed) {
      scoreAndRank.push(i18n.t(getI18nTeam("certificates_school_rank"), {rank: toOrdinal(diploma.schoolRank), total: diploma.schoolParticipants}));
   }
   if (diploma.rankDemi2018 != null) {
      scoreAndRank.push(i18n.t(getI18nTeam("certificates_semifinals_rank"), {rank: toOrdinal(diploma.rankDemi2018)}));
   }
   // Qualification
   if (diploma.qualified) {
      scoreAndRank.push(qualificationText);
   }
   if(scoreAndRank.length > 0) {
      displayStr += intermediateStr + joinDiplomaLines(scoreAndRank);
   }
   return displayStr;
}

function addDiploma(content, diploma, contest, school, user) {
   var contestLogo = {image: allImages.logo, width:150};
   if (contest.ID == "algorea") {
      contestLogo.width = 75;
   }
   var yearBackground = {image: allImages.yearBackground, width:150};

   var gradeAndLevel = '';
   if(diploma.grade) {
      var grade = i18n.t('grade_' + diploma.grade);
      if(contest.ID == '619714287977504425' && (diploma.grade == 11 || diploma.grade == 12)) {
         // Special grade text for two grades merged in rankings
         var grade = '1re - Term';
      }
      var levelNbContestants = "";
      if (contest.rankNbContestants == '1') {
         levelNbContestants = " - " + i18n.t('nbContestants_' + diploma.nbContestants);
      }

      gradeAndLevel = grade + levelNbContestants;
      var contestSubtitle = i18n.t('certificates_category') + gradeAndLevel;
   } else {
      var contestSubtitle = '';
   }

   // New strings
   var certifiedOn = i18n.t("certificates_certified_on");
   var certifiedBy = i18n.t("certificates_certified_by");

   var coordName =  getCoordName(user);
   var today = dateFormat(new Date());


/*
   STYLES
   ======

The styles depend on the contest.

*/

   var scoreAndRank = getDisplayedScoreAndRank(diploma, contest, gradeAndLevel);

   var contestTitle = contest.certificateTitle || window.defaultCertificateTitle;

   var contentStack = [
      {image: 'background', absolutePosition: {x:40, y:45}, width:750},
      {stack: [contestLogo], absolutePosition: {x:20, y:40}}, //this is an image
      {text: contestTitle, style: ['contestName', 'accentColor'], margin: [0, 30, 0, 20]},
      {text: contestSubtitle, style: ['contestSubtitle', 'mainColor']}
   ];

   var contestantNames = [diploma.firstName, ' ', diploma.lastName]
   var contestantNamesMargins = [0, 40, 0];
   if (diploma.otherContestants) {
      for(var i = 0; i < diploma.otherContestants.length; i++) {
         if(i < diploma.otherContestants.length - 1) {
            contestantNames[contestantNames.length - 1] += ', ';
         } else if(i == diploma.otherContestants.length - 1) {
            contestantNames.push(' ' + i18n.t('certificates_and') + ' ')
            contestantNamesMargins.push(0);
         }
         if(diploma.otherContestants.length > 1 && i == Math.floor((diploma.otherContestants.length + 1) / 2) - 1) {
            contestantNames.push('\n');
            contestantNamesMargins.push(0);
         }
         contestantNames.push(diploma.otherContestants[i].firstName);
         contestantNames.push(' ');
         contestantNames.push(diploma.otherContestants[i].lastName);
         contestantNamesMargins.push(0);
         contestantNamesMargins.push(40);
         contestantNamesMargins.push(0);
      }
   }
   contestantNamesMargins.push(0);
   contentStack.push({text: contestantNames, style: ['accentColor', 'contestantName'], margin: contestantNamesMargins});

   contentStack = contentStack.concat([
      {text: scoreAndRank, style: 'diplomaScore', margin: [0, 20, 0, 0]},
      {
         table: {
            widths: [300],
            body: [
               [{text: contestUrl, fontSize:16, alignment: 'center'}]
            ]
         },
         layout: 'noBorders',
         absolutePosition: {x: partnersStartX + partnersWidth / 2 - 150, y: partnersStartY - 30}
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
   ]);
   if (showYear == "middle") {
      contentStack.push(
         {stack: [yearBackground], absolutePosition: {x:650, y:40}}//this is an image
      );
      contentStack.splice(3, 0,
         {text: contest.year, alignment: 'center', fontSize:36, bold: true, color:'#7B3E26', margin: [0, 0, 0, 40]}
      );
   } else if(showYear) {
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

