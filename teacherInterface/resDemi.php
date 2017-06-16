<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$query = "SELECT algorea_registration.scoreDemi, contestant.firstName, contestant.lastName, contestant.grade FROM algorea_registration JOIN contestant ON contestant.ID = algorea_registration.contestantID ".
"JOIN team ON team.ID = contestant.teamID ".
"JOIN `group` ON `group`.ID = team.groupID ".
"JOIN `school_user` ON school_user.schoolID = `group`.schoolID ".
"WHERE school_user.userID = :userID AND scoreDemi IS NOT NULL";

$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);

echo "<html>
<meta charset='utf-8'>
<body><style>table tr td { border: solid black 1px; padding:2px; }</style><p>Vous trouverez ci-dessous les résultats de vos équipes pour la demi-finale Algoréa.</p><p>Les élèves qui ont obtenu 300 points ou plus auront accès aux épreuves de la finale à partir du 10 juillet sur concours.algorea.org avec les mêmes identifiants que ceux utilisés pour passer la demi-finale, et un classement officiel de ces épreuves sera publié en septembre.</p><p>Les 20 élèves qualifiés pour la finale à Paris ont été contactés par email, les critères correspondants seront publiés bientôt.</p><table cellspacing=0><tr><td>Nom</td><td>Prénom</td><td>Score demi-finale</td></tr>";

while ($row = $stmt->fetchObject()) {
   echo "<tr><td>".$row->lastName."</td><td>".$row->firstName."</td><td>".$row->scoreDemi."/600</td></tr>";
}
echo "</table>";

echo "<p>Si vos élèves ont participé à la demi-finale mais ne sont pas listés ici, c'est que leur compte n'est pas rattaché à vos groupes. Vos élèves pourront retrouver leur score très bientôt en se connectant sur concours.algorea.org</p>";

?>