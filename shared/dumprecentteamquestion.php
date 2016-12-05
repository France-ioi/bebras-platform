<?php

require_once 'tinyORM.php';

$tableName = $config->db->dynamoDBPrefix.'team_question';
$beginDate = "2016-11-11 07:00:00";
$endDate = "2016-12-11 13:00:00";

do {
    $request = [
        'TableName' => $tableName,
        'FilterExpression' => '#dt > :beginDate and #dt < :endDate',
        'ExpressionAttributeNames' => ['#dt' => 'date'],
        'ExpressionAttributeValues' => [
            ':beginDate' => ['S' => $beginDate],
            ':endDate' => ['S' => $endDate]
        ],
    ];
    if(isset($response) && isset($response['LastEvaluatedKey'])) {
        $request['ExclusiveStartKey'] = $response['LastEvaluatedKey'];
    }
    $response = $dynamoDB->scan($request);
    foreach ($response['Items'] as $item) {
        echo json_encode($item)."\n";
    }
}
while(isset($response['LastEvaluatedKey'])); 
