<?php

include('./config.php');
require_once("../shared/common.php");
require_once("commonAdmin.php");


if (!isset($_SESSION['userID'])) {
   die(json_encode(array('success' => false, 'error' => translate("session_expired"))));
   exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
.settings tr:first-child td {
   font-weight: bold;
   background-color: #99c4e0;
}

.settings tr td {
   border: solid black 1px;
   padding: 5px;
   text-align: center;
}
.settings tr td:first-child {
   text-align: left;
}
</style>
</head>
<body>
<?php

$query = "select CONCAT(CONCAT(`school`.`name`, ', '), `school`.`city`) as name, `school`.`ID`, `school_user`.`allowContestAtHome` FROM `school` JOIN `school_user` ON `school`.`ID` = `school_user`.`schoolID` WHERE `school_user`.`userID` = :userID";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
$queryUpdate = "UPDATE `school_user` SET allowContestAtHome = :allow WHERE schoolID = :schoolID AND userID = :userID";
$stmtUpdate = $db->prepare($queryUpdate);
$schools = array();
$nbModified = 0;
$hasRowsSubmitted = false;
while ($row = $stmt->fetchObject()) {
   if (isset($_POST["allowContestAtHome_".$row->ID])) {
      $hasRowsSubmitted = true;
      $allow = $_POST["allowContestAtHome_".$row->ID];
      if ($allow != $row->allowContestAtHome) {
         $stmtUpdate->execute(array('userID' => $_SESSION['userID'], 'schoolID'=> $row->ID, 'allow' => $allow));
         $nbModified = 1;
      }
   }
}
if ($hasRowsSubmitted) {
   echo "<div style='background-color:#F99;border:solid black 1px;font-weight:bold'>Enregistrement effectué. ".$nbModified." réglage(s) modifié(s)</div>";
}

if (isset($_POST["schoolID"])) {
   if ($_POST["lastName"] == "" || $_POST["firstName"] == "") {
   } else {
      $query = "SELECT `code` FROM algorea_registration WHERE schoolID = :schoolID AND userID = :userID AND firstName = :firstName AND lastName = :lastName AND grade = :grade";
      $stmt = $db->prepare($query);
      $stmt->execute(['userID' => $_SESSION['userID'],
         'schoolID' => $_POST["schoolID"],
         'firstName' => $_POST["firstName"],
         'lastName' => $_POST["lastName"],
         'grade' => $_POST["grade"]
         ]);
   }
}

?>
<h1>Réglages</h1>
<p>
Vous pouvez choisir d'empêcher vos élèves de faire le concours Algoréa directement avec leur code de participant,
pour vous assurer qu'ils le feront en classe et non à la maison. Ils pourront toujours utiliser leur code pour s'entraîner.
</p>
<p>
<b>Attention :</b> si vous activez cette option, cela signifie qu'il faudra créer des codes de groupes comme pour le concours Castor, à leur donner en début de séance. Les élèves pourront fournir leur code de participant lors du concours, après avoir saisi le code de groupe et choisi leur catégorie ou langage. Nous n'avons en effet pas de moyen de détecter automatiquement s'ils sont en classe ou à la maison.
</p>
<p>
Le réglage s'applique uniquement aux élèves de vos propres groupes Castor. Si vous avez des collègues coordinateur, ils doivent effectuer le réglage pour les élèves ayant participé au Castor avec leurs groupes.
</p>
<form action="userSettings.php" method="post">
<p>
   <table class='settings' cellspacing=0>
      <tr><td>Établissement</td><td>Autoriser&nbsp;la&nbsp;participation<br/>par&nbsp;code&nbsp;de&nbsp;participant</td></tr>
      <?php
$query = "select CONCAT(CONCAT(`school`.`name`, ', '), `school`.`city`) as name, `school`.`ID`, `school_user`.`allowContestAtHome` FROM `school` JOIN `school_user` ON `school`.`ID` = `school_user`.`schoolID` WHERE `school_user`.`userID` = :userID";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
while ($row = $stmt->fetchObject()) {
   $selectedYes = "";
   $selectedNo = "";
   if ($row->allowContestAtHome) {
      $selectedYes = "selected";
   } else {
      $selectedNo = "selected";
   }
   echo "<tr><td>".htmlentities($row->name)."</td>".
      "<td><select name='allowContestAtHome_".$row->ID."'>".   
      "<option value='1'".$selectedYes.">Oui</option>".
      "<option value='0'".$selectedNo.">Non</option>".
      "</select></td></tr>";
}
?>
   </table>
   <p>
   <button type="submit">Enregistrer les modifications.</button>
   </p>
</form>
<span id="result"></span>
<?php
   script_tag('/bower_components/i18next/i18next.min.js');
?>
</html>