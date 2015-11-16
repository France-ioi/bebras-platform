<?php 

require_once("../shared/common.php");

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
$query = "SELECT `contest`.`ID`, `contest`.`folder`, `group`.`participationType` FROM `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
if (!($row = $stmt->fetchObject())) {
   echo json_encode(['status' => 'fail', 'error' => "invalid teamID"]);
   exit;
}

if ($row->participationType == 'Official') {
   // no score computation for official contests
   echo json_encode(array("status"  => "success"));
   exit;
}

$response = array('status' => 'failed');
if (isset($_POST['scores'])) {
   $teamScore = intval($_SESSION["bonusScore"]);
   foreach ($_POST['scores'] as $key => $score) {
      $teamScore += intval($score['score']);
   }
   echo $teamID;
   // Update the team score in DB
   $query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($teamScore, $teamID));
   
   $response['status'] = 'success';
}

echo json_encode($response);
