<?php

require_once("../shared/common.php");

header("Content-Type: application/json");
header("Connection: close");

initSession();

if (!isset($_SESSION["teamID"])) {
   echo json_encode(['status' => 'fail', 'error' => "team not logged"]);
   exit;
}
if (!isset($_SESSION["closed"])) {
   echo json_encode(['status' => 'fail', 'error' => "contest is not over (scores)!"]);
   exit;
}
$teamID = $_SESSION["teamID"];
$query = "SELECT `contest`.`ID`, `contest`.`folder`, `group`.`participationType` FROM `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?;";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
if (!($row = $stmt->fetchObject())) {
   echo json_encode(['status' => 'fail', 'error' => "invalid teamID"]);
   exit;
}

$response = array('status' => 'failed');
if (isset($_POST['scores'])) {
   $teamScore = intval($_SESSION["bonusScore"]);
   foreach ($_POST['scores'] as $key => $score) {
      $teamScore += intval($score['score']);
   }
   // Update the team score in DB
   $query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ? AND `team`.`score` IS NULL;";
   $stmt = $db->prepare($query);
   $stmt->execute(array($teamScore, $teamID));
   
   echo json_encode(['status' => 'success']);
} else {
   echo json_encode(['status' => 'fail', 'error' => "missing scores"]);
}
