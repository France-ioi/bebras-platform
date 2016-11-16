<?php

require_once "../shared/common.php";
require_once "common_contest.php";

initSession();

if (!isset($_SESSION["teamID"])) {
   exitWithJsonFailure("error_invalid_session");
}
if (!isset($_SESSION["closed"])) {
   exitWithJsonFailure("error_contest_not_over");
}
$teamID = $_SESSION["teamID"];
$query = "SELECT `contest`.`ID`, `contest`.`folder`, `group`.`participationType` FROM `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?;";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
if (!($row = $stmt->fetchObject())) {
   exitWithJsonFailure("error_invalid_session");
}

if (isset($_POST['scores'])) {
   $teamScore = intval($_SESSION["bonusScore"]);
   foreach ($_POST['scores'] as $key => $score) {
      $teamScore += intval($score['score']);
   }
   // Update the team score in DB
   $query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ? AND `team`.`score` IS NULL;";
   $stmt = $db->prepare($query);
   $stmt->execute(array($teamScore, $teamID));
   
   exitWithJson(['success' => true]);
} else {
   exitWithJsonFailure("error_invalid_action");
}
