<?php

/*
 * This file is a small script to transfer table_question from DynamoDB to
 * SQL. You can call it from command line with no argument, but with correct
 * values in config.json.
 */

// Debugging option
$display = true;

require_once('../config.php');
require_once('connect.php');
require_once('models.php');
require_once('tinyORM.php');

use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\ComparisonOperator;

function displayMsg($msg) {
   global $display;
   if($display) { echo $msg; }
}


if(!$config->transferTeamQuestion->srcTable) {
   $config->transferTeamQuestion->srcTable = $config->db->dynamoDBPrefix . 'team_question';
}

$finished = false;
$lastUpdatedTeam = null;
$stmt = $db->prepare("SELECT * FROM team_question_transfer_state;");
$stmt->execute();
$lastUpdatedTeam = $stmt->fetch(PDO::FETCH_ASSOC);
if($lastUpdatedTeam) {
   displayMsg("LastUpdatedTeam loaded.\n");
} else {
   die("Unable to load LastUpdatedTeam. Please check that the table team_question_transfer_state has at least one line.\n");
}

// Loop by chunks until finished
while(!$finished) {
   // Fetch teams to update
   $query = "SELECT ID, startTime FROM " . $config->transferTeamQuestion->dateTable . " WHERE";
   $queryArgs = [];
   if($lastUpdatedTeam) {
      $query .= " ((startTime = :startTime AND ID > :ID) OR startTime > :startTime) AND";
      $queryArgs = $lastUpdatedTeam;
   }
   $query .= " startTime <= " . $config->transferTeamQuestion->startTimeLimit;
   $query .= " ORDER BY startTime ASC, ID ASC LIMIT " . $config->transferTeamQuestion->nbTeamsPerChunk . " ;";
   $stmt = $db->prepare($query);
   $stmt->execute($queryArgs);
   $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
   $stmt = null;

   if(count($teams) < $config->transferTeamQuestion->nbMinTeams) {
      displayMsg('Number of teams insufficient (' . count($teams) . ' < ' . $config->transferTeamQuestion->nbMinTeams . "), ending processing.\n");
      $finished = true;
      $teams = [];
   } else {
      displayMsg('First team: ' . $teams[0]['ID'] . ' // ' . $teams[0]['startTime'] . "\n");
   }

   foreach($teams as $team) {
      $items = array();
      // Load data
      $dynamoFinished = false;
      $LastEvaluatedKey = null;
      while(!$dynamoFinished) {
         $options = [
            'TableName' => $config->transferTeamQuestion->srcTable,
            'KeyConditionExpression' => 'teamID = :t',
            'ExpressionAttributeValues' => [':t' => ['N' => $team['ID']]]];
         if ($LastEvaluatedKey) {
            $options['ExclusiveStartKey'] = $LastEvaluatedKey;
         }
         displayMsg('?');
         $response = $dynamoDB->query($options);
         displayMsg('!');
         $items = array_merge($items, $response['Items']);
         $LastEvaluatedKey = isset($response['LastEvaluatedKey']) ? $response['LastEvaluatedKey'] : 0;
    
         $dynamoFinished = ($LastEvaluatedKey == 0);
      }
      $response = null;
      foreach($items as $item) {
         displayMsg('.');
         $item = $tinyOrm->deformatAttributes($item);
         unset($item['ID']);
         $fields = [];
         foreach(['ffScore', 'date', 'answer'] as $field) {
            if(isset($item[$field])) {
               $fields[] = $field;
            }
         }
         $tinyOrm->insertSQL($config->transferTeamQuestion->dstTable, $item, ['on duplicate update' => $fields]);
      }
      $lastUpdatedTeam = $team;
   }
   displayMsg("\n");
   if($lastUpdatedTeam) {
      displayMsg('Last team: ' . $lastUpdatedTeam['ID'] . ' // ' . $lastUpdatedTeam['startTime'] . "\n");
      $stmt = $db->prepare("UPDATE team_question_transfer_state SET ID = :ID, startTime = :startTime;");
      if($stmt->execute($lastUpdatedTeam)) {
         displayMsg("LastUpdatedTeam saved.\n");
      } else {
         displayMsg("Warning: unable to save LastUpdatedTeam.\n");
      }
   }
   if(!$finished) {
      displayMsg("Sleeping for " . $config->transferTeamQuestion->sleepSecs . " seconds...\n");
      sleep($config->transferTeamQuestion->sleepSecs);
   }
}

