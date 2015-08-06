<?php

/*
 * This file is a small script to transfer table_question from DynamoDB to
 * SQL. You can call it from command line with no argument, but with correct
 * values in config.json.
 */

require_once('connect.php');
require_once('models.php');
require_once('tinyORM.php');

use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\ComparisonOperator;

// get all items, even above 1MB
function getAllItems($table) {
   global $dynamoDB;
   $items = array();
   $finished = false;
   $LastEvaluatedKey = 0;
   while(!$finished) {
      $options = array('TableName' => $table);
      if ($LastEvaluatedKey) {
         $options['ExclusiveStartKey'] = $LastEvaluatedKey;
      }
      $response = $dynamoDB->scan($options);
      $items = array_merge($items, $response['Items']);
      $LastEvaluatedKey = isset($response['LastEvaluatedKey']) ? $response['LastEvaluatedKey'] : 0;
      $finished = ($LastEvaluatedKey == 0);
   }
   return $items;
}

$table = 'team_question';
$items = getAllItems($table);
foreach($items as $item) {
   $item = $tinyOrm->deformatAttributes($item);
   unset($item['ID']);
   $tinyOrm->insertSQL($table, $item);
}
