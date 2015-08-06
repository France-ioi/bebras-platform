<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");

initSession();

if (!isset($_SESSION["teamID"])) {
   echo "team not logged";
   exit;
}
if (!isset($_SESSION["closed"])) {
   echo "contest is not over (scores)!";
   exit;
}
$teamID = $_SESSION["teamID"];
$query = "SELECT `contest`.`ID`, `contest`.`folder`, `group`.`participationType` FROM `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
if (!($row = $stmt->fetchObject())) {
   echo "contestID inconnu";
   exit;
}

if ($row->participationType == 'Official') {
   echo json_encode(array("status"  => "success"));

//   echo 'Participation officielle. Calcul du score impossible';
   exit;
}

$contestID = $row->ID;

$response = array('status' => 'failed');
if (isset($_POST['scores'])) {
   // Loop through all questions of the contest
   $query = "SELECT `team_question`.`questionID`, `team_question`.`answer`, `question`.`key`, `contest_question`.`minScore`, `contest_question`.`noAnswerScore`, `contest_question`.`maxScore`, `contest_question`.`options` FROM `team_question` JOIN `question` ON (`team_question`.`questionID` = `question`.`ID`) JOIN `contest_question` ON (`contest_question`.`questionID` = `question`.`ID`) WHERE `contest_question`.`contestID` = ? AND `team_question`.`teamID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID, $teamID));
   
   $teamScore = $_SESSION["bonusScore"];
   while ($row = $stmt->fetchObject()) {
      if (isset($_POST['scores'][$row->key]) && isset($_POST['scores'][$row->key]['score'])) {
         $curScore = (int)$_POST['scores'][$row->key]['score'];
         if ($curScore >= $row->minScore && $curScore <= $row->maxScore) {
            // Update the score in DB
            $stmtUpdate = $db->prepare("UPDATE `team_question` SET `score` = ? WHERE `team_question`.`questionID`= ? AND `team_question`.`teamID` = ?");
            $stmtUpdate->execute(array($curScore, $row->questionID, $teamID));
            $teamScore += $curScore;
         }
         else {
            $response['status'] = 'score_error';
         }
      }
      else {
         $response['status'] = 'score_not_available';
      }
   }
   
   // Update the team score in DB
   $query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ? AND `team`.`score` IS NULL";
   $stmt = $db->prepare($query);
   $stmt->execute(array($teamScore, $teamID));
   
   $response['status'] = 'success';
}

echo json_encode($response);
