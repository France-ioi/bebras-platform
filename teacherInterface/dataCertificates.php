<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");


if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

if (!isset($_POST['contestID']) || !isset($_POST['schoolID'])) {
   echo translate("certificates_please_specify_contestID_schoolID");
}

if ($_POST['contestID'] != "algorea") {
   $stmt = $db->prepare("SELECT * FROM `contest` WHERE ID = :contestID;");
   $stmt->execute(['contestID' => $_POST['contestID']]);
   $contest = $stmt->fetch();
   if (!$contest) {
      echo sprintf(translate("certificates_unknown_contest"), $_POST['contestID']);
   }
   if ($contest["parentContestID"] != null) {
      $stmt = $db->prepare("SELECT * FROM `contest` WHERE ID = :contestID;");
      $stmt->execute(['contestID' => $contest["parentContestID"]]);
      $contest = $stmt->fetch();
   }

   $groupBy = '';
   $select = '';
   if ($contest['rankGrades']) {
      if ($contest['rankNbContestants']) {
         $groupBy = 'GROUP BY `team`.`nbContestants`, `contestant`.`grade`';
         $select = ', `contestant`.`grade`, `team`.`nbContestants`';
      } else {
         $groupBy = 'GROUP BY `contestant`.`grade`';
         $select = ', `contestant`.`grade`';
      }
   } elseif ($contest['rankNbContestants']) {
      $groupBy = 'GROUP BY `team`.`nbContestants`';
      $select = ', `team`.`nbContestants`';
   }

   $query = "SELECT count(distinct contestant.ID) AS `totalContestants`, `contestant`.`grade`, `team`.`nbContestants` FROM `contestant` ".
      "JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) ".
      "JOIN `group` ON (`group`.`ID` = `team`.`groupID`) ".
      "JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) ".
      "LEFT JOIN `user_user` ON (`group`.`userID` = `user_user`.`userID`) ".
      "WHERE `group`.`schoolID` = :schoolID ".
      "AND `team`.`participationType` = 'Official' ".
      "AND (`contest`.`ID` = :contestID OR `contest`.`parentContestID` = :contestID) ".
      $groupBy;
     
   $data = array("contestID"  => $contest["ID"],
      "schoolID" => $_REQUEST["schoolID"]);

   $stmt = $db->prepare($query);
   $stmt->execute($data);

   $itemsPerSchool = array();
   while ($row = $stmt->fetchObject()) {
      $itemsPerSchool[] = $row;
   }

   $query = "SELECT count(contestant.ID) AS `totalContestants`, `contestant`.`grade`, `team`.`nbContestants` FROM `contestant` ".
      "JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) ".
      "JOIN `group` ON (`group`.`ID` = `team`.`groupID`) ".
      "JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) ".
      "WHERE `team`.`participationType` = 'Official' ".
      "AND (`contest`.`ID` = :contestID OR `contest`.`parentContestID` = :contestID) ".
      $groupBy;
      
   $data = array("contestID"  => $contest["ID"]);
   
   $stmt = $db->prepare($query);
   $stmt->execute($data);

   $itemsPerContest = array();
   while ($row = $stmt->fetchObject()) {
      $itemsPerContest[] = $row;
   }

   $items = array(
      "perSchool" => $itemsPerSchool,
      "perContest" => $itemsPerContest
   );

   echo json_encode($items);
} else {
   $query = "SELECT count(algorea_registration.ID) AS `totalContestants`, `algorea_registration`.`grade`, 1 as nbContestants FROM `algorea_registration` 
      WHERE `algorea_registration`.`totalScoreAlgorea` IS NOT NULL AND `algorea_registration`.`totalScoreAlgorea` > 0
      GROUP BY `grade`";

   $stmt = $db->prepare($query);
   $stmt->execute();

   $itemsPerContest = array();
   while ($row = $stmt->fetchObject()) {
      $itemsPerContest[] = $row;
   }
   
   $query = "SELECT count(algorea_registration.ID) AS `totalContestants`, `algorea_registration`.`grade`, 1 as nbContestants FROM `algorea_registration` 
      WHERE `algorea_registration`.`schoolID` = :schoolID 
      AND `algorea_registration`.`userID` = :userID
      AND `algorea_registration`.`totalScoreAlgorea` IS NOT NULL AND `algorea_registration`.`totalScoreAlgorea` > 0
      GROUP BY `grade`";

   $stmt = $db->prepare($query);
   $stmt->execute(array("userID"  => $_SESSION["userID"], "schoolID" => $_REQUEST["schoolID"]));

   $itemsPerSchool = array();
   while ($row = $stmt->fetchObject()) {
      $itemsPerSchool[] = $row;
   }
   $items = array(
      "perSchool" => $itemsPerSchool,
      "perContest" => $itemsPerContest
   );

   echo json_encode($items);   
}