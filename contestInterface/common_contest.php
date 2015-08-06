<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

function createTeamFromUserCode($db, $password) {
   if (strlen($password) > 8) {
      $password = substr($password, strlen($password) - 8, 8);
      commonLoginTeam($db, $password);
      return;
   }
   $stmt = $db->prepare("SELECT `ID` FROM `algorea_registration` WHERE `code` = ?");
   $stmt->execute(array($password));
   $row = $stmt->fetchObject();
   if (!$row) {
      $stmt = $db->prepare("SELECT `ID` FROM `contestant` WHERE `algoreaCode` = ?");
      $stmt->execute(array($password));
      $row = $stmt->fetchObject();
      if (!$row) {
         return (object)array("success" => false, "message" => "Mot de passe invalide");
      }
   }
   $_SESSION["userCode"] = $password;
   if (!openGroup($db, "usr432144", false)) { // TODO: do not hardcode this
      return (object)array("success" => false, "message" => "Cette épreuve est actuellement fermée.");
   } else {
      // TODO: openGroup should return an object...
      unset($db);
      exit;
   }
}

function commonLoginTeam($db, $password) {
   global $tinyOrm, $config;
   $stmt = $db->prepare("SELECT `team`.`ID` as `teamID`, `group`.`ID` as `groupID`, `group`.`contestID`, `group`.`name`, `contest`.`nbMinutes`, `contest`.`bonusScore`, `contest`.`allowTeamsOfTwo`, `contest`.`fullFeedback`, `contest`.`folder`, `contest`.`status`, `group`.`schoolID`, `team`.`endTime` FROM `team` JOIN `group` ON (`team`.`groupID` = `group`.`ID`) JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`password` = ?");
   $stmt->execute(array($password));
   $row = $stmt->fetchObject();
   if (!$row) {
      return createTeamFromUserCode($db, $password);
   }
   if ($config->db->use == 'dynamoDB') {
      try {
         $teamDynamoDB = $tinyOrm->select('team', array('ID', 'groupID'), array('password' => $password));
      } catch (\Aws\DynamoDb\Exception $e) {
         error_log($e->getMessage . " - " . $e->getCode());
         error_log('DynamoDB error finding team with password: '.$password);
      }
      if (!isset($teamDynamoDB[0]) || $row->teamID != $teamDynamoDB[0]['ID'] || $row->groupID != $teamDynamoDB[0]['groupID']) {
         error_log('enregistrement différent entre MySQL et DynamoDB! SQL: teamID='.$row->teamID.', groupID='.$row->groupID.(isset($teamDynamoDB[0]) ? ' DDB: ID='.$teamDynamoDB[0]['ID'].', groupID='.$teamDynamoDB[0]['groupID'] : 'pas d\'enregistrement DynamoDB'));
         return (object)array("success" => false, "message" => "enregistrement différent entre MySQL et DynamoDB!");
      }
   }
   if ($row->status == "Closed") {
      return (object)array("success" => false, "message" => "Le concours lié à votre participation est actuellement fermé. Il réouvrira bientôt.");
   }
   if ($row->endTime && $row->status == 'RunningContest') {
      $stmt = $db->prepare("UPDATE `team` SET `endTime` = NULL WHERE `team`.`password` = ?");
      $stmt->execute(array($password));
   }
   $_SESSION["contestID"] = $row->contestID;
   $_SESSION["teamID"] = $row->teamID;
   $_SESSION["teamPassword"] = $password;
   $_SESSION["groupID"] = $row->groupID;
   $_SESSION["schoolID"] = $row->schoolID;
   $_SESSION["nbMinutes"] = $row->nbMinutes;
   $_SESSION["bonusScore"] = $row->bonusScore;
   $_SESSION["allowTeamsOfTwo"] = $row->allowTeamsOfTwo;
   $_SESSION["fullFeedback"] = $row->fullFeedback;
   $_SESSION["contestFolder"] = $row->folder;
   $_SESSION["contestStatus"] = $row->status;
   return (object)array(
      "success" => true,
      "name" => $row->name,
      "contestID" => $row->contestID,
      "contestFolder" => $row->folder,
      "contestStatus" => $row->status,
      "nbMinutes" => $row->nbMinutes,
      "bonusScore" => $row->bonusScore,
      "allowTeamsOfTwo" => $row->allowTeamsOfTwo,
      "fullFeedback" => $row->fullFeedback,
      "teamID" => $row->teamID,
      );
}

function reconnectSession($db) {
   if (!isset($_POST["teamPassword"])) {
      echo json_encode(array("success" => false, "message" => "Session invalide"));
      error_log("invalid session : ".json_encode($_SESSION));
      error_log(json_encode($_REQUEST));
      return;
   }
   $res = commonLoginTeam($db, $_POST["teamPassword"]);
   if (!$res->success) {
      echo json_encode($res);
      error_log("invalid session and bad password : ".json_encode($_SESSION));
      error_log(json_encode($_REQUEST));
      return;
   }
   $teamID = $_SESSION["teamID"];
   error_log("reconnexion de session acceptée ".json_encode($_REQUEST));
   // TODO: factoriser ce qui suit (copier-collé issu de data.php)
   $stmt = $db->prepare("SELECT TIME_TO_SEC(TIMEDIFF(NOW(), `team`.`startTime`)) as `timeUsed`, `endTime`, UNIX_TIMESTAMP() as `timeNow` FROM `team` WHERE `ID` = ?");
   $stmt->execute(array($teamID));
   $row = $stmt->fetchObject();
   $_SESSION["startTime"] = $row->timeNow - intval($row->timeUsed);
   if ($row->endTime != null) {
      $_SESSION["closed"] = true;
   }
   return true;
}
