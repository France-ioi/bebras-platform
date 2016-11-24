<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
table tr td {
   border: solid black 1px;
   padding: 3px;
}
</style>
<body>
<h1>Liste des participants ayant indiqué une classe différente de celle du groupe</h1>
<p>
Ci-dessous, vous trouverez la liste des participants officiels au concours qui ont renseigné une classe différente de la classe associée à leur groupe.
</p>
<p>Vous pouvez corriger les classes des participants dans votre onglet "Élèves". Si cette liste contient des enseignants, passez les en hors concours depuis la dernière colonne de l'onglet Équipes.</p>
<p>
Si la classe indiquée par l'élève est la bonne, mais que c'est la classe du groupe qui est erronnée, ou si par exemple il s'agit d'un groupe avec des élèves de plusieurs classes, il n'y a rien à corriger (vous ne pouvez plus changer la classe d'un groupe). L'important est que la classe renseignée par l'élève soit bien sa classe.
</p>
<p>
Attention : ne sont actuellement listés ici que les participants des groupes que vous avez créés. Les groupes créés par vos collègues coordinateurs sont à vérifier depuis leur compte coordinateur.
</p>
<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


function translateGrade($grade) {
	$gradeNames = array(
		"-1"=> "Enseignant",
		"-4"=> "Autre",
		"4" => "CM1",
		"5" => "CM2",
		"6" => "6ème",
		"7" => "5ème",
		"8" => "4ème",
		"9" => "3ème",
		"10" => "2nde",
		"11" => "1ère",
		"12" => "Terminale",
		"13" => "2nde pro",
		"14" => "1ère pro",
		"15" => "Terminale pro"
    );
	if (isset($gradeNames[$grade])) {
		return $gradeNames[$grade];
	}
	return $grade;
}

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$query = "SELECT `contest`.`name` as `contestName`, `school`.`name` as `schoolName`, `group`.`name` as `groupName`, `group`.`grade` as `groupGrade`, `contestant`.`firstName`, `contestant`.`lastName`, `contestant`.`grade` as `contestantGrade` ".
" FROM `group` ".
"JOIN `contest` ON `group`.`contestID` = `contest`.`ID` ".
"JOIN `team` ON `team`.`groupID` = `group`.`ID` ".
"JOIN `contestant` ON `contestant`.`teamID` = `team`.`ID` ".
"JOIN `school` ON `school`.`ID` = `group`.`schoolID` ".
"WHERE `group`.`userID` = :userID ".
"AND `group`.`participationType` = 'Official' ".
"AND `team`.`participationType` = 'Official' ".
"AND `group`.`grade` != `contestant`.`grade` ".
"ORDER BY `contest`.`name`, `school`.`name`, `group`.`grade`, `contestant`.`grade`, `contestant`.`lastName`, `contestant`.`firstName`";

$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION["userID"]));
$rows = $stmt->fetchAll();

if (count($rows) == 0) {
   echo "<p><b>Aucun participant de vos groupes n'a indiqué une classe différente de celle du groupe, tout semble bon pour vous de ce point de vue.</b></p>";
} else {
   echo "<table cellspacing=0><tr><td>Concours</td><td>Établissement</td><td>Groupe</td><td>Classe du groupe</td><td>Classe indiquée par l'élève</td><td>Prénom</td><td>Nom</td></tr>";
   foreach ($rows as $row) {
      echo "<tr><td>".$row["contestName"]."</td>".
         "<td>".$row["schoolName"]."</td>".
         "<td>".$row["groupName"]."</td>".
         "<td>".translateGrade($row["groupGrade"])."</td>".
         "<td>".translateGrade($row["contestantGrade"])."</td>".
         "<td>".$row["firstName"]."</td>".
         "<td>".$row["lastName"]."</td>".
         "</tr>";
   }
   echo "</table>";
}

?>

<p>
En cas de question, contactez info@castor-informatique.fr
</p>
</body>
</html>