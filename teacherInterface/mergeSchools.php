<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo json_encode((object)array("status" => 'error', "message" => translate("admin_restricted")));
   exit;
}

if ((!isset($_GET["schoolIDRemove"])) || (!isset($_GET["schoolIDKeep"]))) {
   echo "missing parameters schoolIDRemove and schoolIDKeep";
   exit;
}

$schoolIDRemove = $_GET["schoolIDRemove"];
$schoolIDKeep = $_GET["schoolIDKeep"];

$queriesParams = array("schoolIDKeep" => $schoolIDKeep, "schoolIDRemove" => $schoolIDRemove);

function runSchoolQuery($db, $query, $queriesParams) {
   echo $query."<br/>";
   $stmt = $db->prepare($query);
   $stmt->execute($queriesParams);
   echo "done ".$stmt->rowCount()." rows affected.";
   echo "<br/><br>";
}

echo "Updating school_user :<br/>";
$query = "UPDATE `school_user` SET `schoolID` = :schoolIDKeep WHERE `schoolID` = :schoolIDRemove";
runSchoolQuery($db, $query, $queriesParams);

echo "Merging shared school_year :<br/>";
$query = "UPDATE school_year sKeep ".
   "JOIN school_year sRemove ON (sKeep.schoolID = :schoolIDKeep AND ".
                                 "sRemove.schoolID = :schoolIDRemove AND ".
                                 "sKeep.year = sRemove.year AND ".
                                 "sKeep.contest = sRemove.contest)".
   "SET sKeep.nbOfficialContestants = sKeep.nbOfficialContestants + sRemove.nbOfficialContestants, ".
   "sKeep.awarded = sKeep.awarded + sRemove.awarded ";
runSchoolQuery($db, $query, $queriesParams);

echo "Deleting shared school_year from old school :<br/>";
$query = "DELETE sRemove.* FROM school_year sKeep ".
   "JOIN school_year sRemove ON (sKeep.schoolID = :schoolIDKeep AND ".
                                 "sRemove.schoolID = :schoolIDRemove AND ".
                                 "sKeep.year = sRemove.year AND ".
                                 "sKeep.contest = sRemove.contest)";
runSchoolQuery($db, $query, $queriesParams);


echo "Transfering remaining old school_year :<br/>";
$query = "UPDATE school_year SET schoolID = :schoolIDKeep WHERE schoolID = :schoolIDRemove";
runSchoolQuery($db, $query, $queriesParams);

echo "Transfering old groups :<br/>";
$query = "UPDATE `group` SET `schoolID` = :schoolIDKeep WHERE `schoolID` = :schoolIDRemove";
runSchoolQuery($db, $query, $queriesParams);

echo "Delete old school :<br/>";
$query = "DELETE FROM school WHERE ID = :schoolIDRemove";
$stmt = $db->prepare($query);
$stmt->execute(array("schoolIDRemove" => $schoolIDRemove));
echo "done ".$stmt->rowCount()." rows affected.";
echo "<br/><br>";

$query = "SELECT officialEmail, alternativeEmail, lastLoginDate FROM user JOIN school_user ON (school_user.userID = user.ID AND school_user.schoolID = :schoolIDKeep)";
$stmt = $db->prepare($query);
$stmt->execute(array("schoolIDKeep" => $schoolIDKeep));


while ($row = $stmt->fetchObject()) {
   echo $row->officialEmail.", ".$row->alternativeEmail." : ".$row->lastLoginDate."<br/>";
}


