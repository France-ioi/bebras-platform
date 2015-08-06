<?php

/* This file is a small script to transfer team_question from dynamoDB to
 * SQL.
 */

// number of team questions in SQL insert request
$max_teamQuestions_in_request = 20;
$noSessions = true;
require_once(__DIR__.'/connect.php');

require_once dirname(__FILE__).'/../ext/autoload.php';
if (!isset($dynamoDB)) {
   $dynamoDB = connect_dynamoDB($config);
}

require_once(__DIR__.'/tinyORM.php');

// get teamIDs of yesterday
function getTeamIDs() {
   global $db;
   $yesterday = date('Y-m-d',strtotime("-1 days"));
   $startTimeAbove = $yesterday.' 00:00:00';
   $startTimeBelow = $yesterday.' 23:59:59';
   $query = 'select ID from team where startTime < ? and startTime > ?;';
   $sth = $db->prepare($query);
   $sth->execute(array($startTimeBelow, $startTimeAbove));
   return $sth->fetchAll(PDO::FETCH_ASSOC);
}

$results = array();
function insert_team_question($tinyOrm, $result) {
   global $results, $max_teamQuestions_in_request;
   if (!count($result)) {
      return;
   }
   $results[] = array('teamID' => $result['teamID'], 'questionID' => $result['questionID'], 'answer' => isset($result['answer']) ? $result['answer'] : null, 'ffScore' => isset($result['ffScore']) ? $result['ffScore'] : null, 'date' => $result['date']);
   if (count($results) >= $max_teamQuestions_in_request) {
      print_r($results);
      $tinyOrm->batchWriteSQL('team_question', $results, array('teamID', 'questionID', 'answer', 'ffScore', 'date'), array('answer', 'ffScore', 'date'));
      $results = array();
   }
}

function finish_inserts($tinyOrm) {
   global $results;
   if (count($results)) {
      print_r($results);
      $tinyOrm->batchWriteSQL('team_question', $results, array('teamID', 'questionID', 'answer', 'ffScore', 'date'), array('answer', 'ffScore', 'date'));
   }
}


function transfer_team_questions($dynamoDB, $db, $tinyOrm, $teamIDs) {
   foreach($teamIDs as $teamID) {
      $results = $tinyOrm->select('team_question', array('teamID', 'questionID', 'date', 'answer', 'ffScore'), array(
         'teamID' => $teamID['ID'],
      ));
      foreach ($results as $id => $result) {
         insert_team_question($tinyOrm, $result);
      }
   }
   finish_inserts($tinyOrm);
}

$teamIDs = getTeamIDs();
echo 'treating '.count($teamIDs)." teams\n";
transfer_team_questions($dynamoDB, $db, $tinyOrm, $teamIDs);
