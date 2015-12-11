<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

$noSQL = true;

require_once("config.php");
require_once("certiGen.inc.php");
require_once __DIR__.'/../shared/common.php';

ini_set('display_errors',1); 
error_reporting(E_ALL);

function getExpRank($rank) {
   return ($rank == 1)?"ère":"e";
}

function genSchoolCertificates($schoolID, $conf) {
   shell_exec("rm -rf ".CERTIGEN_EXPORTDIR."/".$conf['folder'].'/'.$schoolID);
   mkdir(CERTIGEN_EXPORTDIR."/".$conf['folder'].'/'.$schoolID, $code = 0777, $recursive = true);
   list($aGroups, $aContestants) = getGroupsAndContestants($schoolID, $conf);
   $nbStudents =  count($aContestants);
   if ($nbStudents == 0)
      return 0;

   foreach ($aContestants as $contestant) {
      $contestant->Group->certificates .= getHtmlCertificate($contestant, $conf);
      $contestant->Group->contestants[] = $contestant;
   }

   $groupsHtml = "";
   foreach ($aGroups as $groupID => $group) {
      $groupHtml = getGroupContestantsList($group, $schoolID, $conf);
      $groupHtml .= $group->certificates;
      $groupFullHtml = file_get_contents("school_template.html");
      $groupFullHtml = str_replace("{groups}", $groupHtml, $groupFullHtml);
      file_put_contents("certificates_group.html", $groupFullHtml);
      $groupPdf = CERTIGEN_EXPORTDIR.'/'.$conf['folder'].'/'.CertiGen::getGroupOutput($groupID, $schoolID, $conf).'.pdf';
      shell_exec("wkhtmltopdf -O landscape -n -R 5 -L 5 -T 5 -B 5 -s A4 certificates_group.html ".$groupPdf);
      $groupsHtml .= $groupHtml;
   }

   $schoolHtml = file_get_contents($conf['school_template']);
   $schoolHtml = str_replace("{groups}", $groupsHtml, $schoolHtml);


   file_put_contents("certificates_school.html", $schoolHtml);
   $outPdf = CERTIGEN_EXPORTDIR.'/'.$conf['folder'].'/'.CertiGen::getSchoolOutput($schoolID, $conf).'.pdf';
   // warning: requires wkhtmltopdf with qt patches
   shell_exec("wkhtmltopdf -O landscape -n -R 5 -L 5 -T 5 -B 5 -s A4 certificates_school.html ".$outPdf);
   return $nbStudents;
}

function getGroupsAndContestants($schoolID, $conf) {
   global $db;

   $aGroups = array();
   $aContestants = array();

   // Groups infos
   $aGroups = array();
   $query = "
      SELECT          
         `group`.`ID`,
         `group`.`name`,
         `school`.`name` AS schoolName,
         `school`.`city` AS schoolCity,
         `user`.`gender`,
         `user`.`lastName`
       FROM `school`, `group`, `user`
       WHERE 
          `school`.ID = `group`.schoolID AND
          `group`.userID = `user`.ID AND
          `school`.ID = :schoolID
       ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':schoolID' => $schoolID)); 
   while($group = $stmt->fetchObject()) {
      $group->gender = ($group->gender == 'F')?"Mme.":"M.";
      $group->coordName = $group->gender." ".$group->lastName;
      $group->contestants = array();
      $group->certificates = "";
      $aGroups[$group->ID] = $group;
   }

   $aContestsData = array();
   foreach ($conf['contestIDs'] as $id) {
      $aContestsData[$id] = (object)array();
   }

   $nbStudents = array();
   if (!$conf['differenciateNbStudents']) {
      $query = "
      SELECT `group`.contestID, COUNT(*) AS nbStudents
      FROM `contestant`, `team`,  `group`
      WHERE 
         `contestant`.teamID = `team`.ID AND
         `team`.groupID = `group`.ID AND
         `team`.`participationType` = 'Official' AND
         `group`.contestID IN (".implode(', ', $conf['contestIDs']).")  
      GROUP BY `group`.contestID        
      ";
      $stmt = $db->prepare($query);
      $stmt->execute(); 
      while($row = $stmt->fetchObject())
      {
         $nbStudents[$row->contestID] = $row->nbStudents;
         $aContestsData[$row->contestID] = new stdClass();
         $aContestsData[$row->contestID]->nbStudents = $row->nbStudents;
      }
   } else {
      foreach($conf['contestIDs'] as $contestID) {
         $aContestsData[$contestID] = new stdClass();
         $aContestsData[$contestID]->nbStudents = [];
         $nbStudents[$contestID] = [];
         foreach ($conf['grades'] as $grade) {
            $nbStudents[$contestID][$grade] = [];
            $aContestsData[$contestID]->nbStudents[$grade] = [];
            for ($i = 1; $i<= $conf['nbContestantsMax']; $i++) {
               $nbStudents[$contestID][$grade][$i] = getTotalContestants($contestID, $grade, $i);
               $aContestsData[$contestID]->nbStudents[$grade][$i] = $nbStudents[$contestID][$grade][$i];
            }
         }
      }
   }
   // Max scores
   $nbStudents = array();
   $query = "
   SELECT  `contestID`, SUM(`maxScore`) as maxScore 
   FROM  `contest_question` 
   WHERE  `contestID` IN (".implode(', ', $conf['contestIDs']).")  
   GROUP BY  `contestID` 
   ";
   $stmt = $db->prepare($query);
   $stmt->execute(); 
   while($row = $stmt->fetchObject()) {
      $aContestsData[$row->contestID]->maxScore = $row->maxScore;
   }

   if (!$conf['differenciateNbStudents']) {
      $query = "
      SELECT
         `group`.contestID,
         COUNT(*) AS count
      FROM `contestant`, `team`,  `group`, `contest`
      WHERE 
         `contestant`.teamID = `team`.ID AND
         `team`.groupID = `group`.ID AND
         `group`.contestID = `contest`.ID AND
         `group`.schoolID = :schoolID AND
         `team`.`participationType` = 'Official' AND
         `group`.contestID IN (".implode(', ', $conf['contestIDs']).")  
      GROUP BY `group`.schoolID, `group`.contestID    
      ";
      $stmt = $db->prepare($query);
      $stmt->execute(array(':schoolID' => $schoolID));
      while($row = $stmt->fetchObject()) {
         $aContestsData[$row->contestID]->nbStudentsSchool = $row->count;
      }
   } else {
      $query = "
      SELECT
         COUNT(*) AS count
      FROM `contestant`, `team`,  `group`, `contest`
      WHERE 
         `contestant`.teamID = `team`.ID AND
         `team`.groupID = `group`.ID AND
         `group`.contestID = `contest`.ID AND
         `group`.schoolID = :schoolID AND
         `team`.nbContestants = :nbContestants AND
         `contestant`.grade = :grade AND
         `team`.`participationType` = 'Official' AND
         `group`.contestID =:contestID  
      ";
      $stmt = $db->prepare($query);
      foreach($conf['contestIDs'] as $contestID) { 
         $aContestsData[$contestID]->nbStudentsSchool = [];
         foreach ($conf['grades'] as $grade) {
            $aContestsData[$contestID]->nbStudentsSchool[$grade] = [];
            for ($i = 1; $i<= $conf['nbContestantsMax']; $i++) {
               $stmt->execute(['contestID' => $contestID, 'schoolID' => $schoolID, 'nbContestants' => $i, 'grade' => $grade]);
               $aContestsData[$contestID]->nbStudentsSchool[$grade][$i] = $stmt->fetchColumn();
            }
         }
      }
   }

   // Contestants
   $query = "
   SELECT 
      `group`.ID AS groupID,
      `group`.schoolID,
      `group`.name AS groupName,
      `group`.contestID,
      `contest`.level AS level,

      `contestant`.ID AS idContestant,
      `contestant`.grade,
      `contestant`.genre,     
      `contestant`.lastName,
      `contestant`.firstName,
      `contestant`.algoreaCode,
      `contestant`.rank AS rank,
      `contestant`.schoolRank AS schoolRank,
      `team`.nbContestants AS nbContestants,
      `team`.score AS score
   FROM `contestant`, `team`,  `group`, `contest`
   WHERE 
      `contestant`.teamID = `team`.ID AND
      `team`.groupID = `group`.ID AND
      `group`.contestID = `contest`.ID AND
      `group`.schoolID = :schoolID AND
      `team`.`participationType` = 'Official' AND
      `group`.contestID IN (".implode(', ', $conf['contestIDs']).")  
   ORDER BY lastName, firstName        
   ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':schoolID' => $schoolID));
   while($row = $stmt->fetchObject()) {
      $row->userName = ucwords(mb_strtolower($row->lastName." ".$row->firstName, "UTF-8"));
      $row->schoolName = $aGroups[$row->groupID]->schoolName;
      $row->schoolCity = $aGroups[$row->groupID]->schoolCity;
      $row->coordName = $aGroups[$row->groupID]->coordName;
      $row->Group = $aGroups[$row->groupID];
      if (!$conf['differenciateNbStudents']) {
         $row->nbStudents = $aContestsData[$row->contestID]->nbStudents;
         $row->nbStudentsSchool = $aContestsData[$row->contestID]->nbStudentsSchool;
      } else {
         $row->nbStudents = $aContestsData[$row->contestID]->nbStudents[$row->grade][$row->nbContestants];
         $row->nbStudentsSchool = $aContestsData[$row->contestID]->nbStudentsSchool[$row->grade][$row->nbContestants];
      }
      $row->maxScore = $aContestsData[$row->contestID]->maxScore;
      if ($row->rank > $row->nbStudents) {
         echo "ERROR RANK {$row->rank} > {$row->nbStudents}\n";
      }
      $aContestants[] = $row;
   }
   return array($aGroups, $aContestants);
}

function getGroupContestantsList($group, $schoolID, $conf) {
   $html = file_get_contents($conf['group_template']);
   $html = str_replace("{schoolName}", $group->schoolName, $html);
   $html = str_replace("{groupName}", $group->name, $html);
   $html = str_replace("{coordName}", $group->coordName, $html);
   $gradeNames = array(
      -1 => "Professeur",
      4 => "CM1",
      5 => "CM2",
      6 => "6<sup>e</sup>",
      7 => "5<sup>e</sup>",
      8 => "4<sup>e</sup>",
      9 => "3<sup>e</sup>",
      11 => "Première",
      12 => "Terminale",
      10 => "Seconde",
      13 => "Seconde Pro.",
      14 => "Première Pro.",
      15 => "Terminale Pro.",
   );
   $nbContestantsNames = array(
      1 => "Individuelle",
      2 => "En binôme"
   );
   $list = "";
   foreach ($group->contestants as $contestant) {
      $list .= "<tr><td>".$contestant->userName."</td><td>".$contestant->score."/".$contestant->maxScore."</td><td>".$gradeNames[$contestant->grade]."</td><td>".$nbContestantsNames[$contestant->nbContestants]."</td><td style='text-align:right'>".
         $contestant->rank." / ".$contestant->nbStudents."</td><td style='text-align:right'>".$contestant->schoolRank." / ".$contestant->nbStudentsSchool."</td></tr>\r\n";
   }
   $html = str_replace("{listContestants}", $list, $html);
   return $html;
}

function setSampleContestantData($contestant) {
   $contestant->level = 4;
   $contestant->userName = "Albert Dupond";
   $contestant->score = 143;
   $contestant->genre = 2;
   $contestant->maxScore = 176;
   $contestant->rank = 100000;
   $contestant->nbStudents = 44560;
   $contestant->nbContestants = 1;
   $contestant->schoolRank = 5;
   $contestant->nbStudentsSchool = 20;
   $contestant->schoolName = "Lycée Maximilien Sorre, Cachan XXXXXXXXXXXXXXXX";
   $contestant->coordName = "M. Leluron";
   $contestant->AlgoreaCode = null;
   return $contestant;
}

function getHtmlCertificate($contestant, $conf) {
   //$contestant = setSampleContestantData($contestant);
   $categoryNames = array(
      1 => "Niveau 6<sup>e</sup>-5<sup>e</sup>",
      2 => "Niveau 4<sup>e</sup>-3<sup>e</sup>",
      3 => "Niveau Seconde",
      4 => "Niveau 1<sup>ère</sup>-Terminale",
   );

   $gradeNames = array(
      -1 => "Professeur",
      4 => "Niveau CM1",
      5 => "Niveau CM2",
      6 => "Niveau 6<sup>e</sup>",
      7 => "Niveau 5<sup>e</sup>",
      8 => "Niveau 4<sup>e</sup>",
      9 => "Niveau 3<sup>e</sup>",
      10 => "Niveau Seconde",
      11 => "Niveau Première",
      12 => "Niveau Terminale",
      13 => "Niveau Seconde Pro.",
      14 => "Niveau Première Pro.",
      15 => "Niveau Terminale Pro.",
   );

   $strRank = "";
   $strAlgoreaCode = "";
   $strExtraLines = "";
   if ($contestant->algoreaCode) {
      $strAlgoreaCode = '<div style="height:0px; overflow:visible;font-size:20.8px;">
            Qualifié'.($contestant->genre == 1 ? 'e' : '')." pour pour le 1<sup>er</sup> tour du concours Algoréa.
            <br/>
            Validez votre qualification sur algorea.org avec le code : ".$contestant->algoreaCode."
            </div>";
   }
   if ($contestant->rank <= $contestant->nbStudents / 2) {
      $scoreSeparator = ",";
      $strRank = "la ".$contestant->rank.getExpRank($contestant->rank)." place sur ".$contestant->nbStudents;
   } else {
      $strExtraLines .= "<br/>";
   }
   if ($contestant->schoolRank <= $contestant->nbStudentsSchool / 2) {
      if ($strRank != "") {         
         $strRank .= ",<br/>";
      }
      $scoreSeparator = ",";
      $strRank .= "et la ".$contestant->schoolRank.getExpRank($contestant->schoolRank)." place sur ".$contestant->nbStudentsSchool." dans l'établissement";
   } else {
      if ($strRank != "") {
         $strRank = "et ".$strRank;
      } else {
         $scoreSeparator = ".";
      }
      $strExtraLines .= "<br/>";
   }
   if ($strRank != "") {
      $strRank .= ".";
   }

   if (preg_match('/^M\./', $contestant->coordName))
      $title = "coordinateur";
   else
      $title = "coordinatrice";
   $category = $gradeNames[$contestant->grade];
   if ($conf['differenciateNbStudents']) {
      $category .= ' - '.($contestant->nbContestants == 2 ? 'binôme' : 'individuel');
   }
   $data = array(
      "category" => $category,
      "userName" => $contestant->userName,
      "score" => $contestant->score,
      "maxScore" => $contestant->maxScore,
      "scoreSeparator" => $scoreSeparator,
      "strRank" => $strRank,
      "strAlgoreaCode" => $strAlgoreaCode,
      "date" => date("d/m/Y"),
      "coordinator" => $contestant->coordName.", ".$title,
      "schoolName" => $contestant->schoolName,
      "schoolCity" => $contestant->schoolCity         
   );

   $html = file_get_contents($conf["certificate_template"]);
   foreach ($data as $key => $value) {
      $html = str_replace("{".$key."}", $value, $html);
   }
   return $html;
}
   
if (isset($_GET["schoolID_test"])) {
   genSchoolCertificates($_GET["schoolID_test"]);
   echo "done<br>";
}

?>
