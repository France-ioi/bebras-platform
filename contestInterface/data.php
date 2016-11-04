<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

include_once("../shared/common.php");
include_once("../shared/tinyORM.php");
include_once("common_contest.php");

function getGroupTeams($db, $groupID) {
   $stmt = $db->prepare("SELECT `team`.`ID`, `contestant`.`lastName`, `contestant`.`firstName` FROM `contestant` LEFT JOIN `team` ON `contestant`.`teamID` = `team`.`ID` WHERE `team`.`groupID` = ?");
   $stmt->execute(array($groupID));
   $teams = array();
   while ($row = $stmt->fetchObject()) {
      if (!isset($teams[$row->ID])) {
         $teams[$row->ID] = (object)array("nbContestants" => 0, "contestants" => array());
      }
      $teams[$row->ID]->nbContestants++;
      $contestant = (object)array("lastName" => htmlentities(utf8_decode($row->lastName)), "firstName" => htmlentities(utf8_decode($row->firstName)));
      $teams[$row->ID]->contestants[] = $contestant;
   }
   return $teams;
}

function reloginTeam($db, $password, $teamID) {
   global $tinyOrm, $config;
   $stmt = $db->prepare("SELECT `group`.`password`, `contest`.`status`, `group`.`isPublic` FROM `group` JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `group`.`ID` = ?");
   $stmt->execute(array($_SESSION["groupID"]));
   $row = $stmt->fetchObject();
   if (!$row) {
      exitWithJsonFailure("Groupe invalide");
   }
   if ($row->password !== $password) {
      exitWithJsonFailure("Mot de passe invalide");
   }
   if ($row->status == "Closed" || $row->status == "PreRanking") {
      exitWithJsonFailure("Concours fermé");
   }
   $stmt = $db->prepare("SELECT `password`, `nbMinutes` FROM `team` WHERE `ID` = ? AND `groupID` = ?");
   $stmt->execute(array($teamID, $_SESSION["groupID"]));
   $row = $stmt->fetchObject();
   if (!$row) {
      exitWithJsonFailure("Équipe invalide pour ce groupe");
   }
   if ($config->db->use == 'dynamoDB') {
      try {
         $teamDynamoDB = $tinyOrm->get('team', array('ID', 'groupID', 'nbMinutes'), array('ID' => $teamID));
      } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error retrieving: '.$teamID);
      }
      if (!count($teamDynamoDB) || $teamDynamoDB['groupID'] != $_SESSION["groupID"]) {
         error_log('team.groupID différent entre MySQL et DynamoDB! nb résultats DynamoDB: '.count($teamDynamoDB).(count($teamDynamoDB) ? ', $teamDynamoDB[groupID]'.$teamDynamoDB['groupID'].', $_SESSION[groupID]'.$_SESSION["groupID"] : ''));
      }
   }
   $_SESSION["teamID"] = $teamID;
   $_SESSION["teamPassword"] = $row->password;
   $_SESSION["nbMinutes"] = intval($row->nbMinutes);
}

function getRandomID() {
   $rand = (string) mt_rand(100000, 999999999);
   $rand .= (string) mt_rand(1000000, 999999999);
   return $rand;
}

function handleLoadPublicGroups($db) {
   addBackendHint("ClientIP.loadPublicGroups");
   $stmt = $db->prepare("SELECT `group`.`name`, `group`.`code`, `contest`.`year`, `contest`.`category`, `contest`.`level` ".
      "FROM `group` JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `isPublic` = 1 AND `contest`.`visibility` <> 'Hidden';");
   $stmt->execute(array());
   $groups = array();
   while ($row = $stmt->fetchObject()) {
      $groups[] = $row;
   }
   exitWithJson(array("success" => true, "groups" => $groups));
}

function handleCreateTeam($db) {
   global $tinyOrm, $config;
   if (!isset($_POST["contestants"])) {
      exitWithJsonFailure("Informations sur les candidats manquantes");
   }
   if (!isset($_SESSION["groupID"])) {
      exitWithJsonFailure("Groupe non chargé");
   }
   if ($_SESSION["groupClosed"]) {
      error_log("Hack attempt ? trying to create team on closed group ".$_SESSION["groupID"]);
      exitWithJsonFailure("Groupe fermé");
   }
   // $_SESSION['userCode'] is set by optional password handling function,
   // see comments of createTeamFromUserCode in common_contest.php.
   $groupID = $_SESSION["groupID"];
   if (isset($_SESSION["userCode"]) && isset($_SESSION["userCodeGroupID"]) && $_SESSION["userCodeGroupID"] == $groupID) {
      $password = $_SESSION["userCode"];
      unset($_SESSION["userCode"]);
      unset($_SESSION["userCodeGroupID"]);
   } else {
      $password = genAccessCode($db);
   }
   $teamID = getRandomID();
   $stmt = $db->prepare("INSERT INTO `team` (`ID`, `groupID`, `password`, `nbMinutes`) VALUES (?, ?, ?, ?)");
   $stmt->execute(array($teamID, $groupID, $password, $_SESSION["nbMinutes"]));
   if ($config->db->use == 'dynamoDB') {
      try {
         $tinyOrm->insert('team', array(
            'ID'       => $teamID,
            'groupID'  => $groupID,
            'password' => $password,
            'nbMinutes' => $_SESSION["nbMinutes"]
         ));
      } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error creating team, teamID: '.$teamID);
      }
   }

   $contestants = $_POST["contestants"];
   $stmt = $db->prepare("UPDATE `group` SET `startTime` = UTC_TIMESTAMP() WHERE `group`.`ID` = ? AND `startTime` IS NULL");
   $stmt->execute(array($groupID));
   $stmt = $db->prepare("UPDATE `group` SET `nbTeamsEffective` = `nbTeamsEffective` + 1, `nbStudentsEffective` = `nbStudentsEffective` + ? WHERE `ID` = ?");
   $stmt->execute(array(count($contestants), $groupID));

   $_SESSION["teamID"] = $teamID;
   $_SESSION["teamPassword"] = $password;
   foreach ($contestants as $contestant) {
      if (!isset($contestant["grade"])) {
         $contestant["grade"] = -2;
      }
      if (!isset($contestant["genre"])) {
         $contestant["genre"] = 0;
      }
      if (!isset($contestant["studentId"])) {
         $contestant["studentId"] = "";
      }
      list($contestant["firstName"], $contestant["lastName"], $saniValid, $trash) =
         DataSanitizer::formatUserNames($contestant["firstName"], $contestant["lastName"]);
      $stmt = $db->prepare("
         INSERT INTO `contestant` (`ID`, `lastName`, `firstName`, `genre`, `grade`, `studentId`, `teamID`, `cached_schoolID`, `saniValid`, `email`, `zipCode`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute(array(getRandomID(), $contestant["lastName"], $contestant["firstName"], $contestant["genre"], $contestant["grade"], $contestant["studentId"], $teamID, $_SESSION["schoolID"], $saniValid, $contestant["email"], $contestant["zipCode"]));
   }
   addBackendHint(sprintf("ClientIP.createTeam:%s", $_SESSION['isPublic'] ? 'public' : 'private'));
   addBackendHint(sprintf("Group(%s):createTeam", escapeHttpValue($groupID)));
   exitWithJson((object)array("success" => true, "teamID" => $teamID, "password" => $password));
}

function handleLoadContestData($db) {
   global $tinyOrm, $config;
   if (!isset($_SESSION["teamID"])) {
      if (!isset($_POST["groupPassword"])) {
         exitWithJsonFailure("Mot de passe manquant");
      }
      if (!isset($_POST["teamID"])) {
         exitWithJsonFailure("Équipe manquante");
      }
      if (!isset($_SESSION["groupID"])) {
         exitWithJsonFailure("Groupe non chargé");
      }
      $password = strtolower($_POST["groupPassword"]);
      reloginTeam($db, $password, $_POST["teamID"]);
   }
   $teamID = $_SESSION["teamID"];
   $stmt = $db->prepare("UPDATE `team` SET `startTime` = UTC_TIMESTAMP() WHERE `ID` = :teamID AND `startTime` IS NULL");
   $stmt->execute(array("teamID" => $teamID));
   if ($config->db->use == 'dynamoDB') {
      $stmt = $db->prepare("SELECT `startTime` FROM `team` WHERE `ID` = :teamID");
      $stmt->execute(array("teamID" => $teamID));
      $row = $stmt->fetchObject();
      try {
         $tinyOrm->update('team', array('startTime' => $row->startTime), array('ID'=>$teamID, 'startTime'=>null));
      } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error updating team for teamID: '.$teamID);
      }
   }
   $questionsData = getQuestions($db, $_SESSION["contestID"], $_SESSION["subsetsSize"], $teamID);
   //$stmt = $db->prepare("SELECT `questionID`, `answer` FROM `team_question` WHERE `teamID` = ?");
   //$stmt->execute(array($teamID));
   try {
      $results = $tinyOrm->select('team_question', array('questionID', 'answer', 'ffScore', 'score'), array('teamID' =>$teamID));
   } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
      if (strval($e->getAwsErrorCode()) != 'ConditionalCheckFailedException') {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error retrieving team_questions for teamID: '.$teamID);
      }
      $results = [];
   }
   $answers = array();
   $scores = array();
   //while ($row = $stmt->fetchObject()) {
   foreach($results as $row) {
      if (isset($row['answer'])) {
         $answers[$row['questionID']] = $row['answer'];
      }
      if (isset($row['score'])) {
         $scores[$row['questionID']] = $row['score'];
      } elseif (isset($row['ffScore'])) {
         $scores[$row['questionID']] = $row['ffScore'];
      }
   }
   $stmt = $db->prepare("SELECT TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as `timeUsed`, `endTime` FROM `team` WHERE `ID` = ?");
   $stmt->execute(array($teamID));
   $row = $stmt->fetchObject();
   $_SESSION["startTime"] = time() - intval($row->timeUsed);
   if ($row->endTime != null) {
      $_SESSION["closed"] = true;
   } else {
      unset($_SESSION["closed"]);
   }
   addBackendHint("ClientIP.loadContestData:pass");
   addBackendHint(sprintf("Team(%s):loadContestData", escapeHttpValue($teamID)));
   exitWithJson((object)array("success" => true, "questionsData" => $questionsData, 'scores' => $scores, "answers" => $answers, "timeUsed" => $row->timeUsed, "endTime" => $row->endTime, "teamPassword" => $_SESSION["teamPassword"]));
}

function handleCloseContest($db) {
   if (!isset($_SESSION["teamID"]) && !reconnectSession($db)) {
      exitWithJsonFailure("Pas de session en cours");
   }
   $teamID = $_SESSION["teamID"];
   $stmtUpdate = $db->prepare("UPDATE `team` SET `endTime` = UTC_TIMESTAMP() WHERE `ID` = ? AND `endTime` is NULL");
   $stmtUpdate->execute(array($teamID));
   $_SESSION["closed"] = true;
   $stmt = $db->prepare("SELECT `endTime` FROM `team` WHERE `ID` = ?");
   $stmt->execute(array($teamID));
   $row = $stmt->fetchObject();
   addBackendHint("ClientIP.closeContest:pass");
   addBackendHint(sprintf("Team(%s):closeContest", escapeHttpValue($teamID)));
   exitWithJson((object)array("success" => true, "endTime" => $row->endTime));
}

function handleLoadSession() {
   $sid = session_id();
   // If the session is new or closed, just return the SID.
   if (!isset($_SESSION["teamID"]) || isset($_SESSION["closed"])) {
      addBackendHint("ClientIP.loadSession:new");
      exitWithJson(['success' => true, "SID" => $sid]);
   }
   // Otherwise, data from the session is also returned.
   addBackendHint("ClientIP.loadSession:found");
   addBackendHint(sprintf("SessionId(%s):loadSession", escapeHttpValue($sid)));
   exitWithJson(array(
      "success" => true,
      "teamID" => $_SESSION["teamID"],
      "nbMinutes" => $_SESSION["nbMinutes"],
      "bonusScore" => $_SESSION["bonusScore"],
      "allowTeamsOfTwo" => $_SESSION["allowTeamsOfTwo"],
      "newInterface" => $_SESSION["newInterface"],
      "customIntro" => $_SESSION["customIntro"],
      "fullFeedback" => $_SESSION["fullFeedback"],
      "nbUnlockedTasksInitial" => $_SESSION["nbUnlockedTasksInitial"],
      "subsetsSize" => $_SESSION["subsetsSize"],
      "contestID" => $_SESSION["contestID"],
      "isPublic" => $_SESSION["isPublic"],
      "contestFolder" => $_SESSION["contestFolder"],
      "contestName" => $_SESSION["contestName"],
      "contestOpen" => $_SESSION["contestOpen"],
      "contestShowSolutions" => $_SESSION["contestShowSolutions"],
      "contestVisibility" => $_SESSION["contestVisibility"],
      "SID" => $sid));
}

function handleDestroySession() {
   $sid = session_id();
   addBackendHint("ClientIP.destroySession");
   restartSession();
   exitWithJson(array("success" => true, "SID" => $sid));
}

function handleCheckPassword($db) {
   addFailureBackendHint("ClientIP.checkPassword:fail");
   addFailureBackendHint("ClientIP.error");
   if (!isset($_POST["password"])) {
      exitWithJsonFailure("Mot de passe manquant");
   }
   $getTeams = array_key_exists('getTeams', $_POST) ? $_POST["getTeams"] : False;
   $password = strtolower($_POST["password"]);
   // Search for a group matching the entered password, and if found create
   // a team in that group (and end the request).
   handleCheckGroupPassword($db, $password, $getTeams);
   // If no matching group was found, look for a team with the entered password.
   handleCheckTeamPassword($db, $password);
}

function handleCheckGroupPassword($db, $password, $getTeams) {
   // Find a group whose code matches the given password.
   $query = "SELECT `group`.`ID`, `group`.`name`, `group`.`bRecovered`, `group`.`contestID`, `group`.`isPublic`, `group`.`schoolID`, `group`.`startTime`, TIMESTAMPDIFF(MINUTE, `group`.`startTime`, UTC_TIMESTAMP()) as `nbMinutesElapsed`,  `contest`.`nbMinutes`, `contest`.`bonusScore`, `contest`.`allowTeamsOfTwo`, `contest`.`newInterface`, `contest`.`customIntro`, `contest`.`fullFeedback`, `contest`.`nextQuestionAuto`, `contest`.`folder`, `contest`.`nbUnlockedTasksInitial`, `contest`.`subsetsSize`, `contest`.`open`, `contest`.`showSolutions`, `contest`.`visibility`, `contest`.`askEmail`, `contest`.`askZip`, `contest`.`askGenre`, `contest`.`askGrade`, `contest`.`askStudentId`, `contest`.`name` as `contestName` FROM `group` JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `code` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($password));
   $row = $stmt->fetchObject();
   if (!$row) {
      // No such group.
      return;
   }
   if ($row->open != "Open") {
      exitWithJson((object)array("success" => false, "message" => "Le concours de ce groupe n'est pas ouvert."));
   }
   $groupID = $row->ID;
   $schoolID = $row->schoolID;
   $contestID = $row->contestID;
   $contestFolder = $row->folder;
   $contestOpen = $row->open;
   $contestShowSolutions = $row->showSolutions;
   $contestVisibility = $row->visibility;
   $name = $row->name;
   $nbMinutes = $row->nbMinutes;
   $bonusScore = $row->bonusScore;
   $allowTeamsOfTwo = $row->allowTeamsOfTwo;
   $newInterface = $row->newInterface;
   $customIntro = $row->customIntro;
   $fullFeedback = $row->fullFeedback;
   $nextQuestionAuto = $row->nextQuestionAuto;
   $nbUnlockedTasksInitial = $row->nbUnlockedTasksInitial;
   $subsetsSize = $row->subsetsSize;
   $isPublic = $row->isPublic;
   if ($row->startTime === null) {
      $nbMinutesElapsed = 0;
   } else {
      $nbMinutesElapsed = $row->nbMinutesElapsed;
   }
   if ($getTeams === "true") {
      $teams = getGroupTeams($db, $groupID);
   } else {
      $teams = "";
   }
   $_SESSION["groupID"] = $groupID;
   $_SESSION["contestName"] = $row->contestName;
   $_SESSION["schoolID"] = $schoolID;
   $_SESSION["contestID"] = $contestID;
   $_SESSION["contestFolder"] = $contestFolder;
   $_SESSION["contestOpen"] = $contestOpen;
   $_SESSION["contestShowSolutions"] = $contestShowSolutions;
   $_SESSION["contestVisibility"] = $contestVisibility;
   $_SESSION["nbMinutes"] = $nbMinutes;
   $_SESSION["bonusScore"] = $bonusScore;
   $_SESSION["allowTeamsOfTwo"] = $allowTeamsOfTwo;
   $_SESSION["newInterface"] = $newInterface;
   $_SESSION["customIntro"] = $customIntro;
   $_SESSION["fullFeedback"] = $fullFeedback;
   $_SESSION["nextQuestionAuto"] = $nextQuestionAuto;
   $_SESSION["nbUnlockedTasksInitial"] = $nbUnlockedTasksInitial;
   $_SESSION["subsetsSize"] = $subsetsSize;
   $_SESSION["isPublic"] = $isPublic;
   $_SESSION["groupClosed"] = (($nbMinutesElapsed > 60) && (!$isPublic));
   // We don't want $_SESSION['userCode'] in the session at this point
   if (isset($_SESSION["userCode"])) {
      unset($_SESSION["userCode"]);
      unset($_SESSION["userCodeGroupID"]);
   }
   addBackendHint("ClientIP.checkPassword:pass");
   addBackendHint(sprintf("Group(%s):checkPassword", escapeHttpValue($groupID)));
   exitWithJson((object)array(
      "success" => true,
      "groupID" => $groupID,
      "contestID" => $contestID,
      "contestName" => $row->contestName,
      "contestFolder" => $contestFolder,
      "contestOpen" => $contestOpen,
      "contestShowSolutions" => $contestShowSolutions,
      "contestVisibility" => $contestVisibility,
      "name" => $name,
      "teams" => $teams,
      "nbMinutes" => $nbMinutes,
      "bonusScore" => $bonusScore,
      "allowTeamsOfTwo" => $allowTeamsOfTwo,
      "newInterface" => $newInterface,
      "customIntro" => $customIntro,
      "fullFeedback" => $fullFeedback,
      "nbUnlockedTasksInitial" => $nbUnlockedTasksInitial,
      "subsetsSize" => $subsetsSize,
      'bRecovered' => $row->bRecovered,
      "nbMinutesElapsed" => $nbMinutesElapsed,
      "askEmail" => !!intval($row->askEmail),
      "askZip" => !!intval($row->askZip),
      "askGenre" => !!intval($row->askGenre),
      "askGrade" => !!intval($row->askGrade),
      "askStudentId" => !!intval($row->askStudentId),
      "isPublic" => $isPublic));
}

function handleCheckTeamPassword($db, $password) {
   $result = commonLoginTeam($db, $password);
   if ($result->success) {
      addBackendHint("ClientIP.checkPassword:pass");
      addBackendHint(sprintf("Team(%s):checkPassword", escapeHttpValue($result->teamID)));
   }
   exitWithJson($result);
}

function handleGetRemainingTime($db) {
   if (!isset($_SESSION["nbMinutes"]) || !isset($_SESSION['teamID'])) {
      exitWithJson((object)array("success" => false));
   }
   $teamID = $_SESSION['teamID'];
   $stmt = $db->prepare("SELECT TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as `timeUsed` FROM `team` WHERE `ID` = ?");
   $stmt->execute(array($teamID));
   $row = $stmt->fetchObject();
   if (!$row) {
      exitWithJson((object)array("success" => false));
   }
   $remainingTime = (60 * $_SESSION["nbMinutes"]) - $row->timeUsed;
   addBackendHint("ClientIP.getRemainingTime:pass");
   addBackendHint(sprintf("Team(%s):getRemainingTime", escapeHttpValue($teamID)));
   exitWithJson((object)array("success" => true, 'remainingTime' => $remainingTime));
}

function handleRecoverGroup($db) {
   if (!isset($_POST['groupCode']) || !isset($_POST['groupPass'])) {
      exitWithJson((object)array("success" => false, "message" => 'Code ou mot de passe manquant'));
   }
   $stmt = $db->prepare("SELECT `ID`, `bRecovered`, `contestID`, `expectedStartTime`, `name`, `userID`, `gradeDetail`, `grade`, `schoolID`, `nbStudents`, `nbTeamsEffective`, `nbStudentsEffective`, `noticePrinted`, `isPublic`, `participationType`, `password` FROM `group` WHERE `code` = ?");
   $stmt->execute(array($_POST['groupCode']));
   $row = $stmt->fetchObject();
   if (!$row || $row->password != $_POST['groupPass']) {
      exitWithJson((object)array("success" => false, "message" => 'Mot de passe invalide'));
   }
   if ($row->bRecovered == 1) {
      exitWithJson((object)array("success" => false, "message" => 'L\'opération n\'est possible qu\'une fois par groupe.'));
   }
   $stmtUpdate = $db->prepare("UPDATE `group` SET `code` = ?, `password` = ?, `bRecovered`=1 WHERE `ID` = ?;");
   $stmtUpdate->execute(array('#'.$_POST['groupCode'], '#'.$row->password, $row->ID));
   $groupID = getRandomID();
   $stmtInsert = $db->prepare("INSERT INTO `group` (`ID`, `startTime`, `bRecovered`, `contestID`, `expectedStartTime`, `name`, `userID`, `gradeDetail`, `grade`, `schoolID`, `nbStudents`, `nbTeamsEffective`, `nbStudentsEffective`, `noticePrinted`, `isPublic`, `participationType`, `password`, `code`) values (:groupID, UTC_TIMESTAMP(), 1, :contestID, UTC_TIMESTAMP(), :name, :userID, :gradeDetail, :grade, :schoolID, :nbStudents, 0, 0, 0, :isPublic, :participationType, :password, :code);");
   $stmtInsert->execute(array(
      'groupID' => $groupID,
      'contestID' => $row->contestID,
      'name' => ($row->name).'-bis',
      'userID' => $row->userID,
      'gradeDetail' => $row->gradeDetail,
      'grade' => $row->grade,
      'schoolID' => $row->schoolID,
      'nbStudents' => $row->nbStudents,
      'isPublic' => $row->isPublic,
      'participationType' => $row->participationType,
      'password' => $row->password,
      'code' => $_POST['groupCode'],
   ));
   $_SESSION["groupID"] = $groupID;
   $_SESSION["startTime"] = time(); // warning: SQL and PHP server must be in sync...
   $_SESSION["closed"] = false;
   $_SESSION["groupClosed"] = false;
   exitWithJson((object)array("success" => true, "startTime" => $_SESSION["startTime"]));
}

if (!isset($_POST["action"])) {
   exitWithJsonFailure("Aucune action fournie");
}

$action = $_POST["action"];

// Handle loadPublicGroups first as it does not require loading the session.
if ($action === "loadPublicGroups") {
   handleLoadPublicGroups($db);
}

initSession();

if ($action === "loadSession") {
   handleLoadSession($db);
}

if ($action === "destroySession") {
   handleDestroySession();
}

if ($action === "checkPassword") {
   handleCheckPassword($db);
}

if ($action === "createTeam") {
   handleCreateTeam($db);
}

if ($action === "loadContestData") {
   handleLoadContestData($db);
}

if ($action === "getRemainingTime") {
   handleGetRemainingTime($db);
}

if ($action === "closeContest") {
   handleCloseContest($db);
}

if ($action === 'recoverGroup') {
   handleRecoverGroup($db);
}

exitWithJsonFailure("Action inconnue");
