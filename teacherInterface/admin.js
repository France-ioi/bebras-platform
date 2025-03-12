/* Copyright (c) 2012-2016 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

// TODO: avoid using undefined as a value, use null instead.

var loggedUser;
var contests;
var contestCategories;
var questions;
var questionsKeysToIds;
var schools;
var groups;
var filterSchoolID = "0";
var filterGroupID = "0";
var generating = false;
var models = null;
var selectedContestID;  // currently selected contestID
var selectedQuestionID; // currently selected questionID
var selectedGroupID;    // currently selected groupID

var t = i18n.t;

/**
 * Old IE versions does not implement the Array.indexOf function
 * Setting it in Array.prototype.indexOf makes IE crash
 * So the graders are using this inArray function
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


/* Converts a string of the form "2018-07-14 16:53:28" or
 * "19/07/2016" to date (forgetting about the time) */
function toDate(dateStr, sep, fromServer) {
   var dateOnly = dateStr.split(" ")[0];
   var timeParts = dateStr.split(" ")[1].split(":");
   var parts = dateOnly.split(sep);
   if (fromServer) {
      return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2], timeParts[0], timeParts[1]));
   }
   return new Date(parts[2], parts[1] - 1, parts[0], timeParts[0], timeParts[1]);
}

function dateToDisplay(d) {
   var date = $.datepicker.formatDate("dd/mm/yy", d);
   var h = d.getHours();
   h = (h < 10) ? ("0" + h) : h ;

   var m = d.getMinutes();
   m = (m < 10) ? ("0" + m) : m ;

   var s = d.getSeconds();
   s = (s < 10) ? ("0" + s) : s ;

   return date + " " + h + ":" + m + ":" + s;
}

function utcDateFormatter(cellValue) {
   if ((cellValue == undefined) || (cellValue == "0000-00-00 00:00:00") || (cellValue == "")) {
      return "";
   }
   var localDate = toDate(cellValue, "-", true, true);
   return dateToDisplay(localDate);
}

function localDateToUtc(cellValue, options, rowOject) {
   if (cellValue == "") {
      return "";
   }
   var d = toDate(cellValue, "/", false, true);
   var res = d.toISOString().slice(0,16).split("T").join(" ");
   return res;
}

function checkTaskPath(path, silent) {
   function displayWarning(msg) {
      if(silent && msg) {
         console.log(msg);
         return;
      }
      if(msg) {
         $('#preview_question_warning').show();
         $('#preview_question_warning').html(msg);
      } else {
         $('#preview_question_warning').hide();
      }
   }
   if(!path) {
      displayWarning("Task path is empty.");
      return;
   }
   var msg = '';
   if(path.indexOf('\\') != -1) {
      msg += "Warning : task path contains a \\. That might cause issues in loading the task.";
   }
   if(!path.match(/index.*\.html/)) {
      msg += "Warning : task path doesn't seem to contain any index(_lang).html. That might cause issues in loading the task.";
   }
   if(msg) {
      msg += " (Path : `" + path + "`)";
      displayWarning(msg);
   } else {
      displayWarning();
   }
}

function isAdmin() {
   return ((loggedUser !== undefined) && (loggedUser.isAdmin === "1"));
}

function disableButton(buttonId, withOverlay) {
   var button = $("#" + buttonId);
   if (button.attr("disabled")) {
      return false;
   }
   button.attr("disabled", true);
   return true;
}

function enableButton(buttonId) {
   var button = $("#" + buttonId);
   button.removeAttr("disabled");
}

function initErrorHandler() {
   $( "body" ).ajaxError(function(e, jqxhr, settings, exception) {
     if (settings.url.substring(0,4) == 'i18n' || settings.url.substring(settings.url.length - 12) == 'domains.json') {
        return;
     }
     if (exception === "") {
        if (confirm(t("server_not_responding"))) {
           $.post(settings.url, settings.data, settings.success, settings.dataType);
        }
     } else if (jqxhr.responseText.indexOf(t("extract_error_message")) > 0) {
        alert(t("session_expired"));
        window.location.href = t("index_url");
     } else {
        $("#contentError").html(t("exception") + exception + "<br/><br/>" + t("server_response") + "<br/>" + jqxhr.responseText);
        $("#divError").show();
     }
   });
}

function levenshtein(a, b) {
   if(a.length == 0) return b.length; 
   if(b.length == 0) return a.length; 

   var matrix = [];

   var i;
   for(i = 0; i <= b.length; i++){
      matrix[i] = [i];
   }

   var j;
   for(j = 0; j <= a.length; j++){
      matrix[0][j] = j;
   }

   for(i = 1; i <= b.length; i++){
      for(j = 1; j <= a.length; j++){
         if(b.charAt(i-1) == a.charAt(j-1)){
            matrix[i][j] = matrix[i-1][j-1];
         } else {
            matrix[i][j] = Math.min(matrix[i-1][j-1] + 1, // substitution
            Math.min(matrix[i][j-1] + 1, // insertion
            matrix[i-1][j] + 1)); // deletion
         }
      }
   }

   return matrix[b.length][a.length];
};

function checkContestantModifications() {
   if(!originalContestantRow) { return [true, '']; }
   var original = originalContestantRow;
   var inputs = $("#grid_contestant").find("#" + original.id).find('input');
   if(!inputs) { return [true, '']; }

   var fields = {};
   inputs.each(function () {
      fields[this.name] = this.value;
   });
   if(fields.firstName === undefined || fields.lastName === undefined) {
      return [true, ''];
   }
   var distance = Math.min(
      levenshtein(fields.firstName, original.firstName) + levenshtein(fields.lastName, original.lastName),
      levenshtein(fields.firstName, original.lastName) + levenshtein(fields.lastName, original.firstName)
      );

   if(distance > 5) {
      if(confirm(t("confirm_contestant_modification"))) {
         original.firstName = fields.firstName;
         original.lastName = fields.lastName;
      } else {
         inputs.filter('[name=firstName]').val(original.firstName);
         inputs.filter('[name=lastName]').val(original.lastName);
      }
   }

   return [true, ''];
}

function getRegionsObjects(isFilter) {
   var choices = "";
   if (isFilter)
      choices += "_NOF_:" + t("option_no_filter") + ";";
   for (var key in regions) {
      if (regions.hasOwnProperty(key)) {
         choices += key + ":" + regions[key] + ";";
      }
   }
   // Remove the last ';'
   choices = choices.substring(0, choices.length-1);
   return choices;
}

function getGradesList(isFilter) {
   var gradesList = {};
   var gradesFilter = "";
   var grades = [-1, -4, 3, 4, 5, 6, 16, 6, 16, 7, 17, 8, 18, 9, 19, 10, 13, 11, 14, 12, 20];
   if (config.grades != undefined) {
      grades = config.grades;
   }
   for (var iGrade = 0; iGrade < grades.length; iGrade++) {
      var grade = grades[iGrade];
      gradesList["" + grade] = t("grade_" + grade);
      gradesFilter += ";" + grade + ":" + t("grade_" + grade);
   }
   if (isFilter) {
      return gradesFilter;
   } else {
      return gradesList;
   }
}

function getGroupsColModel() {
   // Get list of contests the groups participate in
   var groupContestIDs = [];
   for(groupID in groups) {
      var contestID = groups[groupID].contestID;
      if(contestID && contests[contestID] && !contests[contestID].parentContestID && groupContestIDs.indexOf(contestID) == -1) {
         groupContestIDs.push(contestID);
      }
   }
   function filterGroupContests(contestID) {
      return groupContestIDs.indexOf(contestID) != -1;
   }

   var model = {
      tableName: "group",
      fields: {
         schoolID: {
            label: t("group_schoolID_label"), required: true,
            editable: true, edittype: "select", stype: "select", editoptions: { value:getItemNames(schools)},
            searchoptions: { value:getItemNames(schools, true)},
            width: 150
         },
         userLastName: {label: t("school_admin_1_lastName"),
            editable: false,
            width:100
         },
         userFirstName: {label: t("school_admin_1_firstName"),
            editable: false,
            width:100
         },
         contestID: {label: t("contestID_label"),
            editable: true, edittype: "select", editoptions: { value:getItemNames(contests)},
            stype: "select", searchoptions: { value:getItemNames(contests, true, filterGroupContests)},
            required: true, 
            width: 260, comment: t("contestID_comment")},
         grade: {
            label: t("contestant_grade_label"), editable: true, edittype: "select", width: 100, required: true,
            editoptions:{value: getGradesList(false)},
            stype: "select", searchoptions: { value:"_NOF_:" + t("option_no_filter") + getGradesList(true)},
         },
         participationType: {
            label: t("participationType_label"), longLabel: t("participationType_long_label"), editable: true, required: true, edittype: "select", width: 100,
            editoptions:{ value:{"Official": t("participationType_official"), "Unofficial": t("participationType_unofficial")}},
            stype: "select", searchoptions:{ value:"_NOF_:" + t("option_no_filter") + ";Official:" + t("participationType_official") + ";Unofficial:" + t("participationType_unofficial")},
            comment: t("participationType_comment")
         },
         expectedStartTime: {
            label: t("expectedStartTime_label") + "<br/>(" + jstz.determine().name() + ")",
            longLabel: t("expectedStartTime_long_label"),
            formatter: utcDateFormatter,
            beforeSave: localDateToUtc,
            editable:true,
            edittype: "datetime",
            width: 150,
            required: true,
            comment: t("expectedStartTime_comment")
         },
         name: {label: t("group_name_label"), longLabel: t("group_name_long_label"), editable: true, edittype: "text", width: 200, required: true, comment: t("group_name_comment")},
/*         gradeDetail: {label: "Préciser", editable: true, edittype: "text", width: 200, required: false, comment: "Information qui nous aidera à estimer la diffusion du concours dans les différentes filières et niveaux éducatifs"},*/
         code: {label: t("group_code_label"), width: 100},
         password: {label: t("group_password_label"), width: 100},
         nbTeamsEffective: {label: t("group_nbTeamsEffective_label"), width: 100},
         nbStudentsEffective: {label: t("group_nbStudentsEffective_label"), width: 100},
         nbStudents: {label: t("group_nbStudents_label"), longLabel: t("group_nbStudents_long_label"), editable: true, required: true, edittype: "text", subtype:"positiveint", width: 100, comment: t("group_nbStudents_comment")},
         userID: {hidden: true, visible: false, hiddenlg: true},
         contestPrintCertificates: {hidden: true, visible: false, hiddenlg: true},
         contestPrintCodes: {hidden: true, visible: false, hiddenlg: true},
         minCategory: {label: t("group_minCategory_label"), width: 100},
         maxCategory: {label: t("group_maxCategory_label"), width: 100},
         language: {label: t("group_language_label"), width: 100},
      }
   };
   return model;
}

var groupSelectAfterLoad = null;
function groupLoadComplete() {
   $.each($("#grid_group").find("tr"), function(i, row) {
      var id = row.id;
      if(id) {
          var nbStudents = jQuery("#grid_group").jqGrid("getRowData", id).nbStudentsEffective;
          if (nbStudents > 0) {
             $(row).addClass("usedGroup");
          }
      }
   });
   if(groupSelectAfterLoad) {
      $("#grid_group").jqGrid('setSelection', groupSelectAfterLoad);
      groupSelectAfterLoad = null;
   }
}

function getContestQuestionsColModel() {
   return {
      tableName: "contest_question",
      fields: {
         contestID: {label: t("contest_question_contestID_label"),
            editable: true, edittype: "select", editoptions: { value:getItemNames(contests)},
            stype: "select", searchoptions: { value:getItemNames(contests, true)},
            width: 300},
         questionID: {label: t("contest_question_questionID_label"),
            editable: true, edittype: "select", editoptions: { value:getItemNames(questions)},
            stype: "select", searchoptions: { value:getItemNames(questions, true)},
            width: 300},
         minScore: {label: t("contest_question_minScore_label"), editable: true, edittype: "text", width: 120},
         noAnswerScore: {label: t("contest_question_noAnswerScore_label"), editable: true, edittype: "text", width: 120},
         maxScore: {label: t("contest_question_maxScore_label"), editable: true, edittype: "text", width: 120},
         options: {label: t("contest_question_options_label"), editable: true, edittype: "text", width: 250},
         order: {label: t("contest_question_order_label"), editable: true, edittype: "text", subtype:"int", width: 80}
      }
   };
}

function initModels(isLogged) {
   var groupStype;
   var groupSearchOptions;
   var groupEditOptions;
   if (isAdmin()) {
      groupStype = "text";
      groupSearchOptions = {};
      groupEditOptions = {};
   } else {
      groupStype = "select";
      groupSearchOptions = { value:getItemNames(groups, true) };
      groupEditOptions = { value:getItemNames(groups)};
   }
   var editYesNo = { value:{"1": t("option_yes"), "0": t("option_no")}};
   var searchYesNo = { value:"_NOF_:" + t("option_no_filter") + ";1:" + t("option_yes") + ";0:" + t("option_no")};
   var officialEmailEditType = "email";
   if (config.forceOfficialEmailDomain) {
      officialEmailEditType = "ac-email";
   }
   models = {
      award1: {
         tableName: "award1",
         fields: {
            schoolID: {label: t("contestant_school"), editable: false, search: false, width: 250, edittype: "select", editoptions: { value:getItemNames(schools)}},
            contestID: {label: t("contestant_contestID_label"), editable: false, search: false, width: 300, edittype: "select", editoptions: { value:getItemNames(contests)}},
            groupField: {label: t("contestant_name_label"), width: 300,
               editable: false, edittype: groupStype, editoptions: groupEditOptions,
               search: false },
            firstName: {label: t("contestant_firstName_label"), editable: false, search: false, width:200},
            lastName: {label: t("contestant_lastName_label"), editable: false, search: false, width:200},
            genre: {label: t("contestant_genre_label"),
               editable: true, edittype: "select", editoptions:{ value:{"1": t("option_female"), "2": t("option_male")}},
               search: false, width: 120},
            score: {label: t("contestant_score_label"), editable: false, search: false, width:100},
            rank: {label: t("contestant_rank_label"), editable: false, search: false, width:100},
            country: {hidden: true, visible: false, hiddenlg: true},
            city: {hidden: true, visible: false, hiddenlg: true},
            name: {hidden: true, visible: false, hiddenlg: true},
            algoreaCode: {hidden: true, visible: false, hiddenlg: true},
            algoreaCategory: {label: t("contestant_category_label"), editable: false, search: true, width: 130},
            loginID: {label: t("awards_loginID_label"), editable: false, search: false, width:130, sortable: false}
         }
      },
      contestant: {
         tableName: "contestant",
         fields: {
            schoolID: {label: t("contestant_school"),
               editable: false, edittype: "select", editoptions: { value:getItemNames(schools)},
               stype: "select", searchoptions: { value:getItemNames(schools, true)},
               width: 250},
            contestID: {label: t("contestant_contestID_label"),
               editable: false, edittype: "select", editoptions: { value:getItemNames(contests)},
               stype: "select", searchoptions: { value:getItemNames(contests, true)},
               width: 300},
            groupField: {label: t("contestant_name_label"), width: 300,
               editable: false, edittype: groupStype, editoptions: groupEditOptions,
               stype: groupStype, searchoptions: groupSearchOptions },
            saniValid: {label: t("contestant_saniValid_label"),
                        editable: false, width:120,
                        edittype: "select", editoptions: editYesNo,
                        stype: "select", searchoptions: searchYesNo
                       },
            firstName: {
               label: t("contestant_firstName_label"),
               editable: true,
               edittype: "text",
               width: 150,
               editrules: {
                  custom_func: checkContestantModifications,
                  custom: true,
                  required: true
               }
            },
            lastName: {label: t("contestant_lastName_label"), editable: true, edittype: "text", width:150},
            genre: {label: t("contestant_genre_label"),
               editable: true, edittype: "select", editoptions:{ value:{"1": t("option_female"), "2": t("option_male")}},
               stype: "select", searchoptions:{ value:"_NOF_:" + t("option_no_filter") + ";1:" + t("option_female") + ";2:" + t("option_male")},
               width: 75},
            //contestants: {label: "Équipe", editable: false, width:300},
            grade: {label: t("contestant_grade_label"), editable: true, edittype: "select", required: true, editoptions:{
               value:getGradesList(false)}, searchoptions:{ value:"_NOF_:" + t("option_no_filter") + getGradesList(true)},
               stype: "select", width:75},
            score: {label: t("contestant_score_label"), editable: false, width:75},
            duration: {label: t("contestant_duration_label"), editable: false, width: 100, search: false},
            nbContestants: {label: t("contestant_nbContestants_label"), editable: false, width:60, editoptions:{
               value:{
                  "1": t("nbContestants_1"),
                  "2": t("nbContestants_2"),
               }}, searchoptions:{ value:"_NOF_:" + t("option_no_filter") +
                  ";1:" + t("nbContestants_1") +
                  ";2:" + t("nbContestants_2")
               },
               edittype: "select", stype: "select"},
            rank: {label: t("contestant_rank_label"), editable: false, width:150},
            category: {label: t("contestant_category_label"), editable: false, search: true, width: 130},
            email: {label: t("contestant_email_label"), editable: true, edittype: "text", width:150},
            zipCode: {label: t("contestant_zipCode_label"), editable: true, edittype: "text", width:150}
         }
      },
      school: {
         tableName: "school",
         fields: {
            name: {label: t("school_name_label"), editable: true, edittype: "text", width: 250, required: true},
            region: {label: t("school_region_label"),
               editable: true, edittype: "select", width: 120, editoptions:{ value:getRegionsObjects() },
               stype: "select", searchoptions:{ value:getRegionsObjects(true) },
               comment: t("school_region_comment"),
               required: true
            },
            address: {label: t("school_address_label"), editable: true, edittype: "text", width: 150, required: true},
            city: {label: t("school_city_label"), editable: true, edittype: "text", width: 200, required: true},
            zipcode: {label: t("school_zipcode_label"), longLabel: t("school_zipcode_long_label"), editable: true, edittype: "text", width: 120, required: true},
            country: {label: t("school_country_label"), editable: true, edittype: "text", width: 100, required: true},
            nbStudents: {label: t("school_nbStudents_label"), longLabel: t("school_nbStudents_label_long"), editable: true, edittype: "text", subtype:"positiveint", width: 100, comment:t("school_nbStudents_comment"), required: true}
         }
      },
      school_search: {
         tableName: "school_search",
         fields: {
            name: {label: t("school_name_label"), width: 250},
            region: {label: t("school_region_label"),
               editable: false, edittype: "select", width: 120, editoptions:{ value:getRegionsObjects() },
               stype: "select", searchoptions:{ value:getRegionsObjects(true) },
               comment: t("school_region_comment")
            },
            address: {label: t("school_address_label"), width: 150},
            city: {label: t("school_city_label"), width: 200},
            zipcode: {label: t("school_zipcode_label"), width: 120},
            country: {label: t("school_country_label"), width: 100}
         }
      },
      colleagues: {
         tableName: "colleagues",
         fields: {
            lastName: {label: t("user_lastName_label") , editable: false, width: 250},
            firstName: {label: t("user_firstName_label") , editable: false, width: 250},
            accessTypeGiven: {label: t("colleagues_access_given"),
               editable: true, edittype: "select", editoptions:{ value:{"none": t("colleagues_access_none"), "read": t("colleagues_access_read"), "write": t("colleagues_access_write")}},
               stype: "select", searchoptions:{ value:"_NOF_:" + t("option_no_filter") + ";none:" + t("colleagues_access_none") + ";read:" + t("colleagues_access_read") + ";write:" + t("colleagues_access_write")},
               width: 150},
            accessTypeReceived: {label: t("colleagues_access_received"),
               editable: false,edittype: "select", editoptions:{ value:{"none": t("colleagues_access_none"), "read": t("colleagues_access_read"), "write": t("colleagues_access_write")}},
               stype: "select", searchoptions:{ value:"_NOF_:" + t("option_no_filter") + ";none:" + t("colleagues_access_none") + ";read:" + t("colleagues_access_read") + ";write:" + t("colleagues_access_write")},
               width: 150}
         }
      },
      user: {
         tableName: "user",
         fields: {
            saniValid: {label: t("user_saniValid_label"), 
                        editable: false, width:120,
                        edittype: "select", editoptions: editYesNo,
                        stype: "select", searchoptions: searchYesNo
                       },
            gender: {label: t("user_gender_label"), editable: true, edittype: "select", width: 50, editoptions:{ value:{"F": t("user_gender_female"), "M": t("user_gender_male")}}},
            lastName: {label: t("user_lastName_label"), editable: true, edittype: "text", width: 90},
            firstName: {label: t("user_firstName_label"), editable: true, edittype: "text", width: 90},
            officialEmail: {label: t("user_officialEmail_label"), editable: true, edittype: "text", width: 200},
            officialEmailValidated: {label: t("user_officialEmailValidated_label"), editable: true, edittype: "select", width: 50, editoptions: editYesNo, stype: "select", searchoptions: searchYesNo},
            alternativeEmail: {label: t("user_alternativeEmail_label"), editable: true, edittype: "text", width: 200},
            validated: {label: t("user_validated_label"), editable: true, edittype: "select", width: 60, editoptions: editYesNo},
            allowMultipleSchools: {label: t("user_allowMultipleSchools_label"), editable: true, edittype: "select", width: 90, editoptions: editYesNo, stype: "select", searchoptions: searchYesNo},
            registrationDate: {
               label: t("user_registrationDate_label"),
               formatter: utcDateFormatter,
               editable: false,
               width: 170
            },
            lastLoginDate: {
               label: t("user_lastLoginDate_label"),
               formatter: utcDateFormatter,
               editable: false,
            width: 170},
            awardPrintingDate: {label: t("user_awardPrintingDate_label"), editable: false, width: 170},
            isAdmin: {label: t("user_isAdmin_label"), editable: true, edittype: "select", width: 60, editoptions: editYesNo, stype: "select", searchoptions: searchYesNo},
            comment: {label: t("user_comment_label"), editable: true, edittype: "textarea", width: 450, editoptions:{rows:"8",cols:"40"}}
         }
      },
      user_create: {
         tableName: "user",
         fields: {
            gender: {label: t("user_gender_label"), editable: true, edittype: "select", width: 20, editoptions:{ value:{"F": t("user_gender_female"), "M": t("user_gender_male")}}, required:true},
            lastName: {label: t("user_lastName_label"), editable: true, edittype: "text", width: 90, required: true},
            firstName: {label: t("user_firstName_label"), editable: true, edittype: "text", width: 90, required: true},
            officialEmail: {label: t("user_officialEmail_label"), editable: true, edittype: officialEmailEditType, width: 90, required: false},
            alternativeEmail: {label: t("user_alternativeEmail_label"), editable: true, edittype: "email", width: 90},
            password: {label: t("user_password_label"), editable: true, edittype: "password", width: 90, required: true},
            password2: {label: t("user_password_confirm_label"), editable: true, edittype: "password", width: 90, required: true}
         }
      },
      user_edit: {
         tableName: "user",
         fields: {
            gender: {label: t("user_gender_label"), editable: true, edittype: "select", width: 20, editoptions:{ value:{"F": t("user_gender_female"), "M": t("user_gender_male")}}, required:true},
            lastName: {label: t("user_lastName_label"), editable: true, edittype: "text", width: 90, required: true},
            firstName: {label: t("user_firstName_label"), editable: true, edittype: "text", width: 90, required: true},
            officialEmail: {label: t("user_officialEmail_label"), editable: true, edittype: officialEmailEditType, width: 90, required: true},
            alternativeEmail: {label: t("user_alternativeEmail_label"), editable: true, edittype: "text", width: 90},
            old_password: {label: t("user_old_password_label"), editable: true, edittype: "password", width: 90, comment:t("user_change_password_explanation")},
            password: {label: t("user_new_password_label"), editable: true, edittype: "password", width: 90},
            password2: {label: t("user_new_password_confirm_label"), editable: true, edittype: "password", width: 90}
         }
      },
      team_view: {
         tableName: "team_view",
         fields: {
            schoolID: {label: t("team_view_school"),
               editable: false, edittype: "select", editoptions: { value:getItemNames(schools)},
               stype: "select", searchoptions: { value:getItemNames(schools, true)},
               width: 250},
            contestID: {label: t("contestant_contestID_label"),
               editable: false, edittype: "select", editoptions: { value:getItemNames(contests)},
               stype: "select", searchoptions: { value:getItemNames(contests, true)},
               width: 300},
            groupField: {label: t("team_view_name_label"), editable: false, width: 300,
               stype: groupStype, searchoptions: groupSearchOptions},
            contestants: {label: t("team_view_contestants_label"), editable: false, width: 500},
            password: {label: t("team_view_password_label"), editable: false, width: 100, search: false},
            startTime: {
               label: t("team_view_startTime_label") + "<br/>(" + jstz.determine().name() + ")",
               editable: false,
               formatter: utcDateFormatter,
               width: 180,
               search: false
            },
            score: {label: t("team_view_score_label"), editable: false, width: 100, search: false},
            duration: {label: t("team_view_duration_label"), editable: false, width: 100, search: false},
            participationType: {label: t("participationType_label"), longLabel: t("participationType_long_label"), editable: false, required: false, edittype: "select", width: 130, editoptions:{ value:{"Official": t("participationType_official"), "Unofficial": t("participationType_unofficial")}}, comment: t("participationType_comment")},
         }
      },
      contest: {
         tableName: "contest",
         fields: {
            name: {label: t("contest_name_label"), editable: true, edittype: "text", width:350},
            level: {label: t("contest_level_label"), editable: true, edittype: "select", width: 100,
               editoptions:{ value:{
                  "0": t("option_undefined"),
                  "1": t("option_grades_6_7"),
                  "2": t("option_grades_8_9"),
                  "3": t("option_grades_10"),
                  "4": t("option_grades_11_12"),
                  "5": t("option_grades_10_pro"),
                  "6": t("option_grades_11_12_pro")
               }},
               searchoptions:{ value:"_NOF_:" + t("option_no_filter") +
                     ";0:" + t("option_undefined") +
                     ";1:" + t("option_grades_6_7") +
                     ";2:" + t("option_grades_8_9") +
                     ";3:" + t("option_grades_10") +
                     ";4:" + t("option_grades_10_pro") +
                     ";5:" + t("option_grades_11_12") +
                     ";6:" + t("option_grades_11_12_pro")
                     },
               stype: "select"
            },
            year: {label: t("contest_year_label"), editable: true, edittype: "text", subtype:"int", width: 40},
            status: {label: t("contest_status_label"), editable: true, edittype: "select", width: 100,
               editoptions:{
                  value:{
                     "FutureContest": t("option_future_contest"),
                     "RunningContest": t("option_running_contest"),
                     "PreRanking": t("option_preranking_contest"),
                     "PastContest": t("option_past_contest"),
                     "Other": t("option_other_contest"),
                     "Closed": t("option_closed_contest"),
                     "Hidden": t("option_hidden_contest")
                   }
               },
               search: false
            },
            open: {label: t("contest_open_label"), editable: true, edittype: "select", width: 70,
               editoptions:{
                  value:{
                     "Open": t("option_open_contest"),
                     "Closed": t("option_closed_contest"),
                   }
               },
               search: false
            },
            visibility: {label: t("contest_visibility_label"), editable: true, edittype: "select", width: 70,
               editoptions:{
                  value:{
                     "Hidden": t("option_hidden_contest"),
                     "Visible": t("option_visible_contest"),
                   }
               },
               search: false
            },
            closedToOfficialGroups: {label: t("contest_closedToOfficialGroups_label"), editable: true, edittype: "select", width: 70,
               editoptions: editYesNo,
               search: false
            },             
            showSolutions: {label: t("contest_showSolutions_label"), editable: true, edittype: "select", width: 60, editoptions: editYesNo},
            startDate: {
               label: t("contest_begin_date_label") + "<br/>(" + jstz.determine().name() + ")",
               formatter: utcDateFormatter,
               beforeSave: localDateToUtc,
               editable:true,
               width: 120
            },
            endDate: {
               label: t("contest_end_date_label") + "<br/>(" + jstz.determine().name() + ")",
               formatter: utcDateFormatter,
               beforeSave: localDateToUtc,
               editable:true,
               width: 120
            },
            nbMinutes: {label: t("contest_nbMinutes_label"), editable: true, edittype: "text", subtype:"int", width: 100},
            bonusScore: {label: t("contest_bonusScore_label"), editable: true, edittype: "text", subtype:"int", width: 100},
            allowTeamsOfTwo: {
               label: t("contest_allowTeamsOfTwo_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            newInterface: {
               label: t("contest_newInterface_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            customIntro: {label: t("contest_customIntro_label"), editable: true, edittype: "text", width:350},
            fullFeedback: {
               label: t("contest_fullFeedback_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            showTotalScore: {
               label: t("contest_showTotalScore_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            nextQuestionAuto: {
               label: t("contest_nextQuestionAuto_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            nbUnlockedTasksInitial: {label: t("contest_nbUnlockedTasksInitial_label"), editable: true, edittype: "text", subtype:"int", width: 100},
            subsetsSize: {label: t("contest_subsetsSize_label"), editable: true, edittype: "text", subtype:"int", width: 100},
            folder: {label: t("contest_folder_label"), editable: true, edittype: "text", width:350},
            askEmail: {
               label: t("contest_askEmail_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            askZip: {
               label: t("contest_askZip_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            askGrade: {
               label: t("contest_askGrade_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            askStudentId: {
               label: t("contest_askStudentId_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            askPhoneNumber: {
               label: t("contest_askPhoneNumber_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            askGenre: {
               label: t("contest_askGenre_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            headerImageURL: {label: t("contest_headerImageURL_label"), editable: true, edittype: "text"},
            headerHTML: {label: t("contest_headerHTML_label"), editable: true, edittype: "text"},
            certificateTitle: {label: t("contest_certificateTitle_label"), editable: true, edittype: "text"},
            logActivity: {
               label: t("contest_logActivity_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            srlModule: {
               label: t("contest_srlModule_label"),
               editable: true,
               edittype: "select",
               stype: "select",
               editoptions:{ value:{
                  "none": t("option_srlModule_none"),
                  "log": t("option_srlModule_log"),
                  "random": t("option_srlModule_random"),
                  "full": t("option_srlModule_full"),
                  "algorea": t("option_srlModule_algorea")
               }},
               searchoptions:{ value:"_NOF_:" + t("option_no_filter") +
                     ";none:" + t("option_srlModule_none") +
                     ";log:" + t("option_srlModule_log") +
                     ";random:" + t("option_srlModule_random") +
                     ";full:" + t("option_srlModule_full") +
                     ";algorea:" + t("option_srlModule_algorea")
                     },
               width: 100
            },
            sendPings: {
               label: t("contest_sendPings_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            allowFromHome: {
               label: t("contest_allowFromHome_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            certificateAllNames: {
               label: t("contest_certificateAllNames_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            },
            certificateIsIntermediate: {
               label: t("contest_certificateIsIntermediate_label"),
               editable: true,
               edittype: "select", editoptions: editYesNo,
               stype: "select", searchoptions: searchYesNo,
               width: 100
            }
         }
      },
      question: {
         tableName: "question",
         fields: {
            key: {
               label: t("question_key_label"),
               editable: true,
               edittype: "text",
               width: 120,
               beforeSave: function(s) { return s.replace(/\//g, ''); }
            },
            path: {label: t("question_path_label"), editable: true, edittype: "text", width: 300},
            name: {label: t("question_name_label"), editable: true, edittype: "text", width: 300},
            answerType: {label: t("question_answerType_label"), editable: true, edittype: "select", width: 150,
            editoptions:{ value:{"0": t("option_multiple_choice"), "1": t("option_free_input"), "2": t("option_evaluated")}}},
            expectedAnswer: {label: t("question_expectedAnswer_label"), editable: true, edittype: "text", width: 100}
         }
      },
      school_user: {
         tableName: "school_user",
         fields: {
            userID: {editable: true},
            schoolID: {editable: true},
            confirmed: {editable: true},
            awardsReceivedYear: {editable: true}
         }
      },
      school_year: {
         tableName: "school_year",
         fields: {
            schoolID: {editable: false},
            userID: {editable: false},
            year: {editable: false},
            nbOfficialContestants: {editable: false},
            awarded: {editable: false}
         }
      }
   };
   
   if (config.noGender) {
      delete models.user_create.fields.gender;
      delete models.user_edit.fields.gender;
   }
   if (!config.displayDuration) {
      delete models.contestant.fields.duration;
      delete models.team_view.fields.duration;
   }
   if (config.removeLastColumns) {
      for (var mName in config.removeLastColumns) {
         var fields = [];
         for(var field in models[mName].fields) {
            fields.push(field);
         }
         for(var i = fields.length - config.removeLastColumns[mName]; i < fields.length; i++) {
            delete models[mName].fields[fields[i]];
         }
      }
   }
   
   // These fields are only needed if your are an admin
   if (isAdmin())
   {
       models.school.fields.lastName = {label: t("school_admin_1_lastName"), editable: false, width: 150};
       models.school.fields.firstName = {label: t("school_admin_1_firstName"), editable: false, width: 150};
       models.school.fields.saniValid = 
           {label: t("school_saniValid_label"), 
                        editable: false, width:120,
                        edittype: "select", editoptions: editYesNo,
                        stype: "select", searchoptions: searchYesNo,
           };
       models.school.fields.saniMsg = {label: t("school_saniMsg_label"), editable: true, edittype: "text", width: 200};
       models.school.fields.coords = {label: t("school_coords_label"), editable: false, edittype: "text", width: 150};
   }
   if (isLogged) {
      models.group = getGroupsColModel();
      models.contest_question = getContestQuestionsColModel();
   }
}

function jqGridNames(modelName) {
   var fields = models[modelName].fields;
   var names = [];
   for (var fieldName in fields) {
      if (!fields[fieldName].label) {
         fields[fieldName].label = '';
      }
      names.push(fields[fieldName].label);
   }
   return names;
}

function jqGridModel(modelName) {
   var fields = models[modelName].fields;
   var res = [];
   for (var fieldName in fields) {
      var field = fields[fieldName];
      var jqGridField = {
         name: fieldName,
         index: fieldName,
         width: field.width,
         editable: !config.readOnly && field.editable,
         edittype: field.edittype,
         editoptions: field.editoptions,
         editrules: field.editrules,
         formatter: field.formatter,
         unformat: field.unformat,
         formatoptions: field.formatoptions,
         searchoptions: field.searchoptions,
         stype: field.stype,
         search: field.search
      };
      if (field.edittype === "select") {
         jqGridField.formatter = "select";
      }
      if (typeof field.sortable !== 'undefined') {
         jqGridField.sortable = field.sortable;
      }
      if (field.hidden) {
         jqGridField.hidden = true;
         jqGridField.visible= false;
         jqGridField.hiddenlg= true;
      }
      res.push(jqGridField);
   }
   return res;
}

function jqGridDataFail(jqXHR, textStatus, error) {
   if (jqXHR.responseText) {
      jqAlert(jqXHR.responseText);
   } else {
      jqAlert('Error');
   }
}

function teamViewMakeUnofficial(id) {
   if (confirm(t("confirm_make_team_unofficial"))) {
      jQuery("#grid_team_view").jqGrid('setCell',id,'participationType', 'Unofficial');
      jQuery("#grid_team_view").saveCell(id, 'participationType'); // doesn't work, no idea why...
      var item = {id: id, participationType: 'Unofficial'};
      $.post("jqGridData.php", {tableName:"team_view", oper: "update", record: item}).fail(jqGridDataFail);
   }
}

function teamViewLoadComplete() {
   $.each($("#grid_team_view").find("tr"), function(i, row) {
      var id = row.id;
      if(id) {
          var participationType = jQuery("#grid_team_view").jqGrid("getRowData", id).participationType;
          if (participationType == 'Official') {
             $(row).find('td:last').append("<input style='height:22px;width:20px;' type='button' value='X' onclick=\"teamViewMakeUnofficial('"+id+"');\" />");
          }
      }
   });
}

function beforeSaveRow(grid, display) {
   if(!grid) { return true; }
   var modelName = grid.id.split("_")[1];
   var valid = true;
   $(grid).find('input').each(function () {
      var field = models[modelName].fields[this.name];
      if (field === undefined) { return; }
      if (field.beforeSave) {
         $(this).val(field.beforeSave($(this).val()));
      }
      if (field.editrules) {
         var fieldValid = field.editrules.custom_func($(this).val());
         valid = valid && fieldValid[0];
         if(display && !fieldValid[0]) {
            alert(fieldValid[1]);
         }
      }
   });
   return valid;
}

function loadGrid(modelName, sortName, rowNum, rowList, onSelectRow, withToolbar) {
  var lastSel = 0;
  var loadComplete;
  if (modelName == "group") {
     loadComplete = groupLoadComplete;
  } else if (modelName == 'team_view') {
     loadComplete = teamViewLoadComplete;
  }

  var tableName = models[modelName].tableName;
  $("#grid_" + modelName).jqGrid({
    url: "jqGridData.php?tableName=" + tableName,
    datatype: 'xml',
    mtype: 'GET',
    colNames: jqGridNames(modelName),
    colModel: jqGridModel(modelName),
    pager: "#pager_" + modelName,
    rowNum: rowNum,
    rowList: rowList,
    sortname: sortName,
    sortorder: 'asc',
    regional : config.defaultLanguage,
    viewrecords: true,
    gridview: true,
    width: "100%",
    height: "100%",
    caption: '',  
    onSelectRow: function(id){
       if(id && (id !== lastSel)){
          if(beforeSaveRow($('#grid_' + modelName)[0], true)) {
            $('#grid_' + modelName).saveRow(lastSel); 
          } else if(lastSel) {
            // We're already deselecting the row so we have to restore it if it's not valid
            $('#grid_' + modelName).restoreRow(lastSel);
          }
          lastSel = id; 
          onSelectRow(id);
       } else {
          if (modelName === "school") {
              var school = schools[id];
              if (school.nbUsers > 1) {
                 alert(t("alert_school_has_multiple_coordinators", { nbUsers: school.nbUsers, infoEmail: config.infoEmail }));
                 return;
              }
              /*
              if (school.userID !== loggedUser.ID) {
                 alert("Seul le créateur de cet établissement (" + school.userLastName + " " + school.userFirstName + ") peut l'éditer");
                 return;
              }
              */
          }
          if (modelName !== "group") { // TODO: clean
             $('#grid_' + modelName).editRow(id, true);
          }
       }
    },
    editurl: "jqGridData.php?tableName=" + tableName,
    loadComplete: loadComplete,
    loadError: jqGridDataFail
  }); 
  if (withToolbar) {
     $("#grid_" + modelName).jqGrid('filterToolbar', {autosearch:true, searchOnEnter:true});
  }
  $.extend(true, $.jgrid.inlineEdit, {
     beforeSaveRow: function () { beforeSaveRow(this); }
  });
}

function loadCustomAwards() {
   $.post("nextContestData.php", {}, function(res) {
      if (!res.success) return;
      var data = res.data;
      $('#custom_award_title').html(data.title);
      $('#custom_award_help').html(data.help);
      var table = '<table class="ui-common-table" style="border-spacing:0px 0px;"><thead><tr class="ui-jqgrid-labels">';
      for (var iCol = 0 ; iCol < data.colNames.length; iCol++) {
         table += '<th style="width: '+data.colNames[iCol].width+'; border: 1px solid gray;">';
         table += data.colNames[iCol].name;
         table += '</th>';
      }
      table +="</tr></thead><tbody>";
      for (var rowID in data.colData) {
         var row = data.colData[rowID];
         table += '<tr style="border: 1px solid gray;">';
         for (iCol = 0 ; iCol < data.colNames.length; iCol++) {
            table += '<td style="border: 1px solid gray;'+data.colNames[iCol].style+'">'+row[iCol]+'</td>';
         }
         table += '</tr>';
      }
      table += '</tbody></table>';
      $('#custom_award_data').html(table);
   }, 'json');
}

var originalContestantRow = null;
function loadContestants() {
   loadGrid("contestant", "", 20, [20, 50, 200, 500], function(id) {
      originalContestantRow = $('#grid_contestant').jqGrid('getRowData', id);
      originalContestantRow.id = id;
   }, true);
}

function loadListAwards() {
   loadGrid("award1", "", 20, [20, 50, 200, 500], function() {}, true);
   loadCustomAwards();
}

function loadSchoolSearch() {
   loadGrid("school_search", "", 20, [20, 50, 200, 500], function() {}, true);
}

function loadTeams() {
   loadGrid("team_view", "startTime", 20, [20, 50, 200, 500], function() {}, true);
}

function loadUsers() {
   loadGrid("user", "lastName", 20, [20, 50, 200, 500], function() {}, true);
}

function loadListContests() {
   loadGrid("contest", "name", 10, [10, 20, 30], function(id) {
      selectedContestID = id;
      $('#grid_contest_question').jqGrid('setGridParam', {
         url:'jqGridData.php?tableName=contest_question&contestID=' + id
      }).trigger('reloadGrid');
   }, true);
}

function loadListQuestions() {
   loadGrid("question", "key", 20, [20, 50, 200], function(id) {
      function setPath() {
         selectedQuestionID = id;
         var path = questions[id].path;
         checkTaskPath(path);
         if(!path) {
            $("#preview_question").attr("src", '');
            return;
         }
         var url = config.tasksPathInterface + path;
         $("#preview_question").attr("src", url);
      }

      if(questions[id] && questions[id].path) {
         setPath();
      } else {
         // Question might be new, try reloading first
         // TODO :: could just reload each time?
         loadQuestions().done(setPath);
      }
   }, true);
}

function loadListSchools() {
   loadGrid("school", "name", 20, [20, 50, 200, 500], function() {}, isAdmin());
}

function loadListColleagues() {
   loadGrid("colleagues", "lastName", 20, [20, 50, 200], function() {}, false);
}

function loadContestQuestions() {
   loadGrid("contest_question", "order", 20, [20, 50, 200], function() {}, true);
}

function loadListGroups() {
   loadGrid("group", "name", 20, [10, 20, 50], function(id) {selectedGroupID = id;}, true);
}

function filterSchool() {
   filterSchoolID = jQuery("#grid_school").jqGrid('getGridParam','selrow');
   if (filterSchoolID === "null") {
      filterSchoolID = "0";
      jqAlert(t("warning_no_school_selected"));
   }
   updateFilters();
}

function filterGroup() {
   filterGroupID = jQuery("#grid_group").jqGrid('getGridParam','selrow');
   if (filterGroupID === "null") {
      filterGroupID = "0";
      jqAlert(t("warning_no_group_selected"));
   }
   updateFilters();
}

function cancelFilterGroup() {
   filterGroupID = "0";
   updateFilters();
}

function cancelFilterSchool() {
   filterSchoolID = "0";
   updateFilters();
}

function updateFilters() {
   var html = "";
   if (filterSchoolID !== "0") {
      html += "<b>" + t("filter_school") + "</b> " + schools[filterSchoolID].name + " <input type='button' onclick='cancelFilterSchool()' value='" + t("filter_remove") + "' /><br/>";
   }
   if (filterGroupID !== "0") {
      html += "<b>" + t("filter_group") + "</b> " + groups[filterGroupID].name + " <input type='button' onclick='cancelFilterGroup ()' value='" + t("filter_remove") + "' /><br/>";
   }
   html += "<br/>";
   $("#filters").html(html);
   reloadGrids();
}

function exportCSV(tableName) {
   var urlFilters = "";
   if (filterSchoolID != "0") {
      urlFilters += "&schoolID=" + filterSchoolID;
   }
   if (filterGroupID != "0") {
      urlFilters += "&filterGroupID=" + filterGroupID;
   }
   window.location.href = "jqGridData.php?tableName=" + tableName + urlFilters + "&format=csv&sidx&sord&page=1&rows=50000";
}

function reloadGrids() {
   var urlFilters = "";
   if (filterSchoolID != "0") {
      urlFilters += "&schoolID=" + filterSchoolID;
   }
   if (filterGroupID != "0") {
      urlFilters += "&filterGroupID=" + filterGroupID;
   }
   $('#grid_group').jqGrid('setGridParam', {
      url: "jqGridData.php?tableName=group" + urlFilters
   }).trigger('reloadGrid');
   $('#grid_contestant').jqGrid('setGridParam', {
      url:'jqGridData.php?tableName=contestant' + urlFilters
   }).trigger('reloadGrid');
   $('#grid_team_view').jqGrid('setGridParam', {
      url:'jqGridData.php?tableName=team_view' + urlFilters
   }).trigger('reloadGrid');
}


function refreshGrid(model) {
     $("#grid_" + model).trigger('reloadGrid');
}

function isLogged() {
   return $.post("login.php", {isLogged: 1},
      function(data) {
         if (data.success) {
            logUser(data.user);
         } else {
            $("#loading").hide();
            $("#login_form").show();
            initModels(false);
         }
      }, "json"
   );
}

function loadUser(user) {
   var gender = '';
   if (user.gender == 'F')
       gender = t("user_gender_female");
   if (user.gender == 'M')
       gender = t("user_gender_male");

   $("#user-gender").html(gender);
   $("#user-lastName").html(user.lastName);
   $("#user-firstName").html(user.firstName);
   if (user.officialEmail !== "") {
      var strValidated = " (" + t("not_confirmed") + ")";
      if (user.officialEmailValidated === "1") {
         strValidated = " (" + t("confirmed") + ")";
      }
      $("#user-officialEmail").html(user.officialEmail + strValidated);
   }
   $("#user-alternativeEmail").html(user.alternativeEmail);
}

function continueLogUser() {
   var state = 'normal';
   var spreadCastorBox = false;
   loadSchoolsUsers();
   $("#login_form").hide();
   $("#login_link_to_home").hide();
   $("#loading").hide();
   initModels(true);
   $("#logoutLink").show();
   $("#headerWarning").show();
   loadListSchools();
   loadListColleagues();
   if (!spreadCastorBox) {
      $("#spread-castor").hide();
   }
   if (isAdmin()) {
      $("#title").html(t("title_administrator"));
      loadUsers();
      loadListContests();
      loadListQuestions();
      loadContestQuestions();
      if (state !== 'normal') {
         //loadContestants();
         //loadTeams();
         //loadListAwards();
      }
      initDeleteButton("user");
      initDeleteButton("contest");
      initDeleteButton("question");
      initDeleteButton("contest_question");
      initDeleteButton("contestant");
      initDeleteButton("team_view");
      $("#buttonDeleteSelected_contestant").show();
      $("#buttonDeleteSelected_team_view").show();
      $("#advSchools").show();
      $("#buttonRefreshUsers").show();
      $("#buttonRefreshSchools").show();
      $("#linkExportUsers").show();
      $("#linkExportSchools").show();
      $("#buttonComputeCoords_school").show().click(function(){
         computeCoordsSchools();
      });
      $("#buttonDeleteSelected_school").click(function() {
         jqAlert(t('admin_cannot_delete_school'));
      });
   } else {
      loadContestants();
      loadTeams();
      loadListGroups();
      //if (loggedUser.allowMultipleSchools === "1") {
         //$("#singleSchool").hide();
         //$("#multipleSchools").show();
         $("#advSchools").show();
      //}
      if (spreadCastorBox) {
         initSpreadCastor();
      }
      initDeleteButton("school", function() { $('#grid_colleagues').trigger('reloadGrid'); return [true];});
      $("#li-tabs-questions").hide();
      $("#li-tabs-contests").hide();
      if (state === 'contest') {
         $("#li-tabs-contestants").hide();
         $("#li-tabs-teams").hide();
         $("#tabs-contestants").hide();
         $("#tabs-teams").hide();
      }
      if (state !== 'normal') {
         $("#li-tabs-certificates").hide();
         $("#tabs-certificates").hide();
      }      
      $("#li-tabs-awards").hide();
      $("#tabs-questions").hide();
      $("#tabs-contests").hide();
      if (state === 'normal') {
         loadListAwards();
      }
   }
   $("#admin_view").tabs();
   $("#admin_view").show();
   initDeleteButton("group");
}

function getPersonalCode() {
   $.post('personalQualificationCode.php', {}, function(data) {
      if (!data.success) {
         $('#noPersonalCode').hide();
         $('#withPersonalCode').hide();
         console.error(data.error);
      } else {
         if (data.code === false) {
            $('#noPersonalCode').hide();
            $('#withPersonalCode').hide();
         } else if (!data.code){
            $('#withPersonalCode').hide();
         } else {
            $('#personalCode').html(data.code);
            $('#noPersonalCode').hide();
         }
      }
   }, 'json');
}

function generatePersonalCode() {
    $.post('personalQualificationCode.php', {create: true}, function(data) {
      if (!data.code) {
         $('#withPersonalCode').hide();
      } else {
         $('#personalCode').html(data.code);
         $('#noPersonalCode').hide();
         $('#withPersonalCode').show();
      }
   }, 'json');
}

function logUser(user) {
   loggedUser = user;
   loadUser(user);
   getPersonalCode();
   loadSchools().done(function() {
      loadContests().done(function() {
         if (isAdmin()) {
            loadQuestions().done(function() {
               continueLogUser();
            });
         } else {
            loadGroups().done(function() {
               continueLogUser();
            });
         }
      });
   });
}

function logout() {
   return $.post("login.php", {logout: 1}, 
      function(data) {
         if (data.success) {
            window.location.reload();
         }
      }, "json"
   );   
}

function initSpreadCastor() {
   $("#spread-castor-send").click(function() {
      $("#spread-castor-send").attr("disabled", "disabled");
      var email = $("#spread-castor-email").val();
      $.get("recommendSystem.php", {recommendTo: email}, 
         function(data) {
            $("#spread-castor-send").removeAttr("disabled"); 
            if (!data.success) {
               alert("ERROR for sendMessage");
            } else {
               jqAlert(t("spread_castor_" + data.message));
               if (data.message == "successful_email") {
                  $("#spread-castor-email").val('');
               }
            }
         }, "json"
     );
   });

   $.get("recommendSystem.php", {nearby: 1},
      function(data) {
         if (! data.success) {
            alert("ERROR for nearbySchools");
            return;
         }
         var names = data.nearbySchools;
         var url = data.fullListURL;
         // for debug:
         // var names = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];
         // var url = "recommendSystem.php?academieID=4&level=Lycee";
         var nb = names.length;
         var s = '';
         s += "<div>" + t("schools_without_coordinator")+ "</div>";
         s += "<table class='spread-castor-table'><tr><td><ul>";
         for (var i = 0; i < Math.min(5, nb); i++) {
            s += "<li>" + names[i] + "</li>";
         }
         s += "</ul></td><td><ul>";
         for (i = 5; i < Math.min(9, nb); i++) {
            s += "<li>" + names[i] + "</li>";
         }
         s += "<li>... <span style='font-size: small'><a href='" + url + "'>" + t("schools_view_all") + "</a></span></li>";
         s += "</ul></td></tr></table>";
         $("#spread-castor-table").html(s);
      }, "json"
   );
}

function initDeleteButton(modelName, afterSubmit) {
   $("#buttonDeleteSelected_" + modelName).show();
   $("#buttonDeleteSelected_" + modelName).click(function(){
      var gr = jQuery("#grid_" + modelName).jqGrid('getGridParam','selrow');
      if( gr ) {
         if (modelName === "school") {
         }
         if (modelName === "group") {
             var nbStudents = jQuery("#grid_group").jqGrid("getRowData", gr).nbStudentsEffective;
             if ((nbStudents !== "0") && (loggedUser.isAdmin !== "1")) {
                jqAlert(t("number_of_participants_1") + nbStudents + t("number_of_participants_2"));
                return;
             }
         }
         jQuery("#grid_" + modelName).jqGrid('delGridRow',gr,{reloadAfterSubmit:false,     afterSubmit: afterSubmit});
      } else {
         jqAlert(t("select_record_to_delete"));
      }
   });
}

function loadData(tableName, callback) {
   return $.post("jqGridData.php", {oper: "select", tableName: tableName},
      function(data) {
         if (data.success) {
            callback(data.items);
         } else {
            jqAlert(t("error_while_loading"));
         }
      }, "json"
   );
}

function getItemNames(items, withUnselect, filterFunc) {
   var itemNames = "";
   if (withUnselect) {
      itemNames += "_NOF_:" + t("option_no_filter") + ";";
   }
   var toSort = [];
   for (var itemID in items) {
      if(filterFunc && !filterFunc(itemID)) {
         continue;
      }
      toSort.push({ID : itemID, name: items[itemID].name});
   }
   toSort.sort(function(itemA, itemB) {
      return itemA.name.localeCompare(itemB.name);
   });
   for (var iItem = 0;iItem < toSort.length; iItem++) {
      var item = toSort[iItem];
      itemNames += item.ID + ":" + item.name;
      if (iItem < toSort.length - 1) {
         itemNames += ";";
      }
   }
   return itemNames;
}

function loadContestCategories(contests) {
   var categoryToID = {};
   contestCategories = [];
   var categoryID = 0;
   for (var contestID in contests) {
      var contest = contests[contestID];
      if (contestHidden(contest)) {
         continue;
      }
      var category = contest.category;
      if (category == "") {
         category = "-----"; // TODO: translate
         contest.category = category;
      }
      if (categoryToID[category] == undefined) {
         categoryToID[category] = categoryID;
         contestCategories[categoryID] = category;
         categoryID++;
      }
   }
   contestCategories.sort(function(contestA, contestB) {
      return contestA.localeCompare(contestB);
   });
   for (var categoryID = 0; categoryID < contestCategories.length; categoryID++) {
      var category = contestCategories[categoryID];
      categoryToID[category] = categoryID;
   }
   for (var contestID in contests) {
      var contest = contests[contestID];
      contest["categoryID"] = categoryToID[contest.category];
   }
}

function loadContests() {
   return loadData("contest", function(items) {
      contests = items;
      loadContestCategories(contests);
      if (!window.config.allowCertificates) {
         $('#buttonPrintCertificates_group').hide();
         $('#buttonPrintCertificates_team').hide();
         $('#group_print_certificates_help').hide();
         $('#group_print_certificates_title').hide();
         $('#team_print_certificates_help').hide();
         $('#team_print_certificates_title').hide();
         $('#school_print_certificates_help').hide();
         $('#school_print_certificates_title').hide();
         return;
      }
      var contestList = '<ul>';
      var nbContests = 0;
      for (var contestID in contests) {
         var contest = contests[contestID];
         if (contest.printCertificates == 1) {
            nbContests += 1;
            contestList += '<li><button type="button" onclick="printSchoolCertificates(\''+contest.ID+'\')" class="btn btn-default">'+contest.name+'</button></li>';
         }
         if (contestID == "884044050337033997") { // hard-coded special case for Algorea 2018
            nbContests += 1;
            contestList += '<li><button type="button" onclick="printSchoolCertificates(\'algorea\')" class="btn btn-default">Concours Algoréa</button></li>';
         }
      }
      contestList += "</ul>";
      if (nbContests == 0) {
         $('#buttonPrintCertificates_group').hide();
         $('#buttonPrintCertificates_team').hide();
         $('#group_print_certificates_help').hide();
         $('#group_print_certificates_title').hide();
         $('#team_print_certificates_help').hide();
         $('#team_print_certificates_title').hide();
         $('#school_print_certificates_help').hide();
         $('#school_print_certificates_title').hide();
      } else {
         $('#school_print_certificates_contests').html(contestList);
      }
   });
}

function loadQuestions() {
   return loadData("question", function(items) {
      questions = items;
      questionsKeysToIds = {};
      for (var questionID in questions) {
         var question = questions[questionID];
         questionsKeysToIds[question.key] = questionID;
      }
   });
}

function loadGroups() {
   return loadData("group", function(items) {
      groups = items;
   });
}

function loadSchoolsYears(school_users) {
//   return; // disabled; keeps displaying in some cases (ex : Mauras du liban)
   if (isAdmin()) {
      return;
   }
   return loadData("school_year", function(items) {
      var school_user_by_school = {};
      var school_user;
      for (var school_userID in school_users) {
         school_user = school_users[school_userID];
         school_user_by_school[school_user.schoolID] = school_user;
      }
      for (var itemID in items) {
         var school_year = items[itemID];
         school_user = school_user_by_school[school_year.schoolID];
         school_year.awarded = parseInt(school_year.awarded);
         school_year.year = parseInt(school_year.year);
         if (school_user.awardsReceivedYear) {
            school_user.awardsReceivedYear = parseInt(school_user.awardsReceivedYear);
         }
         if (school_user && ((!school_user.awardsReceivedYear) || (school_user.awardsReceivedYear < school_year.year)) && (school_year.awarded > 0) && (school_year.year > 2013)) {
            var buttons =  [ { text: "Oui", click: function() {
               school_user.awardsReceivedYear = Math.max(school_user.awardsReceivedYear, school_year.year);
               $.post("jqGridData.php", {tableName:"school_user", oper: "update", record:school_user}, function(data) {});
               $(this).dialog( "close" );
            }},
            { text: "Non", click: function() {
               if (!school_user.awardsReceivedYear) {
                  school_user.awardsReceivedYear = 0;
               $.post("jqGridData.php", {tableName:"school_user", oper: "update", record:school_user}, function(data) {});
               }
               $(this).dialog( "close" );
            }}];
            //jqAlert(t("alert_awards_received"), null, buttons); // TODO : paramètre pour le nb de lots, établissement
         }
      }
   });
}

function loadSchoolsUsers() {
   return loadData("school_user", function(items) {
      if (isAdmin()) {
         return;
      }
      var toSave = {};
      var toDelete = [];
      for (var itemID in items) {
         var school_user = items[itemID];
         if (school_user.confirmed != 1) {
            var schoolName = schools[school_user.schoolID].name;
            if (confirm(t("still_school_user") + schoolName)) {
               school_user.confirmed = 1;
               toSave[itemID] = school_user;
               $.post("jqGridData.php", {tableName:"school_user", oper: "update", record:school_user},
                  function(data) {}
               );
            } else {
               $.post("jqGridData.php", {tableName:"school_user", oper: "del", record:school_user},
                  function(data) {}
               );
            }
         }
      }
      loadSchoolsYears(items);
   });
}

function loadSchools() {
   return loadData("school", function(items) {
      schools = items;
   });
}

function objectHasProperties(object) {
   for (var iProperty in object) {
      return true;
   }
   return false;
}

function groupFormShowContestDetails(contestID) {
   var categories = {};
   var languages = {};
   for (var subContestID in contests) {
      var subContest = contests[subContestID];
      if (subContest.parentContestID == contestID) {
         if (subContest.categoryColor != "") {
            if (!categories[subContest.categoryColor]) {
               categories[subContest.categoryColor] = "<option value='" + subContest.categoryColor + "'>" + subContest.categoryColor + "</option>";
            }
         }
         if (subContest.language != "") {
            if (!languages[subContest.language]) {
               languages[subContest.language] = "<option value='" + subContest.language + "'>" + subContest.language + "</option>";
            }
         }
      }
   }
   var strCategories = "";
   var allCategories = ["blanche", "jaune", "orange", "verte", "bleue", "cm1cm2", "6e5e", "4e3e", "2depro", "2de", "1reTalepro", "1reTale", "all"];  // TODO: do not hardcode values
   for (var iCategory = 0; iCategory < allCategories.length; iCategory++) {
      var category = allCategories[iCategory];
      if (categories[category] != undefined) {
         strCategories += categories[category];
      }
   }
   if (strCategories != "") {
      strCategories = "<br/><p>Catégorie minumum : <select id='group_minCategory'><option value=''>Aucune</option>" + strCategories + "</select></p>"
                        + "<p>Catégorie maximum : <select id='group_maxCategory'><option value=''>Aucune</option>" + strCategories + "</select></p>";
   }
   var strLanguages = "";
   var allLanguages = ["blockly", "scratch", "python"]; // TODO: do not hardcode values
   for (var iLanguage = 0; iLanguage < allLanguages.length; iLanguage++) {
      var language = allLanguages[iLanguage];
      if (languages[language] != undefined) {
         strLanguages += languages[language];
      }
   }
   if (strLanguages != "") {
      strLanguages = "<br/><p>Langage de programmation : <select id='group_language'><option value=''>Libre</option>" + strLanguages + "</select></p>";
   }
   $("#contestParams").html(strCategories + strLanguages);
}

function groupFormHandleContestChange() {
   $("#group_contestCategoryID").change(function() {
      updateContestOptions();
   });
   $("#group_contestID").change(function() {
      var contestID = $("#group_contestID").val();
      groupFormShowContestDetails(contestID);
   });
}

function newGroup() {
   if (isAdmin()) {
      jqAlert(t('admin_cannot_create_group'));
      return;
   }
   if (!objectHasProperties(schools)) {
      jqAlert(t("school_not_provided"));
      return;
   }
   newForm("group", t("create_group"), t("create_group_comment"), null, t("create_group_footer"));
   updateContestOptions();
   groupFormHandleContestChange();
}

function newContestQuestion() {
   newItem("contest_question", {contestID: selectedContestID});
}

function newItem(modelName, params, callback) {
   if (params === undefined) {
      params = {};
   }
   var tableName = models[modelName].tableName;
   params.oper = "insert";
   params.tableName = tableName;
   if (!callback) {
      callback = function(data) {
         $('#grid_' + modelName).trigger('reloadGrid');   
      };
   }
   $.post("jqGridData.php", params, callback).fail(jqGridDataFail);
}


function checkContestSelectedAndConfirm() {
   if (selectedContestID === "0" || selectedContestID === undefined) {
      jqAlert(t("select_contest"));
      return false;
   }
   if (!confirm(t("confirm_contest_operation"))) {
      return false;
   }
   return true;
}

function checkGroupSelectedAndConfirm() {
   if (selectedGroupID === "0" || selectedGroupID === undefined) {
      jqAlert(t("select_group"));
      return false;
   }
   if (!confirm(t("confirm_group_operation"))) {
      return false;
   }
   return true;
}

function computeCoordsSchools() {
   $.get("actions.php", {action: "getSchoolList"},
      function(data) {
         if (!data.success)
            return;
         computeCoords(0, data.schools);
      }, "json"
   );
}

function computeCoords(cur, schools) {
   if (cur == schools.length) {
      $("#computeCoordsLog").html("");
      return;
   }
    $.get("actions.php", {action: "computeCoordinates", schoolID: schools[cur].ID},
      function(data) {
         if (!data.success)
            alert("ERROR for computeCoords");
      }, "json"
   );

   $("#computeCoordsLog").html(t("compute_school_coords") + " " +(cur+1) + " / " + schools.length + " : " + schools[cur].name);
   setTimeout(function(){ computeCoords(cur+1, schools); }, 1000);
}


function computeTotalScoresContest() {
   if (!checkContestSelectedAndConfirm()) {
      return;
   }
   var button = $("#buttonComputeScoresContest");
   button.attr("disabled", true);
   computeScores(selectedContestID, null, 0);
}

function displayScoresGroup() {
   if (selectedGroupID === "0" || selectedGroupID === undefined) {
      jqAlert(t("select_group"));
   } else {
      window.open("detailsGroup.php?groupID=" + selectedGroupID,'_new');
   }
}

function openEditGroupContestants() {
   if (selectedGroupID === "0" || selectedGroupID === undefined) {
      jqAlert(t("select_group"));
   } else {
      window.open("editGroupContestants.php?groupID=" + selectedGroupID,'_new');
   }
}

function gradeGroup() {
   if (!checkGroupSelectedAndConfirm()) {
      return;
   }
   var group = groups[selectedGroupID];
   var contest = contests[group.contestID];
   if (contest.open != 'Open') {
      jqAlert(t("cannot_grade_closed_contest"));
      return;
   }
   if (group.participationType == 'Official') {
      jqAlert(t("cannot_grade_official_group"));
      return;
   }
   $("#buttonGradeSelected_group").attr("disabled", true);
   loopGradeContest(undefined, selectedGroupID);
}


function gradeContest() {
   if (!checkContestSelectedAndConfirm()) {
      return;
   }
   var button = $("#buttonGradeContest");
   button.attr("disabled", true);
   loopGradeContest(selectedContestID, undefined);
}

function rankContest() {
   if (!checkContestSelectedAndConfirm()) {
      return;
   }
   var button = $("#buttonRankContest");
   button.attr("disabled", true);
   $.post("rankContest.php", {contestID: selectedContestID},
      function(data) {
         if (!data.success) {
            jqAlert(data.message);
            return;
         }
         var button = $("#buttonRankContest");
         jqAlert(t("ranks_computed"));
         button.attr("disabled", false);
      }, "json"
   );
}

function generateAlgoreaCodes() {
   if (!checkContestSelectedAndConfirm()) {
      return;
   }
   var button = $("#buttonGenerateAlgoreaCodes");
   button.attr("disabled", true);
   $.post("generateAlgoreaCodes.php", {contestID: selectedContestID},
      function(data) {
         if (!data.success) {
            jqAlert(data.message);
            return;
         }
         var button = $("#buttonGenerateAlgoreaCodes");
         jqAlert(t("codes_computed"));
         button.attr("disabled", false);
      }, "json"
   );
}

function Generator(contestID, contest) {
   this.contestID = contestID;
   this.contest = contest;
   this.tasks = [];
}

Generator.prototype.success = function () {
   jqAlert(t("contest_generated"));
   $("#generateContest").attr("disabled", false);
   refreshGrid("contest");
};

Generator.prototype.failure = function (message) {
   jqAlert(message);
   $("#generateContest").attr("disabled", false);
};

Generator.prototype.start = function () {
   var self = this;
   $("#generateContest").attr("disabled", true);
   loadContests().done(function() {
      // Retrieve the tasks' list
      var params = {
         action: "prepare",
         contestID: self.contestID,
         contestFolder: self.contest.folder,
         newFolder: "true"
      };
      $.post("generateContest.php", params, function(data) {
         if (!data.success) {
            return self.failure(t("contest_generation_prepare_failed"));
         }
         self.contestFolder = data.contestFolder;
         self.questionsUrl = data.questionsUrl;
         self.questionsKey = data.questionsKey;
         // Start generating the tasks.
         self.currentTaskIndex = 0;
         self.doTask();
      }, 'json').fail(function() {
         self.failure(t("contest_generation_failed"));
      });;
   });
}

Generator.prototype.doTask = function () {
   var self = this;
   var currentTaskIndex = self.currentTaskIndex;
   if (currentTaskIndex >= self.questionsUrl.length) {
      return self.upload();
   }
   var taskUrl = self.questionsUrl[currentTaskIndex];
   var taskKey = self.questionsKey[currentTaskIndex];
   generating = true;
   checkTaskPath(taskUrl, true);
   $('#preview_question').attr("src", config.tasksPathInterface + taskUrl);
   $('#preview_question').on('load', onQuestionLoaded);
   function onQuestionLoaded () {
      $('#preview_question').off('load', onQuestionLoaded);
      TaskProxyManager.getTaskProxy('preview_question', function (task) {
         task.getResources(function (bebras) {
            self.tasks.push({'bebras': bebras, 'url': taskUrl, 'key': taskKey});
            generating = false;
            self.currentTaskIndex += 1;
            self.doTask();
         });
      }, true);
   }
};

Generator.prototype.upload = function() {
   var self = this;
   // XXX: status is needed only because of https://github.com/aws/aws-sdk-php/
   var params = {
      action: "generate",
      contestID: this.contestID,
      contestFolder: this.contestFolder,
      fullFeedback: this.contest.fullFeedback,
      showTotalScore: this.contest.showTotalScore,
      status: this.contest.status,
      tasks: JSON.stringify(this.tasks)
   };
   $.post("generateContest.php", params, function(data) {
      if (!data.success) {
         return self.failure(t("contest_generation_failed"));
      }
      self.setFolder();
   }, 'json').fail(function() {
      self.failure(t("contest_generation_failed"));
   });
};

Generator.prototype.setFolder = function() {
   var self = this;
   var params = {
      action: "setFolder",
      contestID: this.contestID,
      contestFolder: this.contestFolder
   };
   $.post("generateContest.php", params, function(data) {
      if (!data.success) {
         return self.failure(t("contest_generation_failed_set_folder"));
      }
      self.success();
   }, 'json').fail(function() {
      return self.failure(t("contest_generation_failed_set_folder"));
   });
};

function genContest () {
   if (selectedContestID === "0" || selectedContestID === undefined) {
      jqAlert(t("select_contest"));
      return;
   }
   var generator = new Generator(selectedContestID, contests[selectedContestID]);
   generator.start();
}

// Unused, keep it 'til 100% sure
function genQuestion() {
   var questionID = selectedQuestionID;
   if (questionID === "0" || questionID === undefined) {
      jqAlert(t("select_question"));
      return;
   }
   var button = $("#generateQuestion");
   button.attr("disabled", true);
   tasks = []; // Reinit
   var url = config.tasksPathInterface + questions[questionID].path;
   $("#preview_question").attr("src", url);
   // Retrieve bebras
   generating = true;
   $('#preview_question').load(function() {
      $('#preview_question').unbind('load');
      var bebras = $('#preview_question')[0].contentWindow.task.getResources();
      tasks.push({
         'bebras': bebras,
         'key': questions[questionID].key,
         'url': questions[questionID].path
      });
      generating = false;
      // Compilation
      tasks = JSON.stringify(tasks);
      var params = {
         action: "generate",
         contestID: questions[questionID].key,
         contestFolder: questions[questionID].key,
         tasks: tasks
      }
      $.post("generateContest.php", params, function(data) {
         if (data.success) {
            jqAlert(t("question_generated"));
         } else {
            jqAlert(t("question_generation_failed"));
         }
         button.attr("disabled", false);
      }, 'json').fail(function() {
         jqAlert(t("question_generation_failed"));
         button.attr("disabled", false);
      });
   });
}

function showForm(modelName) {
   $("#buttonValidate_" + modelName).removeAttr("disabled");
   $("#buttonCancel_" + modelName).removeAttr("disabled");
   $("#edit_form").show();
   $("#main_screen").hide();
   $("#headerWarning").hide();
   $("#divSchoolSearch").hide();
}

function getSelectHours(fieldId) {
   var html = "<select id='" + fieldId + "_hours' type='text' style='width:50px'>";
   for (var h = 0; h < 24; h++) {
      html += "<option value='" + h + "'>" + h + "</option>";
   }
   html += "</select>";
   return html;
}

function getSelectMinutes(fieldId) {
   var html = "<select id='" + fieldId + "_minutes' type='text' style='width:50px'>";
   for (var m = 0; m < 60; m += 5) {
      html += "<option value='" + m + "'>" + m + "</option>";
   }
   html += "</select>";
   return html;
}

function getContestFromID(ID) {
   for (var contestID in contests) {
      if (contestID == ID) {
         return contests[contestID];
      }
   }
   return null;
}

function contestHidden(contest, group) {
   return ((contest.visibility == 'Hidden') ||
           ((contest.parentContestID != "0") && (contest.parentContestID != null) && ((group == null) || (group.contestID != contest.ID))));
}

function updateContestOptions(group) {
   var categoryID = parseInt($("#group_contestCategoryID").val());
   var listContests = [];
   for (var iContest in contests) {
      var contest = contests[iContest];
      if (contestHidden(contest, group)) {
         continue;
      }
      if (contest.categoryID != categoryID) {
         continue;
      }
      listContests.push(contest);
   }
   listContests.sort(function(c1, c2) {
      return c1.name.localeCompare(c2.name);
   });
   var options = "";
   for (iContest in listContests) {
      var contest = listContests[iContest];
      options += "<option value='" + contest.ID + "'>" + contest.name + "</option>";
   }
   $("#group_contestID").html(options);
}

function newForm(modelName, title, message, item, footer) {
   var js = "";
   var html = "<h2>" + title + "</h2>" + message +
      "<input type='hidden' id='" + modelName + "_ID' /><table>";
   for (var fieldName in models[modelName].fields) {
      var field = models[modelName].fields[fieldName];
      if (field.edittype === undefined) {
         continue;
      }
      html += "<tr><td style='width:230px;padding:10px 0;'><b>";
      if (field.longLabel !== undefined) {
         html += field.longLabel;
      } else {
         html += field.label;
      }
      if (field.required) {
         html += "<sup title='" + t("mandatory_field") + "'>*</sup>";
      }
      html += "&nbsp;:</b></td><td style='width:350px'>";
      var fieldId = modelName + "_" + fieldName;
      var requiredString = field.required ? 'required' : '';
      if (field.edittype === "text") {
         html += "<input type='text' style='width:350px' id='" + fieldId + "' "+requiredString+"/>";
      } else if (field.edittype === "email") {
         html += "<input type='email' style='width:350px' id='" + fieldId + "' "+requiredString+"/>";   
      } else if (field.edittype === "password") {
         html += "<input type='password'  style='width:350px' id='" + fieldId + "' "+requiredString+"/>";
      } else if (field.edittype === "select") {
         if (fieldName == "contestID") {
            if(contestCategories.length > 1) {
               html += t("content_type") + " <select id='group_contestCategoryID'>";
               for (var categoryID = 0; categoryID < contestCategories.length; categoryID++) {
                  html += "<option value='" + categoryID + "'>"  + contestCategories[categoryID] + "</option>";
               }
               html += "</select><br/><br/>";
            } else if(contestCategories.length == 1 && contestCategories[0] != "-----") {
               html += t("content_type") + " : " + contestCategories[0] + "<br/><br/>";
               html += "<input id='group_contestCategoryID' type='text' value='0' style='display: none;'>";
            } else {
               html += "<input id='group_contestCategoryID' type='text' value='0' style='display: none;'>";
            }
         }
         html += "<select id='" + fieldId + "'>";
         html += "<option value='0'>" + t("select") + "</option>";
         var optionValue, optionName;
         if (typeof field.editoptions.value === "string") {
            if (modelName == "group" && fieldName == "contestID" && !field.editoptions.value) {
               jqAlert(t("contest_needed_for_group"));
               return;
            }
            var optionsList = field.editoptions.value.split(";");
            for (var iOption = 0; iOption < optionsList.length; iOption++)  {
               var optionParts = optionsList[iOption].split(":");
               if (fieldName == "contestID") { 
                  continue;
               }
               optionValue = optionParts[0];
               optionName = optionParts[1];
               html += "<option value='" + optionValue + "'>" + optionName + "</option>";
            }
         } else {
            for (optionValue in field.editoptions.value) {
               optionName = field.editoptions.value[optionValue];
               html += "<option value='" + optionValue + "'>" + optionName + "</option>";
            }
         }
         html += "</select>";
         if (modelName == "group" && fieldName == "contestID") {
            html += "<div id='contestParams'></div>";
         }
      } else if (field.edittype === "ac-email") {
         html += "<input type='text' id='" + fieldId + "' "+requiredString+"/>@";
         html += "<select id='" + fieldId + "_domain'>";
         html += "<option value='undefined'>" + t("region") + "</option>";
         for (var iDomain = 0; iDomain < domains.length; iDomain++) {
            var allowedDomain = domains[iDomain];
            html += "<option value='" + allowedDomain + "'>" + allowedDomain + "</option>";
         }
         html += "</select>";
         html += "<br/><input type='checkbox' id='" + fieldId + "_none'>" + t("user_no_official_email");
      } else if (field.edittype === "datetime") {
         html += "<input id='" + fieldId + "_date' type='text' "+requiredString+"/> ";
         html += " " + t("at_time") + " ";
         html += getSelectHours(fieldId) + ":" + getSelectMinutes(fieldId);
         html += "<br/>" + t("expectedStartTime_timeZone") + "<b>" + jstz.determine().name() + "</b>";
         js += "$('#" + fieldId + "_date').datepicker({ dateFormat: 'dd/mm/yy' });";
      }
      html += "</td><td style='width:500px;text-align:justify'>";
      if (field.comment !== undefined) {
         html += "<i>" + field.comment + "</i>";
      }
      html += "<br/></td></tr>";
   }
   html += "</table>";
   if (modelName == 'user_create') {
      html += '<label><input type="checkbox" id="users_okMail">';
      html += t('user_accept_email')+'</label>';
   }
   if(footer) {
      html += footer;
   }
   html += "<input id='buttonValidate_" + modelName + "' type='button' value='OK' onclick='validateForm(\"" + modelName + "\")' class='btn btn-primary'/> ";
   html += "<input id='buttonCancel_" + modelName + "' type='button' value='" + t("cancel") + "' onclick='endEditForm(\"" + modelName + "\", 0 , {})' class='btn btn-default'/>";
   html += "<div id='edit_form_error' style='color:red'></div>";
   $("#edit_form").html(html);
   eval(js);
   showForm(modelName);
}

function loadOneRecord(tableName, recordID, callback) {
   return $.post("jqGridData.php", {oper: "selectOne", tableName: tableName, recordID: recordID},
      function(data) {
         if (data.success) {
            callback(data.items[recordID]);
         } else {
            jqAlert(t("error_while_loading"));
         }
      }, "json"
   );
}

function editGroupDetails(group) {
   groupFormHandleContestChange();
   groupFormShowContestDetails(group.contestID);
   $("#group_minCategory").val(group.minCategory);
   $("#group_maxCategory").val(group.maxCategory);
   $("#group_language").val(group.language);
}

function editGroup() {
   var groupID = jQuery("#grid_group").jqGrid('getGridParam','selrow');
   if (groupID === null) {
      jqAlert(t("warning_no_group_selected"));
      return;
   }
   if (isAdmin()) {
      loadOneRecord("group", groupID, function(item) {
         editForm("group", t("edit_group"), item);
         editGroupDetails(item);
      }).fail(jqGridDataFail);

   } else {
      if (groups[groupID].userID != loggedUser.ID) {
         jqAlert(t("only_group_creator_can_edit"));
         return;
      } else {
         editForm("group", t("edit_group"), groups[groupID]);
         editGroupDetails(groups[groupID])
      }
   }
}

function preparePrintTeamCertificates() {
   var teamID = jQuery("#grid_team_view").jqGrid('getGridParam','selrow');
   if (teamID === null) {
      jqAlert(t("warning_no_team_selected"));
      return;
   }
   loadOneRecord("team_view", teamID, function(team) {
      teamToPrint = team;
      $("#buttonDoPrintCertificates_team").html(team.contestants);
      $("#buttonDoPrintCertificates_team").show();      
   })  
}

function printTeamCertificates() {
   if (teamToPrint.participationType != 'Official') {
      jqAlert(t("team_print_certificates_impossible"));
   } else {
      window.open("printCertificatesPdf.php?schoolID="+teamToPrint.schoolID+"&contestID="+teamToPrint.contestID+"&teamID=" + teamToPrint.ID, "printTeam" + teamToPrint.ID, 'width=700,height=600,menubar=yes,status=yes,toolbar=yes,scrollbars=yes,resizable=yes');
   }
}

function printGroupCertificates() {
   var groupID = jQuery("#grid_group").jqGrid('getGridParam','selrow');
   if (groupID === null) {
      jqAlert(t("warning_no_group_selected"));
      return;
   }
   var group = groups[groupID];
   if (group.participationType != 'Official' || group.contestPrintCertificates != 1) {
      jqAlert(t("group_print_certificates_impossible"));
      return;
   }
   window.open("printCertificatesPdf.php?schoolID="+group.schoolID+"&contestID="+group.contestID+"&groupID=" + groupID, "printGroup" + groupID, 'width=700,height=600,menubar=yes,status=yes,toolbar=yes,scrollbars=yes,resizable=yes');
}

function printGroupAwards() {
   var groupID = jQuery("#grid_group").jqGrid('getGridParam','selrow');
   if (groupID === null) {
      jqAlert(t("warning_no_group_selected"));
      return;
   }
   var group = groups[groupID];
   if (group.participationType != 'Official' || group.contestPrintCodes != 1) {
      jqAlert(t("group_print_awards_impossible"));
      return;
   }
   window.open("awardsPrint.php?schoolID="+group.schoolID+"&contestID="+group.contestID+"&groupID=" + groupID, "printGroup" + groupID, 'width=700,height=600,menubar=yes,status=yes,toolbar=yes,scrollbars=yes,resizable=yes');
}

function printSchoolCertificates(contestID) {
   var schoolID = jQuery("#grid_school").jqGrid('getGridParam','selrow');
   if (schoolID === null) {
      jqAlert(t("warning_no_school_selected"));
      return false;
   }
   window.open("printCertificatesPdf.php?schoolID="+schoolID+"&contestID="+contestID, "printSchool" + schoolID, 'width=700,height=600,menubar=yes,status=yes,toolbar=yes,scrollbars=yes,resizable=yes');
   return false;
}

function printSchoolAwards() {
   var schoolID = jQuery("#grid_school").jqGrid('getGridParam','selrow');
   if (schoolID === null) {
      jqAlert(t("warning_no_school_selected"));
      return false;
   }
   window.open("awardsPrint.php?schoolID="+schoolID, "printSchool" + schoolID, 'width=700,height=600,menubar=yes,status=yes,toolbar=yes,scrollbars=yes,resizable=yes');
   return false;
}

function printGroup() {
   var groupID = jQuery("#grid_group").jqGrid('getGridParam','selrow');
   if (groupID === null) {
      jqAlert(t("warning_no_group_selected"));
      return;
   }
   window.open("notice.php?groupID=" + groupID, "printGroup" + groupID, 'width=700,height=600');
}

function printGroupAll() {
   window.open("notice.php", "printGroupAll", 'width=700,height=600');
}

function getDateFromSQL(dateSQL) {
   var dateJS = dateSQL.replace(/(\d+)-(\d+)-(\d+)/, "$3/$2/$1");
   return dateJS;
}

function editForm(modelName, title, item) {
   newForm(modelName, title, "", item);
   updateContestOptions(item);
   $("#" + modelName + "_ID").val(item.ID);
   var fields = models[modelName].fields;
   for (var fieldName in fields) {
      var field = fields[fieldName];
      if (modelName === "group" && fieldName === "contestID") {
         var contestID = item[fieldName];
         $("#group_contestCategoryID").val(contests[contestID].categoryID);
         updateContestOptions(item);
      }
      if (field.edittype !== "datetime") {
         $("#" + modelName + "_" + fieldName).val(item[fieldName]);
      } else {
         if ((item[fieldName]) && (item[fieldName].length > 0)) {
            var localDate = utcDateFormatter(item[fieldName]);
            var parts = localDate.split(' ');

            $("#" + modelName + "_" + fieldName + "_date").val(parts[0]);
            var timeParts = parts[1].split(':');
            $("#" + modelName + "_" + fieldName + "_hours").val(parseInt(timeParts[0]));
            $("#" + modelName + "_" + fieldName + "_minutes").val(parseInt(timeParts[1]));
         }
      }
   }
   showForm(modelName);
}

function checkEmailFormat(email) {
   var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
   if(!re.test(email)) { return false; }
   // Check all characters are ASCII
   // as much as special characters can be allowed in email addresses, it's generally a user mistake
   var asciiRe = /^[\x00-\x7F]*$/;
   return asciiRe.test(email);
}

function checkUser(user, isCreate) {
   /*
   var firstAt = user.officialEmail.indexOf("@");
   if (user.officialEmail.indexOf("@", firstAt + 1) == 0) {
      user.officialEmail = "";
   }
   */
   if (user.officialEmail == "none") {
      $("#edit_form_error").html(t("teacher_required"));
      return false;
   }
   if ((user.officialEmail == "") && (user.alternativeEmail == "")) {
      $("#edit_form_error").html(t("missing_email"));
      return false;
   }
   
   var minPasswordLength = 6;
   if (user.password != user.password2) {
      $("#edit_form_error").html(t("passwords_different"));
      return false;
   }
   if (isCreate || (user.password.length > 0)) {
      if (user.password.length < minPasswordLength) {
         $("#edit_form_error").html(t("password_min_length_1") + minPasswordLength + t("password_min_length_2"));
         return false;
      }
   }
   if ((user.officialEmail === "") && (user.alternativeEmail === "")) {
      $("#edit_form_error").html(t("one_email_required"));
      return false;
   }
   if (!isCreate) {
/*      if ((loggedUser.officialEmailValidated == "1") && (user.officialEmail != loggedUser.officialEmail)) {
         $("#edit_form_error").html(t("officialEmail_readonly"));
         return false;
      }*/
   } else {
      if (!$('#users_okMail').prop('checked')) {
         $("#edit_form_error").html(t("okMail_required"));
         return false;  
      }
   }
   return true;
}

function isPositiveInteger(value) {
   return ((isInteger(value)) && (value > 0));
}

function isInteger(value) {
   var intRegex = /^\d+$/;
   return (intRegex.test(value));
}

function jqAlert(message, closeFunction, buttons) {
    var dialog = $('<div></div>')
      .html(message)
      .dialog({
         modal: true,
         autoOpen: false,
         title: t("warning"),
         close: closeFunction,
         buttons: buttons
      });
   dialog.dialog('open');
}

function getSQLFromJSDate(d) {
   return d.getFullYear() +
      "-" + ("0" + (d.getMonth() + 1)).slice(-2) +
      "-" + ("0" + d.getDate()).slice(-2) +
      " " + ("0" + d.getHours()).slice(-2) +
      ":" + ("0" + d.getMinutes()).slice(-2) +
      ":" + ("0" + d.getSeconds()).slice(-2);
}

function getSQLFromDate(date) {
   try {
      return date.replace(/(\d+)\/(\d+)\/(\d+)/, "$3-$2-$1");
   } catch(e) {
      return "";
   }
}


function validateForm(modelName) {
   var item = {};
   item.ID = $("#" + modelName + "_ID").val();
   var date;
   var fields = models[modelName].fields;
   for (var fieldName in fields) {
      var field = fields[fieldName];
      item[fieldName] = $("#" + modelName + "_" + fieldName).val();
      if(item[fieldName] && item[fieldName].trim() !== item[fieldName]) {
         item[fieldName] = item[fieldName].trim();
         $("#" + modelName + "_" + fieldName).val(item[fieldName]);
      }
      if (field.edittype === "datetime") {
         date = $("#" + modelName + "_" + fieldName + "_date").val();
         var hours = $("#" + modelName + "_" + fieldName + "_hours").val();
         if (!hours) {
            hours = "00";
         }
         var minutes = $("#" + modelName + "_" + fieldName + "_minutes").val();
         if (!minutes) {
            minutes = "00";
         }
         if ((parseInt(hours) > 23) || (parseInt(minutes) > 59)) {
            jqAlert(t("start_time_invalid"));
            return;
         }
         var fullDate = date + " " + hours + ":" + minutes;
         item[fieldName] = localDateToUtc(fullDate);
      } else if (field.edittype === "ac-email") {
         if ($("#" + modelName + "_" + fieldName + "_none").is(":checked")) {
            item[fieldName] = "";
         } else if ($("#" + modelName + "_" + fieldName + "_domain").val() === "undefined") {
            if (item[fieldName] !== "") {
               jqAlert(t("official_email_invalid"));
               return;
            }
            item[fieldName] = "none";
         }
         else {
            var domain = $("#" + modelName + "_" + fieldName + "_domain").val();
            item[fieldName] += "@" +  domain;
            if (!checkEmailFormat(item[fieldName])) {
               jqAlert(t("official_email_invalid"));
               return;
            }
         }
      } else if (field.edittype == 'email') {
         if (item[fieldName] && !checkEmailFormat(item[fieldName])) {
            jqAlert(t("invalid_alternativeEmail"));
            return;
         }
      }
      if (field.required) {
         if (item[fieldName] === "" || item[fieldName] === "0") {
            jqAlert(t("field_missing_1") + field.label + t("field_missing_2"));
            return;
         }
      }
      if (field.subtype === "int") {
         if (!isInteger(item[fieldName])) {
            jqAlert(t("field_not_integer_1") + field.label + t("field_not_integer_2"));
            return;
         }
      }
      if (field.subtype === "positiveint") {
         if (!isPositiveInteger(item[fieldName])) {
            jqAlert(t("field_not_positive_1") + field.label + t("field_not_positive_2"));
            return;
         }
      }
   }
   if (modelName === "user_create") {
      if (!checkUser(item, true)) {
         return;
      }
   } else if (modelName === "user_edit") {
      if (!checkUser(item, false)) {
         return;
      }
   } else if (modelName === "group") {
      // TODO: prevent changing group level if already started
      if (!item.schoolID) {
         return;
      }

      var contest = contests[item.contestID];
      var contestStartDate = null;
      if ((contest.startDate != null) && (contest.startDate != "0000-00-00 00:00:00")) {
         contestStartDate = toDate(contest.startDate, "-", true, true);
      }
      var contestEndDate = null;
      if ((contest.endDate != null) && (contest.endDate != "0000-00-00 00:00:00")) {
         contestEndDate = toDate(contest.endDate, "-", true, true);
      }
      var strDate = $("#group_expectedStartTime_date").val() + " " + $("#group_expectedStartTime_hours").val() + ":" + $("#group_expectedStartTime_minutes").val();
      date = toDate(strDate, "/", false, false);

      if ((item.participationType == "Official") && (parseInt(contest.closedToOfficialGroups) == 1)) {
         jqAlert(t("official_contests_restricted"));
         return;
      }
      if ((contestStartDate && date < contestStartDate) ||
          (contestEndDate && date > contestEndDate)) {
         jqAlert(t("warning_contest_outside_official_date") + "(" + utcDateFormatter(contest.startDate) + " - " + utcDateFormatter(contest.endDate) + ")");
         return;
      }

      // Make sure minCategory and maxCategory are in the right order
      if(item.minCategory && item.maxCategory) {
         var categoriesIdx = {
            'blanche': 1,
            'jaune': 2,
            'orange': 3,
            'verte': 4
         };
         var minCategory = categoriesIdx[item.minCategory];
         var maxCategory = categoriesIdx[item.maxCategory];
         if(minCategory && maxCategory && maxCategory < minCategory) {
            var tmp = item.minCategory;
            item.minCategory = item.maxCategory;
            item.maxCategory = tmp;
         }
      }
   }
   $("#edit_form_error").html("");
   $("#buttonValidate_" + modelName).attr("disabled", true);
   $("#buttonCancel_" + modelName).attr("disabled", true);
   var oper = "insert";
   if (item.ID) {
      oper = "update";
   }
   $.post("jqGridData.php", {tableName:models[modelName].tableName, oper: oper, record:item},
      function(data) {
         if (!data.success) {
            if (data.message !== undefined) {
               jqAlert(data.message);
            } else {
               jqAlert("Erreur");
            }
            $("#buttonValidate_" + modelName).attr("disabled", false);
            $("#buttonCancel_" + modelName).attr("disabled", false);
            return;
         }
         endEditForm(modelName, data.recordID, item);
         if (modelName === "user_create") {
            if (item.officialEmail) {
               jqAlert(t("you_will_get_email") + " " + window.config.infoEmail);
            } else {
               jqAlert(t("no_official_email_1") + getMailToManualValidation(t("contact_us")) + window.config.infoEmail + " " + t("no_official_email_2"));
            }
         }
      }, "json"
   ).fail(jqGridDataFail);
}

function endEditForm(modelName, recordID, item) {
   if (modelName === "school") {
      endEditSchool(recordID, item);
   } else if (modelName === "group") {
      endEditGroup(recordID);
      if (!isAdmin()) {
         if (groups[recordID]) {
            item.userID = groups[recordID].userID; // TODO: do this in a generic way!
         } else {
            item.userID = loggedUser.ID;
         }
         item.ID = recordID;
         groups[recordID] = item;
      }
   } else if (modelName === "user_edit") {
      endEditUser(recordID, item);
   }
   $("#edit_form").hide();
   $("#main_screen").show();
   $("#headerWarning").show();
}

function newSchool() {
//   $("#selectSchool").val(0);
   newForm("school", t("school_details"), "");
}

function editUser() {
   editForm("user_edit", t("edit_user"), loggedUser);
   var officialEmail = loggedUser.officialEmail;
   if (!officialEmail) {
      return;
   }
   var parts = officialEmail.split("@");
   var leftPart = parts[0];

   $("#user_edit_officialEmail").val(leftPart);
   $("#user_edit_officialEmail_domain").val(parts[1]);
}

function searchSchool() {
   $("#main_screen").hide();
   $("#headerWarning").hide();
   $("#divSchoolSearch").show();
   loadSchoolSearch();
}

function endSearchSchool() {
   $("#main_screen").show();
   $("#headerWarning").show();
   $("#divSchoolSearch").hide();
}

function selectSchool() {
   $("#divSchoolSearch").show();
   var schoolID = jQuery("#grid_school_search").jqGrid('getGridParam','selrow');
   if (!schoolID) {
      jqAlert(t("warning_no_school_selected"));
      return false;
   }
   var params = {schoolID: schoolID, userID: loggedUser.ID};
   newItem("school_user", params, function(data) {
      loadSchools().done(function() {
         models.group = getGroupsColModel();
         $('#grid_school').trigger('reloadGrid');   
         $('#grid_colleagues').trigger('reloadGrid');   
      });
   });
   endSearchSchool();
}

function editSchool() {
   newForm("school", t("school_details"), "");
}

function endEditSchool(schoolID) {
   loadSchools().done(function() {
      models.group = getGroupsColModel();
      $('#grid_school').trigger('reloadGrid');
      //selectSchool(schoolID);
   });
}

function endEditGroup(groupID) {
   groupSelectAfterLoad = groupID;
   loadContests().done(function() {
      models.group = getGroupsColModel();
      $('#grid_group').jqGrid('setGridParam', {colModel:jqGridModel("group")}).trigger('reloadGrid');
   });
}

function endEditUser(userID, user) {
   if (loggedUser.ID === userID) {
      loadUser(user);
      var fields = ["gender", "firstName", "lastName", "officialEmail", "alternativeEmail"];
      for (var i = 0; i < fields.length; i++)
         loggedUser[fields[i]] = user[fields[i]];
   }
}

function warningObsolete(data) {
   if(!data.officialEmailObsolete) return '';
   var msg = t('official_email_obsolete_1');
   msg += data.user.officialEmail;
   msg += t('official_email_obsolete_2');
   if(data.obsolete !== true) {
      msg += "<br/>";
      msg += t('official_email_obsolete_replacement');
      msg += data.officialEmailObsolete;
      msg += '.';
   }
   msg += "<br/>";
   return msg;
}

function warningUsers(users) {
   if (!users || !users.length) {
      return '';
   }
   var msg = t("several_users_for_school");
      for (var iUser = 0; iUser < users.length; iUser++) {
         msg += "<li>" + users[iUser].firstName + " " + users[iUser].lastName + "<br/>";
      }
   msg += "<br/>" + t("users_groups_readonly") + "<br/>";
   return msg;
}

function login() {
   disableButton("buttonLogin");
   var email = $("#email").val().trim();
   $("#email").val(email);
   var password = $("#password").val();
   $.post("login.php", {email: email, password: password},
      function(data) {
         $("#login_error").html();
         if (!data.success) {
            if (data.message !== undefined) {
               $("#login_error").html(data.message);
            } else {
               jqAlert(t("invalid_identifiers"));
            }
            return;
         }
         logUser(data.user);
         var warnings = '';
         warnings += warningObsolete(data);
         warnings += warningUsers(data.schoolUsers);
         if(warnings) { jqAlert(warnings); }
      }, "json"
   ).always(function() {
      enableButton("buttonLogin");
   });
}

function recover() {
   disableButton("buttonRecover");
   var email = $("#recoverEmail").val();
   $.post("recover.php", {action:"sendMail", email: email},
      function(data) {
         $("#login_error").html();
         if (!data.success) {
            if (data.message !== undefined) {
               $("#login_error").html(data.message);
            } else {
               jqAlert(t("unknown_email"));
            }
            enableButton("buttonRecover");
            return;
         } else {
            jqAlert(t("recover_email_sent_1") + email + t("recover_email_sent_2"));
         }
      }, "json"
   );
}

function changePassword(email, recoverCode) {
   var password1 = $("#newPassword1").val();
   var password2 = $("#newPassword2").val();

   if (password1 != password2) {
      jqAlert(t("passwords_different"));
      return false;
   }
   var minPasswordLength = 6;
   if (password1.length < minPasswordLength) {
      jqAlert(t("password_min_length_1") + minPasswordLength + t("password_min_length_2"));
      return false;
   }
   disableButton("buttonChangePassword");
   $.post("recover.php", {action:"changePassword", email: email, recoverCode: recoverCode, password: password1},
      function(data) {
         if (!data.success) {
            if (data.message !== undefined) {
               $jqAlert(data.message);
            } else {
               jqAlert(t("unknown_email"));
            }
            enableButton("buttonChangePassword");
            return;
         } else {
            jqAlert(t("password_changed"), function() {window.location.replace(t("index_url"));});
         }
      }, "json"
   );
}

function getMailTo(subject, body, message) {
  return "<a href=\"mailto:" + config.infoEmail + "?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(body) + "\">" + message + "</a>";
}

function getMailToManualValidation(message) {
   var subject = t("email_subject_no_official_email");
   var body = t("email_body_no_official_email");
   return getMailTo(subject, body, message);
}

function newUser() {
   initModels(false);
   var message = "";
   if (window.config.forceOfficialEmailDomain) {
      message = "<p>" + t("warning_official_email_required") + getMailToManualValidation(window.config.infoEmail) + "</p>";
   }

   newForm("user_create", t("user_registration"), message);
}

/* API for certificates generation : in the future, should be a real class... */
Number.prototype.pad = function(size){
      if(typeof(size) !== "number"){size = 2;}
      var s = String(this);
      while (s.length < size) s = "0" + s;
      return s;
    };

function getDateFromSQLFormat(string) {
  var d = new Date(Date.parse(string));
    return d.getDate().pad() + "/" + (d.getMonth() + 1).pad() + "/" + d.getFullYear() + " à " + d.getHours().pad() + "h" + d.getMinutes().pad(); 
}

function printAlgoreaCodes() {
   window.open('awardsPrint.php', "printAlgoreaCodes", 'width=700,height=600');
}

function init() {
   enableButton("buttonLogin"); // strange firefox bug causing disabled login button when loading page
   initErrorHandler();
   i18n.init({
      lng: config.defaultLanguage,
      fallbackLng: [config.defaultLanguage],
      getAsync: true,
      resGetPath: config.i18nResourcePath,
      fallbackNS: 'translation',
      ns: {
         namespaces: config.customStringsName ? [config.customStringsName, 'translation', 'country' + config.countryCode] : ['translation', 'country' + config.countryCode],
         defaultNs: config.customStringsName ? config.customStringsName : 'translation',
      },
      useDataAttrOptions: true
   }, function () {
      var newRegions = {};
      for (var i=0; i < regions.length; i++) {
         newRegions[regions[i].split(':')[1]] = i18n.t(regions[i]);
      }
      window.regions = newRegions;
      $("#login_link_to_home").attr('data-i18n-options',
         '{"contestPresentationURL": "' + config.contestPresentationURL + '"}');
      $("title").i18n();
      $("body").i18n();
      if (config.maintenanceUntil) {
         $("#main_screen").html(t('maintenance_until', {end: config.maintenanceUntil}));
      }
   });
   if (config.maintenanceUntil) {
      return;
   }
   isLogged();
   window.domains = [];
   $.getJSON('regions/' + config.domainCountryCode + '/domains.json', function(data) {
      window.domains = data.domains;
   });
   if (typeof $.jgrid !== 'undefined') {
      $.jgrid.defaults = $.extend($.jgrid.defaults, { autoencode: true });
   }
   if (window.config.useAlgoreaCodes) {
      $('#linkExportAlgoreaCodes').show();
      $('#buttonGenerateAlgoreaCodes').show();
   }
   if (window.config.readOnly) {
      $('body').addClass('read-only-interface');
   }
   $('input[type=button]', this).attr('disabled', false);
}
