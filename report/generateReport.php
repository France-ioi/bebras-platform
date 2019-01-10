<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

$contestsParams = array(
   2012 => array(
      array("contestID" => 6, "minScore" => 173, "grade" => null),
      array("contestID" => 7, "minScore" => 154, "grade" => null),
      array("contestID" => 8, "minScore" => 163, "grade" => null),
      array("contestID" => 9, "minScore" => 197, "grade" => null)
   ),
   2013 => array(
      array("contestID" => 27, "minScore" => 185, "grade" => null),
      array("contestID" => 30, "minScore" => 212, "grade" => null),
      array("contestID" => 28, "minScore" => 193, "grade" => null),
      array("contestID" => 29, "minScore" => 200, "grade" => null)
   ),
   2014 => array(
      array("contestID" => 32, "minScore" => 151, "grade" => null),
      array("contestID" => 33, "minScore" => 183, "grade" => null),
      array("contestID" => 34, "minScore" => 172, "grade" => null),
      array("contestID" => 35, "minScore" => 189, "grade" => null),
      array("contestID" => 36, "minScore" => 150, "grade" => null),
      array("contestID" => 37, "minScore" => 185, "grade" => null)
   ),
   2015 => array(
      array("contestID" => 54, "minScore" => 300, "grade" => 4, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 5, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 6, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 7, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 8, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 9, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 10, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 11, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 12, "nbContestantsPerTeam" => 1),
      array("contestID" => 54, "minScore" => 300, "grade" => 4, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 5, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 6, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 7, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 8, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 9, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 10, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 11, "nbContestantsPerTeam" => 2),
      array("contestID" => 54, "minScore" => 300, "grade" => 12, "nbContestantsPerTeam" => 2),
   ),
   2016 => array(
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 1),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 2),
      array("contestID" => "455965778962240640", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 2),
   ),
   2017 => array(
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 16, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 17, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 18, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 19, "nbContestantsPerTeam" => 1),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 16, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 17, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 18, "nbContestantsPerTeam" => 2),
      array("contestID" => "118456124984202960", "minScore" => 500, "grade" => 19, "nbContestantsPerTeam" => 2)
   ),
   2018 => array(
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 16, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 17, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 18, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 19, "nbContestantsPerTeam" => 1),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 16, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 17, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 18, "nbContestantsPerTeam" => 2),
      array("contestID" => "822122511136074554", "minScore" => 500, "grade" => 19, "nbContestantsPerTeam" => 2)
   ),
   "alkindi2017" => array(
   /*
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 4, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 5, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 6, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 7, "nbContestantsPerTeam" => 2),
   */
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 8, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 9, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 10, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 11, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 12, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 13, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 14, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 15, "nbContestantsPerTeam" => 2),
   /*
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 16, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 16, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 17, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 17, "nbContestantsPerTeam" => 2),
   */
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 18, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 18, "nbContestantsPerTeam" => 2),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 19, "nbContestantsPerTeam" => 1),
      array("contestID" => "770867629642200501", "minScore" => 500, "grade" => 19, "nbContestantsPerTeam" => 2)
   )
);


$contestsMinScores = array(
   2012 => array(
      6 => 173,
      7 => 154,
      8 => 163,
      9 => 187
   ),
   2013 => array(
      27 => 185,
      30 => 212,
      28 => 193,
      29 => 200
   ),
   2014 => array(
      32 => 151,
      33 => 183,
      34 => 172,
      35 => 189,
      36 => 150,
      37 => 185
   )
);



class ContestAnalysis
{
   static function getReport($src, $dst, $year)
   {
      global $contestsParams;

      $stats = ContestAnalysis::getGlobalStats($year);
      $data["{NB_SCHOOLS}"] = $stats->nbSchools;
      $data["{NB_STUDENTS}"] = $stats->nbStudents;
      $data["{PERCENT_GIRLS}"] = round(($stats->nbGirls/$stats->nbStudents)*100);


      $aContests = array();
      foreach ($contestsParams[$year] as $key => $contestData) {
         $aContests[] = ContestAnalysis::display($contestData["minScore"], $contestData["contestID"], $contestData["grade"], $contestData["nbContestantsPerTeam"]);
      }

      $differenciateNbContestants = ($contestsParams[$year][0]["nbContestantsPerTeam"] != null);

      $s = '';

      if ($differenciateNbContestants) {
         $s .= '<div id="nbContestantsTab"><ul><li id="nbContestants1"><a onclick="setNbContestants(1)" href="#null-1">Participations individuelles</a></li><li id="nbContestants2"><a onclick="setNbContestants(2)" href="#null-2">Participations en binômes</a></li></ul><div id="null-1" style="padding:0px;"></div><div id="null-2" style="padding:0px;"></div></div>';
      }

      $s .= "
      <div id='results'>
         <ul>";
      foreach($aContests as $Contest) {
         $s .= "<li class='nbContestants".$Contest->nbContestantsPerTeam."' id='link-".$Contest->ID."'><a href='#tabs-".$Contest->ID."'>".$Contest->tabname."</a></li>";
      }
      $s .= "</ul>";
      foreach($aContests as $Contest) {
         $s .= "<div id='tabs-".$Contest->ID."' class='content-nbContestants".$Contest->nbContestantsPerTeam."'>
            <table width=650>
            ".$Contest->content."
            </table>
         </div>";
      }
      $s .= "</div>";
      $data["{RESULTS}"] = $s;

      $content = file_get_contents($src);
      $content = str_replace(array_keys($data), array_values($data), $content);
      file_put_contents($dst, $content);
   }

   static function getGlobalStats($year)
   {
      global $db;
      global $contestsParams;

      $conditions = array();
      foreach ($contestsParams[$year] as $key => $contestData) {
         $contestID = $contestData["contestID"];
         $condition = "`group`.`contestID` = ".$contestData["contestID"];
         if ($contestData["grade"] != null and $contestData["nbContestantsPerTeam"] != null) {
            $condition = "(".$condition." AND `contestant`.`grade` = ".$contestData["grade"]." AND `team`.`nbContestants` = ".$contestData["nbContestantsPerTeam"].")";
         }
         else if ($contestData["grade"] != null) {
            $condition = "(".$condition." AND `contestant`.`grade` = ".$contestData["grade"].")";
         }
         $conditions[] = $condition;
      }

      $query = "
         SELECT 
            COUNT(DISTINCT(`group`.schoolID)) AS nbSchools,
            COUNT(*) AS nbStudents,
            SUM(IF(genre=1,1,0)) AS nbGirls,
            SUM(IF(genre=2,1,0)) AS nbBoys
         FROM `contestant`, `team`,  `group`
         WHERE 
            `contestant`.teamID = `team`.ID AND
            `team`.groupID = `group`.ID AND
            `team`.`participationType` = 'Official' AND ".
            "(".implode(" OR ", $conditions).")";
      $stmt = $db->prepare($query);
      $stmt->execute(); 
      return $row = $stmt->fetchObject();
   }

   static function getContestantsCount($contestID, $grade = null, $nbContestantsPerTeam= null)
   {
      global $db;

      $query = "
         SELECT COUNT(*) AS nbStudents
         FROM `contestant`, `team`,  `group`
         WHERE 
            `contestant`.teamID = `team`.ID AND
            `team`.groupID = `group`.ID AND
            `team`.`participationType` = 'Official' AND
            `group`.contestID = :contestID  
         ";
      $params = array("contestID" => $contestID);
      if ($grade != null) {
         $query .= " AND `contestant`.`grade` = :grade ";
         $params["grade"] = $grade;
      }
      if ($nbContestantsPerTeam != null) {
         $query .= " AND `team`.`nbContestants` = :nbContestants ";
         $params["nbContestants"] = $nbContestantsPerTeam;
      }
      $stmt = $db->prepare($query);
      $stmt->execute($params); 
      return $row = $stmt->fetchObject()->nbStudents;
   }

   static function getContestGradeInfos($contestID, $grade = null, $nbContestantsPerTeam= null)
   {
      global $db;

      $query = "SELECT * FROM contest WHERE ID = :contestID";
      $stmt = $db->prepare($query);
      $stmt->execute(array("contestID" => $contestID)); 
      $row = $stmt->fetchObject();
      $levels = array("", "6ème-5ème", "4ème-3ème", "Seconde", "Première-Terminale", "Seconde pro", "Première-Terminale pro");
      $row->category = $levels[$row->level];
      $row->tabname = $levels[$row->level];
      $gradeNames = array(
         4 => "CM1",
         5 => "CM2",
         6 => "6e",
         7 => "5e",
         8 => "4e",
         9 => "3e",
         10 => "2de",
         11 => "1re",
         12 => "Tale",
         13 => "2de pro.",
         14 => "1re pro.",
         15 => "Tale pro.",
         16 => "6e Segpa",
         17 => "5e Segpa",
         18 => "4e Segpa",
         19 => "3e Segpa",
         -3 => "Pas encore au collège",
         -4 => "Autre"
      );
      $gradeNamesTunisie = array(
         4 => "5ème",
         5 => "6ème",
         6 => "7ème",
         7 => "8ème",
         8 => "9ème",
         9 => "1ère",
         11 => "3ème",
         12 => "4ème",
         10 => "2nde",
      );
      if ($grade != null && $nbContestantsPerTeam != null) {
         $row->tabname = $gradeNames[$grade];
         $row->category = $gradeNames[$grade].' ('.($nbContestantsPerTeam > 1 ? 'participation en binôme' : 'participation individuelle').')';
         $row->ID = $row->ID."-".$grade."-".$nbContestantsPerTeam;
      }
      elseif ($grade != null) {
         $row->category = $gradeNames[$grade];
         $row->tabname = $gradeNames[$grade];
         $row->ID = $row->ID."-".$grade;
      } else {
         $row->ID = $row->ID;
      }
      return $row;
   }

   static function getMaxScore($contestID)
   {
      global $db;

      // Max scores
      $nbStudents = array();
      // TODO : add bonus
      $query = "
         SELECT SUM(`maxScore`) AS maxScore 
         FROM  `contest_question` 
         WHERE  `contestID` = :contestID
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array("contestID" => $contestID)); 
      return $row = $stmt->fetchObject()->maxScore;
   }

   static function getRankings($contestID, $grade = null, $nbContestantsPerTeam= null)
   {
      global $db;

      $params = array("contestID" => $contestID);
      $query = "
         SELECT `team`.score, COUNT(*) as count
         FROM `contestant` JOIN `team` ON `contestant`.teamID = `team`.ID
         JOIN `group` ON `team`.groupID = `group`.ID
         WHERE 
            `team`.`score` IS NOT NULL AND
            `team`.`participationType` = 'Official' AND
            `group`.contestID = :contestID ";

      if ($grade != null) {
         $query .= " AND `contestant`.`grade` = :grade ";
         $params["grade"] = $grade;
      }
      if ($nbContestantsPerTeam != null) {
         $query .= " AND `team`.`nbContestants` = :nbContestants ";
         $params["nbContestants"] = $nbContestantsPerTeam;
      }

      $query .= "
         GROUP BY `team`.score
         ORDER BY `team`.score DESC
         ";
      $stmt = $db->prepare($query);
      $stmt->execute($params); 

      $rankings = array();
      $rank = 1;
      while($row = $stmt->fetchObject())
      {
         $rankings[] = (object)array(
            'score' => $row->score,
            'rank' => $rank
            );
         $rank += $row->count;
      }
      return $rankings;
   }

   static function getTop($minScore, $contestID, $grade = null, $nbContestantsPerTeam= null)
   {
      global $db;

      $params = array("contestID" => $contestID, "minScore" => $minScore);

      $query = "
         SELECT 
            `contestant`.firstName,
            `contestant`.lastName,
            `contestant`.rank,
            `team`.score,
            `school`.name,
            `school`.city,
            `s2`.`name` as name2,
            `s2`.`city` as city2,
            algorea_registration.franceioiID
         FROM `contestant`,
            `team`
            LEFT JOIN contestant c2 ON (team.password = c2.algoreaCode)
            LEFT JOIN `school` s2 ON (c2.cached_schoolID = `s2`.ID)
            LEFT JOIN algorea_registration ON (team.password = algorea_registration.code),
            `group`
            LEFT JOIN `school` ON (`group`.schoolID = `school`.ID)
         WHERE
            `contestant`.`rank` IS NOT NULL AND 
            `team`.`score` IS NOT NULL AND
            `contestant`.teamID = `team`.ID AND ";

      if ($grade != null) {
         $query .= " `contestant`.`grade` = :grade AND ";
         $params["grade"] = $grade;
      }
      if ($nbContestantsPerTeam != null) {
         $query .= " `team`.`nbContestants` = :nbContestants AND  ";
         $params["nbContestants"] = $nbContestantsPerTeam;
      }

      $query .= "`team`.groupID = `group`.ID AND            
            `team`.`participationType` = 'Official' AND
            `group`.contestID = :contestID AND
            `team`.score >= :minScore
         ORDER BY `team`.score DESC, `contestant`.ID ASC
         ";
      $stmt = $db->prepare($query);
      $stmt->execute($params); 

      $top = array();
      while($row = $stmt->fetchObject()) {
         if ($row->name == null) {
            $row->name = $row->name2;
            $row->city = $row->city2;
         }
         if ($row->name == null) {
            $row->name = "Hors établissement";
            $row->city = "'".$row->algoreaAccount."' sur france-ioi.org";
         }
         $top[] = $row;
      }

      return $top;
   }

   static function display($minScoreTop, $contestID, $grade = null, $nbContestantsPerTeam= null)
   {
      $infos = self::getContestGradeInfos($contestID, $grade, $nbContestantsPerTeam);
      $maxScore = self::getMaxScore($contestID, $grade, $nbContestantsPerTeam);
      $nbStudents = self::getContestantsCount($contestID, $grade, $nbContestantsPerTeam);
      $aRankings = self::getRankings($contestID, $grade, $nbContestantsPerTeam);

      $aTop = self::getTop($minScoreTop, $contestID, $grade, $nbContestantsPerTeam);

      $graphID = $contestID;
      if ($grade != null && $nbContestantsPerTeam != null) {
         $graphID .= "-".$grade."-".$nbContestantsPerTeam;
      }
      elseif ($grade != null) {
         $graphID .= "-".$grade;
      }

      $s = "
      <tr>
         <td colspan=2 align=center>
            <h2>Catégorie ".$infos->category." : ".$nbStudents." élèves</h2>
            <i>Score maximum atteignable : ".$maxScore."</i>
         </td>
      </tr>
      <tr>
         <td>
            <div id='graph-".$graphID."' style='width:480px;height:320px'></div>
         </td>
         <td>
            <div id='ranks-".$graphID."' style='height:300px;width:120px;overflow-y:scroll'>
            <table border=1 cellspacing=0>
            <tr><td><b>Score</b></td><td><b>Rang</b></td></tr>";
      foreach ($aRankings as $line) {
         $s .= "<tr><td>".$line->score."</td><td>".$line->rank."</td></tr>";
      }
      $s .= "
            </table>
            </div>
         </td>
      </tr>";
      return (object)array('ID'=>$infos->ID, 'category'=>$infos->category, 'tabname'=>$infos->tabname, 'content'=>$s, 'nbContestantsPerTeam' => $nbContestantsPerTeam);
   }
}

//ContestAnalysis::getReport("template2012.inc.html", "epreuve2012.html", 2012);
//ContestAnalysis::getReport("template2013.inc.html", "resultats2013.php", 2013);
//ContestAnalysis::getReport("template2013.inc.html", "resultats2014.php", 2014);
//ContestAnalysis::getReport("template2013.inc.html", "../../castor-informatique.fr/resultats2015_castor.php", 2015);
ContestAnalysis::getReport("template2013.inc.html", "resultats2018.html", "2018");
?>
