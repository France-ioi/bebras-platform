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

$query = "SELECT ID, firstName, lastName, code FROM algorea_registration WHERE userID = :userID";
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

$items = array("1456912346504207303",
"93643821302144180",
"1950469197457415954",
"789316457202939816",
"1554293343451030761"
);

$idTeamItem = "1147510128527491497"; // groupe : 337433601613235328

// Query fetching scores on a contest on AlgoreaPlatform
// To fetch scores on another contest, modify:
// -users_items.idItem IN (...) with the IDs of the tasks
// -groups.idTeamItem = '...' with the ID of the chapter on which teams are created
/*
$query = "SELECT `pixal`.`groups`.ID, `pixal`.`groups`.`sName`,
`pixal`.`users`.`ID` as `idUser`,
`pixal`.`users_items`.`idItem`, `pixal`.`users_items`.`iScore`, date(`pixal`.`users`.`sLastLoginDate`) as lastLogin,
`pixal`.`alkindi_teams_2020`.rank,
`pixal`.`alkindi_teams_2020`.rankBigRegion,
`pixal`.`alkindi_teams_2020`.rankRegion,
`pixal`.`alkindi_teams_2020`.qualifiedThird,
`pixal`.`alkindi_teams_2020`.sPassword AS password,
`pixal`.`alkindi_teams_2020`.isCh AS suisse,
`login-module`.`badges`.`code`,
`pixal`.`alkindi_teams_2020`.thirdScore, `pixal`.`alkindi_teams_2020`.thirdTime,
`pixal`.`alkindi_teams_2020`.isOfficial,
`pixal`.`alkindi_teams_2020`.score1, `pixal`.`alkindi_teams_2020`.time1, `pixal`.`alkindi_teams_2020`.score2, `pixal`.`alkindi_teams_2020`.time2, `pixal`.`alkindi_teams_2020`.score3, `pixal`.`alkindi_teams_2020`.time3, `pixal`.`alkindi_teams_2020`.score4, `pixal`.`alkindi_teams_2020`.time4
FROM `login-module`.`badges`
JOIN `pixal`.`users` ON `pixal`.`users`.`loginID` = `login-module`.`badges`.`user_id`
JOIN `pixal`.`groups_groups` ON `pixal`.`groups_groups`.`idGroupChild` = `pixal`.`users`.`idGroupSelf`
JOIN `pixal`.`groups` ON `pixal`.`groups`.`ID` = `pixal`.`groups_groups`.`idGroupParent`
LEFT JOIN `pixal`.`alkindi_teams_2020` ON `pixal`.`alkindi_teams_2020`.idGroup = `pixal`.`groups`.`ID`
LEFT JOIN `pixal`.`users_items` ON (`pixal`.`users`.`ID` = `pixal`.`users_items`.`idUser` AND `users_items`.`idItem` IN (".implode(",", $items)."))
WHERE
`login-module`.`badges`.`code` IN (".$strCodes.")
AND `pixal`.`groups`.`sType` = 'Team'
AND `pixal`.`groups`.`idTeamItem` = ".$idTeamItem."
GROUP BY `pixal`.`users`.`ID`, `pixal`.`users_items`.`idItem`
ORDER BY `pixal`.`groups`.ID ASC, `pixal`.`users_items`.`idItem` ASC";
*/
$query = "SELECT 
groups_items_scores.ID,
groups_items_scores.idItem,
groups_items_scores.iScore,
date(`pixal`.`users`.`sLastLoginDate`) as lastLogin,
`pixal`.`users`.`ID` as `idUser`,
`pixal`.`users`.`sFirstName`,
`pixal`.`users`.`sLastName`,
`pixal`.`groups`.`sName`,
`pixal`.`alkindi_teams_2020`.rank,
`pixal`.`alkindi_teams_2020`.rankBigRegion,
`pixal`.`alkindi_teams_2020`.rankRegion,
`pixal`.`alkindi_teams_2020`.qualifiedThird,
`pixal`.`alkindi_teams_2020`.qualifiedFinal,
`pixal`.`alkindi_teams_2020`.sPassword AS password,
`pixal`.`alkindi_teams_2020`.isCh AS suisse,
`login-module`.`badges`.`code`,
`pixal`.`alkindi_teams_2020`.thirdScore, `pixal`.`alkindi_teams_2020`.thirdTime,
`pixal`.`alkindi_teams_2020`.isOfficial,
`pixal`.`alkindi_teams_2020`.score1, `pixal`.`alkindi_teams_2020`.time1, `pixal`.`alkindi_teams_2020`.score2, `pixal`.`alkindi_teams_2020`.time2, `pixal`.`alkindi_teams_2020`.score3, `pixal`.`alkindi_teams_2020`.time3, `pixal`.`alkindi_teams_2020`.score4, `pixal`.`alkindi_teams_2020`.time4,
`pixal`.`alkindi_teams_2020`.score5, `pixal`.`alkindi_teams_2020`.time5
FROM (
SELECT
`pixal`.`groups`.ID,
`pixal`.`users_items`.`idItem`,
MAX(`pixal`.`users_items`.`iScore`) AS iScore
FROM `login-module`.`badges`
JOIN `pixal`.`users` ON `pixal`.`users`.`loginID` = `login-module`.`badges`.`user_id`
JOIN `pixal`.`groups_groups` ON `pixal`.`groups_groups`.`idGroupChild` = `pixal`.`users`.`idGroupSelf`
JOIN `pixal`.`groups` ON `pixal`.`groups`.`ID` = `pixal`.`groups_groups`.`idGroupParent`
LEFT JOIN `pixal`.`users_items` ON (`pixal`.`users`.`ID` = `pixal`.`users_items`.`idUser` AND `users_items`.`idItem` IN (".implode(",", $items)."))
WHERE
`login-module`.`badges`.`code` IN (".$strCodes.")
AND `pixal`.`groups`.`sType` = 'Team'
AND `pixal`.`groups`.`idTeamItem` = ".$idTeamItem."
GROUP BY `pixal`.`groups`.ID, `pixal`.`users_items`.`idItem`
ORDER BY `pixal`.`groups`.ID ASC, `pixal`.`users_items`.`idItem` ASC
) as groups_items_scores
JOIN `pixal`.`groups` ON `pixal`.`groups`.ID = groups_items_scores.`ID`
JOIN `pixal`.`groups_groups` ON `pixal`.`groups_groups`.`idGroupParent`= groups_items_scores.`ID`
JOIN `pixal`.`users` ON `pixal`.`groups_groups`.`idGroupChild` = `pixal`.`users`.`idGroupSelf`
LEFT JOIN `login-module`.`badges` ON `pixal`.`users`.`loginID` = `login-module`.`badges`.`user_id`
LEFT JOIN `pixal`.`alkindi_teams_2020` ON `pixal`.`alkindi_teams_2020`.idGroup = groups_items_scores.`ID`";






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
echo "<p>Vous pouvez consulter sur cette page les équipes déjà créées par vos élèves.</p><p>Vous pourrez également y consulter les résultats au fur et à mesure de leur participation au 2e tour.</p>";

echo "<h2>Équipes qualifiées</h2>";
$isFrance = true;
//if ($_SERVER['HTTP_HOST'] == "coordinateur.concours-alkindi.fr")  {
   $isFrance = true;
   echo "<p>Les équipes qui ont obtenu 200 points ou plus sont qualifiées pour le 3e tour.</p>";
//} else {
//   echo "<p><strong>Le seuil de qualification au 3ème tour sera annoncé très prochaînement.</strong></p>";
//}
echo "
<!--
<p>Le 3e tour dure 1h30 et doit se faire sous surveillance, entre le 21 mars et le 6 avril inclus.</p>
-->
<p>Le 3e tour dure 1h30 à placer entre le 1er avril et le 22 avril inclus.</p>
<p>Du fait du confinement, les membres de chaque équipe doivent s'organiser pour faire l'épreuve ensemble mais à distance, en
utilisant les outils de leur choix pour communiquer, se partager leur écran, etc. Il est bien sûr important qu'ils se synchronisent
au préalable sur le moment de l'épreuve.</p>
<h2>Fonctionnement de l'épreuve du 3e tour</h2>
<p>Pour chaque équipe sélectionnée, un code secret fourni dans la colonne de droite devra être saisi pour commencer l'épreuve. <b>Il ne doit dans la mesure du possible être transmis à l'équipe que peu avant le moment de commencer l'épreuve</b>.</p>
<p>Munis de ce code secret et de leur code de participant individuel, rappelé ci-dessous, ils devront se connecter sur <a href='https://suite.concours-alkindi.fr' target='_blank'>suite.concours-alkindi.fr</a>.</p>

<p>Les sujets sont les mêmes que pour le 2e tour, mais avec des données différentes. Pour chaque sujet, l'équipe peut effectuer plusieurs tentatives pendant la durée de l'épreuve.</p>

<p>Il n'y a pas de limite au nombre d'ordinateurs qu'une équipe peut utiliser. Par contre l'équipe ne doit utiliser rien d'autre que le site du concours, les outils pour permettre aux membres de communiquer entre eux et uniquement entre eux, des feuilles de brouillon, des crayons et d'éventuelles notes manuscrites ou imprimées. En particulier, les équipes ne peuvent pas écrire ni utiliser de programmes pour résoudre ou aider à résoudre les sujets.</p>

<h2>Calcul du score et départage des équipes</h2>
<p>Le score d'une équipe au 3e tour sera calculé de la même manière que lors du 2e tour. On considèrera pour chaque sujet, la tentative de meilleur score parmi celles effectuées pendant l'épreuve. Le score total sera la somme des scores des 5 sujets.</p>

<p>En cas d'égalité de score, les équipes seront départagées en fonction du temps, calculé selon le principe suivant : pour chaque sujet, parmi les tentatives de meilleur score, on considèrera le temps mis pour celle qui a été résolue le plus rapidement. Il s'agit du temps entre le moment de création de cette tentative, et le moment où son score a été obtenu. Le temps total pour l'équipe sera la somme de ces temps pour les 5 sujets.</p>";

/*
echo "<h2>Classement final</h2>
<p>Dans les trois dernières colonnes, vous pouvez trouver soit le classement de l'équipe au sein de son académie, sa grande région et au niveau national, soit a mention \"Qualifiée en finale\" si l'équipe est qualifiée.</p>
<p>Les récompenses attribuées aux meilleures équipes non finalistes seront indiquées plus tard.</p>";
*/

echo "<table class='resultats' cellspacing=0><tr>";
echo "<td rowspan=2>Nom de l'équipe</td><td rowspan=2>Élèves</td><td colspan=6 style='text-align:center;background:lightgray'>2e tour</td>";
echo "<td rowspan=2>Code secret<br />tour 3</td>";
echo "<td colspan=6 style='text-align:center;background:lightgray'>3e tour</td>";
/*
echo "<td colspan=3 style='text-align:center;'>Classement</td>";
*/
echo "</tr>";

echo "<tr><td>Rétroingénierie<br />(2e tour)</td><td>Braille 1<br />(2e tour)</td><td>Braille 2<br />(2e tour)</td><td>Braille 3<br />(2e tour)</td><td>Boîtes<br/>(2e tour)</td><td>Total<br />(2e tour)</td>";
echo "<td>Rétroingénierie<br />(3e tour)</td><td>Braille 1<br />(3e tour)</td><td>Braille 2<br />(3e tour)</td><td>Braille 3<br />(3e tour)</td><td>Boîtes<br/>(3e tour)</td><td>Total<br />(3e tour)</td>";
/*
echo "<td>Messages 1<br />(3e tour)</td><td>Messages 2<br />(3e tour)</td><td>Messages 3<br />(3e tour)</td><td>Cercle 1<br />(3e tour)</td><td>Cercle 2<br/>(3e tour)</td><td>Total<br />(3e tour)</td>";
*/
echo "<td>Classement<br/>académie</td><td>Classement<br/>grande région</td><td>Classement<br/>national</td>";

echo "</tr>";

$curGroupID = 0;
foreach ($groups as $group) {
   echo "<tr>";
   echo "<td>".htmlentities($group->sName)."</td><td>";
   foreach ($group->users as $user) {
      if (isset($contestants[$user->code])) {
         echo htmlentities($contestants[$user->code]->firstName)." ".
               htmlentities($contestants[$user->code]->lastName)." [".$user->code."]<br/>";
      } else {
         echo htmlentities($user->sFirstName)." ".htmlentities($user->sLastName)." [sans code de participant]<br/>";
      }
   }
   echo "</td>";
   $sum = 0;

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

   echo "<td>".$sum."</td>";
   if($group->password) {
      echo "<td>".$group->password."</td>";
   } else {
      if ($isFrance) {
         echo "<td colspan='10'><i>(non qualifiée pour le 3e tour)</i></td>";
      } else {
         echo "<td colspan='10'><i>(statut de qualification en attente)</i></td>";
      }
   }
   if($group->thirdScore !== null) {
      echo "<td>".$group->score1." (".$group->time1.")</td>";
      echo "<td>".$group->score2." (".$group->time2.")</td>";
      echo "<td>".$group->score3." (".$group->time3.")</td>";
      echo "<td>".$group->score4." (".$group->time4.")</td>";
      echo "<td>".$group->score5." (".$group->time5.")</td>";
      echo "<td>".$group->thirdScore." (".$group->thirdTime.")</td>";
      if ($group->qualifiedFinal == 1) {
         // enlever cette ligne et mettre la suivante quand on affiche les qualifications
         echo "<td></td><td></td><td></td>";
//         echo "<td colspan=3><b>Qualifiée en finale</b></td>";
      } else if ($group->isOfficial != 1) {
         echo "<td colspan=3><i>(hors classement)</i></td>";
      } else {
         echo "<td>".($group->rankRegion ? $group->rankRegion : '')."</td>";
         echo "<td>".($group->rankBigRegion ? $group->rankBigRegion : '')."</td>";
         echo "<td>".($group->rank ? $group->rank : '')."</td>";
      }
   } elseif($group->password) {
      echo '<td colspan="9">Résultats en attente, ou n\'a pas participé au 3e tour</td>';
   }
   echo "</tr>";
}
echo "</table>";


?>
</body>
</html>
