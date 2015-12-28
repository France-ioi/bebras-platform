<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");


if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}


$query = "SELECT count(*) AS `totalContestants`, `contestant`.`grade`, `team`.`nbContestants` FROM `contestant` ".
   "JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) ".
   "JOIN `group` ON (`group`.`ID` = `team`.`groupID`) ".
   "LEFT JOIN `user_user` ON (`group`.`userID` = `user_user`.`userID`) ".
   "WHERE `group`.`schoolID` = :schoolID ".
   "AND `team`.`participationType` = 'Official' ".
   "AND `group`.`contestID` = :contestID ".
   "AND (`group`.`userID` = :userID OR (`user_user`.`targetUserID` = :userID AND `user_user`.`accessType` <> 'none')) ";

   
$data = array("userID" => $_SESSION["userID"],
   "contestID"  => $_REQUEST["contestID"],
   "schoolID" => $_REQUEST["schoolID"]);

if (isset($_REQUEST["groupID"])) {
   $data["groupID"] = $_REQUEST["groupID"];
   $query .= "AND `group`.`ID` = :groupID ";
}

$query .= "GROUP BY `team`.`nbContestants`, `contestant`.`grade`";

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
   "AND `group`.`contestID` = :contestID ".
   "GROUP BY `team`.`nbContestants`, `contestant`.`grade`";

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

