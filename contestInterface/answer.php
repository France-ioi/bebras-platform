<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */
// means that connect.php won't make any sql connection if in dynamoDB mode
$noSQL = true;
$noSessions = true;
use Aws\DynamoDb\Exception;
require_once("../shared/connect.php");
require_once("../shared/tinyORM.php");
include_once("common_contest.php");

$tinyOrm = new tinyOrm();
$testMode = $config->db->testMode;

if (get_magic_quotes_gpc()) {
    function stripslashes_gpc(&$value)
    {
        $value = stripslashes($value);
    }
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

// The encoding used for multi-bytes string in always UTF-8
mb_internal_encoding("UTF-8");

function handleAnswers($tinyOrm) {
   global $config, $db, $testMode;
   $teamID = $_POST["teamID"];
   $teamPassword = $_POST["teamPassword"];
   try {
      $rows = $tinyOrm->select('team', array('password', 'startTime', 'nbMinutes'), array('ID' => $teamID));
   } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
      error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
      error_log('DynamoDB error trying to get record: teamID: '.$teamID);
      exitWithJsonFailure($e->getMessage(), array('error' => 'DynamoDB'));
   }
   if ($testMode == false && $config->db->use == "dynamoDB" && !count($rows)) {
      // Need to connect to mysql
      $config->db->use = "mysql";
      $db = connect_pdo($config->db);
      $tinyOrm = new tinyOrm();
      $rows = $tinyOrm->select('team', array('password', 'startTime', 'nbMinutes'), array('ID' => $teamID));
   }
   if ($testMode == false && (!count($rows) || $teamPassword != $rows[0]['password'])) {
      error_log('teamID '.$teamID.' sent answer with password '.$teamPassword.(count($rows) ? ' instead of '.$rows[0]['password'] : ' (no such team)'));
      exitWithJsonFailure("Requête invalide (password)");
   }

   $teamUpdates = [];
   $teamUpdatesParams = ['id' => $teamID, 'password' => $teamPassword];

   // Check browserID if needed
   if($config->contestInterface->checkBrowserID && isset($_POST['browserID'])) {
      if(!isset($db)) {
         // Need to connect to mysql
         $db = connect_pdo($config->db);
      }
      $stmt = $db->prepare("SELECT browserID FROM team WHERE ID = :teamID");
      $stmt->execute(['teamID' => $teamID]);
      $curBrowserID = $stmt->fetchColumn();
      if($curBrowserID !== null && $curBrowserID != $_POST['browserID']) {
         error_log('teamID '.$teamID.' sent answer with browserID '.$_POST['browserID'].' instead of '.$curBrowserID);
         exitWithJsonFailure("Requête invalide (browserID)", ['browserIDChanged' => true]);
      }
      if($curBrowserID === null) {
         $teamUpdates[] = 'browserID = :browserID';
         $teamUpdatesParams['browserID'] = $_POST['browserID'];
      }
   }

   $row = $rows[0];
   $answers = $_POST["answers"];
   $curTime = new DateTime(null, new DateTimeZone("UTC"));
   $startTime = new DateTime($row['startTime'], new DateTimeZone("UTC"));
   /*$nbMinutes = intval($row['nbMinutes']);
   // We leave 2 extra minutes to handle network lag. The interface already prevents trying to answer after the end.
   if ((($curTime->getTimestamp() - $startTime->getTimestamp()) > ((intval($nbMinutes) + 2) * 60)) && !$testMode && ($nbMinutes > 0)) {
      error_log("submission by team ".$teamID.
        " after the time limit of the contest! curTime : ".$curTime->format(DateTime::RFC850).
        " startTime :".$startTime->format(DateTime::RFC850).
        " nbMinutes : ".$nbMinutes);
      exitWithJsonFailure("La réponse a été envoyée après la fin de l'épreuve", array('error' => 'invalid'));
   }
   */
   $curTimeDB = new DateTime(null, new DateTimeZone("UTC"));
   $curTimeDB = $curTimeDB->format('Y-m-d H:i:s');
   $items = array();
   foreach ($answers as $questionID => $answerObj) {
      $items[] = array('teamID' => $teamID, 'questionID' => $questionID, 'answer'  => $answerObj["answer"], 'ffScore' => $answerObj['score'], 'date' => $curTimeDB);
   }
   try {
      $tinyOrm->batchWrite('team_question', $items, array('teamID', 'questionID', 'answer', 'ffScore', 'date'), array('answer', 'ffScore', 'date'));
   } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
      error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
      error_log('DynamoDB error trying to write records: teamID: '.$teamID.', answers: '.json_encode($items).', items: '.json_encode($items));
      exitWithJsonFailure($e->getAwsErrorCode(), array('error' => 'DynamoDB'));
   }

   // Update lastAnswerTime and finalAnswersTime if required
   if (isset($_POST['sendLastActivity']) && $_POST['sendLastActivity']) {
      $teamUpdates[] = "lastPingTime = UTC_TIMESTAMP()";
      $teamUpdates[] = "lastAnswerTime = UTC_TIMESTAMP()";
   }
   if (isset($_POST['finalAnswersSent']) && $_POST['finalAnswersSent']) {
      $teamUpdates[] = "finalAnswerTime = UTC_TIMESTAMP()";
   }
   if (count($teamUpdates)) {
      if(!isset($db)) {
         // Need to connect to mysql
         $db = connect_pdo($config->db);
      }
      $stmt = $db->prepare("UPDATE team SET " . implode(', ', $teamUpdates) . " WHERE ID = :id AND password = :password;");
      $stmt->execute($teamUpdatesParams);
   }

   addBackendHint("ClientIP.answer:pass");
   addBackendHint(sprintf("Team(%s):answer", escapeHttpValue($teamID)));
   exitWithJson(array("success" => true));
}

if (!isset($_POST["answers"]) || !isset($_POST["teamID"]) || !isset($_POST["teamPassword"])) {
   error_log("answers, teamID or teamPassword is not set : ".json_encode($_REQUEST));
   exitWithJsonFailure("Requête invalide", array('error' => 'invalid'));
}
handleAnswers($tinyOrm);
