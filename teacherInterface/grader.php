<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   if (!isset($_POST['groupID']) || !$_POST['groupID']) {
      echo json_encode((object)array("status" => 'error', "message" => "Seul un admin peut évaluer les scores d'un concours"));
      exit;
   } else if (!isset($_SESSION["userID"]) || !$_SESSION["userID"]) {
      echo json_encode((object)array("status" => 'error', "message" => "Vous n'êtes pas loggé"));
      exit;
   }
}

$contestID = isset($_REQUEST['contestID']) ? $_REQUEST['contestID'] : null;
$groupID = isset($_REQUEST['groupID']) ? $_REQUEST['groupID'] : null;
$questionKey = $_REQUEST['questionKey'];
$contestFolder = null;
$contestYear = null;

$query = "UPDATE `team` SET `endTime` = UTC_TIMESTAMP() WHERE `endTime` IS NULL AND TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) > 3600";
$stmt = $db->prepare($query);
$stmt->execute(array());

if ($contestID != null) {
   // Check contest existance
   $query = "SELECT `ID`, `folder`, `year` FROM `contest` WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => "Le concours n'existe pas"));
      exit;
   }
   $contestFolder = $row->folder;
   $contestYear = $row->year;
} else {
   // Check contest existance
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
   $contestFolder = $row->folder;
   $contestID = $row->contestID;
   $contestYear = $row->year;
}


// Check questionKey existence
$query = 'SELECT `question`.`key`, `contest_question`.`minScore`, `contest_question`.`noAnswerScore`, `contest_question`.`maxScore`, `contest_question`.`options` FROM `question` LEFT JOIN `contest_question` ON (`question`.`ID` = `contest_question`.`questionID`) WHERE `contest_question`.`contestID` = ? AND `question`.`key` = ?';
$stmt = $db->prepare($query);
$stmt->execute(array($contestID, $questionKey));
$row = $stmt->fetchObject();
if (!$row) {
   echo json_encode((object)array("status" => 'error', "message" => "La question ".$questionKey." n'existe pas dans le concours sélectionné"));
   exit;
}

$teamQuestionTable = getTeamQuestionTableForGrading();
if (!$groupID) {
   $query = 'SELECT `'.$teamQuestionTable.'`.`teamID`, `'.$teamQuestionTable.'`.`questionID`, `'.$teamQuestionTable.'`.`answer` FROM `'.$teamQuestionTable.'` JOIN `question` ON (`'.$teamQuestionTable.'`.`questionID` = `question`.`ID`) JOIN `contest_question` ON (`contest_question`.`questionID` = `question`.`ID`) JOIN `team` ON (`team`.`ID`= `'.$teamQuestionTable.'`.`teamID`) JOIN `group` ON (`team`.`groupID` = `group`.`ID`) WHERE `contest_question`.`contestID` = ? AND `group`.`contestID` = ? AND `question`.`key` = ? AND (`'.$teamQuestionTable.'`.`score` IS NULL OR (`'.$teamQuestionTable.'`.`ffScore` is not null and `'.$teamQuestionTable.'`.`score` != `'.$teamQuestionTable.'`.`ffScore`));';
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID, $contestID, $questionKey));
} else {
   $query = 'SELECT `'.$teamQuestionTable.'`.`teamID`, `'.$teamQuestionTable.'`.`questionID`, `'.$teamQuestionTable.'`.`answer` FROM `'.$teamQuestionTable.'` JOIN `question` ON (`'.$teamQuestionTable.'`.`questionID` = `question`.`ID`) JOIN `contest_question` ON (`contest_question`.`questionID` = `question`.`ID`) JOIN `team` ON (`team`.`ID`= `'.$teamQuestionTable.'`.`teamID`) WHERE `contest_question`.`contestID` = ? AND `team`.`groupID` = ? AND `question`.`key` = ? AND (`'.$teamQuestionTable.'`.`score` IS NULL OR (`'.$teamQuestionTable.'`.`ffScore` is not null and `'.$teamQuestionTable.'`.`score` != `'.$teamQuestionTable.'`.`ffScore`));';
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID, $groupID, $questionKey));
}

$teamQuestions = array();
while ($teamQuestion = $stmt->fetchObject()) {
   $teamQuestions[] = array(
       'questionID' => $teamQuestion->questionID,
       'answer' => $teamQuestion->answer,
       'teamID' => $teamQuestion->teamID
   );
}

echo json_encode(array(
   'status' => 'success',
   'questionKey' => $questionKey,
   'contestYear' => $contestYear,
   'teamQuestions' => $teamQuestions,
   'minScore' => intval($row->minScore),
   'noAnswerScore' => intval($row->noAnswerScore),
   'maxScore' => intval($row->maxScore),
   'options' => json_decode(html_entity_decode($row->options))
));
