<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");

header("Content-Type: application/json");
header("Connection: close");

initSession();

if (!isset($_SESSION["teamID"])) {
   echo "team not logged";
   exit;
}
if (!isset($_SESSION["closed"])) {
   echo "contest is not over (eval)!";
   exit;
}
$teamID = $_SESSION["teamID"];
$query = "SELECT `contest`.`ID`, `contest`.`folder`, `contest`.`bonusScore` FROM `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
if (!($row = $stmt->fetchObject())) {
   echo "contestID inconnu";
   exit;
}
$contestID = $row->ID;
$contestFolder = $row->folder;
$contestBonusScore = $row->bonusScore;

$answerTypes = array(
   array("score" => "`contest_question`.`maxScore`", "condition" => "`team_question`.`answer` = `question`.`expectedAnswer`"),
   array("score" => "`contest_question`.`minScore`", "condition" => "(`team_question`.`answer` <> `question`.`expectedAnswer` AND `team_question`.`answer` <> '')"),
   array("score" => "`contest_question`.`noAnswerScore`", "condition" => "`team_question`.`answer` = ''")
   );

foreach($answerTypes as $answerType) {
   $score = $answerType["score"];
   $condition = $answerType["condition"];
   $query = "UPDATE `team_question`, `question`, `contest_question` SET `team_question`.`score` = ".$score." WHERE `team_question`.`questionID` = `question`.`ID` AND ".$condition." AND (`question`.`answerType` = 0 OR `question`.`answerType` = 1) AND `team_question`.`teamID` = ? AND `contest_question`.`contestID` = ? AND `contest_question`.`questionID` = `team_question`.`questionID`";

   $stmt = $db->prepare($query);
   $stmt->execute(array($teamID, $contestID));
}
require_once("contestInterface/contests/".$contestFolder."/contest_".$contestID."_eval.php");

$query = "SELECT `team_question`.`questionID`, `team_question`.`answer`, `question`.`key`, `contest_question`.`minScore`, `contest_question`.`noAnswerScore`, `contest_question`.`maxScore` FROM `team_question` LEFT JOIN `question` ON (`team_question`.`questionID` = `question`.`ID`) LEFT JOIN `contest_question` ON (`contest_question`.`questionID` = `question`.`ID`) WHERE `contest_question`.`contestID` = ? AND `question`.`answerType` = 2 AND `team_question`.`teamID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($contestID, $teamID));
$stmtUpdate = $db->prepare("UPDATE `team_question` SET `score` = ? WHERE `team_question`.`questionID`= ? AND `team_question`.`teamID` = ?");
while ($row = $stmt->fetchObject()) {
  $evalFn = "eval_".str_replace("-", "_", $row->key);
  if ($row->answer == "") {
     $score = $row->noAnswerScore;
  } else {
     $score = $evalFn($row->answer, $row->minScore, $row->maxScore);
  }
  $stmtUpdate->execute(array($score, $row->questionID, $teamID));
}

$query = "SELECT SUM(`team_question`.`score`) + ".$contestBonusScore." as `teamScore` FROM `team_question` WHERE `team_question`.`teamID` = ? GROUP BY `team_question`.`teamID`";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
$teamScore = $contestBonusScore;
if ($row = $stmt->fetchObject()) {
   $teamScore = $row->teamScore;
}
$query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($teamScore, $teamID));

$query = "SELECT `team`.`score`, `question`.`key`, `team_question`.`score` as `questionScore`, `contest_question`.`maxScore` FROM `team`, `contest_question` LEFT JOIN `question` ON  (`contest_question`.`questionID` = `question`.`ID`) LEFT JOIN `team_question` ON (`team_question`.`questionID` = `question`.`ID` AND `team_question`.`teamID` = ?)  WHERE `team`.`ID` = ? AND `contest_question`.`contestID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID, $teamID, $contestID));
$scores = array();
$teamScore = $contestBonusScore;
$maxTeamScore = $contestBonusScore;
while ($row = $stmt->fetchObject()) {
   $teamScore = $row->score;
   if ($row->key != "") {
      $scores[$row->key] = array("score" => $row->questionScore, "maxScore" => $row->maxScore);
      if ($scores[$row->key]["score"] === null) {
         $scores[$row->key]["score"] = "0";
      }
   }
   $maxTeamScore += $row->maxScore;
}
echo(json_encode(array("teamScore" => $teamScore, "maxTeamScore" => $maxTeamScore, "scores" => (object)$scores)));

unset($_SESSION["contestID"]);
unset($_SESSION["contestFolder"]);
unset($_SESSION["groupID"]);
unset($_SESSION["teamID"]);
unset($_SESSION["closed"]);
unset($_SESSION["nbMinutes"]);
unset($_SESSION["bonusScore"]);
unset($_SESSION["startTime"]);

unset($db);

?>