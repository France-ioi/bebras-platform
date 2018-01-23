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

$query = "SELECT `pixal`.`groups`.ID, `pixal`.`groups`.`sName`, `alkindi2016`.`algorea_registration`.`firstName`, `alkindi2016`.`algorea_registration`.`lastName`
FROM `alkindi2016`.`algorea_registration`
JOIN `login-module`.`badges` ON `login-module`.`badges`.`code` = `alkindi2016`.`algorea_registration`.`code`
JOIN `pixal`.`users` ON `pixal`.`users`.`loginID` = `login-module`.`badges`.`user_id`
JOIN `pixal`.`groups_groups` ON `pixal`.`groups_groups`.`idGroupChild`  = `pixal`.`users`.`idGroupSelf`
JOIN `pixal`.`groups` ON `pixal`.`groups`.`ID` = `pixal`.`groups_groups`.`idGroupParent`
WHERE  `alkindi2016`.`algorea_registration`.`userID` = :userID
AND `pixal`.`groups`.`sType` = 'Team'
ORDER BY `pixal`.`groups`.ID ASC";

$stmt = $db2->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);

echo "<h1>Équipes créées pour le 2e tour :</h1>";

echo "<table class='resultats' cellspacing=0><tr><td>Nom de l'équipe</td><td>Élèves";
$curGroupID = 0;
while ($row = $stmt->fetchObject()) {
   if ($row->ID != $curGroupID) {
      $curGroupID = $row->ID;
      echo "</td></tr><tr><td>".htmlentities($row->sName)."</td><td>";
   }
   echo $row->firstName." ".$row->lastName."<br/>";
}
echo "</td></tr></table>";


?>
</body>
</html>