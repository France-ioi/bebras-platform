<html>
<meta charset='utf-8'>
<body><style>
#participations tr td {
   border: solid black 1px;
   padding: 4px;
}


#participations tr:first-child td {
   color: white;
   background-color: #000080;
   font-weight: bold;
}

.results tr:first-child td {
   font-weight: bold;
   color-background:lightgray;
}

td {
   padding: 5px;
   background-color: #F0F0F0;
}

.results tr td {
   border: solid black 1px;
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

if (!isset($_REQUEST["participationCode"])) {
   echo "participationCode parameter missing";
   exit;
}
$participationCode = $_REQUEST["participationCode"];

if (isset($_REQUEST["changeUser"])) {
   $firstName = $_REQUEST["firstName"];
   $lastName = $_REQUEST["lastName"];
   $grade = $_REQUEST["grade"];
   $category = $_REQUEST["category"];
   
   $query = "UPDATE algorea_registration SET firstName = :firstName, lastName = :lastName, grade = :grade, category = :category WHERE code = :code";
   $stmt = $db->prepare($query);
   $stmt->execute(array(
      "code" => $participationCode,
      "firstName" => $firstName,
      "lastName" => $lastName,
      "grade" => $grade,
      "category" => $category
   ));
   $query = "UPDATE algorea_registration JOIN contestant ON algorea_registration.ID = contestant.registrationID SET contestant.firstName = algorea_registration.firstName, contestant.lastName = algorea_registration.lastName, contestant.grade = algorea_registration.grade WHERE algorea_registration.code = :code";
   $stmt = $db->prepare($query);
   $stmt->execute(array(
      "code" => $participationCode
   ));
   echo "<b>Données personnelles modifiées</b>"; 
} else if (isset($_REQUEST["changeSchool"])) {
   $query = "UPDATE algorea_registration SET schoolID = :schoolID, userID = :userID WHERE code = :code";
   $stmt = $db->prepare($query);
   $stmt->execute(array(
      "schoolID" => $_REQUEST["schoolID"],
      "userID" => $_SESSION["userID"],
      "code" => $participationCode
   ));
}

$query = "SELECT algorea_registration.ID, ".
   "algorea_registration.firstName, ".
   "algorea_registration.lastName, ".
   "grade, ".
   "category, ".
   "school.name as schoolName, ".
   "school.city as schoolCity, ".
   "user.firstName as userFirstName, ".
   "user.lastName as userLastName ".
   "FROM algorea_registration ".
   "JOIN `school` ON school.ID = algorea_registration.schoolID ".
   "JOIN `user` ON user.ID = algorea_registration.userID ".
   "WHERE code = :code";
$stmt = $db->prepare($query);

$otherParticipant = null;
/*
if (isset($_REQUEST["otherParticipationCode"])) {
   $otherParticipationCode = $_REQUEST["otherParticipationCode"];
   if (isset($_REQUEST["checkOtherParticipationCode"])) {
      $stmt->execute(array("code" => $otherParticipationCode));
      $otherParticipant = $stmt->fetchObject();
   } else if (isset($_REQUEST["mergeParticipationCodes"])) {
      echo "<p>Will merge ".$participationCode." with ".$otherParticipationCode."</p>";
      $query = "UPDATE contestant SET registrationID = :newRegistrationID ".
         "WHERE registrationID = :oldRegistrationID";
      $stmt = $db->prepare($query);
      $stmt->execute(array("code" => $otherParticipationCode));
      $categories = ["blanche", "jaune", "orange", "verte", "bleue"];
      $maxCategory = "";
      foreach ($categories as $testCategory) {
         if (($category == $testCategory) || ($otherParticipant->category == $testCategory)) {
            $maxCategory = $category;
         }
      }
      $query = "UPDATE algorea_registration SET category = :category WHERE ID = :registrationID";
      var $typeScores = array("Individual", "Team");
      for ($typeScores as $typeScore) {
         $query = "UPDATE registration_category rc_old ".
         "JOIN registration_category rc_new ".
         "ON rc_old.registrationID = :oldRegistrationID ".
         "AND rc_new.registrationID = :registrationID ".
         "AND rc_old.category = rc_new.category ".
         "SET rc_new.bestScore".$typeScore."  = rc_old.bestScore".$typeScore.", ".
         "rc_new.dateBestScore".$typeScore." = rc_old.dateBestScore".$typeScore." ".
         "WHERE rc_new.bestScore".$typeScore." IS NULL ".
         "OR rc_new.bestScore".$typeScore." < rc_old.bestScore".$typeScore."";
      
      
      $query = "DELETE FROM algorea_registration WHERE ID = :oldRegistrationID";
   }
}
*/
echo "<h1>Informations personnelles</h1>";

$stmt->execute(array("code" => $participationCode));
if ($participant = $stmt->fetchObject()) {
   $selectGrade = "<select name='grade'>";
   foreach ($config->grades as $iGrade) {
      $selected = "";
      if ($participant->grade == $iGrade) {
         $selected = "selected";
      }
      $selectGrade .= "<option value='".$iGrade."' ".$selected.">".translate("grade_short_".$iGrade)."</option>";
   }
   $selectGrade .= "</select>";

   if (($participant->category == "verte") || ($participant->category == "bleue")) {
      $selectCategory = $participant->category;
   } else {
      $selectCategory = "<select name='category'>";
      $categories = ["", "blanche", "jaune", "orange"];
      foreach ($categories as $category) {
         $selected = "";
         if ($participant->category == $category) {
            $selected = "selected";
         }
         $selectCategory .= "<option value='".$category."' ".$selected.">".$category."</option>";
      }
      $selectCategory .= "</select>";
   }
   
   echo "
   <form method='POST'>
   <table id='personalData'>
      <tbody>";
   echo "<tr><td>".translate("participation_code")."</td>".
      "<td>".$participationCode."</td>";
   if ($otherParticipant) {
      echo "<td>".$otherParticipationCode."</td>";
   }
   echo "</tr><tr><td>".translate("contestant_firstName_label")."</td>".
      "<td><input type='text' name='firstName' value='".$participant->firstName."' /></td>";
   if ($otherParticipant) {
      echo "<td>".$otherParticipant->firstName."</td>";
   }      
   echo "</tr><td>".translate("contestant_lastName_label")."</td>".
      "<td><input type='text' name='lastName' value='".$participant->lastName."' /></td>";
   if ($otherParticipant) {
      echo "<td>".$otherParticipant->lastName."</td>";
   }
   echo "</tr><tr><td>".translate("contestant_grade_label")."</td>".
      "<td name='grade'>".$selectGrade."</td>";
   if ($otherParticipant) {
      echo "<td>".translate("grade_short_".$otherParticipant->grade)."</td>";
   }
   echo "</tr><tr><td>".translate("codes_category")."</td>".
      "<td name='category'>".$selectCategory."</td>";
   if ($otherParticipant) {
      echo "<td>".$otherParticipant->category."</td>";
   }
   
   echo  "</tr><tr><td>Établissement actuel</td>".
      "<td>".$participant->schoolName.", ".$participant->schoolCity."</td>";
   if ($otherParticipant) {
      echo "<td>".$otherParticipant->schoolName.", ".$otherParticipant->schoolCity."</td>";
   }
   echo  "</tr><tr><td>Coordinateur actuel</td>".
      "<td>".$participant->userFirstName." ".$participant->userLastName."</td>";
   if ($otherParticipant) {
      echo "<td>".$otherParticipant->userFirstName." ".$otherParticipant->userLastName."</td>";
   }
   echo "</tr></tbody></table>
   <input type='hidden' name='participationCode' value='".$participationCode."' />
   <br/>
   <input type='submit' name='changeUser' value='".translate("codes_validate")."' />";
/*   
   if ($otherParticipant) {
      echo "<input type='hidden' name='otherParticipationCode' value='".$otherParticipationCode."' />".      
         "<p>C'est la même personne ? <input type='submit' name='mergeParticipationCodes' value='Regrouper en un seul participant' />. Sinon, <input type='submit' name='cancel' value='Annuler' /><p>";
   } else {
      echo "<p>Si ce participant a un deuxième code, vous pouvez les fusionner. Entrer ce deuxième code ci-dessous pour vérifier qu'il s'agit bien de la même personne :</p>
      <p><input type='text' name='otherParticipationCode' /><input type='submit' name='checkOtherParticipationCode' value='Vérifier'></p>";
   }
*/
   echo "</form>";
   
} else {
   echo "Participant introuvable";
   exit;
}

echo "<h1>Établissement et coordinateur</h1>";

echo       "<table><tr><td>Établissement actuel</td>".
      "<td>".$participant->schoolName.", ".$participant->schoolCity."</td></tr>".
      "<tr><td>Coordinateur actuel</td>".
      "<td>".$participant->userFirstName." ".$participant->userLastName."</td></tr></table><br/><br/>";




$query = "SELECT school.ID, school.name, school.city FROM school JOIN school_user ON school.ID = school_user.schoolID ".
   "WHERE school_user.userID = :userID";
   

$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION["userID"]));

echo "<form form method='POST'>".
   "Rattacher à mon établissement : <select name='schoolID'>";
while ($school = $stmt->fetchObject()) {
   echo "<option value='".$school->ID."'>".$school->name.", ".$school->city."</option>";
}
echo "</select>".
   "<br/><br/><input type='submit' name='changeSchool' value='".translate("codes_validate")."' />".
   "</form>";


echo "<h1>Participations</h1>";


$query = "SELECT contest.name as contestName, `group`.name as groupName, (CONCAT(c1.firstName, CONCAT(' ', c1.lastName))) as contestantName, (CONCAT(c2.firstName, CONCAT(' ', c2.lastName))) as partnerName, SUM(team_question.ffScore) as tmpScore, team.score, team.participationType, c1.rank, c1.schoolRank, team.password, team.startTime
FROM
algorea_registration
JOIN contestant c1 ON algorea_registration.ID = c1.registrationID
JOIN team ON c1.teamID = team.ID
JOIN `group` ON team.groupID = `group`.ID
JOIN `contest` ON `group`.contestID = contest.ID
LEFT JOIN contestant c2 ON c2.teamID = team.ID AND c2.ID != c1.ID
JOIN team_question ON team.ID = team_question.teamID
WHERE algorea_registration.code = :code ".
"GROUP BY team.ID";

$stmt = $db->prepare($query);
$stmt->execute(array("code" => $participationCode));

echo "<table id='participations' cellspacing=0><tr>".
   "<td>Épreuve</td>".
   "<td>Groupe</td>".
   "<td>Date / heure (utc)</td>".
   "<td>Candidat</td>".
   "<td>Équipier</td>".
   "<td>Score<br/>temporaire</td>".
   "<td>Score<br/>final</td>".
   "<td>Officiel</td>".
   "<td>Classement</td>".
   "<td>Classement<br/>établissement</td>".
   "<td>Accès</td>".
   "</tr>";
while ($participation = $stmt->fetchObject()) {
   echo "<tr>";
   echo "<td>".$participation->contestName."</td>";
   echo "<td>".$participation->groupName."</td>";
   echo "<td>".$participation->startTime."</td>";
   echo "<td>".$participation->contestantName."</td>";
   echo "<td>".$participation->partnerName."</td>";
   echo "<td>".$participation->tmpScore."</td>";
   echo "<td>".$participation->score."</td>";
   echo "<td>";
   if ($participation->participationType == 'Official') {
      echo "Oui";
   } else if ($participation->participationType == 'Unofficial') {
      echo "Non";
   }
   echo "</td>";
   echo "<td>".$participation->rank."</td>";
   echo "<td>".$participation->schoolRank."</td>";
   echo "<td><a href='".$config->contestOfficialURL."?team=".$participation->password."' target='_blank'>".$participation->password."</a></td>";
   echo "</tr>";
}
echo "</table>";


?>
</body>
</html>