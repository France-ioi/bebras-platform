<?php
/* Copyright (c) 2020 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

// means that connect.php won't make any sql connection if in dynamoDB mode
$noSQL = true;
$noSessions = true;

require_once("../vendor/autoload.php");
use Aws\DynamoDb\Exception;
require_once("../shared/connect.php");
require_once("../shared/tinyORM.php");
include_once("common_contest.php");

$data = $_GET['q'];
if(!$data) { return; }

if(!isset($dynamoDB)) {
    $dynamoDB = connect_dynamodb($config->aws);
}

$dynamoDB->putItem([
    'TableName' => 'prod_backup',
    'Item' => [
        'Data' => ['S' => $_GET['q']]
    ]]);
