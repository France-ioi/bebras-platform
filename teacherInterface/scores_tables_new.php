<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
.borders tr td {
   border: solid black 1px;
   padding-left: 5px;
   padding-right: 5px;
   text-align: right;
   width:50px;
}
.orange {
   background-color: orange;
}
.blanche {
   background-color: white;
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
</style><body>

<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

function displayScores() {
   global $config, $db;
   
   $categories = array(
      "blanche" => 14,
      "jaune" => 16,
      "orange" => 16,
      "verte" => 9);
   $query = "SELECT DISTINCT totalScoreAlgorea, grade, algoreaRank FROM algorea_registration GROUP BY totalScoreAlgorea, grade ORDER BY grade, totalScoreAlgorea";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $ranks = array();
   $scores = array();
   while ($row = $stmt->fetchObject()) {
      if (!isset($ranks[$row->grade])) {
         $ranks[$row->grade] = array();
      }
      $ranks[$row->grade][$row->totalScoreAlgorea] = $row->algoreaRank;
      if (!isset($scores[$row->totalScoreAlgorea]) && ($row->totalScoreAlgorea > 0)) {
         $scores[$row->totalScoreAlgorea] = true;
      }
   }
   ksort($scores);
   $scoreCategory = array();
   foreach ($scores as $score => $value) {
      $category = "blanche";
      if ($score >= 1000) {
         $category = "jaune";
      }
      if ($score >= 2000) {
         $category = "orange";
      }
      if ($score >= 3000) {
         $category = "verte";
      }
      $scoreCateogry[$score] = $category;
   }
   $grades = [4,5,6,16,7,17,8,18,9,19,10,13,11,14,12,15];
   echo "<table class='borders' cellspacing=0><tr><td rowspan=2>Catégorie</td><td>Classe</td>";
   foreach ($grades as $grade) {
      echo "<td style='min-width:50px' rowspan=2>".translate("grade_".$grade)."</td>";
   }
   echo "</tr>";
   echo "<tr><td>Score</td></tr>";
   $prevCategory = "";
   foreach ($scores as $score => $value) {
      echo "<tr>";
      $category = $scoreCateogry[$score];
      if ($prevCategory != $category) {
         echo "<td class='".$category."' rowspan=".$categories[$category]." style='text-align:center'>".$category."</td>";
         $prevCategory = $category;
      }
      echo "<td class='".$category."'>".($score % 1000)."</td>";
      foreach ($grades as $grade) {
         echo "<td class='".$scoreCateogry[$score]."'>";
         if (isset($ranks[$grade][$score])) {
            echo $ranks[$grade][$score];
         } else {
            echo "&nbsp;";
         }
         echo "</td>";
      }
      echo "</tr>";
   }
   echo "<table>";
}

displayScores();

?>
</body>
</html>