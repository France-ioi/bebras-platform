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
   echo translate("session_expired");
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


echo "<h1>".translate("results_synthesis_title")."</h1>";

echo "<b>".translate("results_display_options")."</b><br/>";
echo "<ul>";
if ($showCodes) {
   echo "<li><a href='contestantsParticipations.php?showCodes=0'>".translate("results_hide_participation_codes")."</a>";
} else {
   echo "<li><a href='contestantsParticipations.php?showCodes=1'>".translate("results_show_participation_codes")."</a>";
}
/*
if ($mergeCodes) {
   echo "<li><a href='contestantsParticipations.php?mergeCodes=0'>Désactiver le mode fusion des élèves qui apparaissent deux fois</a>";
} else {
   echo "<li><a href='contestantsParticipations.php?mergeCodes=1&showCodes=1'>Activer le mode fusion des élèves qui apparaissent deux fois</a>";
}
*/
echo "</ul>";
echo "<p>".translate("results_students_may_appear_twice")."</p>";

echo "<p><b>Classement Algoréa :</b> un classement unique par niveau scolaire est calculé pour chaque participant et mis à jour après chaque tour. Dans chaque niveau scolaire, sont classés en tête les participants ayant obtenu des points dans la catégorie verte par ordre de leur score, puis ceux ayant des points dans la catégorie orange, puis la jaune, puis la blanche.</p>";

$query = "SELECT ID, name, orderResults FROM contest WHERE orderResults IS NOT NULL ORDER BY orderResults ASC";
$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION['userID']));
echo "<div style='border:solid black 1px;padding:5px;width:400px'>";
echo "<p>".translate("results_show_only_participants_of")."</p>";
echo "<form name='filter' method='post'>";
$mainContestID = null;
$data = array();
$contestIDs = array();
$allContestIDs = array();
while ($row = $stmt->fetchObject()) {
   $contestID = $row->ID;
   if ($mainContestID === null) {
      $mainContestID = $contestID;
   }
   $data[$contestID] = $row->name;
   $checked = "";
   if (isset($_POST["contest_".$contestID])) {
      $checked = "checked";
      $contestIDs[] = $contestID;
   }
   $allContestIDs[] = $contestID;
   echo "<input type='checkbox' name='contest_".$contestID."' ".$checked.">".$data[$contestID]."</input><br/>";
}
echo "<input type='submit' value='".translate("filter")."' />";
echo "</form></div>";

if (count($contestIDs) == 0) {
   $contestIDs = $allContestIDs;
}

$categories = ["blanche", "jaune", "orange", "verte"];


$query = "
   SELECT * FROM (
   SELECT
      IFNULL(contestant.registrationID, contestant.ID) as ID,
      contestant.firstName,
      contestant.lastName,
      contestant.grade,
      team.score,
      team.tmpScore,
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
      algorea_registration.franceioiID,
      algorea_registration.round,
      algorea_registration.qualifiedFromSite,
      algorea_registration.algoreaRank,
      algorea_registration.algoreaSchoolRank,
      algorea_registration.scoreQuart2025,
      algorea_registration.qualifiedDemi2025,
      algorea_registration.scoreDemi2025,
      algorea_registration.rankDemi2025,
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
   
   UNION DISTINCT    
       
   SELECT
      IFNULL(contestant.registrationID, contestant.ID) as ID,
      contestant.firstName,
      contestant.lastName,
      contestant.grade,
      team.score,
      team.tmpScore,
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
      algorea_registration.franceioiID,
      algorea_registration.round,
      algorea_registration.qualifiedFromSite,
      algorea_registration.algoreaRank,
      algorea_registration.algoreaSchoolRank,
      algorea_registration.scoreQuart2025,
      algorea_registration.qualifiedDemi2025,
      algorea_registration.scoreDemi2025,
      algorea_registration.rankDemi2025,
      algorea_registration.qualifiedFinal,
      `group`.contestID,
      contest.parentContestID,
      contest.name as contestName,
      parentContest.name as parentContestName,
      contest.language,
      contest.categoryColor,
      school.name as schoolName,
      school.ID as schoolID
   FROM `algorea_registration`
      JOIN contestant ON contestant.registrationID = algorea_registration.ID
      JOIN team ON team.ID = `contestant`.teamID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN `school` ON `school`.ID = `algorea_registration`.schoolID
      LEFT JOIN `contest` parentContest ON contest.parentContestID = parentContest.ID
   WHERE (`contest`.ID IN (".join(",", $contestIDs).") OR `contest`.parentContestID IN (".join(",", $contestIDs)."))
   AND `algorea_registration`.userID = :userID       
       
   ) data
   ORDER BY schoolID, ID, score DESC";

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
      $infos = array(
         "ID" => $row->ID,
         "franceioiID" => $row->franceioiID,
         "round" => $row->round,
         "qualifiedFromSite" => $row->qualifiedFromSite,
         "scoreQuart2025" => $row->scoreQuart2025,
         "qualifiedDemi2025" => $row->qualifiedDemi2025,
         "scoreDemi2025" => $row->scoreDemi2025,
         "rankDemi2025" => $row->rankDemi2025,
         "qualifiedFinal" => $row->qualifiedFinal,
         "algoreaRank" => $row->algoreaRank,
         "algoreaSchoolRank" => $row->algoreaSchoolRank,
         "bebrasGroup" => "-"
      );

      if ($row->regFirstName != null) {
         $infos = array_merge($infos, array(
            "firstName" => $row->regFirstName,
            "lastName" => $row->regLastName,
            "grade" => $row->regGrade,
            "code" => $row->code,
            "qualifiedCategory" => $row->category,
            "validatedCategory" => $row->validatedCategory
         ));
      } else {
         $infos = array_merge($infos, array(
            "firstName" => $row->firstName,
            "lastName" => $row->lastName,
            "grade" => $row->grade,
            "code" => "-",
            "qualifiedCategory" => "-",
            "validatedCategory" => "-"
         ));
      }

      $contestants[$row->ID] = array(
          "infos" => $infos,
          "results" => array()
      );
   }
   if ($row->contestID == $mainContestID) {
      $contestants[$row->ID]["infos"]["bebrasGroup"] = $row->groupName;
   }
   if (!isset($contestants[$row->ID]["results"][$contestKey])) {
      $contestants[$row->ID]["results"][$contestKey] = array(
         "score" => $row->score,
         "tmpScore" => $row->tmpScore,
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

   echo "<table class='results' cellspacing=0><tr>".
        "<td rowspan=2>".translate("results_bebras_group")."</td>".
        "<td rowspan=2>".translate("contestant_firstName_label")."</td>".
        "<td rowspan=2>".translate("contestant_lastName_label")."</td>".
        "<td rowspan=2>".translate("contestant_grade_label")."</td>".
        "<td rowspan=2>".translate("results_qualified_in_category")."</td>";
   if ($showCodes) {
      echo "<td rowspan=2>".translate("participation_code")."</td>";
   }
   if ($mergeCodes) {
      echo "<td rowspan=2>".translate("results_transfer_to_code")."</td>";
   }
   foreach ($contestIDs as $mainContestKey) {
      if (!isset($contests[$mainContestKey])) {
         continue;
      }
      $categoryContests = $contests[$mainContestKey];
      $colSpan = count($categoryContests);
      $rowSpan = 1;
      if (isset($categoryContests[""]) AND count($categoryContests) == 1) {
         $rowSpan = 2;
      } else if (isset($categoryContests[""])) {
         $colSpan -= 1;
      }
      echo "<td colspan='".$colSpan."'"." rowSpan='".$rowSpan."' ";
      echo ">".$mainContestsNames[$mainContestKey]."</td>";
   }
   echo "<td rowspan=2 style='width:70px'>Quart de finale</td>";
   echo "<td rowspan=2 style='width:70px'>Demi-finale</td>";
   echo "<td rowspan=2 style='width:100px'>".translate("results_ranking_national")."</td>";
   echo "<td rowspan=2 style='width:100px'>".translate("results_ranking_school")."</td>";
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
         if (!(isset($categoryContests[""]) AND count($categoryContests) == 1)) {
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
         "<td>".translate("grade_short_".$contestant["infos"]["grade"])."</td>".
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
      if ($contestant["infos"]["round"] != "2" && $contestant["infos"]["qualifiedFromSite"] != "1" && $contestant["infos"]["franceioiID"]) {
         $fioiQualification = verifyFioiQualification($contestant["infos"]["franceioiID"]);
         if($fioiQualification["success"]) {
            $contestant["infos"]["qualifiedFromSite"] = "1";
            $updateStmt = $db->prepare("UPDATE algorea_registration SET qualifiedFromSite = 1 WHERE ID = :ID");
            $updateStmt->execute(['ID' => $contestant["infos"]["ID"]]);
         }
      }
      if ($contestant["infos"]["round"] == "2" || $contestant["infos"]["qualifiedFromSite"] == "1") {
         $scoreQuart = $contestant["infos"]["scoreQuart2025"];
         if (($scoreQuart != null) && ($scoreQuart > 0)) {
            echo $scoreQuart;
         } else {
            echo "<span class='rank'>Qualifié(e), n'a pas participé</span>";
         }
      } else {
         echo "-";
      }
      echo "</td>";
      echo "<td>";
      $scoreDemi = $contestant["infos"]["scoreDemi2025"];
      $qualifiedDemi = $contestant["infos"]["qualifiedDemi2025"];
      if($scoreDemi) {
         echo $scoreDemi;
         echo "<br/>";
         $qualifiedFinal = $contestant["infos"]["qualifiedFinal"];
         echo "<span class='rank'>";
         if ($qualifiedFinal == "0") {
            echo "Non qualifié(e) pour la finale<br/>";
            // echo "Rang ".$contestant["infos"]["rankDemi2025"]." de ";
            // echo translate("grade_short_".$contestant["infos"]["grade"]);
         } else if ($qualifiedFinal == "1") {
            echo "Qualifié(e) pour la finale";
         }
         echo "</span>";
      } elseif($qualifiedDemi == "1") {
         echo "<span class='rank'>Qualifié(e), n'a pas encore participé</span>";
      } elseif($qualifiedDemi == "2") {
         echo "<span class='rank'>Formulaire à remplir pour validation</span>";
      } else {
         echo "-";
      }
      echo "</td>";
      echo "<td>";
      if ($contestant["infos"]["qualifiedFinal"] == '1') {
         echo "Qualifié en finale";
      } else if ($contestant["infos"]["algoreaRank"] != null) {
         echo $contestant["infos"]["algoreaRank"]."e<br/>des ".translate("grade_short_".$contestant["infos"]["grade"]);
      } echo "</td>";
      echo "<td>";
      if ($contestant["infos"]["qualifiedFinal"] == '1') {
            echo "Qualifié en finale";
      } else if ($contestant["infos"]["algoreaSchoolRank"] != null) {
         echo $contestant["infos"]["algoreaSchoolRank"]."e<br/>des ".translate("grade_short_".$contestant["infos"]["grade"]);
      } echo "</td>";
      
      echo "</tr>";
   }

   echo "</table>";
}

function showContestantResult($contestant, $contestKey, $category) {
   if (isset($contestant["results"][$contestKey])) {
      $result = $contestant["results"][$contestKey];
      if ($category == '') {
         $rankInfos = translate("results_ranking_in_progress");
      } else {
         if ($result["score"] != "") {
            $rankInfos = "";
         } else {
            $rankInfos = translate("results_score_in_progress");
         }
      }
      if ($result["rank"] != '') {
         $rankGroup = translate("grade_short_".$contestant["infos"]["grade"])." ";
         if ($result["nbContestants"] == "1") {
            $rankGroup .= translate("results_individuals");
         } else {
            $rankGroup .= translate("results_teams");
         }
         $rankInfos = sprintf(translate("results_rank_of"), $result["rank"], $rankGroup);
      } else if ($result["participationType"] == "Unofficial") {
         $rankInfos = translate("results_unofficial");
      }
      echo "<td class='".$category."'>";
      if($result["score"] != "") {
         echo $result["score"];
      } elseif($result["tmpScore"] != "") {
         echo "<i>".$result["tmpScore"]." (non vérifié)</i>";
      }
      echo "<br/>".
         "<span class='rank'>".$rankInfos."</span>".
         "</td>";
   } else {
      echo "<td class='grise'>-</td>";
   }
}

?>
</body>
</html>
