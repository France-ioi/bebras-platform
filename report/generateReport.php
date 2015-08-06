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
      array("contestID" => 51, "minScore" => 70, "grade" => 6),
      array("contestID" => 51, "minScore" => 73, "grade" => 7),
      array("contestID" => 51, "minScore" => 80, "grade" => 8),
      array("contestID" => 51, "minScore" => 80, "grade" => 9),
      array("contestID" => 51, "minScore" => 85, "grade" => 10),
      array("contestID" => 51, "minScore" => 90, "grade" => 11),
      array("contestID" => 51, "minScore" => 95, "grade" => 12),
      array("contestID" => 51, "minScore" => 70, "grade" => -3),
      array("contestID" => 51, "minScore" => 95, "grade" => -4)
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
         $aContests[] = ContestAnalysis::display($contestData["minScore"], $contestData["contestID"], $contestData["grade"]);
      }

      $s = "
      <div id='results'>
         <ul>";
      foreach($aContests as $Contest) {
         $s .= "<li><a href='#tabs-".$Contest->ID."'>".$Contest->category."</a></li>";
      }
      $s .= "</ul>";
      foreach($aContests as $Contest) {
         $s .= "<div id='tabs-".$Contest->ID."'>
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
         if ($contestData["grade"] != null) {
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

   static function getContestantsCount($contestID, $grade = null)
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
      $stmt = $db->prepare($query);
      $stmt->execute($params); 
      return $row = $stmt->fetchObject()->nbStudents;
   }

   static function getContestGradeInfos($contestID, $grade = null)
   {
      global $db;

      $query = "SELECT * FROM contest WHERE ID = :contestID";
      $stmt = $db->prepare($query);
      $stmt->execute(array("contestID" => $contestID)); 
      $row = $stmt->fetchObject();
      $levels = array("", "6ème-5ème", "4ème-3ème", "Seconde", "Première-Terminale", "Seconde pro", "Première-Terminale pro");
      $row->category = $levels[$row->level];
      $gradeNames = array(
         6 => "6ème",
         7 => "5ème",
         8 => "4ème",
         9 => "3ème",
         10 => "Seconde",
         11 => "Première",
         12 => "Terminale",
         -3 => "Pas encore au collège",
         -4 => "Autre"
      );
      if ($grade != null) {
         $row->category = $gradeNames[$grade];
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

   static function getRankings($contestID, $grade = null)
   {
      global $db;

      $params = array("contestID" => $contestID);
      $query = "
         SELECT `team`.score, COUNT(*) as count
         FROM `contestant`, `team`,  `group`
         WHERE 
            `contestant`.teamID = `team`.ID AND
            `team`.groupID = `group`.ID AND
            `team`.`participationType` = 'Official' AND
            `group`.contestID = :contestID ";

      if ($grade != null) {
         $query .= " AND `contestant`.`grade` = :grade ";
         $params["grade"] = $grade;
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

   static function getTop($minScore, $contestID, $grade = null)
   {
      global $db;

      $params = array("contestID" => $contestID, "minScore" => $minScore);

      $query = "
         SELECT 
            `contestant`.firstName,
            `contestant`.lastName,
            `contestant`.gradeRank,
            `contestant`.rank,
            `team`.score,
            `school`.name,
            `school`.city,
            `s2`.`name` as name2,
            `s2`.`city` as city2,
            algorea_registration.algoreaAccount
         FROM `contestant`,
            `team`
            LEFT JOIN contestant c2 ON (team.password = c2.algoreaCode)
            LEFT JOIN `school` s2 ON (c2.cached_schoolID = `s2`.ID)
            LEFT JOIN algorea_registration ON (team.password = algorea_registration.code),
            `group`
            LEFT JOIN `school` ON (`group`.schoolID = `school`.ID)
         WHERE
            `contestant`.teamID = `team`.ID AND ";

      if ($grade != null) {
         $query .= " `contestant`.`grade` = :grade AND ";
         $params["grade"] = $grade;
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
         if ($grade != null) {
            $row->rank = $row->gradeRank;
         }
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

   static function display($minScoreTop, $contestID, $grade = null)
   {
      $infos = self::getContestGradeInfos($contestID, $grade);
      $maxScore = self::getMaxScore($contestID, $grade);
      $nbStudents = self::getContestantsCount($contestID, $grade);
      $aRankings = self::getRankings($contestID, $grade);

      $aTop = self::getTop($minScoreTop, $contestID, $grade);

      $graphID = $contestID;
      if ($grade != null) {
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
      </tr>
      <tr>
         <td colspan=2 align=center>
            <p>
            Les ".count($aTop)." premiers ayant un score supérieur ou égal à ".$minScoreTop." sont listés ci-dessous.
            </p>
         </td>
      </tr>
      <tr>
         <td colspan=2>
         <div style='height:200px;width:100%;overflow-y:scroll'>
         <table border=1 cellspacing=0 style='width:100%'>
         <tr>
            <td><b>Élève</b></td>
            <td width=80><b>Score</b></td>
            <td><b>Rang</b></td>
            <td><b>Établissement</b></td>
         </tr>";
      foreach ($aTop as $line) {
         $s .= "
         <tr>
            <td>".$line->firstName." ".$line->lastName[0].".</td>
            <td>".$line->score."</td>
            <td>".$line->rank."</td>
            <td>".$line->name.", ".$line->city."</td>
         </tr>";
      }
      $s .= "
         </table>
         </div>
         </td>
      </tr>";
      return (object)array('ID'=>$infos->ID, 'category'=>$infos->category, 'content'=>$s);
   }
}

//ContestAnalysis::getReport("template2012.inc.html", "epreuve2012.html", 2012);
//ContestAnalysis::getReport("template2013.inc.html", "resultats2013.php", 2013);
//ContestAnalysis::getReport("template2013.inc.html", "resultats2014.php", 2014);
ContestAnalysis::getReport("template2013.inc.html", "resultats2015_algorea.php", 2015);
?>
