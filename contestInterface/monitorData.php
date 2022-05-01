<?php
require_once("../shared/common.php");

$stmt = $db->prepare("SELECT ID FROM `group` WHERE password = :password;");
$stmt->execute(['password' => $_POST['password']]);
$groupId = $stmt->fetchColumn();

if(!$groupId) {
   echo json_encode(['success' => false, "error" => translate("invalid_password")]);
   exit;
}

$stmt = $db->prepare("
SELECT
   password, createTime, startTime, endTime, lastAnswerTime, lastPingTime, finalAnswerTime,
   DATE_ADD(startTime, INTERVAL nbMinutes MINUTE) < UTC_TIMESTAMP() AS contestHasEnded, UTC_TIMESTAMP() AS currentTime,
   contestant.firstName, contestant.lastName
FROM team
JOIN contestant ON contestant.teamID = team.ID
WHERE groupID = :groupID
");
$stmt->execute(['groupID' => $groupId]);
$data = $stmt->fetchAll();


/*$data = [
   ['password' => 'abcdefgh', 'finished' => false, 'lastActivityMinutes' => 1],
   ['password' => 'defghijk', 'finished' => false, 'lastActivityMinutes' => 10],
   ['password' => 'efghijkl', 'finished' => false, 'lastActivityMinutes' => 4],
   ['password' => 'bcdefghi', 'finished' => true, 'lastActivityMinutes' => 1],
];*/

echo json_encode(['success' => true, "teams" => $data]);