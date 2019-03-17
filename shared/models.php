<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

$tablesModels = array (
   "algorea_registration" => array(
      "autoincrementID" => true,
      "hasHistory" => false,
      "fields" => array(
         "code" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "contestantID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "franceioiID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user")))
      ),
   ),
   "award_threshold" => array(
      "autoincrementID" => true,
      "hasHistory" => false,
      "fields" => array(
         "contestantID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("user"))),
         "gradeID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("user"))),
         "awardID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("user"))),
         "nbContestants" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("user"))),
         "minScore" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("user")))
      ),
   ),
   "contestant" => array(
      "autoincrementID" => false,
      "fields" => array(
         "firstName" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "lastName" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "genre" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "email" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "zipCode" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "grade" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "studentId" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "teamID" => array("type" => "int"),
         "userID" => array("type" => "int"),
         "cached_schoolID" => array("type" => "int"),
         "rank" => array("type" => "int"),
         "schoolRank" => array("type" => "int"),
         "algoreaCode" => array("type" => "string"),
         "algoreaCategory" => array("type" => "string"),
         "saniValid" => array("type" => "int", "access" => array("write" => array("generator", "admin"), "read" => array("admin"))),
         "orig_firstName" => array("type" => "string"),
         "orig_lastName" => array("type" => "string")
      ),
      "hasHistory" => false
   ),
   "contest" => array(
      "autoincrementID" => false,
      "fields" => array(
         "name" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "level" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "year" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "category" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "status" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "open" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "visibility" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "closedToOfficialGroups" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "showSolutions" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "nbMinutes" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "bonusScore" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "allowTeamsOfTwo" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "newInterface" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "customIntro" =>  array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "fullFeedback" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "nextQuestionAuto" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "nbUnlockedTasksInitial" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "subsetsSize" =>  array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "folder" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "minAward1Rank" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "minAward2Rank" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "rankGrades" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "rankNbContestants" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "printCertificates" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "showResults" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "printCodes" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "askEmail" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "askZip" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "askGrade" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "askStudentId" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "askGenre" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "certificateStringsName" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "startDate" => array("type" => "date", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "endDate" => array("type" => "date", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "parentContestID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "categoryColor" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "language" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "description" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "imageURL" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
      )
   ),
   "contest_question" => array(
      "autoincrementID" => false,
      "fields" => array(
         "contestID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "questionID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "minScore" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "noAnswerScore" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "maxScore" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "options" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "order" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
      )
   ),
   "grade" => array(
      "autoincrementID" => false,
      "hasHistory" => false,
      "fields" => array(
         "name" => array("type" => "string", "access" => array("write" => array(), "read" => array()))
      )
   ),
   "group" => array(
      "autoincrementID" => false,
      "fields" => array(
         "schoolID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "grade" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "gradeDetail" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "userID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "name" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "nbStudents" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "nbTeamsEffective" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "nbStudentsEffective" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "contestID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "code" =>  array("type" => "string", "access" => array("write" => array("generator", "admin"), "read" => array("admin"))),
         "password" => array("type" => "string", "access" => array("write" => array("generator", "admin"), "read" => array("admin"))),
         "expectedStartTime" => array("type" => "date", "access" => array("write" => array("user"), "read" => array("user"))),
         "startTime" => array("type" => "date"),
         "noticePrinted" => array("type" => "int"),
         "isPublic" => array("type" => "int"),
         "participationType" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "minCategory" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "maxCategory" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "language" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user")))
      )
   ),
   "question" => array(
      "autoincrementID" => false,
      "fields" => array(
         "key" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "path" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "name" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "answerType" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "expectedAnswer" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin")))
      )
   ),
   "school" => array(
      "autoincrementID" => false,
      "fields" => array(
         "userID" => array("type" => "int", "access" => array("write" => array("generator", "admin", "user"), "read" => array("admin"))),
         "name" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "region" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "address" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "zipcode" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "city" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "country" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "coords" => array("type" => "string", "access" => array("write" => array("generator", "admin", "user"), "read" => array("admin"))),
         "nbStudents" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "validated" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "saniValid" => array("type" => "int", "access" => array("write" => array("generator", "admin", "user"), "read" => array("admin"))),
         "saniMsg" => array("type" => "string", "access" => array("write" => array("generator", "admin", "user"), "read" => array("user"))),
         "orig_name" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "orig_city" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "orig_country" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user")))
      )
   ),
   "school_user" => array(
      "autoincrementID" => false,
      "fields" => array(
         "userID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "schoolID" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "confirmed" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user"))),
         "awardsReceivedYear" => array("type" => "int", "access" => array("write" => array("user"), "read" => array("user")))
      )
   ),
   "school_year" => array(
      "autoincrementID" => false,
      "fields" => array(
         "schoolID" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "year" => array("type" => "int"),
         "nbOfficialContestants" => array("type" => "int"),
         "awarded" => array("type" => "int")
      )
   ),
   "team" => array(
      "autoincrementID" => false,
      "fields" => array(
         "groupID" => array("type" => "int"),
         "password" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "startTime" => array("type" => "date", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "endTime" => array("type" => "date"),
         "nbMinutes" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "extraMinutes" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "score" => array("type" => "int", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "participationType" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user")))
      ),
      "hasHistory" => false
   ),
   "team_question" => array(
      "autoincrementID" => false,
      "primaryKey" => false,
      "fields" => array(
         "teamID" => array("type" => "int"),
         "questionID" => array("type" => "int"),
         "answer" => array("type" => "string"),
         "score" => array("type" => "int"),
         "ffScore" => array("type" => "int"),
         "date" => array("type" => "date")
      ),
      "hasHistory" => false
   ),
   "user" => array(
      "autoincrementID" => false,
      "fields" => array(
         "firstName" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "lastName" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "officialEmail" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "officialEmailValidated" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "alternativeEmail" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "alternativeEmailValidated" => array("type" => "string", "access" => array("write" => array("generator", "admin"), "read" => array("admin"))),
         "salt" => array("type" => "string", "access" => array("write" => array("generator"), "read" => array("admin"))),
         "passwordMd5" => array("type" => "string", "access" => array("write" => array("generator"), "read" => array("admin"))),
         "recoverCode" => array("type" => "string"),
         "validated" => array("type" => "string", "access" => array("write" => array("generator", "admin"), "read" => array("admin"))),
         "allowMultipleSchools" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "isAdmin" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "registrationDate" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "lastLoginDate" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "awardPrintingDate" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "comment" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin"))),
         "gender" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "saniValid" => array("type" => "int", "access" => array("write" => array("generator", "user"), "read" => array("admin"))),
         "orig_firstName" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user"))),
         "orig_lastName" => array("type" => "string", "access" => array("write" => array("user"), "read" => array("user")))
      )
    ),
    "languages" => array(
        "fields" => array(
            "name" => array(
                "type" => "string",
                "access" => array("write" => array("admin"), "read" => array("admin"))),
            "suffix" => array(
                "type" => "string",
                "access" => array("write" => array("admin"), "read" => array("admin"))),
        )
   ),
    "user_user" => array(
        "autoincrementID" => false,
        "fields" => array(
            "userID" => array(
                "type" => "int",
                "access" => array("write" => array("user"), "read" => array("user"))),
            "targetUserID" => array(
                "type" => "int",
                "access" => array("write" => array("user"), "read" => array("user"))),
            "accessType" => array(
                "type" => "string",
                "access" => array("write" => array("user"), "read" => array("user"))),
        )
   ),
);

if (isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"]) {
   $fieldGroup = array("tableName" => "group", "fieldName" => "name");
   $fieldGroupFilter = array("joins" => array("group"), "condition" => "`[PREFIX]group`.`name` LIKE :groupField");
   $fieldGroupFilterTeam = $fieldGroupFilter;
} else {
   $fieldGroup = array("tableName" => "team", "fieldName" => "groupID", "access" => array("write" => array(), "read" => array("user")));
   $fieldGroupFilter = array("joins" => array("team"), "condition" => "`[PREFIX]team`.`groupID` = :groupField");
   $fieldGroupFilterTeam = array("joins" => array(), "condition" => "`[PREFIX]team`.`groupID` = :groupField");
}

$viewsModels = array(
   'algorea_registration' => array(
      "mainTable" => "algorea_registration",
      "adminOnly" => false,
      "joins" => array(
      ),
      "fields" => array(
         "firstName" => array(),
         "lastName" => array(),
         "genre" => array(),
         "grade" => array(),
         "score" => array("tableName" => "algorea_registration", "fieldName" => "totalScoreAlgorea"),
         "rank" => array("tableName" => "algorea_registration", "fieldName" => "algoreaRank"),
         "schoolRank" => array("tableName" => "algorea_registration", "fieldName" => "algoreaSchoolRank"),
         "rankDemi2018" => array(),
         "category" => array(),
         "round" => array(),
         "userID" => array(),
         "schoolID" => array(),
      ),
      "filters" => array(
         "schoolID" => array("joins" => array(), "condition" => "`algorea_registration`.`schoolID` = :schoolID"),
         "userID" => array("joins" => array(), "condition" => "(`algorea_registration`.`userID` = :userID)"),
         "hasScore" => array("joins" => array(), "ignoreValue" => true, "condition" => "`[PREFIX]algorea_registration`.`totalScoreAlgorea` > 0")
      )
   ),
   'award1' => array(
      'mainTable' => 'contestant',
      'adminOnly' => false,
      'joins'     => array(
         "team" => array("srcTable" => "contestant", "srcField" => "teamID", "dstField" => "ID"),
         "full_groups" => array("srcTable" => "team", "srcField" => "groupID", "dstField" => "ID"),
         "contest" => array("srcTable" => "full_groups", "srcField" => "contestID", "dstField" => "ID"),
         "school" => array("srcTable" => "full_groups", "srcField" => "schoolID", "dstField" => "ID"),
         "award_threshold" => array("srcTable" => "team", "on" => "(team.nbContestants = award_threshold.nbContestants and `full_groups`.contestID = award_threshold.contestID and contestant.grade = award_threshold.gradeID and award_threshold.awardID = 1)"),
         "algorea_registration" => array("type" => "LEFT", "srcTable" => "contestant", "srcField" => "ID", "dstField" => "contestantID"),
      ),
      'fields' => array(
         "schoolID" => array("tableName" => "full_groups", "access" => array("write" => array(), "read" => array("user")), "groupBy" => "`contestant`.`ID`"),
         "contestID" => array("tableName" => "full_groups", "access" => array("write" => array(), "read" => array("user"))),
         "groupField" => $fieldGroup,
         "firstName" => array("tableName" => "contestant"),
         "lastName" => array("tableName" => "contestant"),
         "genre" => array("tableName" => "contestant"),
         "score" => array("tableName" => "team"),
         "rank" => array("tableName" => "contestant"),
         "country" => array("tableName" => "school"),
         "city" => array("tableName" => "school"),
         "name" => array("tableName" => "school"),
         "algoreaCode" => array("tableName" => "contestant"),
         "algoreaCategory" => array("tableName" => "algorea_registration", "fieldName" => "category"),
         "franceioiID" => array("tableName" => "algorea_registration"),
         "groupName" => array("tableName" => "full_groups", "fieldName" => "name")
      ),
      "filters" => array(
         "groupField" => $fieldGroupFilter,
         "score" => array("joins" => array("team"), "condition" => "`[PREFIX]team`.`score` = :[PREFIX_FIELD]score"),
         "printable" => array("joins" => array("contest"), "condition" => "`[PREFIX]contest`.`printCodes` = 1", "ignoreValue" => true),
         "showable" => array("joins" => array("contest"), "condition" => "`[PREFIX]contest`.`showResults` = 1", "ignoreValue" => true),
         "schoolID" => array("joins" => array("full_groups"), "condition" => "`full_groups`.`schoolID` = :[PREFIX_FIELD]schoolID"),
         "groupID" => array("joins" => array("team"), "condition" => "(`team`.`groupID` = :[PREFIX_FIELD]groupID)"),
         "userID" => array("joins" => array("full_groups"), "condition" => "(`full_groups`.`userID` = :[PREFIX_FIELD]userID OR `full_groups`.`targetUserID` = :[PREFIX_FIELD]userID)"),
         "ownerUserID" => array("joins" => array("full_groups"), "condition" => "`full_groups`.`userID` = :[PREFIX_FIELD]ownerUserID"),
         "awarded" => array("joins" => array("group", 'team', 'award_threshold'), "ignoreValue" => true, "condition" => "(`[PREFIX]team`.`participationType` = 'Official' and `[PREFIX]contestant`.`rank` is not null and `[PREFIX]award_threshold`.`minScore` <= [PREFIX]team.score)")
      ),
      'orders' => array(
         array('field' => 'name'),
         array('field' => 'contestID'),
         array('field' => 'groupField'),
         array('field' => 'lastName'),
         array('field' => 'firstName'),
      )
   ),

   'award_threshold' => array(
      'mainTable' => 'award_threshold',
      'adminOnly' => false,
      'joins'     => array(
         "group" => array("srcTable" => "award_threshold", "srcField" => "contestID", "dstTable" => 'group', "dstField" => "contestID"),
      ),
      'fields' => array(
         "contestID" => array(),
         "gradeID" => array(),
         "awardID" => array(),
         "nbContestants" => array(),
         "awardID" => array(),
         "minScore" => array(),
      ),
      "filters" => array(
         "groupID" => array("joins" => array("group"), "condition" => "`[PREFIX]group`.`ID` = :groupID"),
         "contestID" => array("condition" => "`[PREFIX]award_threshold`.`contestID` = :contestID")
      ),
   ),

   "contestant" => array(
      "mainTable" => "contestant",
      "adminOnly" => false,
      "joins" => array(
         "team" => array("srcTable" => "contestant", "srcField" => "teamID", "dstField" => "ID"),
         "full_groups" => array("srcTable" => "team", "srcField" => "groupID", "dstField" => "ID"),
         "school" => array("srcTable" => "full_groups", "srcField" => "schoolID", "dstField" => "ID"),
         "contest" => array("srcTable" => "full_groups", "srcField" => "contestID", "dstField" => "ID"),
         "algorea_registration" => array("type" => "LEFT", "srcTable" => "contestant", "srcField" => "registrationID", "dstField" => "ID")
      ),
      "fields" => array(
         "schoolID" => array("tableName" => "full_groups", "access" => array("write" => array(), "read" => array("user")), "groupBy" => "`contestant`.`ID`"),
         "contestID" => array("tableName" => "full_groups", "access" => array("write" => array(), "read" => array("user"))),
         "groupField" => $fieldGroup,
         "saniValid" => array(),
         "firstName" => array(),
         "lastName" => array(),
         "genre" => array("tableName" => "contestant"),
         "grade" => array(),
         "score" => array("tableName" => "team"),
         "nbContestants" => array("tableName" => "team"),
         "rank" => array(),
         "category" => array("tableName" => "algorea_registration", "fieldName" => "category", "type" => "string"),
         "email" => array(),
         "zipCode" => array(),
         "studentId" => array(),
         "schoolRank" => array(),
         "userID" => array("tableName" => "full_groups"),
         "groupID" => array("tableName" => "team"),
         "qualificationCode" => array("fieldName" => "algoreaCode"),
         "groupName" => array("tableName" => "full_groups", "fieldName" => "name"),         
         "schoolName" => array("tableName" => "school", "fieldName" => "name"),         
      ),
      "filters" => array(
         "groupField" => $fieldGroupFilter,
         "official" => array("joins" => array("team"), "condition" => "`[PREFIX]team`.`participationType` = 'Official'", 'ignoreValue' => true),
         "score" => array("joins" => array("team"), "condition" => "`[PREFIX]team`.`score` = :score"),
         "contestID" => array("joins" => array("full_groups"), "condition" => "`[PREFIX]full_groups`.`contestID` = :contestID"),
         "teamID" => array("joins" => array("team"), "condition" => "`[PREFIX]team`.`ID` = :teamID"),
         "groupID" => array("joins" => array("team"), "condition" => "`[PREFIX]team`.`groupID` = :groupID"),
         "schoolID" => array("joins" => array("full_groups"), "condition" => "`full_groups`.`schoolID` = :schoolID"),
         "userID" => array("joins" => array("full_groups"), "condition" => "(`full_groups`.`userID` = :userID OR `full_groups`.`targetUserID` = :userID)"),
         "ownerUserID" => array("joins" => array("full_groups"), "condition" => "`full_groups`.`userID` = :[PREFIX_FIELD]ownerUserID"),
         "awarded" => array("joins" => array("team", "algorea_registration"), "ignoreValue" => true, "condition" => "(`[PREFIX]team`.`participationType` = 'Official' and `[PREFIX]algorea_registration`.`code` is not null)"),
         "printable" => array("joins" => array("contest"), "condition" => "`[PREFIX]contest`.`printCodes` = 1", "ignoreValue" => true)
      ),
      'orders' => array(
         array('field' => 'contestID'),
         array('field' => 'groupField'),
         array('field' => 'lastName'),
         array('field' => 'firstName'),
      )
   ),
   
   "contestantCSV" => array(
      "mainTable" => "contestant",
      "adminOnly" => false,
      "joins" => array(
         "team" => array("srcTable" => "contestant", "srcField" => "teamID", "dstField" => "ID"),
         "full_groups" => array("srcTable" => "team", "srcField" => "groupID", "dstField" => "ID"),
         "contest" => array("srcTable" => "full_groups", "srcField" => "contestID", "dstField" => "ID"),
         "school" => array("srcTable" => "full_groups", "srcField" => "schoolID", "dstField" => "ID"),
         "grade" => array("srcTable" => "contestant", "srcField" => "grade", "dstField" => "ID"),
         "algorea_registration" => array("type" => "LEFT", "srcTable" => "contestant", "srcField" => "ID", "dstField" => "contestantID")
      ),
      "fields" => array(
         "schoolID" => array("tableName" => "full_groups", "access" => array("write" => array(), "read" => array("user")), "groupBy" => "`contestant`.`ID`"),
         "schoolName" => array("tableName" => "school", "fieldName" => "name"),
         "grade" => array("tableName" => "full_groups"),
         "contestID" => array("tableName" => "full_groups", "access" => array("write" => array(), "read" => array("user"))),
         "contestName" => array("tableName" => "contest", "fieldName" => "name"),
         "full_groupsName" => array("tableName" => "full_groups", "fieldName" => "name"),
         "saniValid" => array(),
         "firstName" => array(),
         "lastName" => array(),
         "genre" => array("tableName" => "contestant"),
         "grade" => array("tableName" => "grade", "fieldName" => "name"),
         "studentId" => array(),
         "score" => array("tableName" => "team"),
         "nbContestants" => array("tableName" => "team"),
         "rank" => array(),
         "qualificationCode" => array("fieldName" => "algoreaCode"),
         "category" => array("tableName" => "algorea_registration", "fieldName" => "category", "type" => "string"),
         "email" => array(),
         "zipCode" => array(),
      ),
      "filters" => array(
         "full_groupsField" => $fieldGroupFilter,
         "score" => array("joins" => array("team"), "condition" => "`[PREFIX]team`.`score` = :score"),
         "schoolID" => array("joins" => array("full_groups"), "condition" => "`[PREFIX]group`.`schoolID` = :schoolID"),
         "userID" => array("joins" => array("full_groups"), "condition" => "(`full_groups`.`userID` = :userID OR `full_groups`.`targetUserID` = :userID)"),
         "ownerUserID" => array("joins" => array("full_groups"), "condition" => "`full_groups`.`userID` = :[PREFIX_FIELD]ownerUserID"),
      ),
   ),
   
   "team_view" => array(
      "mainTable" => "team",
      "adminOnly" => false,
      "joins" => array(
         "group" => array("srcTable" => "team", "srcField" => "groupID", "dstField" => "ID"),
         "user_user" => array("type" => "LEFT", "srcTable" => "group", "on" => "(`[PREFIX]user_user`.`targetUserID` = :userID and `group`.`userID` = `user_user`.`userID`)"),
         "contestant" => array("srcTable" => "team", "srcField" => "ID", "dstField" => "teamID")
      ),
      "fields" => array(
         "schoolID" => array("tableName" => "group", "access" => array("write" => array(), "read" => array("user"))),
         "contestID" => array("tableName" => "group", "access" => array("write" => array(), "read" => array("user"))),
         "groupName" => array("tableName" => "group", "fieldName" => "name"),
         "contestants" => array("type" => "string", "access" => array("write" => array("admin"), "read" => array("admin")), "tableName" => "contestant", "sql" => "group_concat(`contestant`.`lastName`,' ',`contestant`.`firstName` separator ',')", "groupBy" => "`team`.`ID`"),
         "password" => array(),
         "startTime" => array(),
         "score" => array(),
         "participationType" => array()
      ),
      "filters" => array(
         "schoolID" => array("joins" => array("group"), "condition" => "`[PREFIX]group`.`schoolID` = :schoolID"),
         "groupField" => $fieldGroupFilterTeam,
         "userID" => array("joins" => array("user_user"), "condition" => "(`group`.`userID` = :userID OR `[PREFIX]user_user`.`accessType` <> 'none')"),
         "contestants" => array(
            "joins" => array("contestant"),
            "condition" => "concat(`[PREFIX]contestant`.`firstName`,' ',`[PREFIX]contestant`.`lastName`) LIKE :contestants")
      )
   ),
   "group" => array(
      "mainTable" => "group",
      "adminOnly" => false,
      "joins" => array(
         "school" => array("srcTable" => "group", "srcField" => "schoolID", "dstField" => "ID"),
         "contest" => array("srcTable" => "group", "srcField" => "contestID", "dstField" => "ID"),
         "user_user" => array("type" => "LEFT", "srcTable" => "group", "srcField" => "userID", "dstField" => "userID"),
         "school_user" => array("srcTable" => "group", "srcField" => "schoolID", "dstField" => "schoolID"),
         "user" => array("srcTable" => "group", "srcField" => "userID", "dstField" => "ID")
      ),
      "fields" => array(
         "schoolID" => array("groupBy" => "`group`.`ID`"),
         "userLastName"  => array("fieldName" => "lastName", "tableName" => "user"),
         "userFirstName"  => array("fieldName" => "firstName", "tableName" => "user"),
         "contestID" => array(),
         "grade" => array(),
         "participationType" => array(),
         "expectedStartTime" => array(),
         "name" => array(),
         "code" =>  array(),
         "password" => array(),
         "nbTeamsEffective" => array(),
         "nbStudentsEffective" => array(),
         "nbStudents" => array(),
         "userID" => array("fieldName" => "userID", "tableName" => "group"),
         "contestPrintCertificates" => array("fieldName" => "printCertificates", "tableName" => "contest"),
         "contestPrintCodes" => array("fieldName" => "printCodes", "tableName" => "contest"),
         "minCategory" => array(),
         "maxCategory" => array(),
         "language" => array()
//         "accessUserID" => array("fieldName" => "targetUserID", "tableName" => "user_user")
      ),
      "filters" => array(
         "statusNotHidden" => array(
            "joins" => array("contest"),
            "condition" => "(`[PREFIX]contest`.`visibility` <> 'Hidden')",
            "ignoreValue" => true
         ),
         "checkOfficial" => array(
            "joins" => array("contest"),
            "condition" => "((`[PREFIX]contest`.`closedToOfficialGroups` = 0) OR ".
                            "(`[PREFIX]group`.`participationType` = 'Unofficial'))",
            "ignoreValue" => true
         ),
         "checkSchoolUserID"  => array(
            "joins" => array("school_user"),
            "condition" => "(`[PREFIX]school_user`.`userID` = :[PREFIX_FIELD]checkSchoolUserID)"
         ),
         "checkAccessUserID" => array(
            "joins" => array("user_user"),
            "condition" => "((`[PREFIX]user_user`.`accessType` <> 'none' AND `[PREFIX]user_user`.`targetUserID` = :[PREFIX_FIELD]checkAccessUserID) ".
                           "OR (`[PREFIX]group`.`userID` = :[PREFIX_FIELD]checkAccessUserID))"
         ),
         "checkNoChild" => array(
            "condition" => "(`[PREFIX]group`.`parentGroupID` IS NULL)",
            "ignoreValue" => true
         )
      )
   ),
   "contest_question" => array(
      "mainTable" => "contest_question",
      "adminOnly" => true,
      "joins" => array(
      ),
      "fields" => array(
         "contestID" => array(),
         "questionID" => array(),
         "minScore" => array(),
         "noAnswerScore" => array(),
         "maxScore" => array(),
         "options" => array(),
         "order" => array()
      ),
      "filters" => array(
      )
   ),
   "school_search" => array(
      "mainTable" => "school",
      "adminOnly" => false,
      "joins" => array(),
      "fields" => array(
         "name" => array(),
         "region" => array(),
         "address" => array(),
         "city" => array(),
         "zipcode" => array(),
         "country" => array()
      )
   ),
   "school_user" => array(
      "mainTable" => "school_user",
      "adminOnly" => false,
      "joins" => array(),
      "fields" => array(
         "userID" => array(),
         "schoolID" => array(),
         "confirmed" => array(),
         "awardsReceivedYear" => array()
      )
   ),
   "school_year" => array(
      "mainTable" => "school_year",
      "adminOnly" => false,
      "joins" => array(
         "school_user" => array("srcTable" => "school_year", "srcField" => "schoolID", "dstField" => "schoolID")
      ),
      "fields" => array(
         "schoolID" => array(),
         "userID" => array("tableName"  => "school_user"),
         "year" => array(),
         "nbOfficialContestants" => array(),
         "awarded" => array()
      )
   ),
   "school" => array(
      "mainTable" => "school",
      "adminOnly" => false,
      "joins" => array(
         "user" => array("srcTable" => "school", "srcField" => "userID", "dstField" => "ID", "type" => "LEFT"),
         "school_user" => array("srcTable" => "school", "srcField" => "ID", "dstField" => "schoolID"),
         "school_user_count" => array("srcTable" => "school", "srcField" => "ID", "dstTable" => "school_user", "dstField" => "schoolID", "type" => "LEFT")
      ),
      "fields" => array(
         "name" => array("groupBy" => "`school`.`ID`"),
         "region" => array(),
         "address" => array(),
         "city" => array(),
         "zipcode" => array(),
         "country" => array(),
         "nbStudents" => array(),
         "userLastName" => array("tableName" => "user", "fieldName" => "lastName"),
         "userFirstName" => array("tableName" => "user", "fieldName" => "firstName"),
         "saniValid" => array(),
         "saniMsg" => array(),
         "coords" => array(),
         "userID" => array(),
         "ownerFirstName"  => array("fieldName" => "firstName", "tableName" => "user"),
         "ownerLastName"  => array("fieldName" => "lastName", "tableName" => "user"),
         "nbUsers" => array(
            "tableName" => "school_user_count",
            "type" => "int",
            "sql" => "count(*)",
            "groupBy" => "`school`.`ID`",
            "access" => array("write" => array(), "read" => array("user"))
         )
      ),
      "filters" => array(
         "accessUserID" => array(
            "joins" => array("school_user"),
            "condition" => "(`[PREFIX]school_user`.`userID` = :[PREFIX_FIELD]accessUserID)"
         )
      )
   ),
   "contest" => array(
      "mainTable" => "contest",
      "adminOnly" => true,
      "joins" => array(
         "contest_question" => array("srcTable" => "contest", "srcField" => "ID", "dstField" => "contestID", "type" => "LEFT")
      ),
      "fields" => array(
         "name" => array(
            "groupBy" => "`contest`.`ID`"
         ),
         "level" => array(),
         "year" => array(),
         "status" => array(),
         "open" => array(),
         "visibility" => array(),
         "closedToOfficialGroups" => array(),
         "showSolutions" => array(),
         "startDate" => array(),
         "endDate" => array(),
         "nbMinutes" =>  array(),
         "bonusScore" =>  array(),
         "allowTeamsOfTwo" =>  array(),
         "newInterface" =>  array(),
         "customIntro" =>  array(),
         "fullFeedback" =>  array(),
         "nextQuestionAuto" =>  array(),
         "nbUnlockedTasksInitial" =>  array(),
         "subsetsSize" =>  array(),
         "folder" => array(),
         "askEmail" => array(),
         "askZip" => array(),
         "askGrade" => array(),
         "askStudentId" => array(),
         "askGenre" => array(),
         "minAward1Rank" => array(),
         "minAward2Rank" => array(),
         "rankGrades" => array(),
         "rankNbContestants" => array(),
         "maxScore" => array(
            "type" => "int",
            "tableName" => "contest_question",
            "sql" => "SUM(`contest_question`.`maxScore`) + `contest`.`bonusScore`",
            "groupBy" => "`contest`.`ID`"),
         "showResults" => array(),
         "printCertificates" => array(),
         "certificateStringsName" => array(),
         "printCodes" => array(),
         "parentContestID" => array(),
         "categoryColor" => array(),
         "language" => array(),
         "description" => array(),
         "imageURL" => array(),
      ),
      "filters" => array(
         "statusNotHidden" => array(
            "joins" => array(),
            "condition" => "(`[PREFIX]contest`.`visibility` <> 'Hidden')",
            "ignoreValue" => true
          )
      )
   ),
   "question" => array(
      "mainTable" => "question",
      "adminOnly" => true,
      "joins" => array(
      ),
      "fields" => array(
         "key" => array(),
         "path" => array(),
         "name" => array(),
         "answerType" => array(),
         "expectedAnswer" => array()
      ),
      "filters" => array(
      )
   ),
   "user" => array(
      "mainTable" => "user",
      "adminOnly" => true,
      "joins" => array(
      ),
      "fields" => array(
         "saniValid" => array(),
         "gender" => array(),
         "lastName" => array(),
         "firstName" => array(),
         "officialEmail" => array(),
         "officialEmailValidated" => array(),
         "alternativeEmail" => array(),
         "validated" => array(),
         "allowMultipleSchools" => array(),
         "registrationDate" => array(),
         "lastLoginDate" => array(),
         "awardPrintingDate" => array(),
         "isAdmin" => array(),
         "comment" => array(),
         "passwordMd5" => array(),
         "salt" => array()
      ),
      "filters" => array(
      )
   ),
   "languages" => array(
       "mainTable" => "languages",
       "adminOnly" => true,
       "joins" => array(),
       "fields" => array(
           "name" => array(),
           "suffix" => array()
       ),
       "filters" => array()
   ),
   "translations" => array(
       "mainTable" => "translations",
       "adminOnly" => true,
       "joins" => array(),
       "fields" => array(
           "languageID" => array(),
           "category" => array(),
           "key" => array(),
           "translation" => array()
       ),
       "filters" => array()
   ),
   "colleagues" => array(
      "mainTable" => "user",
      "adminOnly" => false,
      "joins" => array(
         "user_user_target" => array("type" => "LEFT", "srcTable" => "user", "srcField" => "ID", "dstTable" => "user_user", "dstField" => "targetUserID"),
         "user_user_source" => array("type" => "LEFT", "srcTable" => "user", "srcField" => "ID", "dstTable" => "user_user", "dstField" => "userID"),
         "school_user" => array("srcTable" => "user", "srcField" => "ID", "dstField" => "userID"),
         "school" => array("srcTable" => "school_user", "srcField" => "schoolID", "dstField" => "ID"),
         "school_user_self" => array("srcTable" => "school", "srcField" => "ID", "dstTable" => "school_user", "dstField" => "schoolID")
      ),
      "fields" => array(
         "lastName" => array(
            "tableName" => "user",
            "access" => array("write" => array(), "read" => array()),
            "groupBy" => "`user`.`ID`"
         ),
         "firstName" => array(
            "tableName" => "user",
            "access" => array("write" => array(), "read" => array())
         ),
         "accessTypeGiven" => array(
            "type" => "string",
            "tableName" => "user_user_target",
            "fieldName" => "accessType",
            "access" => array("write" => array("admin", "user"), "read" => array("admin", "user"))
         ),
         "accessTypeReceived" => array(
            "type" => "string",
            "tableName" => "user_user_source",
            "fieldName" => "accessType",
            "access" => array("write" => array("admin"), "read" => array("admin", "user"))
         ),
         "gender" => array(
            "tableName" => "user",
            "access" => array("write" => array(), "read" => array()),
            "groupBy" => "`user`.`ID`"
         )
      ),
      "filters" => array(
         "userID" => array(
            "joins" => array("school_user", "school_user_self", "user_user_target", "user_user_source"),
            "condition" => "(`[PREFIX]school_user`.`schoolID` = `[PREFIX]school_user_self`.`schoolID` AND ".
                           "(`[PREFIX]user`.`ID` <> :[PREFIX_FIELD]userID) AND ".
                           "(`[PREFIX]school_user_self`.`userID` = :[PREFIX_FIELD]userID) AND ".
                           "((`[PREFIX]user_user_target`.`userID` = :[PREFIX_FIELD]userID) OR (`[PREFIX]user_user_target`.`userID` IS NULL)) AND ".
                           "((`[PREFIX]user_user_source`.`targetUserID` = :[PREFIX_FIELD]userID) OR (`[PREFIX]user_user_source`.`targetUserID` IS NULL)))"
         ),
      )
   ),   
);

?>
