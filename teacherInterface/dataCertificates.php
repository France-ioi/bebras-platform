<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");


if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

if (!isset($_POST['contestID']) || !isset($_POST['schoolID'])) {
   echo "Vous n'avez pas spécifié de contestID ou de schoolID.";
}

$stmt = $db->prepare("SELECT * FROM `contest` WHERE ID = :contestID;");
$stmt->execute(['contestID' => $_POST['contestID']]);
$contest = $stmt->fetch();
if (!$contest) {
   echo "Impossible de trouver de concours avec l'ID ".$_POST['contestID'].'.';
}

$groupBy = '';
$select = '';
if ($contest['rankGrades']) {
   if ($contest['rankNbContestants']) {
      $groupBy = 'GROUP BY `team`.`nbContestants`, `contestant`.`grade`';
      $select = ', `contestant`.`grade`, `team`.`nbContestants`';
   } else {
      $groupBy = 'GROUP BY `contestant`.`grade`';
      $select = ', `contestant`.`grade`';
   }
} elseif ($contest['rankNbContestants']) {
   $groupBy = 'GROUP BY `team`.`nbContestants`';
   $select = ', `team`.`nbContestants`';
}

$query = "SELECT count(*) AS `totalContestants`, `contestant`.`grade`, `team`.`nbContestants` FROM `contestant` ".
   "JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) ".
   "JOIN `group` ON (`group`.`ID` = `team`.`groupID`) ".
   "LEFT JOIN `user_user` ON (`group`.`userID` = `user_user`.`userID`) ".
   "WHERE `group`.`schoolID` = :schoolID ".
   "AND `team`.`participationType` = 'Official' ".
   "AND `group`.`contestID` = :contestID ";

   
$data = array("contestID"  => $_REQUEST["contestID"],
   "schoolID" => $_REQUEST["schoolID"]);

$query .= $groupBy;

$stmt = $db->prepare($query);
$stmt->execute($data);

$itemsPerSchool = array();
while ($row = $stmt->fetchObject()) {
   $itemsPerSchool[] = $row;
}

$query = "SELECT count(*) AS `totalContestants`, `contestant`.`grade`, `team`.`nbContestants` FROM `contestant` ".
   "JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) ".
   "JOIN `group` ON (`group`.`ID` = `team`.`groupID`) ".
   "WHERE `team`.`participationType` = 'Official' ".
   "AND `group`.`contestID` = :contestID ". $groupBy;

$data = array("contestID"  => $_REQUEST["contestID"]);

$stmt = $db->prepare($query);
$stmt->execute($data);

$itemsPerContest = array();
while ($row = $stmt->fetchObject()) {
   $itemsPerContest[] = $row;
}

$items = array(
   "perSchool" => $itemsPerSchool,
   "perContest" => $itemsPerContest
);

echo json_encode($items);

