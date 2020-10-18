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

function handleActivity($db) {
   global $config;
   addBackendHint("ClientIP.activity:pass");

   $stmt = $db->prepare("INSERT INTO `activity` (teamID, questionID, type, answer, score, date) VALUES(:teamID, :questionID, :type, :answer, :score, NOW());");
   $stmt->execute([
      'teamID' => $_POST['teamID'],
      'questionID' => $_POST['questionID'],
      'type' => $_POST['type'],
      'answer' => isset($_POST['answer']) ? json_encode($_POST['answer']) : null,
      'score' => isset($_POST['score']) ? $_POST['score'] : null
      ]);

   exitWithJson(["success" => true]);
}

if(!isset($_POST["teamID"]) || !isset($_POST["questionID"]) || !isset($_POST["type"])) {
   error_log("teamID, questionID or type is not set : ".json_encode($_REQUEST));
   exitWithJsonFailure("Requête invalide", array('error' => 'invalid'));
}
handleActivity($db);
