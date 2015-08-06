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
<h1>Candidats qualifiés pour le deuxième tour</h1>
<p>
Ci-dessous, vous trouverez la liste des candidats qualifiés pour le deuxième tour Algoréa, rattachés à des groupes que vous avez créés.
</p>
<p>
Il y avait trois manières de participer au premier tour :
<ul>
<li>En classe avec un code de groupe créé par l'enseignant.</li>
<li>En classe ou à la maison, avec un code personnel fourni par l'enseignant à l'issue du concours Castor.</li>
<li>À la maison, suite à une qualification par la validation de 12 exercices sur france-ioi.org</li>
</ul>
</p>
<p>
Nous ne listons ici que les deux premiers types de candidats, car le troisième type ne peut pas être relié à votre établissement.
</p>
<p>
Notez également que nous ne listons pour l'instant pas les candidats rattachés à des groupes créés par vos collègues.
</p>
<p>
Enfin, la présence d'une valeur dans la colonne "Compte france-ioi" ne signifie pas que le compte existe. L'information a pu être fournie par erreur par l'élève, sans qu'il ait réellement créé de compte france-ioi.
</p>
<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$contestsParams = array(
   2015 => array(
      array("contestID" => 51, "minScore" => 70, "grade" => 6),
      array("contestID" => 51, "minScore" => 73, "grade" => 7),
      array("contestID" => 51, "minScore" => 80, "grade" => 8),
      array("contestID" => 51, "minScore" => 80, "grade" => 9),
      array("contestID" => 51, "minScore" => 85, "grade" => 10),
      array("contestID" => 51, "minScore" => 90, "grade" => 11),
      array("contestID" => 51, "minScore" => 95, "grade" => 12),
      array("contestID" => 51, "minScore" => 70, "grade" => -3),
      array("contestID" => 51, "minScore" => 95, "grade" => -4)
   )
);

$query = "SELECT `group`.`name`, `contestant`.`firstName`, `contestant`.`lastName`, `team`.`password`, `algorea_registration`.`algoreaAccount` FROM `group` JOIN `team` ON (`team`.`groupID` = `group`.`ID` AND `team`.`participationType`= 'Official') ".
         "JOIN `contestant` ON (`contestant`.`teamID` = `team`.`ID`) ".
         "LEFT JOIN `algorea_registration` ON (`team`.`password` = `algorea_registration`.`code`) ".
         "WHERE `group`.`contestID` = 51 AND `group`.`userID` = :userID AND (";
$first = true;
foreach ($contestsParams[2015] as $iParam => $params) {
   if ($iParam != 0) {
      $query .= " OR ";
   }
   $query .= "(`contestant`.`grade` = ".$params["grade"]." AND `team`.`score` >= ".$params["minScore"].")";
}
$query .= ")";
$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION["userID"]));
$rows = $stmt->fetchAll();
echo "<h3>Qualifiés au 2ème tour, ayant participé au 1er tour au sein d'un groupe créé pour une participation en classe</h3>";
if (count($rows) == 0) {
   echo "<p>Aucun participant de ce type n'a été qualifié.</p>";
} else {
   echo "<table cellspacing=0><tr><td>Groupe</td><td>Nom</td><td>Prénom</td><td>Code d'accès</td><td>Compte france-ioi</td></tr>";
   foreach ($rows as $row) {
      echo "<tr><td>".$row["name"]."</td>".
         "<td>".$row["lastName"]."</td>".
         "<td>".$row["firstName"]."</td>".
         "<td>".$row["password"]."</td>".
         "<td>".$row["algoreaAccount"]."</td></tr>";
   }
   echo "</table>";
}

$query = "SELECT `contestant`.`firstName`, `contestant`.`lastName`, `ta`.`password`, `algorea_registration`.`algoreaAccount` ".
         "FROM `group` `gc` ".
         "JOIN `team` `tc` ON (`tc`.`groupID` = `gc`.`ID`) ".
         "JOIN `contestant` `cc` ON (`cc`.`teamID` = `tc`.`ID`) ".
         "JOIN `team` `ta` ON (`cc`.`algoreaCode` = `ta`.`password` AND `ta`.`participationType` = 'Official') ".
         "JOIN `contestant` ON (`contestant`.`teamID` = `ta`.`ID`) ".
         "LEFT JOIN `algorea_registration` ON (`ta`.`password` = `algorea_registration`.`code`) ".
         "WHERE `gc`.`contestID` >= 32 AND `gc`.`contestID` <= 37 AND `gc`.`userID` = :userID AND (";
$first = true;
foreach ($contestsParams[2015] as $iParam => $params) {
   if ($iParam != 0) {
      $query .= " OR ";
   }
   $query .= "(`contestant`.`grade` = ".$params["grade"]." AND `ta`.`score` >= ".$params["minScore"].")";
}
$query .= ")";
$stmt = $db->prepare($query);
$stmt->execute(array("userID" => $_SESSION["userID"]));
$rows = $stmt->fetchAll();
echo "<h3>Qualifiés au 2ème tour, ayant participé au 1er tour via le code personnel fourni après le concours Castor</h3>";
if (count($rows) == 0) {
   echo "<p>Aucun participant de ce type n'a été qualifié.</p>";
} else {
   echo "<table cellspacing=0><tr><td>Nom</td><td>Prénom</td><td>Code d'accès</td><td>Compte france-ioi</td></tr>";
   foreach ($rows as $row) {
      echo "<tr>".
         "<td>".$row["lastName"]."</td>".
         "<td>".$row["firstName"]."</td>".
         "<td>".$row["password"]."</td>".
         "<td>".$row["algoreaAccount"]."</td></tr>";
   }
   echo "</table>";
}


?>
<h3>Procédure à suivre par vos élèves (eux-mêmes autant que possible)</h3>
<p>
1) Ils doivent créer un compte france-ioi.org s'ils n'en ont pas déjà un, en allant sur <a href='http://www.france-ioi.org/user/inscription.php' target='new'>http://www.france-ioi.org/user/inscription.php</a>
</p>
<p>
2) Une fois connectés, ils doivent aller sur la page <a href="http://www.algorea.org">www.algorea.org</a>. Ils y verront dans l'encadré une zone de texte dans lequel ils doivent entrer leur code d'accès personnel et le valider.
</p>
<p>
3) Ils doivent alors voir apparaître dans l'encadré un message leur confirmant leur qualification au 2ème tour avec leur compte france-ioi.
</p>
<p>
4) À partir du 4 mai, ils auront accès à l'épreuve depuis la page <a href="http://www.algorea.org">www.algorea.org</a>. Ils peuvent choisir de la faire à tout moment entre le 4 mai et le 15 mai, et doivent y consacrer 2h30 consécutives.
</p>
<p>
En cas de question, contactez info@france-ioi.org
</p>
</body>
</html>