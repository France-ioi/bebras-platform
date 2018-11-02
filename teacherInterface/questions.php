<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

$questionKeys = array();
$contestID = null;

if (isset($_REQUEST["contestID"]) && $_REQUEST['contestID']) {
   if ((!isset($_SESSION["isAdmin"])) || (!$_SESSION["isAdmin"])) {
      echo json_encode((object)array("status" => 'error', "message" => translate("admin_restricted")));
      exit;
   }
   $contestID = $_REQUEST["contestID"];
   $query = "SELECT `contest`.`ID`, `contest`.`folder` FROM `contest` WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($contestID));
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => "This contest doesn't exist"));
      exit;
   }
} else if (isset($_REQUEST["groupID"]) && $_REQUEST['groupID']) {
   $groupID = $_REQUEST["groupID"];
   $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder`, `contest`.`showSolutions` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` WHERE `group`.`ID` = ?";
   $args = array($groupID);
   if ((!isset($_SESSION["isAdmin"])) || (!$_SESSION["isAdmin"])) {
      if (!isset($_SESSION['userID'])) {
         echo json_encode((object)array("status" => 'error', "message" => translate("session_expired")));
         exit;
      } else {
         $query = "SELECT `group`.`ID`, `contest`.`ID` as `contestID`, `contest`.`folder`, `contest`.`showSolutions` FROM `group` JOIN `contest` on `group`.`contestID` = `contest`.`ID` LEFT JOIN `user_user` on `group`.`userID` = `user_user`.`userID` WHERE `group`.`ID` = ? and ((`user_user`.`accessType` = 'write' AND `user_user`.`targetUserID` = ?) OR (`group`.`userID` = ?))";
         $args = array($groupID, $_SESSION['userID'], $_SESSION['userID']);
      }
   }
   $stmt = $db->prepare($query);
   $stmt->execute($args);
   $row = $stmt->fetchObject();
   if (!$row) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_inexistent_group")."(questions.php)"));
      exit;
   }
   if (!intval($row->showSolutions) && (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"])) {
      echo json_encode((object)array("status" => 'error', "message" => translate("grader_contest_running")."(questions.php)"));
      exit;
   }
   $contestID = $row->contestID;
}

$query = 'SELECT `question`.`key`, `question`.`path` FROM `question` JOIN `contest_question` ON (`question`.`ID` = `contest_question`.`questionID`) WHERE `contest_question`.`contestID` = ? ORDER BY contest_question.`order` ASC;';
$stmt = $db->prepare($query);
$stmt->execute(array($contestID));
$questionKeys = array();
$questionPaths = array();
while ($question = $stmt->fetchObject()) {
   $questionKeys[] = $question->key;
   $questionPaths[] = $question->path;
}

echo json_encode(array(
   'status' => 'success',
   'questionKeys' => $questionKeys,
   'questionPaths' => $questionPaths
));
