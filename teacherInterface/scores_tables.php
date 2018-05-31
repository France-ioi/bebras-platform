<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
.borders tr td {
   border: solid black 1px;
   padding-left: 5px;
   padding-right: 5px;
   text-align: right;
   width:50px;
}
.borders tr:first-child, .borders tr td:first-child {
   font-weight: bold
}
.orange {
   background-color: orange;
}
.white {
   background-color: white;
}
.gray td {
   background-color: #C0C0C0;
   font-weight: bold;
}
.yellow {
   background-color: yellow;
}
.green {
   background-color: lightgreen;
}
.blue {
   background-color: #8080FF;
}
</style><body>

<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

function displayScores($category, $contestIDs, $nbContestants, $minForYellow, $minForOrange, $minForGreen, $minForBlue) {
   global $db;

   $className = array("blanche" => "white", "jaune" => "yellow", "orange" => "orange", "verte" => "green", "bleue" => "blue");
   
   $strNbContestants = "";
   if ($nbContestants == 1) {
      $strNbContestants = ", individuels.";
   } else if ($nbContestants == 2) {
      $strNbContestants = ", binômes.";
   }

   $query = "SELECT team.score, count(*) as nb, contestant.grade
      FROM contestant
      JOIN team ON team.ID = contestant.teamID
      JOIN `group` ON team.groupID = `group`.ID
      WHERE team.participationType = 'Official' AND `group`.contestID IN (".implode($contestIDs, ',').")";
      
   if ($nbContestants != null) {
      $query .= " AND team.nbContestants = ".$nbContestants;
   }
   $query .= "
      AND contestant.grade > 0
      GROUP BY contestant.grade, team.score";

   $stmt = $db->prepare($query);
   $stmt->execute();

   $grades = array(4 => "CM1", 5 => "CM2", 6 => "6e", 7 => "5e", 8 => "4e", 9 => "3e", 10 => "2de", 11 => "1ère", 12 => "Tale", 13 => "2de<br/>pro", 14 => "1ère<br/>pro", 15 => "Tale<br/>pro", 16 => "6e Segpa", 17 => "5e Segpa", 18 => "4e Segpa", 19 => "3e Segpa", 20 => "Post-Bac");
   $scores = array();

   $results = array();

   foreach($grades as $grade => $gradeName) {
      $results[$grade] = array();
   }

   $count = 0;
   $maxScore = 0;
   while ($row = $stmt->fetchObject()) {
      $scores[$row->score] = true;
      if ($row->score > $maxScore) {
         $maxScore = $row->score;
      }
      $results[$row->grade][$row->score] = $row->nb;
      $count++;
   }

   echo "<table class='borders' cellspacing=0>\n
   <tr class='gray' style='font-weigth:bold;'><td style='text-align:left;font-size:18px;padding:8px' colspan=".(count($grades)+3).">Catégorie ".$category.$strNbContestants."</td></tr>
   <tr class='gray' style='font-weigth:bold'><td>Score</td>";

   $gradeTotal = array();
   foreach ($grades as $grade => $gradeName) {
      echo "<td>".$gradeName."</td>";
      $gradeTotal[$grade] = 0;
   }
   echo "<td>Classement<br/>général</td><td>Qualifié en<br/>catégorie</tr>\n";

   $rows = "";
   $allTotal = 0;
   for ($score = $maxScore; $score > 0; $score--) {
      $allTotalBefore = $allTotal;
      if (!isset($scores[$score])) {
         continue;
      }
      $qualifiedCategory = "blanche";
      if ($score >= $minForYellow) {
         $qualifiedCategory = "jaune";
      }
      if ($score >= $minForOrange) {
         $qualifiedCategory = "orange";
      }
      if ($score >= $minForGreen) {
         $qualifiedCategory = "verte";
      }
      if ($score >= $minForBlue) {
         $qualifiedCategory = "bleue";
      }
      $row = "<tr class='".$className[$category]."'><td>".$score."</td>";
      foreach ($grades as $grade => $gradeName) {
         $row .= "<td>";
         if (isset($results[$grade][$score])) {
            $row .= $gradeTotal[$grade] + 1;
            $gradeTotal[$grade] += $results[$grade][$score];
            $allTotal += $results[$grade][$score];
         } else {
            $row .= "&nbsp;";
         }
         $row .= "</td>";
      }
      $row .= "<td>".($allTotalBefore + 1)."</td>";
      $row .= "<td class='".$className[$qualifiedCategory]."'>".$qualifiedCategory."</td>";
      $row .= "</tr>\n";
      $rows .= $row;
   }
   echo $rows;

   echo "<tr class='gray'><td>Total</td>";
   foreach ($grades as $grade => $gradeName) {
      echo "<td>".$gradeTotal[$grade]."</td>";
   }
   echo "<td></td><td>".$allTotal."</tr>\n";


   echo "</table>\n";
   echo "<p></p>";
}


/*
if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}
*/
// Algorea 2018 1
/*
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("blanche", array("866488984396180", "95300867864028463", "226161984593556559"), $nbContestants, 100, 400, 400, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("jaune", array("503609694961379947", "553157869958034707", "631835860403469834"), $nbContestants, 0, 100, 400, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("orange", array("695264095539164908", "780696932767704624", "786950565192017915"), $nbContestants, 0, 0, 100, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("verte", array("194265151775855451", "724496314570819714", "877860856667136566"), $nbContestants, 0, 0, 0, 100);
}
*/
/*
// Algorea 2018 2
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("blanche", array("433875944567802955", "464234573261791223", "478814972692557331"), $nbContestants, 100, 400, 400, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("jaune", array("489660906155539641", "538693023575903347", "635186277262638479"), $nbContestants, 0, 100, 400, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("orange", array("645319172551959739", "669911621655411237", "726425520897651240"), $nbContestants, 0, 0, 100, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("verte", array("755470871511205442", "896961304723791165", "929621055141482397"), $nbContestants, 0, 0, 0, 100);
}
*/
// Algorea 2018 3
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("blanche", array("48391093164618821", "114487714666658710", "996122155980313798"), $nbContestants, 100, 400, 400, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("jaune", array("545356571926977995", "617648920823399729", "834754723498000394"), $nbContestants, 0, 100, 400, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("orange", array("261656471759188263", "358123805952272903", "783319671389043423"), $nbContestants, 0, 0, 100, 400);
}
for ($nbContestants = 1; $nbContestants <= 2; $nbContestants++) {
   displayScores("verte", array("19597742245918219", "156853804889706187", "972697214621656335"), $nbContestants, 0, 0, 0, 100);
}


?>
</body>
</html>