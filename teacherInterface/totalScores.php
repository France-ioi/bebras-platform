<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once '../shared/tinyORM.php';

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   if (!isset($_POST['groupID']) || !$_POST['groupID']) {
      echo json_encode((object)array("status" => 'error', "message" => translate("admin_restricted")));
      exit;
   } else if (!isset($_SESSION["userID"]) || !$_SESSION["userID"]) {
      echo json_encode((object)array("status" => 'error', "message" => translate("session_expired")));
      exit;
   }
}

$contestID = isset($_REQUEST['contestID']) ? $_REQUEST['contestID'] : null;
$groupID = isset($_REQUEST['groupID']) ? $_REQUEST['groupID'] : null;
$bonusScore = null;
$stmt = null;

function getDynamoDBScore($teamID) {
   global $tinyOrm;
   try {
      $results = $tinyOrm->select('team_question', array('ffScore', 'score'), array('teamID' =>$teamID));
   } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
      if (strval($e->getAwsErrorCode()) != 'ConditionalCheckFailedException') {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error retrieving team_questions for teamID: '.$teamID);
      }
      $results = [];
   }
   $totalScore = 0;
   foreach($results as $result) {
      if (isset($result['score'])) {
         $totalScore += intval($result['score']);
      }
   }
   return $totalScore;
}

if ($groupID == null) {
   // Check contest existance
   $query = "SELECT `contest`.`ID`, `contest`.`folder`, `contest`.`bonusScore`, `contest`.`nbMinutes` FROM `contest` WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_inexistent_contest")));
      exit;
   }

   $query = "UPDATE `team` SET `endTime` = UTC_TIMESTAMP() WHERE `endTime` IS NULL AND TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) > ?";
   $stmt = $db->prepare($query);
   $nbSeconds = intval($row->nbMinutes) * 60;
   $stmt->execute(array($nbSeconds));

   $query = "UPDATE `team` JOIN ".
      "(SELECT IFNULL(SUM(`team_question`.`score`), 0) + ".($row->bonusScore)." as `teamScore`, ".
      //IFNULL(SUM(team_question.scoreNeedsChecking),0) as scoreNeedsChecking, ".
      "`team`.`ID` as `teamID` ".
      "FROM `team` ".
      "LEFT JOIN `team_question` ON (`team`.`ID` = `team_question`.`teamID`) ".
      "JOIN `group` ON (`team`.`groupID` = `group`.`ID`) ".
      "WHERE `team`.`endTime` IS NOT NULL ".
      "AND `group`.`contestID` = ? ".
      "GROUP BY `team`.`ID`) as teamScores ON team.ID = teamScores.teamID
      SET team.score = teamScores.teamScore ".
      //where teamScores.scoreNeedsChecking = 0;";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   
   $query = "
      SELECT COUNT(*) as `differences`
      FROM `team_question`
      JOIN `team` ON (`team`.`ID` = `team_question`.`teamID`)
      JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
      WHERE `group`.`contestID` = ?
      AND `team_question`.`score` IS NOT NULL
      AND `team_question`.`ffScore` IS NOT NULL
      AND `team_question`.`score` != `team_question`.`ffScore`
   ";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $differences = $stmt->fetchColumn();

   echo json_encode(array(
       'status' => 'success',
       'finished' => true,
       'differences' => $differences,
   ));
} else {
   // Check group existance and access
   $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder` as `folder`, `contest`.`bonusScore` as `bonusScore`, `contest`.`showSolutions`, `contest`.`nbMinutes` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` WHERE `group`.`ID` = ?";
   $args = array($groupID);
   if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
      $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder`, `contest`.`bonusScore` as `bonusScore`, `contest`.`showSolutions`, `contest`.`nbMinutes` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` LEFT JOIN `user_user` on `group`.`userID` = `user_user`.`userID` WHERE `group`.`ID` = ? and ((`user_user`.`accessType` = 'write' AND `user_user`.`targetUserID` = ?) OR (`group`.`userID` = ?))";
      $args = array($groupID, $_SESSION['userID'], $_SESSION['userID']);
   }
   $stmt = $db->prepare($query);
   $stmt->execute($args);
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_inexistent_group")."(totalScores.php)"));
      exit;
   }
   if (!intval($row->showSolutions) && (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"])) {
      echo json_encode((object)array("status" => 'error', "message" => "Vous ne pouvez pas évaluer les soumissions d'un groupe correspondant à un concours en cours."));
      exit;
   }
   $bonusScore = intval($row->bonusScore);

   $query = "UPDATE `team` SET `endTime` = UTC_TIMESTAMP() WHERE `groupID` = ? AND `endTime` IS NULL AND TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) > ?";
   $stmt = $db->prepare($query);
   $nbSeconds = intval($row->nbMinutes) * 60;
   $stmt->execute(array($groupID, $nbSeconds));

   $query = "SELECT IFNULL(SUM(IFNULL(`team_question`.`score`,0)),0) + ".$bonusScore." as `teamScore`,
      `team`.`ID` as `teamID`, team.password as teamPassword
      FROM `team`
      LEFT JOIN `team_question` ON (`team`.`ID` = `team_question`.`teamID`)
      WHERE `team`.`endTime` IS NOT NULL
      AND `team`.`groupID` = ?
      GROUP BY `team`.`ID`
      ORDER BY `team`.`ID`;";

   $stmt = $db->prepare($query);
   $stmt->execute(array($groupID));
   $teams = $stmt->fetchAll();
   foreach($teams as $team) {
      $sqlScore = $team['teamScore'];
      $dynamoDBScore = 0;
      if ($config->db->use == 'dynamoDB') {
         $dynamoDBScore = $bonusScore + getDynamoDBScore($team['teamID']);
      }
      $score = ($sqlScore >= $dynamoDBScore) ? $sqlScore : $dynamoDBScore;
      $query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ?;";
      $stmtUpdate = $db->prepare($query);
      $stmtUpdate->execute(array($score, $team['teamID']));
   }

   echo json_encode(array(
       'status' => 'success',
       'finished' => true
   ));
}
