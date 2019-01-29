<html>
<meta charset='utf-8'>
<body><style>
.resultats tr:first-child td {
   font-weight: bold;
   color-background:lightgray;
}

.resultats tr td {
   border: solid black 1px;
   padding: 5px;
}
</style>
<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

$query = "SELECT ID, firstName, lastName, code FROM algorea_registration";// WHERE userID = :userID";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
$contestants = array();
$contestantCodes = array();
while ($row = $stmt->fetchObject()) {
   $contestants[$row->code] = $row;
   $contestantCodes[] = "'".$row->code."'";
}
$strCodes = implode(",", $contestantCodes);

$db2 = new PDO($dbConnexionString2, $dbUser2, $dbPasswd2);

$items = array(0);

$idTeamItem = "426154796109911742"; // groupe : 337433601613235328

// Query fetching scores on a contest on AlgoreaPlatform
// To fetch scores on another contest, modify:
// -users_items.idItem IN (...) with the IDs of the tasks
// -groups.idTeamItem = '...' with the ID of the chapter on which teams are created
$query = "SELECT `pixal`.`groups`.ID, `pixal`.`groups`.`sName`,
`pixal`.`users`.`ID` as `idUser`,
`pixal`.`users_items`.`idItem`, `pixal`.`users_items`.`iScore`, date(`pixal`.`users`.`sLastLoginDate`) as lastLogin,
`pixal`.`alkindi_teams`.rank,
`pixal`.`alkindi_teams`.rankBigRegion,
`pixal`.`alkindi_teams`.rankRegion,
`pixal`.`alkindi_teams`.qualifiedFinal,
`pixal`.`alkindi_teams`.sPassword AS password,
`login-module`.`badges`.`code`,
`pixal`.`alkindi_teams`.thirdScore, `pixal`.`alkindi_teams`.thirdTime,
`pixal`.`alkindi_teams`.isOfficial,
`pixal`.`alkindi_teams`.score1, `pixal`.`alkindi_teams`.time1, `pixal`.`alkindi_teams`.score2, `pixal`.`alkindi_teams`.time2, `pixal`.`alkindi_teams`.score3, `pixal`.`alkindi_teams`.time3, `pixal`.`alkindi_teams`.score4, `pixal`.`alkindi_teams`.time4
FROM `login-module`.`badges`
JOIN `pixal`.`users` ON `pixal`.`users`.`loginID` = `login-module`.`badges`.`user_id`
JOIN `pixal`.`groups_groups` ON `pixal`.`groups_groups`.`idGroupChild` = `pixal`.`users`.`idGroupSelf`
JOIN `pixal`.`groups` ON `pixal`.`groups`.`ID` = `pixal`.`groups_groups`.`idGroupParent`
LEFT JOIN `pixal`.`alkindi_teams` ON `pixal`.`alkindi_teams`.idGroup = `pixal`.`groups`.`ID`
LEFT JOIN `pixal`.`users_items` ON (`pixal`.`users`.`ID` = `pixal`.`users_items`.`idUser` AND `users_items`.`idItem` IN (".implode(",", $items)."))
WHERE
`login-module`.`badges`.`code` IN (".$strCodes.")
AND `pixal`.`groups`.`sType` = 'Team'
AND `pixal`.`groups`.`idTeamItem` = ".$idTeamItem."
GROUP BY `pixal`.`users`.`ID`, `pixal`.`users_items`.`idItem`
ORDER BY `pixal`.`groups`.ID ASC, `pixal`.`users_items`.`idItem` ASC";




//echo $query;

$stmt = $db2->prepare($query);
$stmt->execute();

$groups = array();
$curGroupID = 0;
$curUserID = 0;
$groupUsers = array();
$group = null;
while ($row = $stmt->fetchObject()) {
   if ($row->ID != $curGroupID) {
      $curGroupID = $row->ID;
      $group = $row;
      $groups[] = $group;
      $group->scores = array();      
      $group->users = array();
   }
   if ($row->idUser != $curUserID) {
      $curUserID = $row->idUser;
      $group->users[$curUserID] = $row;      
   }
   if ($row->iScore !== null) {
      if (!isset($group->scores[$row->idItem])) {
         $group->scores[$row->idItem] = intval($row->iScore);
      }
      $group->scores[$row->idItem] = max($group->scores[$row->idItem], intval($row->iScore));
   }
}


echo "<h1>Équipes créées pour le 2e tour</h1>";
echo "<p>Vous pouvez consulter sur cette page les équipes déjà créés par vos élèves.</p><p>Vous pourrez également y consulter les résultats au fur et à mesure de leur participation au 2e tour.</p>";
/*
echo "<h2>Équipes qualifiées</h2><p>Les équipes qui ont obtenu 285 points ou plus sont qualifiées pour le 3e tour.</p>
<p>Le 3e tour dure 1h30 et doit se faire sous surveillance, entre le 19 mars et le 7 avril inclus.</p>
<h2>Fonctionnement de l'épreuve du 3e tour</h2>
<p>Pour chaque équipe sélectionnée, un code secret fourni dans la colonne de droite devra être saisi pour commencer l'épreuve. <b>Il ne doit être transmis à l'équipe qu'au moment de commencer l'épreuve</b>.</p>
<p>Munis de ce code secret et de leur code de participant individuel, rappelé ci-dessous, ils devront se connecter sur <a href='https://suite.concours-alkindi.fr' target='_blank'>suite.concours-alkindi.fr</a>.</p>

<p>Les sujets sont les mêmes que pour le 2e tour, mais avec des données différentes. Pour chaque sujet, l'équipe peut effectuer plusieurs tentatives pendant la durée de l'épreuve.</p>

<p>Les élèves ne doivent utiliser rien d'autre que le site du concours, des feuilles de brouillon et des crayons.</p>

<p>Attention : nous avons précédemment indiqué qu'il ne fallait utiliser qu'un ordinateur par équipe. Nous avons cependant décidé qu'il était préférable de ne pas imposer cette règle. Il n'y a donc pas de limite au nombre d'ordinateurs qu'une équipe peut utiliser.</p>

<h2>Calcul du score et départage des équipes</h2>
<p>Le score d'une équipe au 3e tour sera calculé de la même manière que lors du 2e tour. On considèrea pour chaque sujet, la tentative de meilleur score parmi celles effectuées pendant l'épreuve. Le score total sera la somme des scores des 4 sujets.</p>

<p>En cas d'égalité de score, les équipes seront départagées en fonction du temps, calculé selon le principe suivant : pour chaque sujet, parmi les tentatives de meilleur score, on considèrera le temps mis pour celle qui a été résolue le plus rapidement. Il s'agit du temps entre le moment de création de cette tentative, et le moment où son score a été obtenu. Le temps total pour l'équipe sera la somme de ces temps pour les 4 sujets.</p>

<h2>Classement final</h2>
<p>Dans les trois dernières colonnes, vous pouvez trouver soit le classement de l'équipe au sein de son académie, sa grande région et au niveau national, soit a mention \"Qualifiée en finale\" si l'équipe est qualifiée.</p>
<p>Les récompenses attribuées aux meilleures équipes non finalistes seront indiquées plus tard.</p>";
*/

echo "<table class='resultats' cellspacing=0><tr>";
echo "<td>Nom de l'équipe</td><td>Élèves</td>";
/*
echo "<td>Réseau&nbsp;1D<br />(2e tour)</td><td>Réseau&nbsp;2D<br />(2e tour)</td><td>Enigma&nbsp;1<br />(2e tour)</td><td>Enigma&nbsp;2<br />(2e tour)</td><td>Total<br />(2e tour)</td><td>Code secret tour 3</td>";
echo "<td>Réseau&nbsp;1D<br />(3e tour)</td><td>Réseau&nbsp;2D<br />(3e tour)</td><td>Enigma&nbsp;1<br />(3e tour)</td><td>Enigma&nbsp;2<br />(3e tour)</td><td>Total<br />(3e tour)</td><td>Classement<br/>académie</td><td>Classement<br/>grande région</td><td>Classement<br/>national</td>";
*/
echo "</tr>";
$curGroupID = 0;
foreach ($groups as $group) {
   echo "<tr>";
   echo "<td>".htmlentities($group->sName)."</td><td>";
   foreach ($group->users as $user) {
      echo htmlentities($contestants[$user->code]->firstName)." ".
            htmlentities($contestants[$user->code]->lastName)." [".$user->code."]<br/>";
   }
   echo "</td>";
   $sum = 0;
   /*
   foreach ($items as $idItem) {
      echo "<td>";
      if (!isset($group->scores[$idItem])) {
         echo "-";
      } else {
         $score = $group->scores[$idItem];
         $sum += intval($score);
         echo $score;
      }
      echo "</td>";
   }
   */
   //echo "<td>".$sum."</td>";
   //echo "<td>".$group->password."</td>";
   /*
   if($group->thirdScore) {
        echo "<td>".$group->score1." (".$group->time1.")</td>";
        echo "<td>".$group->score2." (".$group->time2.")</td>";
        echo "<td>".$group->score3." (".$group->time3.")</td>";
        echo "<td>".$group->score4." (".$group->time4.")</td>";
        echo "<td>".$group->thirdScore." (".$group->thirdTime.")</td>";
   } elseif($sum >= 285) {
        echo '<td colspan="5">Participation ou résultats en attente</td>';
   } else {
        echo '<td colspan="5">Non qualifiée</td>';
   }
   if ($group->qualifiedFinal == 1) {
      echo "<td colspan=3>Qualifiée en finale</td>";
   } else if ($group->isOfficial != 1) {
      echo "<td colspan=3>Hors classement</td>";
   } else {
      echo "<td>".($group->rankRegion ? $group->rankRegion : '')."</td>";
      echo "<td>".($group->rankBigRegion ? $group->rankBigRegion : '')."</td>";
      echo "<td>".($group->rank ? $group->rank : '')."</td>";
   }
   */
   echo "</tr>";
}
echo "</table>";


?>
</body>
</html>
