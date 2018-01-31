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
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$db2 = new PDO($dbConnexionString2, $dbUser2, $dbPasswd2);

$query = "SELECT `pixal`.`groups`.ID, `pixal`.`groups`.`sName`,
`pixal`.`users`.`ID`  as `idUser`, `alkindi2016`.`algorea_registration`.`firstName`, `alkindi2016`.`algorea_registration`.`lastName`,
`pixal`.`users_items`.`idItem`, `pixal`.`users_items`.`iScore`, date(`pixal`.`users`.`sLastLoginDate`) as lastLogin
FROM `alkindi2016`.`algorea_registration`
JOIN `login-module`.`badges` ON `login-module`.`badges`.`code` = `alkindi2016`.`algorea_registration`.`code`
JOIN `pixal`.`users` ON `pixal`.`users`.`loginID` = `login-module`.`badges`.`user_id`
JOIN `pixal`.`groups_groups` ON `pixal`.`groups_groups`.`idGroupChild`  = `pixal`.`users`.`idGroupSelf`
JOIN `pixal`.`groups` ON `pixal`.`groups`.`ID` = `pixal`.`groups_groups`.`idGroupParent`
LEFT JOIN `pixal`.`users_items` ON (`pixal`.`users`.`ID` = `pixal`.`users_items`.`idUser`  AND `users_items`.`idItem` IN (220599740790459496, 1158858004591700590, 197716040621949845, 439985607120600097))
WHERE  `alkindi2016`.`algorea_registration`.`userID` = :userID
AND `pixal`.`groups`.`sType` = 'Team'
GROUP BY `pixal`.`users`.`ID`, `pixal`.`users_items`.`idItem`
ORDER BY `pixal`.`groups`.ID ASC, `pixal`.`users_items`.`idItem` ASC";

echo $_SESSION['userID'];

$stmt = $db2->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);

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

$items = array("220599740790459496", "1158858004591700590", "197716040621949845", "439985607120600097");

echo "<h1>Équipes créées pour le 2e tour :</h1>";

echo "<table class='resultats' cellspacing=0><tr><td>Nom de l'équipe</td><td>Élèves</td><td>Réseau&nbsp;1D</td><td>Réseau&nbsp;2D</td><td>Enigma&nbsp;1</td><td>Enigma&nbsp;2</td><td>Total</td></tr>";
$curGroupID = 0;
foreach ($groups as $group) {
   echo "<tr><td>".htmlentities($group->sName)."</td><td>";
   foreach ($group->users as $user) {
      echo htmlentities($user->firstName)." ".htmlentities($user->lastName)."<br/>";
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
   echo "</tr>";
}
echo "</table>";


?>
</body>
</html>