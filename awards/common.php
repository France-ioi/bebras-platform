<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */
/*
A faire : 
- quel ordre pour les écoles ?

*/
require_once(__DIR__."/../shared/common.php");

ini_set('display_errors',1); 
error_reporting(E_ALL);

$levelStr = array(
   1 => "6ème-5ème",
   2 => "4ème-3ème",
   3 => "Seconde",
   4 => "Première-Terminale",
   5 => "Seconde professionnelle",
   6 => "Première-Terminale professionnelle",
);


function tpl($sTemplateName, $aData = array(), $bPrint = true)
{
   ob_start();
   extract((array)$aData);
   include($sTemplateName);   
   $s = ob_get_contents();
   ob_end_clean();
   // Removes HTML comments
   $s = preg_replace("/<!--.*-->/", "", $s);
   if($bPrint)
      echo $s;
   else
      return $s;
}

function updateContestNbAwarded($year) {
   global $db;

   $query = "UPDATE `school_year` JOIN (SELECT count(`contestant`.`ID`) AS `nbAwarded`, `group`.`schoolID`
   FROM `contestant`
   JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`)
   JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
   JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`)
   WHERE (`contestant`.`rank` IS NOT NULL
            AND `contest`.`minAward2Rank` IS NOT NULL
            AND `contestant`.`rank` <= `contest`.`minAward2Rank`)
   GROUP BY `schoolID`) AS `awards` ON (`awards`.`schoolID` = `school_year`.`schoolID`)
   SET `school_year`.`awarded` = `school_year`.`awarded` + `awards`.`nbAwarded`
   WHERE `school_year`.`year` = :year";
   $stmt = $db->prepare($query);
   $stmt->execute(array('year' => $year));
}

function updateNbAwarded($year) {
   global $db;
   $query = "UPDATE `school_year` SET `awarded` = 0 WHERE `year` = :year";
   $stmt = $db->prepare($query);
   $stmt->execute(array('year' => $year));
   updateContestNbAwarded($year);
   $query = "SELECT SUM(`awarded`) as `nbAwarded`, `year` FROM `school_year` GROUP BY `year`";
   $stmt = $db->prepare($query);
   $stmt->execute();
   while($row = $stmt->fetchObject()) {
      echo $row->year . " : ".$row->nbAwarded."<br/>";
   }
}

function getSchoolInfos()
{
   global $db;
   $query = "
      SELECT school.*,
         userMain.firstName,
         userMain.lastName,
         user.firstName as firstNameOther,
         school_user.ownGroupsContestants,
         user.lastName as lastNameOther
      FROM `school`
      JOIN user as userMain
      ON school.userID = userMain.ID
      LEFT JOIN school_user
      ON school_user.schoolID = school.ID AND school_user.confirmed = 1
      LEFT JOIN user
      ON user.ID = school_user.userID
      ORDER BY school.ID, lastNameOther, firstNameOther
      ";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $aRes = array();
   while($row = $stmt->fetchObject())
   {
      if (!array_key_exists($row->ID, $aRes))
      {
         $aRes[$row->ID] = clone (object)$row;
         unset($aRes[$row->ID]->firstNameOther, $aRes[$row->ID]->lastNameOther);
         $aRes[$row->ID]->users = array();
      }
      $aRes[$row->ID]->users[] = (object)array(
         'firstName' => $row->firstNameOther,
         'lastName' => $row->lastNameOther,
         'ownGroupsContestants' => $row->ownGroupsContestants
      );
   }//print_r($aRes);die();
   return $aRes;
}

function getAllAwardedContestants($year, $awardNum) {
   global $db;
   $query = "
      SELECT 
         `group`.contestID,
         `group`.schoolID,
         `contestant`.ID AS idContestant,         
         `contestant`.lastName,
         `contestant`.firstName,
         `contestant`.rank AS rank,
         `team`.score AS score,
         `contest`.level AS level,
         IF(`contestant`.`rank` <= `contest`.`minAward1Rank`,1,2) AS `award`
      FROM `contestant`
      JOIN `team` AS `team` ON (`contestant`.`teamID` = `team`.`ID`)
      JOIN `group` AS `group` ON (`team`.`groupID` = `group`.`ID`)
      JOIN `school` AS `school` ON (`group`.`schoolID` = `school`.`ID`)
      JOIN `contest` AS `contest` ON (`group`.`contestID` = `contest`.`ID`)
      WHERE 
         `team`.`participationType` = 'Official' AND
         `contest`.`year` = ? AND
         (`contestant`.`rank` IS NOT NULL
            AND `contest`.`minAward2Rank` IS NOT NULL
            AND `contest`.`minAward1Rank` IS NOT NULL
            AND `contestant`.`rank` <= `contest`.`minAward".$awardNum."Rank`)
      ORDER BY `school`.country, `school`.city, `school`.name,
               `group`.contestID, 
               `contestant`.rank, lastName, firstName        
      ";
   $stm = $db->prepare($query);
   $stm->execute(array($year));
   return $stm->fetchAll(PDO::FETCH_OBJ);
}

function getAllData()
{
   // Gets all the contestants data
   $currentYear = date("Y");
   $aAllContestants = getAllAwardedContestants($currentYear, 2);

   // Group them by schools
   $aSchools = array();
   foreach ($aAllContestants as $Cont)
   {
      if (!array_key_exists($Cont->schoolID, $aSchools))
      {
         $aSchools[$Cont->schoolID] = (object)array(
            'ID' => $Cont->schoolID,
            'nbTot' => 0,
            'aContestants' => array()
            );
      }
      $aSchools[$Cont->schoolID]->nbTot++;
      $aSchools[$Cont->schoolID]->aContestants[] = $Cont;
   }
   
   // Add the schools data
   $aAllData = getSchoolInfos();
   foreach ($aSchools as $School)
   {
       foreach (array('name', 'address', 'zipcode', 'city', 'country', 'firstName', 'lastName', 'users') as $field)
          $School->$field = $aAllData[$School->ID]->$field;
   }

   // Make the groups
   foreach ($aSchools as $School)
   {
      $School->nbGroups = ceil($School->nbTot / 10);
      $School->aGroups = array();
      array_pad($School->aGroups, $School->nbGroups, array());

      $nbByGroup = ceil($School->nbTot / $School->nbGroups);
      foreach ($School->aContestants as $i => $Cont)
      {
         $idGroup = floor($i/$nbByGroup);
         $School->aGroups[$idGroup][] = $Cont;
      }
      unset($School->aContestants);
   }
   usort($aSchools, function($Sch1, $Sch2){
      // Country then id (for random) : to minimze the risk of errors when posting the letters
      return strcmp($Sch1->country.$Sch1->ID, $Sch2->country.$Sch2->ID);
   });
   return $aSchools;
}

