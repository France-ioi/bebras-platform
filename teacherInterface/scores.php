<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   if (!isset($_POST['groupMode']) || !$_POST['groupMode']) {
      echo json_encode((object)array("status" => 'error', "message" => "Seul un admin peut évaluer les scores d'un concours"));
      exit;
   } else if (!isset($_SESSION["userID"]) || !$_SESSION["userID"]) {
      echo json_encode((object)array("status" => 'error', "message" => "Vous n'êtes pas loggé"));
      exit;
   }
}

$response = array('status' => 'failed', 'questionKey' => $_POST['questionKey']);

if (!isset($_POST['scores'])) {
   echo json_encode((object)$response);
   exit;
}

// getting contestID or groupID, and sticking to it:
$contestID = $_POST['scores'][0]['contestID'];
$groupID = false;
if (isset($_POST['scores'][0]['groupID'])) {
   $groupID = $_POST['scores'][0]['groupID'];
}
if ($groupID) {
   $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder` as `folder`, `contest`.`year` as `year` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` WHERE `group`.`ID` = ?";
   $args = array($groupID);
   if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
      $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` LEFT JOIN `user_user` on `group`.`userID` = `user_user`.`userID` WHERE `group`.`ID` = ? and ((`user_user`.`accessType` = 'write' AND `user_user`.`targetUserID` = ?) OR (`group`.`userID` = ?))";
      $args = array($groupID, $_SESSION['userID'], $_SESSION['userID']);
   }
   $stmt = $db->prepare($query);
   $stmt->execute($args);
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => "Le groupe n'existe pas ou vous n'y avez pas accès (grader.php)"));
      exit;
   }
} else {
   // Check contest existance
   $query = "SELECT `ID`, `folder`, `year` FROM `contest` WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => "Le concours n'existe pas"));
      exit;
   }
}

$teamQuestionTable = getTeamQuestionTableForGrading();

$stmtUpdate = null;
foreach ($_POST['scores'] as $scoreInfos) {
   if (($contestID && $scoreInfos['contestID'] != $contestID) || ($groupID && $scoreInfos['groupID'] != $groupID)) {
      echo json_encode((object)array("status" => 'error', "message" => "Le groupe ou le concours n'est pas le même entre les enregistrements"));
      exit;
   }
   if ($contestID) {
      $query = "
      UPDATE `team`
      JOIN `".$teamQuestionTable."` ON (`team`.`ID` = `".$teamQuestionTable."`.`teamID`)
      JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
      SET `".$teamQuestionTable."`.`score` = ?
      WHERE `group`.`contestID` = ?
      AND `".$teamQuestionTable."`.`questionID`= ?
      AND `".$teamQuestionTable."`.`answer` = ?
      ";
      $args = array($scoreInfos['score'], $scoreInfos['contestID'], $scoreInfos['questionID'], $scoreInfos['answer']);
      if ($scoreInfos['usesRandomSeed'] == "true") {
         $query .= " AND `team`.`ID` = ?";
         $args[] = $scoreInfos['teamID'];
      }
      if (!$stmtUpdate) {
         $stmtUpdate = $db->prepare($query);
      }
   } else {
      $query = "
         UPDATE `team`
         JOIN `".$teamQuestionTable."` ON (`team`.`ID` = `".$teamQuestionTable."`.`teamID`)
         SET `".$teamQuestionTable."`.`score` = ?
         WHERE `team`.`groupID` = ?
         AND `".$teamQuestionTable."`.`questionID`= ?
         AND `".$teamQuestionTable."`.`answer` = ?
      ";
      $args = array($scoreInfos['score'], $scoreInfos['groupID'], $scoreInfos['questionID'], $scoreInfos['answer']);
      if ($scoreInfos['usesRandomSeed']) {
         $query .= " AND `team`.`ID` = ?";
         $args[] = $scoreInfos['teamID'];
      }
      if (!$stmtUpdate) {
         $stmtUpdate = $db->prepare($query);
      }
   }
   $stmtUpdate->execute($args);
   $response['status'] = 'success';
}

echo json_encode($response);
