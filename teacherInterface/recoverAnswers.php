<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

include_once("../shared/common.php");

$data = $_REQUEST["data"];
$pwd = $data["pwd"];
$ans = $data["ans"];

$stmt = $db->prepare("SELECT `team`.`ID` FROM `team` WHERE `password` = ?");
$stmt->execute(array($pwd));
if ($row = $stmt->fetchObject()) {
   $stmtInsert = $db->prepare("INSERT IGNORE INTO `team_question_recover` (`teamID`, `questionID`, `answer`) VALUES (?, ?, ?)");
   $stmtUpdate = $db->prepare("UPDATE `team_question_recover` SET `answer` = ? WHERE `teamID` = ? AND `questionID` = ?");
   $stmtReset = $db->prepare("UPDATE `team` SET `score` = NULL WHERE ID = ?");
   $teamID = $row->ID;
   foreach ($ans as $answer) {
      $stmtInsert->execute(array($teamID, $answer[0], $answer[1]));
      $stmtUpdate->execute(array($answer[1], $teamID, $answer[0]));
      $stmtReset->execute(array($teamID));
   }
   echo json_encode(array("success" => true, "message" => "Team ".$teamID.", ".count($ans)." réponses enregistrées"));
} else {
   echo json_encode(array("success" => false));
}


unset($db);

?>