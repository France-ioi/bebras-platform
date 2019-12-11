<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

ini_set('error_reporting', E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 1);

require_once('../vendor/autoload.php');
require_once('../shared/common.php');
require_once("commonAdmin.php");
require_once('contest-generator/ContestGenerator.php');





if (!array_key_exists("action", $_REQUEST)) {
   echo json_encode(['success' => false, 'message' => 'no action provided']);
   exit;
}
if (!array_key_exists("contestID", $_REQUEST)) {
   echo json_encode(['success' => false, 'no contest ID provided']);
   exit;
}
if (!array_key_exists("contestFolder", $_REQUEST)) {
   echo json_encode(['success' => false, 'no contest folder provided']);
   exit;
}

$action = $_REQUEST["action"];
$contestID = $_REQUEST["contestID"];
$contestFolder = $_REQUEST["contestFolder"];


if ($action === "prepare") {
   /* Create a fresh contestFolder by replacing the timestamp suffix. */
   $timestamp = time();
   if (array_key_exists("newFolder", $_REQUEST) && $_REQUEST["newFolder"] === "true") {
      $contestFolder = preg_replace("/(\.[0-9]+)+$/", "", $contestFolder) . "." . $timestamp;
   }
   try {
      // Retrieve the question's list
      $questions = getQuestions($db, $contestID);
      $questionsUrl = array();
      $questionsKey = array();
      foreach ($questions as $curQuestion) {
         $questionsUrl[] = $curQuestion->path;
         $questionsKey[] = $curQuestion->key;
      }
      echo json_encode(array(
         'success' => true,
         'questionsUrl' => $questionsUrl,
         'questionsKey' => $questionsKey,
         'contestFolder' => $contestFolder
      ));
   } catch (Exception $e) {
      echo json_encode(['success' => false, ]);
      echo json_encode([
         'success' => false,
         'message' => $e->getMessage(),
         'contestFolder' => $contestFolder
      ]);
   }
   exit;
}

if ($action === "generate") {
   // TODO: fail unless the contest folder is empty
   try {
      $generator = new ContestGenerator($config);
      $generator->generate();
      echo json_encode(['success' => true]);
   } catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
   }
   exit;
}

if ($action === "setFolder") {
   $roles = getRoles();
   $request = [
      "modelName" => "contest",
      "model" => getViewModel("contest"),
      "filters" => [],
      "fields" => ["folder"],
      "records" => [
         [
            "ID" => $contestID,
            "values" => [
               "folder" => $contestFolder
            ]
         ]
      ]
   ];
   $success = true == updateRows($db, $request, $roles);
   echo json_encode(['success' => $success]);
   exit;
}

echo json_encode(['success' => false, 'message' => 'unknown action']);
exit;
