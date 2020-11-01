<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("genQualificationCode.php");

if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

if (!isset($_REQUEST["groupID"])) {
   echo translate("groups_groupID_missing");
   exit;
}

$groupID = $_GET["groupID"];
$userID = $_SESSION["userID"];


$strContestantNames = "";
if (isset($_REQUEST["contestantNames"])) {
   $strContestantNames = $_REQUEST["contestantNames"];
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<style>
#participationCodes tr td {
   padding:5px;
   border: solid black 1px;
}
#participationCodes tr:first-child td {
   background-color: #e0e0ff;
   font-weight: bold;
}
</style>
<?php
//.vertical-text {	transform: rotate(-45deg); 	transform-origin: left bottom 0; overflow:}</style>";

echo "<body style='max-width: 800px'>";

echo "<h1>Codes de participants associés à un groupe</h1>";

$rowGroup = getGroupInfo($groupID, $userID);

echo "<h3>Groupe : ".$rowGroup->groupName."</h3>";
echo "<h3>Activité : ".$rowGroup->contestName."</h3>";

if (isset($_REQUEST["selectedCodes"])) {
   $query = "DELETE FROM algorea_registration WHERE code = :code AND groupID = :groupID";
   $selectedCodes = $_REQUEST["selectedCodes"];
   foreach ($selectedCodes as $code) {
      $stmt = $db->prepare($query);
      $stmt->execute([
         'code' => $code,
         'groupID' => $groupID
      ]);
   };
}



echo "<h2>Création de nouveaux codes :</h2>";

$nbGenerated = 0;
$contestantNames = array();
if ($strContestantNames != "") {
   $contestantNames = explode(PHP_EOL, $strContestantNames);
   foreach ($contestantNames as $contestant) {
      $fields = explode(",", $contestant);
      if (count($fields) != 2) {
         showError("Format de ligne invalide : ".$contestant.". Les lignes restantes seront ignorées.");
         break;
      }
      $lastName = $fields[0];
      $firstName = $fields[1];
      $code = generateCode($rowGroup->schoolID, $userID, $groupID, $lastName, $firstName, $rowGroup->grade);
      if ($code == null) {
         break;
      }
      $nbGenerated++;
      echo "<code>   ".$contestant." : ".$code."</code><br/>";
   }
   echo "<p>".$nbGenerated." codes générés.</p>";
}
$contestantNames = array_slice($contestantNames, $nbGenerated);
$strContestantNames = implode(PHP_EOL, $contestantNames);


echo "<p>Pour permettre une participation à la maison de ce groupe, vous pouvez générer ci-dessous des codes de participants individuels pour les élèves de ce groupe. Vous pourrez ensuite les imprimer, et les distribuer individuellement aux élèves. Chaque élève pourra utiliser son code de participant pour effectuer le concours.</p><p>Il est important que chaque élève fasse attention à ne pas perdre ce code, et à ne le transmettre à personne, faute de quoi il risquerait de ne pas pouvoir participer.</p>";


echo "<p><b>Pour créer des codes</b>, dans la zone de texte ci-dessous, entrez une ligne pour chaque élève pour lequel vous souhaitez créer un code de participant.</p><p>Chaque ligne doit contenir le nom de famille d'un élève, une virgule puis son prénom.</p><p>Par exemple :</p><p><pre>Dupont,Julie\nLe Grand,Aïcha</pre></p>";

echo "<form name='createParticipationCodes' action='editGroupContestants.php' method='post'>";
echo "<input type='hidden' name='groupID' value='".$groupID."' />";
echo "<textarea name='contestantNames' style='min-width:350px;min-height:200px';>".$strContestantNames."</textarea>";

echo "<br/><p><button type='submit'>Créer des codes pour ces élèves</button></p>";
echo "</form>";

echo "<h2>Codes de participants rattachés au groupe :</h2>";

$query = "SELECT algorea_registration.`firstName`,algorea_registration.`lastName`,".
         "algorea_registration.`code`,contestant.ID as contestantID ".
         "FROM algorea_registration ".
         "LEFT JOIN contestant ON contestant.registrationID = algorea_registration.ID ".
         "WHERE algorea_registration.groupID = :groupID ".
         "GROUP BY algorea_registration.ID ";
$stmt = $db->prepare($query);
$stmt->execute(['groupID' => $groupID]);


echo "<form name='deleteParticipationCodes' action='editGroupContestants.php' method='post'>";
echo "<input type='hidden' name='groupID' value='".$groupID."' />";

$hasRows = false;
while ($row = $stmt->fetchObject()) {
   if (!$hasRows) {
      echo "<table id='participationCodes' cellspacing=0>"."<tr><td>&nbsp;</td><td>Nom</td><td>Prénom</td><td>Code de participant</td><td>Utilisé</td></tr>";
      $hasRows = true;
   }
   echo "<tr>";
   if ($row->contestantID != null) {
      echo "<td>-</td>";
      $participated = "Oui";
   } else {
      echo "<td><input type='checkbox' name='selectedCodes[]' value='".$row->code."' /></td>";
      $participated = "Non";
   }
   
   echo "<td>".$row->lastName."</td>".
        "<td>".$row->firstName."</td>".
        "<td><code>".$row->code."</code></td>".
        "<td>".$participated."</td>".
        "</tr>";
}
if ($hasRows) {
   echo "</table>";
   
   echo "<p><button type='submit'>Supprimer les codes sélectionnés</button></p>";
   echo "<p>Seuls les codes qui n'ont pas encore été utilisés peuvent être supprimés.</p>";
} else {
   echo "<p>Aucun code créé pour ce groupe.</p>";
}

echo "</form>";
echo "<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>" ;

?>