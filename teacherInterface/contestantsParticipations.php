<?php
   require_once("../shared/common.php");
   require_once("commonAdmin.php");
?>
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

tr.attention td {
   font-weight: bold;
   background-color: #FFCCCC;
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

.popup {}

.popup .popup-overlay {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  opacity: 0.5;
  background: #000;
}

.popup .popup-window {
  position: absolute;
  background: #FFF;
  top: 15%;
  width: 50%;
  left: 25%;
}

.popup .popup-body {
  padding: 15px;
}
</style>

<script type="text/javascript">
   function $(id) {
      return document.getElementById(id);
   }

   window.verification = {
      open: function(data) {
         window.verification.user_id = data.user_id;
         var el;
         for(var k in data.reg) {
            el = $('reg_' + k);
            if(el) el.innerHTML = data.reg[k];
            el = $('original_' + k);
            if(el) el.innerHTML = data.original[k];
         }
         $('verification_popup').style.display = '';
      },

      run: function(action) {
         $('verification_popup_buttons').style.display = 'none';
         $('verification_form_action').value = action;
         $('verification_form_user_id').value = window.verification.user_id;
         $('verification_form').submit();
      }
   }
</script>

<form id="verification_form" method="POST" action="contestantsVerification.php">
   <input type="hidden" name="action" id="verification_form_action"/>
   <input type="hidden" name="user_id" id="verification_form_user_id"/>
</form>

<div class="popup" id="verification_popup" style="display: none">
   <div class="popup-overlay"></div>
    <div class="popup-window">
        <div class="popup-body">
            <table>
                <tr>
                    <th><?php echo translate("old_value");?></th>
                    <th><?php echo translate("new_value");?></th>
                </tr>
                <tr>
                    <td id="original_firstName"></td>
                    <td id="reg_firstName"></td>
                </tr>
                <tr>
                    <td id="original_lastName"></td>
                    <td id="reg_lastName"></td>
                </tr>
                <tr>
                    <td id="original_genre"></td>
                    <td id="reg_genre"></td>
                </tr>
                <tr>
                    <td id="original_email"></td>
                    <td id="reg_email"></td>
                </tr>
                <tr>
                    <td id="original_grade"></td>
                    <td id="reg_grade"></td>
                </tr>
                <tr>
                    <td id="original_zipCode"></td>
                    <td id="reg_zipCode"></td>
                </tr>
                <tr>
                    <td id="original_studentID"></td>
                    <td id="reg_studentID"></td>
                </tr>
            </table>
            <p id="verification_popup_buttons">
                <button type="button" onclick="verification.run('approve')"><?php echo translate("approve");?></button>
                <button type="button" onclick="verification.run('reject')"><?php echo translate("reject");?></button>
            </p>
        </div>
    </div>
</div>

<?php
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

$mainContestID = "822122511136074554";
$allContestIDs = ["822122511136074554","337033997884044050"];


$query = "SELECT ID, name FROM contest WHERE ID IN (".join(",", $allContestIDs).")";
$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION['userID']));
echo "<div style='border:solid black 1px;padding:5px;width:400px'>";
echo "<p>".translate("results_show_only_participants_of")."</p>";
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
echo "<input type='submit' value='".translate("filter")."' />";
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
      algorea_registration.genre as regGenre,
      algorea_registration.email as regEmail,
      algorea_registration.zipCode as regZipCode,
      algorea_registration.studentID as regStudentID,
      IFNULL(algorea_registration.confirmed, 1) as confirmed,
      `group`.contestID,
      contest.parentContestID,
      contest.name as contestName,
      parentContest.name as parentContestName,
      contest.language,
      contest.categoryColor,
      school.name as schoolName,
      school.ID as schoolID,
      aro.firstName as originalFirstName,
      aro.lastName as originalLastName,
      aro.genre as originalGenre,
      aro.email as originalEmail,
      aro.grade as originalGrade,
      aro.zipCode as originalZipCode,
      aro.studentID as originalStudentID
   FROM `group`
      JOIN `school` ON `school`.ID = `group`.schoolID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team ON team.groupID = `group`.ID
      JOIN contestant ON contestant.teamID = team.ID
      LEFT JOIN algorea_registration ON contestant.registrationID = algorea_registration.ID
      LEFT JOIN `contest` parentContest ON contest.parentContestID = parentContest.ID
      LEFT JOIN `algorea_registration_original` as aro ON aro.ID = algorea_registration.ID
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

      $validation = false;
      if(!$row->confirmed) {
         $reg_genre = $row->regGenre == "1" ? translate("option_female") : translate("option_male");
         $original_genre = $row->originalGenre == "1" ? translate("option_female") : translate("option_male");
         $reg_grade = translate("grade_short_".$row->regGrade);
         $original_grade = translate("grade_short_".$row->originalGrade);

         $validation = array(
            "user_id" => $row->ID,
            "reg" => array(
               "firstName" => $row->regFirstName,
               "lastName" => $row->regLastName,
               "genre" => $reg_genre,
               "email" => $row->regEmail,
               "grade" => $reg_grade,
               "zipCode" => $row->regZipCode,
               "studentID" => $row->regStudentID
            ),
            "original" => array(
               "firstName" => $row->originalFirstName,
               "lastName" => $row->originalLastName,
               "genre" => $original_genre,
               "email" => $row->originalEmail,
               "grade" => $original_grade,
               "zipCode" => $row->originalZipCode,
               "studentID" => $row->originalStudentID
            )
         );

      }

      $contestants[$row->ID] = array(
          "infos" => $infos,
          "validation" => $validation,
          "results" => array()
      );
   }
   if ($row->contestID == $mainContestID) {
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

   echo "<table class='results' cellspacing=0><tr>".
        "<td rowspan=2>".translate("results_bebras_group")."</td>".
        "<td rowspan=2>".translate("contestant_firstName_label")."</td>".
        "<td rowspan=2>".translate("contestant_lastName_label")."</td>".
        "<td rowspan=2>".translate("contestant_grade_label")."</td>".
        "<td rowspan=2>".translate("action")."</td>".
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
      echo "<td colspan='".count($categoryContests)."'";
      if (count($categoryContests) == 1) {
         echo " rowspan=2 ";
      }
      echo ">".$mainContestsNames[$mainContestKey]."</td>";
   }
   /*
   echo "<td rowspan=2 style='width:70px'>".translate("results_semi_finals")."</td>";
   */
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
      $row_class = '';
      $validation_btn = '';
      if($contestant['validation']) {
         $row_class = ' class="attention"';
         $validation_btn = '<button onclick=\'verification.open('.json_encode($contestant['validation']).')\'>'.translate('verify').'</button>';
      }

      echo "<tr ".$row_class.">".
         "<td>".$contestant["infos"]["bebrasGroup"]."</td>".
         "<td>".$contestant["infos"]["firstName"]."</td>".
         "<td>".$contestant["infos"]["lastName"]."</td>".
         "<td>".translate("grade_short_".$contestant["infos"]["grade"])."</td>".
         "<td>".$validation_btn."</td>".
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
      /*
      echo "<td>";
      if ($contestant["infos"]["round"] == "1") {
         $score = $contestant["infos"]["scoreDemi2018"];
         if (($score != null) && ($score > 0)) {
            echo $score;
            echo "<br/>";
            $qualifiedFinal = $contestant["infos"]["qualifiedFinal"];
            echo "<span class='rank'>";
            if ($qualifiedFinal == "0") {
               echo $contestant["infos"]["rankDemi2018"]."e des ".translate("grade_short_".$contestant["infos"]["grade"])."<br/>".
               translate("results_not_qualified_to_finals");
            } else if ($qualifiedFinal == "1") {
               echo translate("results_qualified_to_finals");
            } else {
               echo translate("results_qualified_to_online_finals");
            }
            echo "</span>";
         } else {
            echo "-";
         }
      } else {
         echo "-";
      }
      echo "</td>";
      */
      echo "</tr>";
   }

   echo "</table>";
}

function showContestantResult($contestant, $contestKey, $category) {
   if (isset($contestant["results"][$contestKey])) {
      $result = $contestant["results"][$contestKey];
      $rankInfos = translate("results_ranking_in_process");
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