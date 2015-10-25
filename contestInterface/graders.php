<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
use Aws\S3\S3Client;

initSession();

if (!isset($_SESSION["teamID"])) {
   echo json_encode(array(
      'status' => 'fail',
      'reason' => 'Equipe non loggée'
   ));
   exit;
}

$teamID = $_SESSION["teamID"];
$query = "SELECT `contest`.`ID` as `ID`, `contest`.`folder` as `folder`, `contest`.`status` as `status`, `contest`.`fullFeedback` as `fullFeedback` FROM `team` JOIN `group` ON (`team`.`groupID` = `group`.`ID`) JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?";
$stmt = $db->prepare($query);
$stmt->execute(array($teamID));
if (!($row = $stmt->fetchObject())) {
   echo json_encode(array(
      'status' => 'fail',
      'reason' => 'impossible de trouver le concours'
   ));
   exit;
}

if ($row->fullFeedback == 0 && (!isset($_SESSION["closed"]) || $row->status == 'RunningContest' || $row->status == 'FutureContest')) {
   echo json_encode(array(
      'status' => 'fail',
      'reason' => 'Participation officielle sans score en direct, évaluation impossible'
   ));
   exit;
}

$contestID = $row->ID;
$contestFolder = $row->folder;

$gradersUrl = null;
$gradersUrlIE = null;
$graders = null;
if ($config->teacherInterface->generationMode == 'local') {
   $graders = file_get_contents(__DIR__.$config->teacherInterface->sContestGenerationPath.$contestFolder.'/contest_'.$contestID.'_graders.html');
} else if (!$row->fullFeedback) {
   require '../ext/autoload.php';
   $publicClient = S3Client::factory(array(
      'key'    => $config->aws->key,
      'secret' => $config->aws->secret,
      'region' => $config->aws->region,
   ));
   $publicBucket = $config->aws->bucketName;
   $gradersUrl = $privateClient->getObjectUrl($publicBucket, 'contests/'.$contestFolder.'/contest_'.$contestID.'_graders.html', '+10 minutes');
} else {
   $gradersUrl = $config->teacherInterface->sAbsoluteStaticPath.'contests/'.$contestFolder.'/contest_'.$contestID.'_graders.html';
}

header("Content-Type: application/json");
header("Connection: close");

echo json_encode(array(
   'status' => 'success',
   'graders' => $graders,
   'gradersUrl' => $gradersUrl,
   'bonusScore' => $_SESSION["bonusScore"]
));
