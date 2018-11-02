<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../schoolsMap/googleMap.inc.php");

function error()
{
   echo json_encode(array("success" => false));
   exit(0);
}
function output($data)
{
   echo json_encode($data);
   exit(0);
}

function mergeDuplicates($db, $schoolID) {
   // Let's get schools identical to the current one
   $query = "
      SELECT
         `school`.`ID`,
         `school`.name,
         COUNT(`group`.ID) as nbGroups
      FROM `school` AS `ref`
      JOIN `school`
      ON
         `ref`.ID = :schoolID AND
         `school`.name = ref.name AND
         `school`.zipCode = ref.zipCode
      LEFT JOIN `group`
      ON `school`.ID = `group`.schoolID
      GROUP BY `school`.ID
   ";
   $stmt = $db->prepare($query);
   $stmt->execute(array('schoolID' => $schoolID));

   $schoolIDS = array();
   $schoolIDMax;
   $nbMax = -1;
   $name = "";
   while ($row = $stmt->fetchObject())
   {
      $schoolIDS[] = $row->ID;
      if ($row->nbGroups > $nbMax)
      {
         $nbMax = $row->nbGroups;
         $schoolIDMax = $row->ID;
      }
      $name = $row->name;
      //print_r($row);
   }
   
   $stmtUpdateGroups = $db->prepare("UPDATE `group` SET `schoolID` = :schoolIDKeep WHERE `schoolID` = :schoolIDDel");
   $stmtUpdateContestants = $db->prepare("UPDATE `contestant` SET `cached_schoolID` = :schoolIDKeep WHERE `cached_schoolID` = :schoolIDDel");
   $stmtUpdateSchoolUser = $db->prepare("UPDATE `school_user` SET `schoolID` = :schoolIDKeep WHERE `schoolID` = :schoolIDDel");

   $numSchool = 1;
   // Now we can merge it !
   foreach ($schoolIDS as $ID)
   {
      if ($ID == $schoolIDMax)
         continue;
      $params = array('schoolIDKeep' => $schoolIDMax, 'schoolIDDel' => $ID);

      // Attach the groups to the school we keep
      $stmtUpdateGroups->execute($params);

      // Same thing for the contestants
      $stmtUpdateContestants->execute($params);

      // And attached the user to the new school
      $stmtUpdateSchoolUser->execute($params);

      // Delete the old school ?
      $stmtUpdateSchool = $db->prepare("UPDATE `school` SET `name` = CONCAT('_ALONE".$numSchool."_', `name`) WHERE ID = :schoolIDDel");
      unset($params['schoolIDKeep']);
      $stmtUpdateSchool->execute($params);
      $numSchool++;
   }
}

// Let's merge identical schools !
if ($_GET["action"] == "mergeSchools")
{
   if (!$_SESSION["isAdmin"])
      error();
   $queryDuplicates = "select distinct(`s1`.`ID`) FROM `school` `s1`, `school` `s2` WHERE `s1`.`ID` < `s2`.`ID` ".
      " AND `s1`.`zipCode` = `s2`.`zipCode` AND `s1`.`name` = `s2`.`name`";
   $stmtDuplicates = $db->prepare($queryDuplicates);
   $stmtDuplicates->execute();
   $nbDuplicates = 0;
   while ($rowDuplicate = $stmtDuplicates->fetchObject()) {
      $nbDuplicates++;
      mergeDuplicates($db, $rowDuplicate->ID);
   }
   if ($nbDuplicates == 1)
      $msg = translate("merge_failed_no_duplicates");
   else
      $msg = sprintf(translate("merge_success"), $nbDuplicates);
   output(array("success" => true, "msg" => $msg));   
}
if ($_GET["action"] == "getSchoolList")
{
   if (!$_SESSION["isAdmin"])
      error();
   $query = "SELECT `school`.ID, `school`.name FROM `school` ORDER BY name ASC";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $all = array();
   while ($row = $stmt->fetchObject())
   {
      $all[] = $row;
   }
   output(array("success" => true, "schools" => $all));
}
if ($_GET["action"] == "computeCoordinates")
{
   if (!$_SESSION["isAdmin"])
      error();
   $query = "SELECT `school`.* FROM `school` WHERE ID = :schoolID ORDER BY name ASC";
   $stmt = $db->prepare($query);
   $stmt->execute(array("schoolID" => $_GET["schoolID"]));
   $row = $stmt->fetchObject();
   if (is_null($row))
      output(array("success" => true, "msg" => "No school ".$_GET["schoolID"]));

   list($lat, $lng, $msg) = getCoordinatesSchool((array)$row);
   $coords = "$lng,$lat,0";
   $query = "UPDATE school SET coords = :coords, saniMsg = CONCAT(saniMsg, :msg) WHERE ID = :ID";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':ID' => $row->ID, ':coords' => $coords, ':msg' => $msg));

   output(array("success" => true, "msg" => "Coordinates computed for ".$_GET["schoolID"]));
}

error();
?> 