<html>
<meta charset='utf-8'>
<body><style>
.results tr:first-child td {
   font-weight: bold;
   color-background:lightgray;
}

td {
   background-color: #F0F0F0;
}

.results tr td {
   border: solid black 1px;
   padding: 5px;
}
.orange {
   background-color: orange;
}
.blanche {
   background-color: white;
}
.grise {
   background-color: #C0C0C0;
   font-weight: bold;
}
.jaune {
   background-color: yellow;
}
.verte {
   background-color: lightgreen;
}
.bleue {
   background-color: #8080FF;
}

.rank {
   font-size: 10px;
}
</style>
<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$showCodes = 0;
if (isset($_GET["showCodes"])) {
   $showCodes = $_GET["showCodes"];
}
$mergeCodes = 0;
if (isset($_GET["mergeCodes"])) {
   $mergeCodes = $_GET["mergeCodes"];
}


echo "<h1>Synthèse des résultats Castor et Algoréa</h1>";

echo "<b>Options d'affichage</b><br/>";
echo "<ul>";
if ($showCodes) {
   echo "<li><a href='contestantsParticipations.php?showCodes=0'>Masquer les codes de participants</a>";
} else {
   echo "<li><a href='contestantsParticipations.php?showCodes=1'>Afficher les codes de participants</a>";
}
/*
if ($mergeCodes) {
   echo "<li><a href='contestantsParticipations.php?mergeCodes=0'>Désactiver le mode fusion des élèves qui apparaissent deux fois</a>";
} else {
   echo "<li><a href='contestantsParticipations.php?mergeCodes=1&showCodes=1'>Activer le mode fusion des élèves qui apparaissent deux fois</a>";
}
*/
echo "</ul>";
echo "<p>Dans les résultats ci-dessous, des élèves peuvent apparaître en double s'ils n'ont pas utilisé leur code de participant pour participer à Algoréa.</p>";

$grades = array(-2 => "Inconnu", -1 => "Profs", -4 => "Autres", 4 => "CM1", 5 => "CM2", 6 => "6e", 7 => "5e", 8 => "4e", 9 => "3e", 10 => "2de", 11 => "1ère", 12 => "Tale", 13 => "2de<br/>pro", 14 => "1ère<br/>pro", 15 => "Tale<br/>pro", 16 => "6e Segpa", 17 => "5e Segpa", 18 => "4e Segpa", 19 => "3e Segpa", 20 => "Post-Bac");

$allContestIDs = ["118456124984202960","884044050337033997","112633747529078424", "404363140821714044"];

$query = "SELECT ID, name FROM contest WHERE ID IN (".join(",", $allContestIDs).")";
$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION['userID']));
echo "<div style='border:solid black 1px;padding:5px;width:400px'>";
echo "<p>N'afficher que les participants aux concours :</p>";
echo "<form name='filter' method='post'>";
$data = array();
while ($row = $stmt->fetchObject()) {
   $data[$row->ID] = $row->name;
}
$contestIDs = array();
foreach ($allContestIDs as $contestID) {
   if (isset($data[$contestID])) {
      $checked = "";
      if (isset($_POST["contest_".$contestID])) {
         $checked = "checked";
         $contestIDs[] = $contestID;
      }
      echo "<input type='checkbox' name='contest_".$contestID."' ".$checked.">".$data[$contestID]."</input><br/>";
   }
}
if (count($contestIDs) == 0) {
   $contestIDs = $allContestIDs;
}
echo "<input type='submit' value='Filtrer' />";
echo "</form></div>";


$categories = ["blanche", "jaune", "orange", "verte", "bleue"];


$query = "
   SELECT
      IFNULL(contestant.registrationID, contestant.ID) as ID,
      contestant.firstName,
      contestant.lastName,
      contestant.grade,
      team.score,
      team.nbContestants,
      contestant.rank,
      contestant.schoolRank,
      team.participationType,
      team.groupID,
      `group`.name as groupName,
      team.password,
      algorea_registration.code,
      algorea_registration.firstName as regFirstName,
      algorea_registration.lastName as regLastName,
      algorea_registration.grade as regGrade,
      algorea_registration.category,
      algorea_registration.validatedCategory,
      algorea_registration.round,
      algorea_registration.scoreDemi2018,
      algorea_registration.rankDemi2018,
      algorea_registration.qualifiedFinal,
      `group`.contestID,
      contest.parentContestID,
      contest.name as contestName,
      parentContest.name as parentContestName,
      contest.language,
      contest.categoryColor,
      school.name as schoolName,
      school.ID as schoolID
   FROM `group`
      JOIN `school` ON `school`.ID = `group`.schoolID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team ON team.groupID = `group`.ID
      JOIN contestant ON contestant.teamID = team.ID
      LEFT JOIN algorea_registration ON contestant.registrationID = algorea_registration.ID
      LEFT JOIN `contest` parentContest ON contest.parentContestID = parentContest.ID
   WHERE (`contest`.ID IN (".join(",", $contestIDs).") OR `contest`.parentContestID IN (".join(",", $contestIDs)."))
   AND `group`.userID = :userID
   ORDER BY schoolID, IFNULL(contestant.registrationID, contestant.ID), team.score DESC";

$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION['userID']));

//echo $query." userID : ".$_SESSION['userID'];

$contests = array();
$mainContestsNames = array();
$curSchoolID = 0;
$schools = array();
$contestants = array();
$count = 0;
while ($row = $stmt->fetchObject()) {
   if ($row->schoolID != $curSchoolID) {
      if ($curSchoolID != 0) {
         $schools[$curSchoolID]["contestants"] = $contestants;
         $contestants = array();
      }
      $curSchoolID = $row->schoolID;
      $schools[$row->schoolID] = array("name"  => $row->schoolName);
   }
   $contestKey = $row->parentContestID;
   $mainContestKey = $row->parentContestID;
   $mainContestName = $row->parentContestName;
   if ($contestKey == null) {
      $contestKey = $row->contestID;
      $mainContestKey = $row->contestID;
      $mainContestName = $row->contestName;
   } else {
      $contestKey .= "_".$row->categoryColor;
   }
   if (!isset($contests[$mainContestKey])) {
      $mainContestsNames[$mainContestKey] = $mainContestName;
      $contests[$mainContestKey] = array();
   }
   if (!isset($contests[$mainContestKey][$row->categoryColor])) {
      $contests[$mainContestKey][$row->categoryColor] = $contestKey;
   }
   if (!isset($contestants[$row->ID])) {
      if ($row->regFirstName != null) {
         $infos = array(
             "ID" => $row->ID,
             "firstName" => $row->regFirstName,
             "lastName" => $row->regLastName,
             "grade" => $row->regGrade,
             "code" => $row->code,
             "round" => $row->round,
             "scoreDemi2018" => $row->scoreDemi2018,
             "rankDemi2018" => $row->rankDemi2018,
             "qualifiedFinal" => $row->qualifiedFinal,
             "qualifiedCategory" => $row->category,
             "validatedCategory" => $row->validatedCategory,
             "bebrasGroup" => "-"
          );
      } else {
         $infos = array(
             "ID" => $row->ID,
             "firstName" => $row->firstName,
             "lastName" => $row->lastName,
             "grade" => $row->grade,
             "code" => "-",
             "round" => $row->round,
             "scoreDemi2018" => $row->scoreDemi2018,
             "rankDemi2018" => $row->rankDemi2018,
             "qualifiedFinal" => $row->qualifiedFinal,
             "qualifiedCategory" => "-",
             "validatedCategory" => "-",
             "bebrasGroup" => "-"
          );
      }
      $contestants[$row->ID] = array(
          "infos" => $infos,
          "results" => array()
      );
   }
   if ($row->contestID == "118456124984202960") {
      $contestants[$row->ID]["infos"]["bebrasGroup"] = $row->groupName;
   }
   if (!isset($contestants[$row->ID]["results"][$contestKey])) {
      $contestants[$row->ID]["results"][$contestKey] = array(
         "score" => $row->score,
         "rank" => $row->rank,
         "schoolRank" => $row->schoolRank,
         "nbContestants" => $row->nbContestants,
         "participationType" => $row->participationType,
         "language" => $row->language
      );
   }
}
if ($curSchoolID != 0) {
   $schools[$curSchoolID]["contestants"] = $contestants;
}

foreach ($schools as $schoolID => $school) {
   echo "<h2>".$school["name"]."</h2>";
   $contestants = $school["contestants"];

   echo "<table class='results' cellspacing=0><tr><td rowspan=2>Groupe Castor</td><td rowspan=2>Prénom</td><td rowspan=2>Nom</td><td rowspan=2>Classe</td><td rowspan=2>Qualifié en<br/>catégorie</td>";
   if ($showCodes) {
      echo "<td rowspan=2>Code de participant</td>";
   }
   if ($mergeCodes) {
      echo "<td rowspan=2>Déplacer vers le code</td>";
   }
   foreach ($contestIDs as $mainContestKey) {
      if (!isset($contests[$mainContestKey])) {
         continue;
      }
      $categoryContests = $contests[$mainContestKey];
      echo "<td colspan='".count($categoryContests)."'";
      if (count($categoryContests) == 1) {
         echo " rowspan=2 ";
      }
      echo ">".$mainContestsNames[$mainContestKey]."</td>";
   }
   echo "<td rowspan=2 style='width:70px'>Demi-finale</td>";
   echo "</tr><tr>";
   foreach ($contestIDs as $mainContestKey) {
      if (!isset($contests[$mainContestKey])) {
         continue;
      }
      $categoryContests = $contests[$mainContestKey];
      foreach ($categories as $category) {
         if (!isset($categoryContests[$category])) {
            continue;
         }
         if (count($categoryContests) > 1) {
            echo "<td>".$category."</td>";
         }
      }
   }
   echo "</tr>";

   usort($contestants, function ($a, $b) {
       $cmpGroups = strcmp($a["infos"]['bebrasGroup'], $b["infos"]['bebrasGroup']);
       if ($cmpGroups != 0) {
          return $cmpGroups;
       }
       $cmpNames = strcmp($a["infos"]['lastName'], $b["infos"]['lastName']);
       if ($cmpNames != 0) {
          return $cmpNames;
       };
       return strcmp($a["infos"]['firstName'], $b["infos"]['firstName']);
   });

   foreach ($contestants as $contestant) {
      echo "<tr>".
         "<td>".$contestant["infos"]["bebrasGroup"]."</td>".
         "<td>".$contestant["infos"]["firstName"]."</td>".
         "<td>".$contestant["infos"]["lastName"]."</td>".
         "<td>".$grades[$contestant["infos"]["grade"]]."</td>".
         "<td class='".$contestant["infos"]["qualifiedCategory"]."'>".$contestant["infos"]["qualifiedCategory"]."</td>";
      if ($showCodes) {
         echo "<td>".$contestant["infos"]["code"]."</td>";
      }
      if ($mergeCodes) {
         echo "<td><input class='mergeCode' onchange='changeCode(\"".$contestant["infos"]["ID"]."\")' id='merge_".$contestant["infos"]["ID"]."' style='width:150px' /></td>";
      }
      foreach ($contestIDs as $mainContestKey) {
         if (!isset($contests[$mainContestKey])) {
            continue;
         }
         $categoryContests = $contests[$mainContestKey];
         $shown = false;
         foreach ($categories as $category) {
            if (isset($categoryContests[$category])) {
               showContestantResult($contestant, $categoryContests[$category], $category);
               $shown = true;
            }
         }
         if (!$shown) {
            showContestantResult($contestant, $categoryContests[""], "");
         }
      }
      echo "<td>";
      if ($contestant["infos"]["round"] == "1") {
         $score = $contestant["infos"]["scoreDemi2018"];
         if (($score != null) && ($score > 0)) {
            echo $score;
            echo "<br/>";
            $qualifiedFinal = $contestant["infos"]["qualifiedFinal"];
            echo "<span class='rank'>";
            if ($qualifiedFinal == "0") {
               echo $contestant["infos"]["rankDemi2018"]."e des ".$grades[$contestant["infos"]["grade"]]."<br/>pas en finale";
            } else if ($qualifiedFinal == "1") {
               echo "Finaliste (à Paris)";
            } else {
               echo "Qualifié pour la<br/>finale en ligne";
            }
            echo "</span>";
         } else {
            echo "-";
         }
      } else {
         echo "-";
      }
      echo "</td>";
      echo "</tr>";
   }

   echo "</table>";
}

function showContestantResult($contestant, $contestKey, $category) {
   global $grades;
   if (isset($contestant["results"][$contestKey])) {
      $result = $contestant["results"][$contestKey];
      $rankInfos = "classement en attente";
      if ($result["rank"] != '') {
         $rankGroup = $grades[$contestant["infos"]["grade"]]." ";
         if ($result["nbContestants"] == "1") {
            $rankGroup .= "individuels";
         } else {
            $rankGroup .= "binômes";
         }
         $rankInfos = $result["rank"]."e des ".$rankGroup;
      } else if ($result["participationType"] == "Unofficial") {
         $rankInfos = "Hors concours";
      }
      echo "<td class='".$category."'>".
         $result["score"]."<br/>".
         "<span class='rank'>".$rankInfos."</span>".
         "</td>";
   } else {
      echo "<td class='grise'>-</td>";
   }
}

?>
</body>
</html>