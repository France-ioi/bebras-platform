<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../shared/tinyORM.php");

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
$questionKey = $_REQUEST['questionKey'];
$onlyMarked = isset($_REQUEST['onlyMarked']) ? $_REQUEST['onlyMarked'] : false;
$contestFolder = null;

// Commented it as I don't think it can do any good...
// $query = "UPDATE `team` SET `endTime` = UTC_TIMESTAMP() WHERE `endTime` IS NULL AND TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) > 3600";
// $stmt = $db->prepare($query);
// $stmt->execute(array());

if ($contestID != null) {
   // Check contest existance
   $query = "SELECT `ID`, `folder` FROM `contest` WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_inexistent_contest")));
      exit;
   }
   $contestFolder = $row->folder;
} else {
   // Check group existance
   $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder` as `folder`, `contest`.`showSolutions` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` WHERE `group`.`ID` = ?";
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
   $contestFolder = $row->folder;
   $contestID = $row->contestID;
}

// Check questionKey existence
$query = 'SELECT `question`.`ID` as `questionID`, `question`.`key`, `contest_question`.`minScore`, `contest_question`.`noAnswerScore`, `contest_question`.`maxScore`, `contest_question`.`options` FROM `question` LEFT JOIN `contest_question` ON (`question`.`ID` = `contest_question`.`questionID`) WHERE `contest_question`.`contestID` = ? AND `question`.`key` = ?';
$stmt = $db->prepare($query);
$stmt->execute(array($contestID, $questionKey));
$row = $stmt->fetchObject();
if (!$row) {
   echo json_encode((object)array("status" => 'error', "message" => sprintf(translate("grader_inexistent_question"), $questionKey)));
   exit;
}

$questionID = $row->questionID;

$teamQuestionTable = getTeamQuestionTableForGrading();
$teamQuestions = array();
$checkStatus = "none";
if ($onlyMarked) {
   $checkStatus = "requested";
}
if (!$groupID) {
   $query = 'SELECT `'.$teamQuestionTable.'`.`teamID`, `'.$teamQuestionTable.'`.`questionID`, `'.$teamQuestionTable.'`.`answer` '.
	   'FROM `'.$teamQuestionTable.'` '.
	   'JOIN `contest_question` ON (`contest_question`.`questionID` = `'.$teamQuestionTable.'`.`questionID`) '.
	   'JOIN `team` ON (`team`.`ID`= `'.$teamQuestionTable.'`.`teamID`) '.
	   'JOIN `group` ON (`team`.`groupID` = `group`.`ID`) '.
	   'WHERE `contest_question`.`contestID` = ? AND `group`.`contestID` = ? '.
	   'AND `'.$teamQuestionTable.'`.`questionID` = ? '.
	   'AND `'.$teamQuestionTable.'`.`score` IS NULL '.
      'AND `'.$teamQuestionTable.'`.`checkStatus` = \''.$checkStatus.'\' LIMIT 0,10000';
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID, $contestID, $questionID));
   while ($teamQuestion = $stmt->fetchObject()) {
      $teamQuestions[] = array(
          'questionID' => $teamQuestion->questionID,
          'answer' => $teamQuestion->answer,
          'teamID' => $teamQuestion->teamID
      );
   }
} else {
   // always get SQL answers:
   $query = 'SELECT `'.$teamQuestionTable.'`.`teamID`, `'.$teamQuestionTable.'`.`questionID`, `'.$teamQuestionTable.'`.`answer` FROM `'.$teamQuestionTable.'` JOIN `question` ON (`'.$teamQuestionTable.'`.`questionID` = `question`.`ID`) JOIN `contest_question` ON (`contest_question`.`questionID` = `question`.`ID`) JOIN `team` ON (`team`.`ID`= `'.$teamQuestionTable.'`.`teamID`) WHERE `contest_question`.`contestID` = ? AND `team`.`groupID` = ? AND `question`.`key` = ? AND (`'.$teamQuestionTable.'`.`score` IS NULL OR (`'.$teamQuestionTable.'`.`ffScore` is not null and `'.$teamQuestionTable.'`.`score` != `'.$teamQuestionTable.'`.`ffScore`)) LIMIT 0,1000;';
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID, $groupID, $questionKey));
   $seenAnswers = [];
   while ($teamQuestion = $stmt->fetchObject()) {
      $seenAnswers[$teamQuestion->teamID.'-'.$teamQuestion->questionID] = true;
      $teamQuestions[] = array(
          'questionID' => $teamQuestion->questionID,
          'answer' => $teamQuestion->answer,
          'teamID' => $teamQuestion->teamID
      );
   }
   // if we use dynamodb, add answers we haven't seen yet:
   if ($config->db->use == 'dynamoDB') {
      $query = 'SELECT team.ID FROM team WHERE `team`.`groupID` = ? LIMIT 0,1000';
      $stmt = $db->prepare($query);
      $stmt->execute(array($groupID));
      while ($teamID = $stmt->fetchColumn()) {
         if (isset($seenAnswers[$teamID.'-'.$questionID])) {
            // TODO: select sql or dynamodb answer according to most recent date
            continue;
         }
         try {
            $teamQuestion = $tinyOrm->get('team_question', array('answer', 'ffScore', 'score'), array('teamID' =>$teamID, 'questionID' => $questionID));
            if ($teamQuestion && $teamQuestion['answer'] && ($teamQuestion['score'] == null || ($teamQuestion['ffScore'] && $teamQuestion['score'] != $teamQuestion['ffScore']))) {
               $teamQuestions[] = array(
                  'questionID' => $questionID,
                  'answer' => $teamQuestion['answer'],
                  'teamID' => $teamID
               );
            }
         } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
            if (strval($e->getAwsErrorCode()) != 'ConditionalCheckFailedException') {
               error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
               error_log($e->getMessage());
               error_log('DynamoDB error retrieving team_questions for teamID: '.$teamID.', questionID: '.$questionID);
            }
         }
      }
   }
}

echo json_encode(array(
   'status' => 'success',
   'questionKey' => $questionKey,
   'teamQuestions' => $teamQuestions,
   'minScore' => intval($row->minScore),
   'noAnswerScore' => intval($row->noAnswerScore),
   'maxScore' => intval($row->maxScore),
   'options' => json_decode(html_entity_decode($row->options))
));
