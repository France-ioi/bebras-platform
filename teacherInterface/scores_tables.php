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
.orange td {
   background-color: orange;
}
.white td {
   background-color: white;
}
.gray td {
   background-color: #C0C0C0;
   font-weight: bold;
}
.yellow td {
   background-color: yellow;
}
.green td {
   background-color: lightgreen;
}
.blue td {
   background-color: #8080FF;
}
</style><body>

<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

function displayScores($contestIDs, $minForYellow, $minForOrange, $minForGreen, $minForBlue) {
   global $db;

   $query = "SELECT team.score, count(*) as nb, contestant.grade FROM contestant JOIN team ON team.ID = contestant.teamID JOIN `group` ON team.groupID = `group`.ID WHERE team.participationType = 'Official' AND `group`.contestID IN (".implode($contestIDs, ',').") AND contestant.grade > 0 GROUP BY contestant.grade, team.score";

   $stmt = $db->prepare($query);
   $stmt->execute();

   $grades = array(4 => "CM1", 5 => "CM2", 6 => "6e", 7 => "5e", 8 => "4e", 9 => "3e", 10 => "2de", 11 => "1ère", 12 => "Tale", 13 => "2de<br/>pro", 14 => "1ère<br/>pro", 15 => "Tale<br/>pro", 16 => "6e Segpa", 17 => "5e Segpa", 18 => "4e Segpa", 19 => "3e Segpa");
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

   echo "<table class='borders' cellspacing=0>\n<tr><td>Score</td>";

   $gradeTotal = array();
   foreach ($grades as $grade => $gradeName) {
      echo "<td>".$gradeName."</td>";
      $gradeTotal[$grade] = 0;
   }
   echo "<td>Classement<br/>général</td></tr>\n";

   $rows = "";
   $allTotal = 0;
   for ($score = $maxScore; $score > 0; $score--) {
      $allTotalBefore = $allTotal;
      if (!isset($scores[$score])) {
         continue;
      }
      $class = "white";
      if ($score >= $minForYellow) {
         $class = "yellow";
      }
      if ($score >= $minForOrange) {
         $class = "orange";
      }
      if ($score >= $minForGreen) {
         $class = "green";
      }
      if ($score >= $minForBlue) {
         $class = "blue";
      }
      $row = "<tr class='".$class."'><td>".$score."</td>";
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
      $row .= "</tr>\n";
      $rows .= $row;
   }
   echo $rows;

   echo "<tr class='gray'><td>Total</td>";
   foreach ($grades as $grade => $gradeName) {
      echo "<td>".$gradeTotal[$grade]."</td>";
   }
   echo "<td>".$allTotal."</tr>\n";


   echo "</table>\n";
}


/*
if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}
*/

echo "<p>Catégorie blanche :</p>";
displayScores(array("866488984396180", "95300867864028463", "226161984593556559"), 100, 400, 400, 400);

echo "<p>Catégorie jaune :</p>";
displayScores(array("503609694961379947", "553157869958034707", "631835860403469834"), 0, 100, 400, 400);



echo "<p>Catégorie orange :</p>";
displayScores(array("695264095539164908", "780696932767704624", "786950565192017915"), 0, 0, 100, 400);


?>
</body>
</html>