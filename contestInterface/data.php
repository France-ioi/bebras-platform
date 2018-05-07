<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

include_once("config.php");
include_once("../shared/common.php");
include_once("../shared/tinyORM.php");
include_once("common_contest.php");

function getGroupTeams($db, $groupID) {
   $stmt = $db->prepare("SELECT `team`.`ID`, `contestant`.`lastName`, `contestant`.`firstName` FROM `contestant` JOIN `team` ON `contestant`.`teamID` = `team`.`ID` JOIN `group` ON `team`.groupID = `group`.ID WHERE `team`.`groupID` = :groupID OR `group`.`parentGroupID` = :groupID");
   $stmt->execute(array("groupID" => $groupID));
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

function getGroupForSubContest($db, $oldGroupID, $newContestID) {
   $query = "SELECT `group`.`ID`, `group`.`code` FROM `group` JOIN `group` `oldGroup` ON (`group`.`grade` = `oldGroup`.`grade` AND `group`.`schoolID` = `oldGroup`.`schoolID` AND `group`.`userID` = `oldGroup`.`userID`) WHERE `oldGroup`.ID = :oldGroupID AND `group`.`contestID` = :newContestID AND `group`.`parentGroupID` = :oldGroupID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("oldGroupID" => $oldGroupID, "newContestID" => $newContestID));
   $row = $stmt->fetchObject();
   if ($row) {
      return array("code" => $row->code, "ID" => $row->ID);
   } else {
      $groupCode = genAccessCode($db);
      $groupPassword = genAccessCode($db);
      $groupID = getRandomID();
      $query = "INSERT INTO `group` (`ID`, `name`, `contestID`, `schoolID`, `userID`, `grade`, `expectedStartTime`, `startTime`, `code`, `password`, `isGenerated`, nbStudents, nbStudentsEffective, nbTeamsEffective, parentGroupID, language, minCategory, maxCategory) ".
         "SELECT :groupID, LEFT(CONCAT(IF(`oldGroup`.`isGenerated`, CONCAT(CONCAT('Indiv ', `oldGroup`.`grade`), ' '), CONCAT(`oldGroup`.`name`, '/')), `contest`.`name`), 50), :contestID, `oldGroup`.`schoolID`, `oldGroup`.`userID`, `oldGroup`.`grade`, NOW(), NOW(), :groupCode, :password, 1, 0, 0, 0, :oldGroupID, `contest`.`language`, `contest`.`categoryColor`, `contest`.`categoryColor` FROM `contest`, `group` `oldGroup` WHERE `contest`.`ID` = :contestID AND `oldGroup`.`ID` = :oldGroupID";
      $stmt = $db->prepare($query);
      $stmt->execute(array(
         "contestID" => $newContestID,
         "oldGroupID" => $oldGroupID,
         "groupID" => $groupID,
         "groupCode" => $groupCode,
         "password" => $groupPassword));
      return array("code" => $groupCode, "ID" => $groupID);
   }
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
   if (isset($_POST["contestID"])) {
      if ($_SESSION["contestID"] != $_POST["contestID"]) {
         $_SESSION["contestID"] = $_POST["contestID"];
         $stmt = $db->prepare("SELECT `folder` FROM contest WHERE ID = ?");
         $stmt->execute(array($_SESSION["contestID"]));
         $row = $stmt->fetchObject();
         $_SESSION["contestFolder"] = $row->folder;
         $groupData = getGroupForSubContest($db, $_SESSION["groupID"], $_SESSION["contestID"]);
         $_SESSION["groupID"] = $groupData["ID"];
      }
   }
   // $_SESSION['userCode'] is set by optional password handling function,
   // see comments of createTeamFromUserCode in common_contest.php.
   $groupID = $_SESSION["groupID"];
   if (isset($_SESSION["userCode"]) && isset($_SESSION["userCodeGroupCode"]) && $_SESSION["userCodeGroupCode"] == $_SESSION["groupCode"]) {
      $password = $_SESSION["userCode"];
   } else {
      $password = genAccessCode($db);
   }
   unset($_SESSION["userCode"]);
   unset($_SESSION["userCodeGroupCode"]);
   $teamID = getRandomID();
   $stmt = $db->prepare("INSERT INTO `team` (`ID`, `groupID`, `password`, `nbMinutes`, `contestID`) VALUES (?, ?, ?, ?, ?)");
   $stmt->execute(array($teamID, $groupID, $password, $_SESSION["nbMinutes"], $_SESSION["contestID"]));
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

   if (isset($_SESSION['mysqlOnly'])) {
      unset($_SESSION['mysqlOnly']);
   }
   $_SESSION["teamID"] = $teamID;
   $_SESSION["teamPassword"] = $password;
   foreach ($contestants as $contestant) {
      if (isset($contestant["registrationCode"])) {
         $stmt = $db->prepare("INSERT INTO `contestant` (`ID`, `lastName`, `firstName`, `genre`, `grade`, `studentId`, `teamID`, `cached_schoolID`, `saniValid`, `email`, `zipCode`, `registrationID`)
         SELECT :contestantID, `lastName`, `firstName`, `genre`, `grade`, `studentId`, :teamID, `schoolID`, 1, `email`, `zipCode`, `ID`
         FROM `algorea_registration` WHERE `algorea_registration`.`code` = :code");
         $stmt->execute(array(
            "contestantID" => getRandomID(),
            "code" => $contestant["registrationCode"],
            "teamID" => $teamID
         ));
      } else {
         if ((!isset($contestant["grade"])) || ($contestant["grade"] == '')) {
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
   }
   addBackendHint(sprintf("ClientIP.createTeam:%s", $_SESSION['isPublic'] ? 'public' : 'private'));
   addBackendHint(sprintf("Group(%s):createTeam", escapeHttpValue($groupID)));
   exitWithJson((object)array("success" => true, "teamID" => $teamID, "password" => $password));
}

function updateDynamoDBStartTime($db, $teamID) {
   global $tinyOrm, $config;
   if ($config->db->use == 'dynamoDB' && (!isset($_SESSION["mysqlOnly"]) || !$_SESSION["mysqlOnly"])) {
      $stmt = $db->prepare("SELECT `startTime` FROM `team` WHERE `ID` = :teamID");
      $stmt->execute(array("teamID" => $teamID));
      $startTime = $stmt->fetchColumn();
      try {
         $tinyOrm->update('team', array('startTime' => $startTime), array('ID'=>$teamID));
      } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error updating team for teamID: '.$teamID);
      }
   }
}

function handleStartTimer($db) {
   $teamID = $_SESSION["teamID"];
   $stmt = $db->prepare("UPDATE `team` SET `startTime` = UTC_TIMESTAMP() WHERE `ID` = :teamID AND `startTime` IS NULL");
   $stmt->execute(array("teamID" => $teamID));
   updateDynamoDBStartTime($db, $teamID);

   $remainingSeconds = getRemainingSeconds($db, $teamID, true);

   if ($remainingSeconds <= 0) {
      $_SESSION["closed"] = true;
   } else {
      unset($_SESSION["closed"]);
   }

   exitWithJson((object)array(
      "success" => true,
      "remainingSeconds" => $remainingSeconds,
      "ended" => ($remainingSeconds <= 0) && ($_SESSION["nbMinutes"] > 0)
   ));
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
      $password = strtolower(trim($_POST["groupPassword"]));
      reloginTeam($db, $password, $_POST["teamID"]);
   }
   $teamID = $_SESSION["teamID"];
   $stmt = $db->prepare("UPDATE `team` SET `createTime` = UTC_TIMESTAMP() WHERE `ID` = :teamID AND `createTime` IS NULL");
   $stmt->execute(array("teamID" => $teamID));
   
   $questionsData = getQuestions($db, $_SESSION["contestID"], $_SESSION["subsetsSize"], $teamID);
   $mode = null;
   if (isset($_SESSION['mysqlOnly']) && $_SESSION['mysqlOnly']) {
      $mode = 'mysql';
   }
   try {
      $results = $tinyOrm->select('team_question', array('questionID', 'answer', 'ffScore', 'score'), array('teamID' =>$teamID), null, $mode);
   } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
      if (strval($e->getAwsErrorCode()) != 'ConditionalCheckFailedException') {
         error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
         error_log('DynamoDB error retrieving team_questions for teamID: '.$teamID);
      }
      $results = [];
   }
   $answers = array();
   $scores = array();
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

   addBackendHint("ClientIP.loadContestData:pass");
   addBackendHint(sprintf("Team(%s):loadContestData", escapeHttpValue($teamID)));
   exitWithJson((object)array(
      "success" => true,
      "questionsData" => $questionsData,
      'scores' => $scores,
      "answers" => $answers,
      "isTimed" => ($_SESSION["nbMinutes"] > 0),
      "teamPassword" => $_SESSION["teamPassword"]
   ));
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
   exitWithJson((object)array("success" => true));
}

function handleLoadSession() {
   global $config;
   $sid = session_id();
   // If the session is new or closed, just return the SID.
   if (!isset($_SESSION["teamID"]) || isset($_SESSION["closed"])) {
      addBackendHint("ClientIP.loadSession:new");
      exitWithJson(['success' => true, "SID" => $sid]);
   }
   // Otherwise, data from the session is also returned.
   addBackendHint("ClientIP.loadSession:found");
   addBackendHint(sprintf("SessionId(%s):loadSession", escapeHttpValue($sid)));
   $message = "Voulez-vous reprendre l'épreuve commencée ?";
   if ($config->defaultLanguage == "es") {
      $message = "¿Desea reiniciar la prueba comenzada anteriormente?";
   }
   if ($config->defaultLanguage == "en") {
      $message = "Would you like to continue the participation that was started?";
   }
   exitWithJson(array(
      "success" => true,
      "teamID" => $_SESSION["teamID"],
      "message" => $message,
      "nbMinutes" => $_SESSION["nbMinutes"],
      "bonusScore" => $_SESSION["bonusScore"],
      "allowTeamsOfTwo" => $_SESSION["allowTeamsOfTwo"],
      "askParticipationCode" => $_SESSION["askParticipationCode"],
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
   global $config;

   addFailureBackendHint("ClientIP.checkPassword:fail");
   addFailureBackendHint("ClientIP.error");

   // Check common.js version
   $commonJsVersion = isset($_POST['commonJsVersion']) ? intval($_POST['commonJsVersion']) : 0;
   $commonJsTimestamp = isset($_POST['commonJsTimestamp']) ? $_POST['commonJsTimestamp'] : '[none]';
   $timestamp = isset($_POST['timestamp']) ? $_POST['timestamp'] : '[none]';

   if($commonJsVersion < $config->minimumCommonJsVersion) {
      // Reject old common.js versions
      $errormsg = "Mauvaise version de common.js : client ".$commonJsVersion." < minimum ".$config->minimumCommonJsVersion." (server timestamp ".$timestamp.", common.js loaded at ".$commonJsTimestamp.").";

      // Log error
      $stmt = $db->prepare('insert into error_log (date, message) values (UTC_TIMESTAMP(), :errormsg);');
      $stmt->execute(['errormsg' => $errormsg]);
      unset($stmt);

      // Send back error message to user
      $userMsg = "Vous utilisez une ancienne version de l'interface, veuillez rafraîchir la page. Si cela ne règle pas le problème, essayez de vider votre cache.";
      if(isset($config->contestBackupURL)) {
         $userMsg .= " Vous pouvez aussi essayer le domaine alternatif du concours http://" . $config->contestBackupURL . ".";
      }
      exitWithJsonFailure($userMsg);
   }

   if (!isset($_POST["password"])) {
      exitWithJsonFailure("Mot de passe manquant");
   }
   $getTeams = array_key_exists('getTeams', $_POST) ? $_POST["getTeams"] : False;
   $password = strtolower($_POST["password"]);
   // Search for a group matching the entered password, and if found create
   // a team in that group (and end the request).
   handleCheckGroupPassword($db, $password, $getTeams);

   handleGroupFromRegistrationCode($db, $password);

   // If no matching group was found, look for a team with the entered password.
   handleCheckTeamPassword($db, $password);
}

function getRegistrationData($db, $code) {
   $query = "SELECT `algorea_registration`.`ID`, `code`, `category` as `qualifiedCategory`, `validatedCategory`, `firstName`, `lastName`, `genre`, `grade`, `studentID`, `email`, `zipCode`, ".
      "IFNULL(`algorea_registration`.`schoolID`, 0) as `schoolID`, IFNULL(`algorea_registration`.  `userID`, 0) as `userID`, IFNULL(`school_user`.`allowContestAtHome`, 1) as `allowContestAtHome` ".
      "FROM `algorea_registration` ".
      "LEFT JOIN `school_user` ON (`school_user`.`schoolID` = `algorea_registration`.`schoolID` AND `school_user`.`userID` = `algorea_registration`.`userID`) ".
      "WHERE `code` = :code";
   $stmt = $db->prepare($query);
   $stmt->execute(array("code" => $code));
   return $stmt->fetchObject();
}




function createGroupForContestAndRegistrationCode($db, $code, $contestID) {
   $groupCode = genAccessCode($db);
   $groupPassword = genAccessCode($db);
   $groupID = getRandomID();
   $query = "INSERT INTO `group` (`ID`, `name`, `contestID`, `schoolID`, `userID`, `grade`, `expectedStartTime`, `startTime`, `code`, `password`, `isGenerated`, nbStudents, nbStudentsEffective, nbTeamsEffective) ".
      "SELECT :groupID, LEFT(CONCAT(CONCAT(CONCAT('Indiv', `algorea_registration`.`grade`), ' '), `contest`.`name`), 50), :contestID, `algorea_registration`.`schoolID`, `algorea_registration`.`userID`, `algorea_registration`.`grade`, NOW(), NOW(), :groupCode, :password, 1, 0, 0, 0 FROM `contest`, `algorea_registration` WHERE `contest`.`ID` = :contestID AND `algorea_registration`.`code` = :code";
   $stmt = $db->prepare($query);
   $stmt->execute(array(
      "contestID" => $contestID,
      "code" => $code,
      "groupID" => $groupID,
      "groupCode" => $groupCode,
      "password" => $groupPassword));
   return array("code" => $groupCode, "ID" => $groupID);
}

function handleGroupFromRegistrationCode($db, $code) {
   $registrationData = getRegistrationData($db, $code);
   if (!$registrationData) {
      return;
   }
   $newCategories = updateRegisteredUserCategory($db, $registrationData->ID, $registrationData->qualifiedCategory, $registrationData->validatedCategory);
   $registrationData->qualifiedCategory = $newCategories["qualifiedCategory"];
   $registrationData->validatedCategory = $newCategories["validatedCategory"];
   
   
   $query = "SELECT IFNULL(tmp.score, 0) as score, tmp.sumScores, tmp.password, tmp.startTime, tmp.contestName, tmp.contestID, tmp.parentContestID, tmp.contestCategory, ".
       "tmp.nbMinutes, tmp.remainingSeconds, tmp.teamID, ".
       "GROUP_CONCAT(CONCAT(CONCAT(contestant.firstName, ' '), contestant.lastName)) as contestants, tmp.rank, tmp.schoolRank, count(*) as nbContestants ".
       "FROM (".
          "SELECT team.ID as teamID, team.score, SUM(team_question.ffScore) as sumScores, contestant.rank, contestant.schoolRank, team.password, team.startTime, contest.ID as contestID, contest.parentContestID, contest.name as contestName, contest.categoryColor as contestCategory, ".
          "team.nbMinutes, (team.`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as remainingSeconds ".
          "FROM `contestant` ".
          "JOIN team ON `contestant`.teamID = `team`.ID ".
          "JOIN `group` ON team.groupID = `group`.ID ".
          "JOIN `contest` ON `group`.contestID = `contest`.ID ".
          "LEFT JOIN `team_question` ON team_question.teamID = team.ID ".
          "WHERE contestant.registrationID = :registrationID ".
          "GROUP BY team.ID".
       ") tmp ".
       "JOIN contestant ON tmp.teamID = contestant.teamID ".
       "GROUP BY tmp.teamID ".
       "ORDER BY tmp.startTime ASC";
   $stmt = $db->prepare($query);
   $stmt->execute(array("registrationID" => $registrationData->ID));
   $participations = array();
   while ($row = $stmt->fetchObject()) {
      $participations[] = $row;
   }
   $registrationData->participations = $participations;
       
   addBackendHint("ClientIP.checkPassword:pass");
   addBackendHint(sprintf("Group(%s):checkPassword", escapeHttpValue($registrationData->ID))); // TODO : check hint
   $contestID = "485926402649945250"; // hard-coded training contest
   $isOfficialContest = false;
   if (isset($_POST["startOfficial"])) {
      $contestID = "404363140821714044"; // hard-coded real contest
      $isOfficialContest = true;
   }
   if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == "chticode.algorea.org")) {
      $contestID = "100001";
   }
   $query = "SELECT `code` FROM `group` WHERE `contestID` = :contestID AND `schoolID` = :schoolID AND `userID` = :userID AND `grade` = :grade AND isGenerated = 1";
   $stmt = $db->prepare($query);
   $stmt->execute(array("contestID" => $contestID, "schoolID" => $registrationData->schoolID, "userID" => $registrationData->userID, "grade" => $registrationData->grade));
   $rowGroup = $stmt->fetchObject();
   if (!$rowGroup) {
      $groupData = createGroupForContestAndRegistrationCode($db, $code, $contestID);
      $groupCode = $groupData["code"];
   } else {
      $groupCode = $rowGroup->code;
   }
   handleCheckGroupPassword($db, $groupCode, false, "", $registrationData, $isOfficialContest);
}

function handleCheckGroupPassword($db, $password, $getTeams, $extraMessage = "", $registrationData = null, $isOfficialContest = false) {
   global $allCategories, $config;
   
   // Find a group whose code matches the given password.
   $query = "SELECT `group`.`ID`, `group`.`name`, `group`.`bRecovered`, `group`.`contestID`, `group`.`isPublic`, `group`.`schoolID`, `group`.`startTime`, TIMESTAMPDIFF(MINUTE, `group`.`startTime`, UTC_TIMESTAMP()) as `nbMinutesElapsed`,  `contest`.`nbMinutes`, `contest`.`bonusScore`, `contest`.`allowTeamsOfTwo`, `contest`.`askParticipationCode`, `contest`.`newInterface`, `contest`.`customIntro`, `contest`.`fullFeedback`, `contest`.`nextQuestionAuto`, `contest`.`folder`, `contest`.`nbUnlockedTasksInitial`, `contest`.`subsetsSize`, `contest`.`open`, `contest`.`showSolutions`, `contest`.`visibility`, `contest`.`askEmail`, `contest`.`askZip`, `contest`.`askGenre`, `contest`.`askGrade`, `contest`.`askStudentId`, `contest`.`name` as `contestName`, `contest`.`allowPauses`, `group`.`isGenerated`, `group`.`language`, `group`.`minCategory`, `group`.`maxCategory` FROM `group` JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `code` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($password));
   $row = $stmt->fetchObject();
   if (!$row) {
      // No such group.
      return;
   }
   if ($row->open != "Open") {
      $messages = array("fr" => "Le concours de ce groupe n'est pas ouvert.",
         "en" => "The contest associated with this group is not open",
         "ar" => "المسابقة لم تبدأ بعد"
      );
      if (isset($messages[$config->defaultLanguage])) {
         $message = $messages[$config->defaultLanguage];
      } else {
         $message = $messages["en"];
      }
      exitWithJson((object)array("success" => false, "message" => $message));
   }
   $groupID = $row->ID;
   $schoolID = $row->schoolID;
   $isPublic = intval($row->isPublic);
   $isGenerated = intval($row->isGenerated);

   if ($row->startTime === null) {
      $nbMinutesElapsed = 0;
   } else {
      $nbMinutesElapsed = intval($row->nbMinutesElapsed);
   }
   if ($getTeams === "true") {
      $teams = getGroupTeams($db, $groupID);
   } else {
      $teams = "";
   }
   if (isset($_SESSION['mysqlOnly'])) {
      unset($_SESSION['mysqlOnly']);
   }
   $_SESSION["name"] = $row->name;
   $_SESSION["groupID"] = $groupID;
   $_SESSION["groupCode"] = $password;
   $_SESSION["schoolID"] = $schoolID;
   $_SESSION["nbMinutes"] = intval($row->nbMinutes);
   $_SESSION["isPublic"] = intval($row->isPublic);
   $_SESSION["isGenerated"] = intval($row->isGenerated);
   $_SESSION["language"] = $row->language;
   $_SESSION["minCategory"] = $row->minCategory;
   $_SESSION["maxCategory"] = $row->maxCategory;
   $_SESSION["groupClosed"] = (($nbMinutesElapsed > 60) && (!$_SESSION["isPublic"]) && (!$_SESSION["isGenerated"]));
   $_SESSION["registrationData"] = $registrationData;

   updateSessionWithContestInfos($row);
   
   $query = "SELECT contest.ID as contestID, contest.folder, contest.name, contest.language, contest.categoryColor, contest.customIntro, contest.imageURL, contest.description, contest.allowTeamsOfTwo, contest.askParticipationCode ".
      "FROM contest WHERE parentContestID = :contestID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("contestID" => $row->contestID));
   $childrenContests = array();
   $hadChildrenContests = false;
   while ($rowChild = $stmt->fetchObject()) {
      $hadChildrenContests = true;
      $discardCategory = false;
      if ($isOfficialContest) {
         foreach ($allCategories as $category) {
            if ($rowChild->categoryColor == $category) {
               break;
            }
            if ($registrationData->qualifiedCategory == $category) { // the contest's category is higher than the user's qualified category
               $discardCategory = true;
            }
         }
         foreach ($registrationData->participations as $participation) {
            if (($participation->parentContestID == $row->contestID) && ($participation->contestCategory == $rowChild->categoryColor)) {
               if ($participation->startTime == null) {
                  $query = "DELETE contestant.* FROM `team` JOIN contestant ON team.ID = contestant.teamID WHERE team.ID = :teamID AND startTime IS NULL";
                  $stmt2 = $db->prepare($query);
                  $stmt2->execute(array("teamID" => $participation->teamID));
                  $query = "DELETE FROM `team` WHERE ID = :teamID AND startTime IS NULL";
                  $stmt2 = $db->prepare($query);
                  $stmt2->execute(array("teamID" => $participation->teamID));
               } else if ($participation->remainingSeconds < (20 * $participation->nbMinutes)) {
                  $discardCategory = true;
               }
               break;
            }
         }
      }
      if (!$discardCategory) {
         $childrenContests[] = $rowChild;
      }
   };
   $allContestsDone = ((count($childrenContests) == 0) && $hadChildrenContests);

   addBackendHint("ClientIP.checkPassword:pass");
   addBackendHint(sprintf("Group(%s):checkPassword", escapeHttpValue($groupID)));
   exitWithJson((object)array(
      "success" => true,
      "groupID" => $_SESSION["groupID"],
      "nbMinutes" => $_SESSION["nbMinutes"],
      "contestID" => $_SESSION["contestID"],
      "contestName" => $_SESSION["contestName"],
      "contestFolder" => $_SESSION["contestFolder"],
      "contestOpen" => $_SESSION["contestOpen"],
      "contestShowSolutions" => $_SESSION["contestShowSolutions"],
      "contestVisibility" => $_SESSION["contestVisibility"],
      "bonusScore" => $_SESSION["bonusScore"],
      "allowTeamsOfTwo" => $_SESSION["allowTeamsOfTwo"],
      "askParticipationCode" => $_SESSION["askParticipationCode"],
      "newInterface" => $_SESSION["newInterface"],
      "nextQuestionAuto" => $_SESSION["nextQuestionAuto"],
      "customIntro" => $_SESSION["customIntro"],
      "fullFeedback" => $_SESSION["fullFeedback"],
      "nbUnlockedTasksInitial" => $_SESSION["nbUnlockedTasksInitial"],
      "subsetsSize" => $_SESSION["subsetsSize"],
      "name" => $_SESSION["name"],
      "teams" => $teams,
      'bRecovered' => $row->bRecovered,
      "nbMinutesElapsed" => $nbMinutesElapsed,
      "askEmail" => !!intval($row->askEmail),
      "askZip" => !!intval($row->askZip),
      "askGenre" => !!intval($row->askGenre),
      "askGrade" => !!intval($row->askGrade),
      "askStudentId" => !!intval($row->askStudentId),
      "extraMessage" => $extraMessage,
      "isPublic" => $isPublic,
      "isGenerated" => $isGenerated,
      "minCategory" => $_SESSION["minCategory"],
      "maxCategory" => $_SESSION["maxCategory"],
      "language" => $_SESSION["language"],
      "childrenContests" => $childrenContests,
      "registrationData" => $registrationData,
      "isOfficialContest" => $isOfficialContest,
      "allContestsDone" => $allContestsDone
   ));
}

function handleCheckTeamPassword($db, $password) {
   $result = commonLoginTeam($db, $password);
   if ($result->success) {
      addBackendHint("ClientIP.checkPassword:pass");
      addBackendHint(sprintf("Team(%s):checkPassword", escapeHttpValue($result->teamID)));
   }
   exitWithJson($result);
}

function handleCheckRegistrationCode($db) {
   $code = $_POST["code"];
   $registrationData = getRegistrationData($db, $code);
   if (!$registrationData) {
      exitWithJson((object)array("success" => false));
   } else {
      $registrationData->success = true;
      exitWithJson($registrationData);
   }
}

function getRemainingSeconds($db, $teamID, $restartIfEnded = false) {
   $stmt = $db->prepare("SELECT (`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as `remainingSeconds`, ".
      "`endTime`, ".
      "(`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(`team`.`endTime`, `team`.`startTime`)) as `remainingSecondsBeforePause`, ".
      "`extraMinutes` ".
      "FROM `team` WHERE `ID` = ?");
   $stmt->execute(array($teamID));
   $row = $stmt->fetchObject();
   if (!$row) {
      return 0;
   }
   $remainingSeconds = $row->remainingSeconds;
   $pausesAdded = 0;
   $update = false;
   if ($row->endTime != null) {
      if (!$restartIfEnded) {
         return 0;
      }
      if ($_SESSION["contestShowSolutions"]) {
         return 0;
      }
      if ($remainingSeconds < 0) {
         if (!$_SESSION["allowPauses"]) {
            return 0;
         }
      }
      $pausesAdded = 1;
      $remainingSeconds = $row->remainingSecondsBeforePause;
      $update = true;
   }
   if ($remainingSeconds < 0) {
      $remainingSeconds = 0;
   }
   if ($row->extraMinutes != null) {
      $remainingSeconds += $row->extraMinutes * 60;
      $update = true;
   }
   if ($update) {
      $stmt2 = $db->prepare("UPDATE `team` SET ".
         "`endTime` = NULL, ".
         "`nbMinutes` = `nbMinutes` + IFNULL(`extraMinutes`,0), ".
         "`startTime` = DATE_SUB(UTC_TIMESTAMP(), INTERVAL ((`nbMinutes` * 60) - :remainingSeconds) SECOND), ".
         "`extraMinutes` = NULL, ".
         "`nbPauses` = `nbPauses` + :pausesAdded ".
         "WHERE `ID` = :teamID");
      $stmt2->execute(array("teamID" => $teamID, "remainingSeconds" => $remainingSeconds, "pausesAdded" => $pausesAdded));
      updateDynamoDBStartTime($db, $teamID);
   }
   return $remainingSeconds;
}


function handleGetRemainingSeconds($db) {
   if (!isset($_SESSION["nbMinutes"]) || !isset($_SESSION['teamID'])) {
      exitWithJson((object)array("success" => false));
   }
   $teamID = $_SESSION['teamID'];
   $remainingSeconds = getRemainingSeconds($db, $teamID);
   addBackendHint("ClientIP.getRemainingTime:pass");
   addBackendHint(sprintf("Team(%s):getRemainingTime", escapeHttpValue($teamID)));
   exitWithJson((object)array("success" => true, 'remainingSeconds' => $remainingSeconds));
}

function handleRecoverGroup($db) {
   if (!isset($_POST['groupCode']) || !isset($_POST['groupPass'])) {
      exitWithJson((object)array("success" => false, "message" => 'Code ou mot de passe manquant'));
   }
   $stmt = $db->prepare("SELECT `ID`, `bRecovered`, `contestID`, `expectedStartTime`, `name`, `userID`, `gradeDetail`, `grade`, `schoolID`, `nbStudents`, `nbTeamsEffective`, `nbStudentsEffective`, `noticePrinted`, `isPublic`, `participationType`, `password`, `language`, `minCategory`, `maxCategory` FROM `group` WHERE `code` = ?");
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
   $stmtInsert = $db->prepare("INSERT INTO `group` (`ID`, `startTime`, `bRecovered`, `contestID`, `expectedStartTime`, `name`, `userID`, `gradeDetail`, `grade`, `schoolID`, `nbStudents`, `nbTeamsEffective`, `nbStudentsEffective`, `noticePrinted`, `isPublic`, `participationType`, `password`, `code`, `language`, `minCategory`, `maxCategory`) values (:groupID, UTC_TIMESTAMP(), 1, :contestID, UTC_TIMESTAMP(), :name, :userID, :gradeDetail, :grade, :schoolID, :nbStudents, 0, 0, 0, :isPublic, :participationType, :password, :code, :language, :minCategory, :maxCategory);");
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
      'language' => $row->language,
      'minCategory' => $row->minCategory,
      'maxCategory' => $row->maxCategory
   ));
   $_SESSION["groupID"] = $groupID;
   $_SESSION["closed"] = false;
   $_SESSION["groupClosed"] = false;
   exitWithJson((object)array("success" => true));
}

function handleGetConfig() {
   global $config;
   $clientConfig = array(
      "imagesURLReplacements" => $config->imagesURLReplacements,
      "imagesURLReplacementsNonStatic" => $config->imagesURLReplacementsNonStatic,
      "upgradeToHTTPS" => $config->upgradeToHTTPS
      );
   exitWithJson(["success" => true, "config" => $clientConfig]);
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

if ($action == "startTimer") {
   handleStartTimer($db);
}

if ($action === "getRemainingSeconds") {
   handleGetRemainingSeconds($db);
}

if ($action === "closeContest") {
   handleCloseContest($db);
}

if ($action === 'recoverGroup') {
   handleRecoverGroup($db);
}

if ($action === 'getConfig') {
   handleGetConfig($db);
}

if ($action === 'checkRegistration') {
   handleCheckRegistrationCode($db);
}

exitWithJsonFailure("Action inconnue");
