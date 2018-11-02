<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

$queryQuestions = "
   SELECT DISTINCT
   question.ID as questionID,
   question.name as questionName
   FROM `group`
   JOIN `contest_question` ON (`group`.`contestID` = `contest_question`.`contestID`)
   JOIN `question` ON (`question`.`ID` = `contest_question`.`questionID`)
   WHERE 1 = 1
";

$query = "
   SELECT 
   `contestant`.`ID`,
   `contestant`.`lastName`,
   `contestant`.`firstName`,
   `contestant`.`genre`,
   `team`.`ID` as `teamID`,
   `team`.`startTime`,
   `team`.`endTime`,
   `team`.`score` as totalScore,
   `group`.`name` as groupName,
   `question`.`ID` as questionID,
   `team_question`.`score` as questionScore
   FROM `group`
   JOIN `team` ON (team.groupID = `group`.`ID`)
   JOIN `contestant` ON (`contestant`.`teamID` = `team`.`ID`)
   JOIN `contest_question` ON (`group`.`contestID` = `contest_question`.`contestID`)
   JOIN `question` ON (`question`.`ID` = `contest_question`.`questionID`)
   JOIN `team_question` ON (`team_question`.`questionID` = `question`.`ID` AND `team_question`.`teamID` = `team`.`ID`)
   WHERE 1 = 1
";
$params = array();

// If a group is specified, restrict it to this group
if (isset($_GET["groupID"])) {
   $query .= " AND `group`.`ID` = :groupID";
   $queryQuestions .= " AND `group`.`ID` = :groupID";
   $params["groupID"] = $_GET["groupID"];
} else if (isset($_GET["contestID"])) {
   $query .= " AND `group`.`contestID` = :contestID";
   $queryQuestions .= " AND `group`.`contestID` = :contestID";
   $params["contestID"] = $_GET["contestID"];
}

// If not admin, only allow access to the correct user
if (!$_SESSION["isAdmin"]) {
   $query .= " AND `group`.`userID` = :userID";
   $queryQuestions .= " AND `group`.`userID` = :userID";
   $params["userID"] = $_SESSION["userID"];
}

// Choose order
$query .= " ORDER BY `contestant`.`ID`";


$stmt = $db->prepare($queryQuestions);
$stmt->execute($params);

$output = fopen('php://output', 'w');
header( 'Content-Type: text/csv' );
header( 'Content-Disposition: attachment;filename=test.csv');

$fields = array("ID" , translate("export_lastName"), translate("export_firstName"), translate("export_gender"), translate("export_group"), "teamID", translate("export_begin"), translate("export_end"), translate("export_total_score"));
$questions = array();
$questionsRanks = array();
$rank = 0;
while ($row = $stmt->fetchObject()) {
   $questions[] = $row;
   $questionsRanks[$row->questionID] = $rank;
   $fields[] = $row->questionName;
   $rank++;
}
fputcsv($output, $fields);

$stmt = $db->prepare($query);
$stmt->execute($params);

$nbFields = count($fields);
$contestantID = 0;
$contestant = null;
while ($row = $stmt->fetchObject()) {
   if ($contestantID != $row->ID) {
      if ($contestantID != 0) {
         fputcsv($output, $contestant);
      }
      $contestantID = $row->ID;
      $contestant = array($row->ID, $row->lastName, $row->firstName, $row->genre, $row->groupName, $row->teamID, $row->startTime, $row->endTime, $row->totalScore);
      foreach ($questions as $rank => $question) {
         $contestant[$rank + $nbFields] = "NR";
      }
   }
   $contestant[$questionsRanks[$row->questionID] + $nbFields] = $row->questionScore;
}
fputcsv($output, $contestant);
