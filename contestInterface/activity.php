<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */
$noSessions = true;
require_once("../shared/connect.php");
require_once("common_contest.php");

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

function handleActivities($db) {
   global $config;
   addBackendHint("ClientIP.activity:pass");

   if (!isset($_POST["data"])) {
      exitWithJsonFailure("data is not set", array('error' => 'invalid'));
   }

   $stmt = $db->prepare("INSERT INTO `activity` (teamID, questionID, type, answer, score, date) VALUES(:teamID, :questionID, :type, :answer, :score, FROM_UNIXTIME(:timestamp));");
   
   foreach ($_POST["data"] as $activity) {
      if (!isset($activity["teamID"]) || !isset($activity["questionID"]) || !isset($activity["type"])) {
         error_log("teamID, questionID or type is not set in activity: " . json_encode($activity));
         continue;
      }

      $timestamp = intval($activity['date']) / 1000;
      // Check timestamp is within the last 10 minutes
      if ($timestamp < (time() - 10 * 60)) {
         $timestamp = time();
      }
      
      $stmt->execute([
         'teamID' => $activity['teamID'],
         'questionID' => $activity['questionID'],
         'type' => $activity['type'],
         'answer' => isset($activity['answer']) ? json_encode($activity['answer']) : null,
         'score' => isset($activity['score']) ? $activity['score'] : null,
         'timestamp' => $timestamp
      ]);
   }
   
   exitWithJson(["success" => true]);
}

handleActivities($db);