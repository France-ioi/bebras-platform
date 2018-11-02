<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
include('./config.php');

// jquery 1.9 is required for IE6+ compatibility.
script_tag('/bower_components/jquery/jquery.min.js');
?>
<style>
</style>
<?php

if (isset($_GET["password"])) {
   if (md5($_GET["password"]) == $config->teacherInterface->genericPasswordMd5) {
      $_SESSION["isAdmin"] = true;
   } else {
      echo translate("invalid_password");
      exit;
   }
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo translate("admin_restricted");
   exit;
}


if (!isset($_GET["contestID"])) {
   echo "contestID parameter is missing.";
   exit;
}
$contestID = $_GET["contestID"];

if (!isset($_GET["schoolNamePrefix"])) {
   echo "schoolNamePrefix parameter is missing.";
   exit;
}
$schoolNamePrefix = $_GET["schoolNamePrefix"];

if (!isset($_GET["nbSchools"])) {
   echo "nbSchools parameter is missing.";
   exit;
}
$nbSchools = intval($_GET["nbSchools"]);

if (!isset($_GET["startSchool"])) {
   echo "startSchool parameter is missing.";
   exit;
}
$startSchool = intval($_GET["startSchool"]);

if (!isset($_GET["nbGroupsPerSchool"])) {
   echo "nbGroupsPerSchool parameter is missing.";
   exit;
}
$nbGroupsPerSchool = intval($_GET["nbGroupsPerSchool"]);


$querySchool = "INSERT INTO `school` (`ID`, `userID`, `name`) VALUES (:schoolID, :userID, :name)";
$stmtSchool = $db->prepare($querySchool);

$queryGroup = "INSERT INTO `group` (`ID`, `schoolID`, `userID`, `name`, `contestID`, `code`, `password`, `participationType`) ".
   "VALUES(:groupID, :schoolID, :userID, :groupName, :contestID, :code, :password, 'Official')";
$stmtGroup = $db->prepare($queryGroup);

$userID = 2;
for ($iSchool = $startSchool - 1; $iSchool < $nbSchools; $iSchool++) {
   $schoolID = getRandomID();
   $schoolName = $schoolNamePrefix." ".($iSchool + 1);
   $stmtSchool->execute(array("schoolID" => $schoolID, "userID" => $userID, "name" => $schoolName));
   echo $schoolID." : ".$schoolName."<br/>";
   
   for ($iGroup = 0; $iGroup < $nbGroupsPerSchool; $iGroup++) {
      $groupID = getRandomID();
      $groupName = $schoolName." day ".($iGroup + 1);
      $code = genAccessCode($db);
      $password = genAccessCode($db);
      $stmtGroup->execute(array(
         "groupID" => $groupID,
         "schoolID" => $schoolID,
         "userID" => $userID,
         "groupName" => $groupName,
         "contestID" => $contestID,
         "code" => $code,
         "password" => $password
      ));
      echo $groupName."<br/>";
   }
}


