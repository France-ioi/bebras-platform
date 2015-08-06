<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("googleMap.inc.php");

function addSchoolCoordinates()
{
   global $db;
   $onlyComplete = true; // set false to replace all (about 25 minutes in total)
   $execRequests = true; // set false for debugging

   $query = "SELECT `school`.* FROM `school` WHERE 1";
   if ($onlyComplete) {
      $query .= " AND `school`.coords = '0,0,0'";
   }
   // for step by step
   // $query .= " LIMIT 5";

   $stmt = $db->prepare($query);
   $stmt->execute();
   $all = array(); 
   while($row = $stmt->fetchObject())
   {      
      $all[] = $row;
   }
   echo "Number of queries to be made: " . count($all). "\n<br>";
   flush(); ob_flush();   
   foreach ($all as $id => $row)
   {
      list($lat, $lng, $msg) = getCoordinatesSchool((array)$row);
      $coords = "$lng,$lat,0";
      if ($execRequests)
      {
         $query = "UPDATE school SET coords = :coords, saniMsg = CONCAT(saniMsg, :msg) WHERE ID = :ID";
         $stmt = $db->prepare($query);
         $stmt->execute(array(':ID' => $row->ID, ':coords' => $coords, ':msg' => $msg));
      }
      sleep(1);
      echo "Completed query: " . ($id+1). " for ID=".$row->ID." [".$msg. "]<br>";
      flush(); ob_flush();
   }
}

header('Content-type: text/html; charset=utf-8');
addSchoolCoordinates();

?>