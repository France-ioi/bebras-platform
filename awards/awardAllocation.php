<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("../modelsManager/csvExport.php");

/*
 *  updates the number of participants of each school for a given year
 *  TODO: contest IDs shouldn't be hard-coded
 */
function updateSchoolsParticipants($db, $year) {
   $query = "INSERT IGNORE INTO `school_year` (`schoolID`, `year`) SELECT `school`.`ID`, :year as `year` FROM `school`";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year)); 

   $query = "UPDATE `school_year`, (SELECT `school`.`ID`, count(`contestant`.`ID`) `nbContestants` FROM
   `school`
   JOIN `user` ON (`school`.`userID` = `user`.`ID`)
   JOIN `group` ON (`group`.`schoolID` = `school`.`ID`)
   JOIN `team` ON (`team`.`groupID` = `group`.`ID`)
   JOIN `contestant` ON (`contestant`.`teamID` = `team`.`ID`)
   JOIN `contest` ON (`contest`.`ID` = `group`.`contestID`)
   WHERE `team`.`participationType` = 'Official'
   AND `contest`.`year` = :year
   GROUP BY `school`.`ID`) `participation`
   SET `school_year`.`nbOfficialContestants` = `participation`.`nbContestants`
   WHERE `year` = '".$year."' AND `school_year`.`schoolID` = `participation`.`ID`";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year)); 
}

/*
 *  What is the max rank of contestant that should be awarded
 *  find the highest rank <= maxNbContestants + 1 and substract 1
 *  then find the highest rank and nbParticipants below that rank
 */
function getAwardedContestantsInfos($db, $year, $contestID, $maxNbContestants, $joinSchoolYearToContestant) {
   $query = "SELECT max(`contestant`.`rank`) as `maxRank`, count(`contestant`.`ID`) as `nbContestants` 
   FROM ".$joinSchoolYearToContestant."
   WHERE `team`.`participationType` = 'Official'
   AND `group`.`contestID` = :contestID
   AND `school_year`.`year` = :year
   AND `contestant`.`rank` <= :rank";

   $stmt = $db->prepare($query);
   $stmt->execute(array("rank" => $maxNbContestants + 1, "contestID" => $contestID, "year" => $year));
   $row = $stmt->fetchObject();

   $nextNbContestants = $row->nbContestants;
   $maxRank = $row->maxRank - 1;
   
   $stmt = $db->prepare($query);
   $stmt->execute(array("rank" => $maxRank, "contestID" => $contestID, "year" => $year));

   $row = $stmt->fetchObject();

   $row->nextNbContestants = $nextNbContestants;
   return $row;
}

function getAwardsLimits($db, $year, $contestIDs, $nbTotalAwards, $levelAwards) {
   $joinSchoolYearToContestant = "`school_year`
      JOIN `group` ON (`group`.`schoolID` = `school_year`.`schoolID`)
      JOIN `team` ON (`team`.`groupID` = `group`.`ID`)
      JOIN `contestant` ON (`contestant`.`teamID` = `team`.`ID`)";


   $query3 = "SELECT count(*) AS `nbContestantsTotal`, `name` FROM (
      SELECT `contestant`.`ID`, `contest`.`name`
      FROM ".$joinSchoolYearToContestant."
      JOIN `contest` ON (`contest`.`ID` = `group`.`contestID`)
      WHERE `team`.`participationType` = 'Official'
      AND `group`.`contestID` = :contestID
      AND `school_year`.`year` = :year
   ) AS `tmp`";
   $stmt3 = $db->prepare($query3);
   $totalNbContestants = 0;
   $levelNbContestants = array();
   $levelName = array();
   foreach ($contestIDs as $key => $contestID) {
      $stmt3->execute(array("contestID" => $contestID, "year" => $year));
      $row3 = $stmt3->fetchObject();
      $levelNbContestants[$key] = $row3->nbContestantsTotal;
      $totalNbContestants += $row3->nbContestantsTotal;
      $levelName[$key] = $row3->name;
   }

   $nbRemainingAwards = $nbTotalAwards;
   $levelMaxRanks = array();
   echo "<style>td { border:solid black 1px;text-align:center;padding:5px }</style>";
   echo "<table cellspacing=0><tr><td>Niveau</td><td>Récompense jusqu'au rang</td><td>Nombre de candidats</td><td>Total du niveau</td><td>Taux de récompense</td><td>Seuil suivant</td></tr>";
   foreach ($contestIDs as $key => $contestID) {
      if ($levelAwards[$key] == "") {
         $levelAwards[$key] = round($nbTotalAwards * $levelNbContestants[$key] / $totalNbContestants);
      }
      $awardedContestantsInfo = getAwardedContestantsInfos($db, $year, $contestID, $levelAwards[$key], $joinSchoolYearToContestant);
      $levelMaxRanks[] = $awardedContestantsInfo->maxRank;
      $nbRemainingAwards -= $awardedContestantsInfo->nbContestants;

      echo "<tr><td>".$levelName[$key]."</td>".
           "<td>".$awardedContestantsInfo->maxRank."</td>".
           "<td>".$awardedContestantsInfo->nbContestants."</td>".
           "<td>".$levelNbContestants[$key]."</td>".
           "<td>".round(100 * $awardedContestantsInfo->nbContestants / $levelNbContestants[$key], 2)." %</td>".
           "<td>".$awardedContestantsInfo->nextNbContestants."</td></tr>";
   }
   echo "</table><br/>";
   echo "Récompenses non attribuées : ".$nbRemainingAwards."<br/>";

   $query = "SELECT count(*) AS `nbSchools` FROM (SELECT DISTINCT `school_year`.`schoolID`
      FROM ".$joinSchoolYearToContestant."
      WHERE `team`.`participationType` = 'Official'
      AND `school_year`.`year` = :year
      AND ((`group`.`contestID` = ".$contestIDs[0]." AND `contestant`.`rank` <= :maxRankLevel1) OR
           (`group`.`contestID` = ".$contestIDs[1]." AND `contestant`.`rank` <= :maxRankLevel2) OR
           (`group`.`contestID` = ".$contestIDs[2]." AND `contestant`.`rank` <= :maxRankLevel3) OR
           (`group`.`contestID` = ".$contestIDs[3]." AND `contestant`.`rank` <= :maxRankLevel4) OR
           (`group`.`contestID` = ".$contestIDs[4]." AND `contestant`.`rank` <= :maxRankLevel5) OR
           (`group`.`contestID` = ".$contestIDs[5]." AND `contestant`.`rank` <= :maxRankLevel6)
)
      GROUP BY `school_year`.`schoolID`) AS `tmp`";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year,
      "maxRankLevel1" => $levelMaxRanks[0],
      "maxRankLevel2" => $levelMaxRanks[1],
      "maxRankLevel3" => $levelMaxRanks[2],
      "maxRankLevel4" => $levelMaxRanks[3],
      "maxRankLevel5" => $levelMaxRanks[4],
      "maxRankLevel6" => $levelMaxRanks[5]));
   $row = $stmt->fetchObject();
   echo "Nombre d'établissements récompensés : ".$row->nbSchools."<br/>";
   echo "Nb de lots moyen par établissement : ".round(($nbTotalAwards - $nbRemainingAwards) / $row->nbSchools, 2)."<br/>";

   $query = "SELECT count(contestant.ID) AS `nbAwarded`, `school_year`.`schoolID`, `school`.`name`, `school`.`city`, `school`.`country`, `school_year`.`nbOfficialContestants`
      FROM ".$joinSchoolYearToContestant."
      JOIN `school` ON `school`.`ID` = `school_year`.`schoolID`
      WHERE `team`.`participationType` = 'Official'
      AND `school_year`.`year` = :year
      AND ((`group`.`contestID` = ".$contestIDs[0]." AND `contestant`.`rank` <= :maxRankLevel1) OR
           (`group`.`contestID` = ".$contestIDs[1]." AND `contestant`.`rank` <= :maxRankLevel2) OR
           (`group`.`contestID` = ".$contestIDs[2]." AND `contestant`.`rank` <= :maxRankLevel3) OR
           (`group`.`contestID` = ".$contestIDs[3]." AND `contestant`.`rank` <= :maxRankLevel4) OR
           (`group`.`contestID` = ".$contestIDs[4]." AND `contestant`.`rank` <= :maxRankLevel5) OR
           (`group`.`contestID` = ".$contestIDs[5]." AND `contestant`.`rank` <= :maxRankLevel6)
)
      GROUP BY `school_year`.`schoolID`
      ORDER BY `nbAwarded` DESC";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year,
      "maxRankLevel1" => $levelMaxRanks[0],
      "maxRankLevel2" => $levelMaxRanks[1],
      "maxRankLevel3" => $levelMaxRanks[2],
      "maxRankLevel4" => $levelMaxRanks[3],
      "maxRankLevel5" => $levelMaxRanks[4],
      "maxRankLevel6" => $levelMaxRanks[5],

));
   echo "Établissements les plus récompensés (3 lots ou plus) : <br/>";
   echo "<table cellspacing=0><tr><td>Établissement</td><td>Lots</td><td>Candidats au total</td><td>% récompensés</td><td>schoolID</td></tr>";
   while ($row = $stmt->fetchObject()) {
      if ($row->nbAwarded < 3) {
         break;
      }
      $maxAwardedInSchool = $row->nbAwarded;
      $rateAwarded = round($row->nbAwarded * 100 / $row->nbOfficialContestants, 2);
      echo "<tr><td>".$row->name.", ".$row->city." (".$row->country.")</td><td>".$maxAwardedInSchool."</td><td>".$row->nbOfficialContestants."</td><td>".$rateAwarded."%</td><td>".$row->schoolID."</td></tr>";
   }
   echo "</table><br/>";


   $query = "SELECT count(contestant.ID) AS `nbAwarded`, `school`.`ID` as `schoolID`, `school`.`name`, `school`.`city`, `school`.`country`, `school_year`.`nbOfficialContestants`
      FROM `school`
      LEFT JOIN `school_year` ON (`school`.`ID` = `school_year`.`schoolID`)
      LEFT JOIN `group` ON (`group`.`schoolID` = `school_year`.`schoolID`)
      LEFT JOIN `team` ON (`team`.`groupID` = `group`.`ID` AND `team`.`participationType` = 'Official')
      LEFT JOIN `contestant` ON (`contestant`.`teamID` = `team`.`ID`
      
      AND ((`group`.`contestID` = ".$contestIDs[0]." AND `contestant`.`rank` <= :maxRankLevel1) OR
           (`group`.`contestID` = ".$contestIDs[1]." AND `contestant`.`rank` <= :maxRankLevel2) OR
           (`group`.`contestID` = ".$contestIDs[2]." AND `contestant`.`rank` <= :maxRankLevel3) OR
           (`group`.`contestID` = ".$contestIDs[3]." AND `contestant`.`rank` <= :maxRankLevel4) OR
           (`group`.`contestID` = ".$contestIDs[4]." AND `contestant`.`rank` <= :maxRankLevel5) OR
           (`group`.`contestID` = ".$contestIDs[5]." AND `contestant`.`rank` <= :maxRankLevel6))
      )
      
      WHERE `school_year`.`year` = :year
     
      GROUP BY `school`.`ID`
      ORDER BY `nbOfficialContestants` DESC";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year,
      "maxRankLevel1" => $levelMaxRanks[0],
      "maxRankLevel2" => $levelMaxRanks[1],
      "maxRankLevel3" => $levelMaxRanks[2],
      "maxRankLevel4" => $levelMaxRanks[3],
      "maxRankLevel5" => $levelMaxRanks[4],
      "maxRankLevel6" => $levelMaxRanks[5]
));
   echo "Établissements avec le plus de participants :<br/>";
   echo "<table cellspacing=0><tr><td>Établissement</td><td>Lots</td><td>Candidats au total</td><td>% récompensés</td><td>schoolID</td></tr>";
   for ($iEtab = 0; $iEtab < 50; $iEtab++) {
      $row = $stmt->fetchObject();
      $maxAwardedInSchool = $row->nbAwarded;
      $rateAwarded = round($row->nbAwarded * 100 / $row->nbOfficialContestants, 2);
      echo "<tr><td>".$row->name.", ".$row->city." (".$row->country.")</td><td>".$maxAwardedInSchool."</td><td>".$row->nbOfficialContestants."</td><td>".$rateAwarded."%</td><td>".$row->schoolID."</td></tr>";
   }
   echo "</table>";



   echo "<br/>Télécharger le <a href='awardAllocation.php?csv=1".
      "&levelMaxRank1=".$levelMaxRanks[0].
      "&levelMaxRank2=".$levelMaxRanks[1].
      "&levelMaxRank3=".$levelMaxRanks[2].
      "&levelMaxRank4=".$levelMaxRanks[3].
      "&levelMaxRank5=".$levelMaxRanks[4].
      "&levelMaxRank6=".$levelMaxRanks[5].
      "'>fichier csv</a><br/>";

}

function getCsv($db, $year, $levelMaxRanks) {
   global $contestIDs;
   $fields = array(
      "user_ID" => "int",
      "user_gender" => "string",
      "user_firstName" => "string",
      "user_lastName" => "string",
      "user_officialEmail" => "string",
      "user_alternativeEmail" => "string",
      "school_ID" => "int",
      "school_name" => "string",
      "school_address" => "string",
      "school_zipcode" => "string",
      "school_city" => "string",
      "school_country" => "string",
      "team_ID" => "int",
      "contestant_ID" => "int",
      "contestant_firstName" => "string",
      "contestant_lastName" => "string",
      "contestant_score" => "int",
      "group_name" => "string",
      "group_grade" => "int"
   );


   $query = "
      SELECT
      `user`.`ID` as `user_ID`,
      `user`.`gender` as `user_gender`,
      `user`.`firstName` as `user_firstName`,
      `user`.`lastName` as `user_lastName`,
      `user`.`officialEmail` as `user_officialEmail`,
      `user`.`alternativeEmail` as `user_alternativeEmail`,
      `school`.`ID` as `school_ID`,
      `school`.`name` as `school_name`,
      `school`.`address` as `school_address`,
      `school`.`zipcode` as `school_zipcode`,
      `school`.`city` as `school_city`,
      `school`.`country` as `school_country`,
      `team`.`ID` as `team_ID`,
      `contestant`.`ID` as `contestant_ID`,
      `contestant`.`firstName` as `contestant_firstName`,
      `contestant`.`lastName` as `contestant_lastName`,
      `team`.`score` as `contestant_score`,
      `group`.`name` as `group_name`,
      `group`.`grade` as `group_grade`
      FROM `school_year`
      JOIN `group` ON (`group`.`schoolID` = `school_year`.`schoolID`)
      JOIN `team` ON (`team`.`groupID` = `group`.`ID`)
      JOIN `contestant` ON (`contestant`.`teamID` = `team`.`ID`)
      JOIN `school` ON (`school`.`ID` = `school_year`.`schoolID`)
      JOIN `user` ON (`user`.`ID` = `school`.`userID`)
      WHERE `team`.`participationType` = 'Official'
      AND `school_year`.`year` = :year
      AND ((`group`.`contestID` = ".$contestIDs[0]." AND `contestant`.`rank` <= :levelMaxRank1) OR
           (`group`.`contestID` = ".$contestIDs[1]." AND `contestant`.`rank` <= :levelMaxRank2) OR
           (`group`.`contestID` = ".$contestIDs[2]." AND `contestant`.`rank` <= :levelMaxRank3) OR
           (`group`.`contestID` = ".$contestIDs[3]." AND `contestant`.`rank` <= :levelMaxRank4) OR
           (`group`.`contestID` = ".$contestIDs[4]." AND `contestant`.`rank` <= :levelMaxRank5) OR
           (`group`.`contestID` = ".$contestIDs[5]." AND `contestant`.`rank` <= :levelMaxRank6))
      ORDER BY `group`.`contestID`, `team`.`score` DESC
           ";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year,
      "levelMaxRank1" => $levelMaxRanks[0],
      "levelMaxRank2" => $levelMaxRanks[1],
      "levelMaxRank3" => $levelMaxRanks[2],
      "levelMaxRank4" => $levelMaxRanks[3],
      "levelMaxRank5" => $levelMaxRanks[4],
      "levelMaxRank6" => $levelMaxRanks[5]
));

   $rows = array();
   while ($row = $stmt->fetchObject()) {
      $rows[$row->contestant_ID] = $row;
   }
   displayRowsAsCsvWithFields("awards", $rows, $fields);
   exit;
}

$contestIDs = array(32, 33, 34, 35, 36, 37);
$year = 2014;

if (!isset($_REQUEST["nbAwards"])) {
   $_REQUEST["nbAwards"] = 0;
}
for ($level = 1; $level <= 6; $level++) {
   if (!isset($_REQUEST['nbAwardsLevel'.$level])) {
      $_REQUEST['nbAwardsLevel'.$level] = "";
   }
}

if (isset($_REQUEST["csv"])) {
   $levelNbAwards = array();
   for ($level = 1; $level <= 6; $level++) {
      $levelMaxRanks[] = $_REQUEST['levelMaxRank'.$level];
   }
   getCsv($db, $year, $levelMaxRanks);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<html>

<form name='form' method='get'>
Nombre total de lots attribués : <input type='text' name='nbAwards' value="<?php echo $_REQUEST['nbAwards'] ?>"/><br/>
Nombre de lots 6e/5e : <input type='text' name='nbAwardsLevel1' value="<?php echo $_REQUEST['nbAwardsLevel1'] ?>"/><br/>
Nombre de lots 4e/3e : <input type='text' name='nbAwardsLevel2' value="<?php echo $_REQUEST['nbAwardsLevel2'] ?>"/><br/>
Nombre de lots 2nde : <input type='text' name='nbAwardsLevel3' value="<?php echo $_REQUEST['nbAwardsLevel3'] ?>"/><br/>
Nombre de lots 1ere/term : <input type='text' name='nbAwardsLevel4' value="<?php echo $_REQUEST['nbAwardsLevel4'] ?>"/><br/>
Nombre de lots 2nde pro : <input type='text' name='nbAwardsLevel5' value="<?php echo $_REQUEST['nbAwardsLevel5'] ?>"/><br/>
Nombre de lots 1ere/term pro : <input type='text' name='nbAwardsLevel6' value="<?php echo $_REQUEST['nbAwardsLevel6'] ?>"/><br/>
<input type='submit' value='OK' /><br/><br/>
<?php

//updateSchoolsParticipants($db, $year);
//echo "done";
//exit;
if ($_REQUEST["nbAwards"] != 0) {
   $levelAwards = array($_REQUEST['nbAwardsLevel1'], $_REQUEST['nbAwardsLevel2'], $_REQUEST['nbAwardsLevel3'], $_REQUEST['nbAwardsLevel4'], $_REQUEST['nbAwardsLevel5'], $_REQUEST['nbAwardsLevel6']);
   getAwardsLimits($db, $year, $contestIDs, $_REQUEST["nbAwards"], $levelAwards);
}

unset($db);
?>

</html>