<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

//echo json_encode((object)array("status" => 'success'));
//exit();

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once '../shared/tinyORM.php';

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   if (!isset($_POST['groupMode']) || !$_POST['groupMode']) {
      echo json_encode((object)array("status" => 'error', "message" => translate("admin_restricted")));
      exit;
   } else if (!isset($_SESSION["userID"]) || !$_SESSION["userID"]) {
      echo json_encode((object)array("status" => 'error', "message" => translate("session_expired")));
      exit;
   }
}

$response = array('status' => 'failed', 'questionKey' => $_POST['questionKey']);

if (!isset($_POST['scores'])) {
   echo json_encode((object)$response);
   exit;
}

// getting contestID or groupID, and sticking to it:
$contestID = isset($_POST['scores'][0]['contestID']) ? $_POST['scores'][0]['contestID'] : null;
$groupID = isset($_POST['scores'][0]['groupID']) ? $_POST['scores'][0]['groupID'] : null;

if ($groupID) {
   $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder` as `folder`, `contest`.`year` as `year`, `contest`.`showSolutions` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` WHERE `group`.`ID` = ?";
   $args = array($groupID);
   if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
      $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder`, `contest`.`showSolutions` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` LEFT JOIN `user_user` on `group`.`userID` = `user_user`.`userID` WHERE `group`.`ID` = ? and ((`user_user`.`accessType` = 'write' AND `user_user`.`targetUserID` = ?) OR (`group`.`userID` = ?))";
      $args = array($groupID, $_SESSION['userID'], $_SESSION['userID']);
   }
   $stmt = $db->prepare($query);
   $stmt->execute($args);
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_inexistent_group")));
      exit;
   }
   if (!intval($row->showSolutions) && (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"])) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_contest_running")));
      exit;
   }
   $stmt->closeCursor();
} else {
   if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
      echo json_encode((object)array("status" => 'error', "message" => translate("admin_restricted")));
      exit;
   }
   // Check contest existance
   $query = "SELECT `ID`, `folder`, `year` FROM `contest` WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_inexistent_contest")));
      exit;
   }
   $stmt->closeCursor();
}

$teamQuestionTable = getTeamQuestionTableForGrading();

$stmtUpdate = null;
foreach ($_POST['scores'] as $scoreInfos) {
   if (($contestID && $scoreInfos['contestID'] != $contestID) || ($groupID && $scoreInfos['groupID'] != $groupID)) {
      echo json_encode((object)array("status" => 'error', "message" => "Le groupe ou le concours n'est pas le même entre les enregistrements"));
      exit;
   }
   if ($scoreInfos['score'] == '') {$scoreInfos['score']= null;}
   if (!isset($scoreInfos['scoreNeedsChecking'])) {$scoreInfos['scoreNeedsChecking'] = 0;}
   if ($contestID) {
      $query = "
      UPDATE `team`
      JOIN `".$teamQuestionTable."` ON (`team`.`ID` = `".$teamQuestionTable."`.`teamID`)
      JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
      SET `".$teamQuestionTable."`.`score` = ?, `".$teamQuestionTable."`.`scoreNeedsChecking` = ?
      WHERE `group`.`contestID` = ?
      AND `".$teamQuestionTable."`.`questionID`= ?";
      $args = array($scoreInfos['score'], $scoreInfos['scoreNeedsChecking'], $scoreInfos['contestID'], $scoreInfos['questionID']);
      if ($scoreInfos['usesRandomSeed'] == "true" || !isset($scoreInfos['answer']) || !$scoreInfos['answer']) {
         $query .= " AND `team`.`ID` = ?";
         $args[] = $scoreInfos['teamID'];
      } else {
         $query .= "AND `".$teamQuestionTable."`.`answer` = ?";
         $args[] = $scoreInfos['answer'];
      }
      $stmtUpdate = $db->prepare($query);
      $stmtUpdate->execute($args);
   } else {
      if ($config->db->use == 'dynamoDB') {
         // verify that team is in the asked group:
         $stmt = $db->prepare("SELECT `groupID` from team where ID = ?;");
         $stmt->execute(array($scoreInfos['teamID']));
         $thisGroupID = $stmt->fetchColumn();
         if ($thisGroupID != $groupID) {
            echo json_encode((object)array("status" => 'error', "message" => "L'équipe demandée n'est pas dans le groupe demandé"));
            exit;
         }
         try {
            $tinyOrm->update('team_question', ['score' => intval($scoreInfos['score']), 'scoreNeedsChecking' => intval($scoreInfos['scoreNeedsChecking'])], ['teamID' => $scoreInfos['teamID'], 'questionID' => $scoreInfos['questionID']]);
         } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
            error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
            error_log('DynamoDB error trying to write records: teamID: '.$teamID.', questionID: '.$questionID.', score: '.$scoreInfos['score']);
            exitWithJsonFailure($e->getAwsErrorCode(), array('error' => 'DynamoDB'));
         }
      }
      // sometimes answers are in dynamoDB, sometimes not... so we always check sql in addition to dynamodb
      $query = "UPDATE `team`
         JOIN `".$teamQuestionTable."` ON (`team`.`ID` = `".$teamQuestionTable."`.`teamID`)
         SET `".$teamQuestionTable."`.`score` = ?, `".$teamQuestionTable."`.`scoreNeedsChecking` = ?
         WHERE `team`.`groupID` = ?
         AND `".$teamQuestionTable."`.`questionID`= ?
         AND `team`.`ID` = ?
      ";
      $args = array($scoreInfos['score'], $scoreInfos['scoreNeedsChecking'], $scoreInfos['groupID'], $scoreInfos['questionID'], $scoreInfos['teamID']);
      if (!$stmtUpdate) {
         $stmtUpdate = $db->prepare($query);
      }
      $stmtUpdate->execute($args);
   }
   $response['status'] = 'success';
}

echo json_encode($response);
