<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo json_encode((object)array("status" => 'error', "message" => "Only admins can do that!"));
   exit;
}

// To (re)compute team.nbContestants:
// update team set nbContestants = 0; insert into team (ID) select teamID from contestant on duplicate key update nbContestants = nbContestants + 1;

// To pass contestants with grade < 0 as unofficial:
//UPDATE contestant join team on contestant.teamID = team.ID SET team.participationType = 'Unofficial' WHERE contestant.grade < 0;

// To reset ranks: update contestant set rank = NULL;

function computeNbContestants($db, $contestID, $maxContestants) {
   $stmt = $db->prepare('update team
            JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
            set nbContestants = 0
            where `group`.contestID = :contestID;');
   $stmt->execute(array('contestID' => $contestID));
   $stmt = $db->prepare('insert into team (ID) 
            select teamID from contestant
            JOIN team as teamjoin on (teamjoin.ID = contestant.teamID)
            JOIN `group` ON (`teamjoin`.`groupID` = `group`.`ID`)
            WHERE `group`.contestID = :contestID
            on duplicate key update team.nbContestants = LEAST(:maxContestants, team.nbContestants + 1);');
   $stmt->execute(array('contestID' => $contestID, 'maxContestants' => $maxContestants));
}

function getContestInfos($db, $contestID) {
   $stmt = $db->prepare('select ID, allowTeamsOfTwo, rankGrades, rankNbContestants from contest where ID = :contestID');
   $stmt->execute(array('contestID' => $contestID));
   $contestInfos = $stmt->fetch(PDO::FETCH_ASSOC);
   if (!$contestInfos) {
      echo json_encode((object)array("status" => 'error', "message" => "Contest not found!"));
      exit;
   }
   // get grades if relevant
   if ($contestInfos['rankGrades']) {
      $stmt = $db->prepare('select distinct contestant.grade from contestant
               JOIN team on (team.ID = contestant.teamID)
               JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
               where 
               `team`.participationType = \'Official\' and
               `group`.contestID = :contestID;');
      $stmt->execute(array('contestID' => $contestID));
      $contestInfos['grades'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
   }
   return $contestInfos;
}

function computeRanks($db, $contestInfos)
{  
   $query = "
      UPDATE `contestant` as `c1`, 
      (
         SELECT 
            `contestant2`.`ID`,
            @curRank := IF(@prevVal=`contestant2`.`score`, @curRank, @studentNumber) AS rank,
            @studentNumber := @studentNumber + 1 as studentNumber,
            @prevVal:=score
         FROM 
         (
            SELECT 
               `contestant`.`ID`, 
               `contestant`.`firstName`,
               `contestant`.`lastName`,
               `team`.`score`
            FROM `contestant` 
            JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) 
            JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
            WHERE 
               `team`.`participationType` = 'Official' AND 
";
   if ($contestInfos['rankGrades']) {
      $query .= " `contestant`.`grade` = :grade AND ";
   }
   if ($contestInfos['rankNbContestants'] && $contestInfos['allowTeamsOfTwo']) {
      $query .= " `team`.`nbContestants` = :nbContestants AND ";  
   }
   $query .= "
            `group`.`contestID` = :contestID                
            ORDER BY
            `team`.`score` DESC
         ) `contestant2`, 
         (
            SELECT 
               @curRank :=0, 
               @prevVal:=null, 
               @studentNumber:=1
         ) r
      ) AS `c2` 
      SET `c1`.`rank` = `c2`.`rank` 
      WHERE `c1`.`ID` = `c2`.`ID`
   ";

   $stmt = $db->prepare($query);
   $maxContestants = 1;
   if ($contestInfos['rankNbContestants'] && $contestInfos['allowTeamsOfTwo']) {
      $maxContestants = 2;
   }
   for ($i = 1; $i<= $maxContestants; $i++) {
      if ($contestInfos['rankGrades']) {
         foreach ($contestInfos['grades'] as $grade) {
            $values = array(':contestID' => $contestInfos['ID'], 'grade' => $grade);
            if ($maxContestants != 1) {
               $values['nbContestants'] = $i;
            }
            $stmt->execute($values);
         }
      } else {
         $values = array(':contestID' => $contestInfos['ID']);
         if ($maxContestants != 1) {
            $values['nbContestants'] = $i;
         }
         $stmt->execute($values);
      }
   }
}

function computeRanksSchool($db, $contestInfos)
{
   $query = "
    UPDATE `contestant` as `c1`,
    (
       SELECT 
           `contestant2`.`ID`,
            @curRank := IF(@prevSchool=`contestant2`.`schoolID`, IF(@prevScore=`contestant2`.`score`, @curRank, @studentNumber + 1), 1) AS schoolRank, 
            @studentNumber := IF(@prevSchool=`contestant2`.`schoolID`, @studentNumber + 1, 1) as studentNumber, 
            @prevScore:=score,
            @prevSchool:=`contestant2`.`schoolID`
    FROM 
    (
       SELECT 
          `contestant`.`ID`,
          `contestant`.`firstName`,
          `contestant`.`lastName`,
          `team`.`score`,
          `group`.`schoolID`
      FROM `contestant`
            JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`)
            JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
      WHERE 
          `team`.`participationType` = 'Official' AND ";
   if ($contestInfos['rankGrades']) {
      $query .= " `contestant`.`grade` = :grade AND ";
   }
   if ($contestInfos['rankNbContestants'] && $contestInfos['allowTeamsOfTwo']) {
      $query .= " `team`.`nbContestants` = :nbContestants AND ";  
   }
   $query .= " `group`.`contestID` = :contestID
      ORDER BY `group`.`schoolID`, `team`.`score` DESC
   ) `contestant2`,
   (
       SELECT 
          @curRank :=0, 
          @prevScore:=null, 
          @studentNumber:=0, 
          @prevSchool:=null
         ) r
    ) as `c2`
    SET `c1`.`schoolRank` = `c2`.`schoolRank` 
    WHERE `c1`.`ID` = `c2`.`ID`;";   
   $stmt = $db->prepare($query);
   $maxContestants = 1;
   if ($contestInfos['rankNbContestants'] && $contestInfos['allowTeamsOfTwo']) {
      $maxContestants = 2;
   }
   for ($i = 1; $i<= $maxContestants; $i++) {
      if ($contestInfos['rankGrades']) {
         foreach ($contestInfos['grades'] as $grade) {
            $values = array(':contestID' => $contestInfos['ID'], 'grade' => $grade);
            if ($maxContestants != 1) {
               $values['nbContestants'] = $i;
            }
            $stmt->execute($values);
         }
      } else {
         $values = array(':contestID' => $contestInfos['ID']);
         if ($maxContestants != 1) {
            $values['nbContestants'] = $i;
         }
         $stmt->execute($values);
      }
   }
}

if ((!isset($_SESSION["isAdmin"])) || (!$_SESSION["isAdmin"])) {
   echo json_encode((object)array("success" => false, "message" => "Seul un admin peut calculer les classements"));
   exit;
}

$contestID = $_REQUEST["contestID"];

$contestInfos = getContestInfos($db, $contestID);
if ($contestInfos['rankNbContestants'] && $contestInfos['allowTeamsOfTwo']) {
   computeNbContestants($db, $contestID, 2);
}
computeRanks($db, $contestInfos);
computeRanksSchool($db, $contestInfos);
unset($db);

echo json_encode(array("success" => true));
