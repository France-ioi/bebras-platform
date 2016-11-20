<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once('./config.php');

if ($config->customStringsName) {
   $translations = json_decode(file_get_contents('i18n/fr/translation.json'), true);
   $translations = array_merge($translations, json_decode(file_get_contents('i18n/fr/'.$config->customStringsName.'.json'), true));
} else {
   $translations = json_decode(file_get_contents('i18n/fr/translation.json'), true);
}

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$query = "
   SELECT 
      `contest`.`year` AS `contestYear`, 
      `contest`.`name` AS `contestName`,
      `contest`.`allowTeamsOfTwo`,
      `school`.`name` AS `schoolName`,
      `group`.`id` AS `groupID`, 
      `group`.`name` AS `groupName`, 
      `group`.`userID`, 
      `group`.`expectedStartTime`, 
      `group`.`grade`, 
      `group`.`code`, 
      `group`.`password`
   FROM `group` 
   LEFT JOIN `contest` 
   ON (`group`.`contestID` = `contest`.`ID`) 
   LEFT JOIN school 
   ON (`group`.`schoolID` = `school`.`ID`) 
   WHERE 1 = 1
";
$params = array();

// If a group is specified, restrict it to this group
if (isset($_GET["groupID"])) {
   $query .= " AND `group`.`ID` = :groupID";
   $params["groupID"] = $_GET["groupID"];
}

// If not admin, only allow access to the correct user
if (!$_SESSION["isAdmin"]) {
   $query .= " AND `group`.`userID` = :userID";
   $params["userID"] = $_SESSION["userID"];
}

// Choose order
$query .= " ORDER BY contestYear ASC, `contest`.level ASC, groupName ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$aGroups = array();
while ($row = $stmt->fetchObject())
{

   $curYear = date("Y");
   if ($row->contestYear === $curYear) {
      $row->contestType = $translations['notice_title_contest'].' '.$curYear;
   } else {
      $row->contestType = $translations['notice_title_training'];
   }
   $query = "UPDATE `group` SET `noticePrinted` = 1 WHERE  `group`.`ID` = :groupID";
   $stmtSub = $db->prepare($query);
   $stmtSub->execute(array("groupID" => $row->groupID));
   $aGroups[] = $row;
}

if (count($aGroups) == 0) {
   echo "Requête invalide";
   exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
   script_tag('/bower_components/jquery/jquery.min.js');
   script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
   script_tag('/admin.js');
?>
<title>Impression de la notice</title>
<style>
   * {
      font-family: Arial, sans-serif;
   }
   .break { 
      page-break-before: always; 
   }
   .groupCode {
      text-align:center;
      color:gray;
      font-family: "Courier New", Courier, "Nimbus Mono L", monospace;
      font-size:1.3em;
   }

   h1 {
      text-align: center;
      margin: 0.1em 0;
   }

   li {
      line-height: 1.4em;
   }
   .red {
      color:red
   }
   .warning {
      text-align: center;
      font-size:1.5em;
      font-weight:bold;
   }
   .header {
      border: solid 1px black;
      text-align: center;
      font-size: 1.3em;
   }
   .footer {
      border: solid black 1px;
      padding:5px;
   }
   .centered {
      text-align: center;
   }
</style>
</head>
<body onload="window.print()">

<?php foreach ($aGroups as $id => $row): ?>
<h1 <?php if ($id !=0):?>class="break"<?php endif;?>>
<?php echo $row->contestType ?><br/>
<span class="red">Notice enseignant encadrant</span>
</h1>

<div class="warning">À NE PAS MONTRER AUX ÉLÈVES</div>
<div class="header">
<?php echo $row->contestName;?>
<br/>
<?php echo $row->schoolName;?>
<br/>
Groupe <b>
<?php
   echo $row->groupName."</b> ";
   if ($row->expectedStartTime == "0000-00-00 00:00:00") {
      echo "à une date indéterminée";
   } else {
      $datetime = strtotime($row->expectedStartTime);
      if ($datetime == "") {
         echo "à une date indéterminée";
      } else {
         echo "le <b><script>document.write(utcDateFormatter('".$row->expectedStartTime."'));</script></b>";
      }
   }
?>
</div>

<ol>
<li>Les élèves s'installent :
<?php
   if ($row->allowTeamsOfTwo == 1) {
      echo "<b>un élève</b> ou <b>deux élèves</b> ";
   } else {
      echo "<b>un seul élève</b> ";
   }
?>
par ordinateur.</li>
<li>
Ils ouvrent un navigateur <b>récent</b> (nous suggérons google chrome) et vont à l'adresse : <br/>
<center><a href='<?php echo $config->contestOfficialURL; ?>'><?php echo $config->contestOfficialURL; ?></a></center>
</li>
<li>
Ils saisissent le code que vous leur donnez au début du concours, mais pas avant :  <br/>
<div class="groupCode"><?php echo $row->code ?></div>
<b>Attention :</b> ce code n'est <b>valide que pendant 30mn</b> après sa première utilisation, donc uniquement pour la session de passage du concours que vous surveillez.
</li>
<li>Ils cliquent sur le bouton : <b>"je commence le concours"</b>.</li>
<?php
   if ($row->allowTeamsOfTwo == 1) {
      echo "<li>Ils précisent alors s'ils font le concours : <b>\"tout seul\"</b> ou <b>\"à deux\"</b>.</li>";
   }
?>
<li>Ils saisissent ensuite : <b>nom, prénom et genre</b>.</li>
<li>Le système leur attribue un <b>code personnel qu'ils doivent noter sur une feuille.</b><br/>
Ce code est très important car il sert en cas de panne d'ordinateur ou autre interruption.</li>
<li>Lorsqu'ils sont prêts, les élèves peuvent <b>cliquer sur le bouton "commencer"</b>.</li>
<li>Le chronomètre se déclenche : <b>le concours dure 45 minutes consécutives</b>.</li>
<li>S'ils ont terminé avant 45mn, ils cliquent sur “J'ai fini”. Sinon le concours se termine automatiquement.</li>
</ol>

<div class="footer">
   <b>Résolution des problèmes : cas d'un élève déconnecté avant d'avoir terminé.</b>
   <ol>
   <li>Il retourne sur le site :  <a href='<?php echo $config->contestOfficialURL; ?>'><?php echo $config->contestOfficialURL; ?></a></li>
   <li>Il clique sur "Continuer le concours".</li>
   <li>Il saisit son code personnel qu'il a noté à l'étape 7.</li>
   <li>Si l'élève n'a pas ou mal noté son code personnel :
      <ul>
      <li>Il saisit le code de son groupe : <span class="groupCode"><?php echo $row->code ?></span></li>
      <li>Il sélectionne son équipe dans la liste</li>
      <li>Vous saisissez sans le montrer à l'élève, votre code de secours secret : <span class="groupCode"><?php echo $row->password ?></span></li>
      </ul>
   </li>
   </ol>
   <b>Hotline</b> pendant la semaine du concours, de 8h à 19h :
   <?php 
      if ($config->teacherInterface->sHotlineNumber != '') {
         echo $config->teacherInterface->sHotlineNumber." ; ";
      }
      echo $config->email->sInfoAddress;   
      if ($config->contestBackupURL != '') {
         $strBackup = "<br/><br/><b>Si le site ne fonctionne pas</b>, essayez <a href='".$config->contestBackupURL."'>".$config->contestBackupURL."</a>";
		 for ($i = 2; $i <= 4; $i++) {
			 $property = "contestBackupURL".$i;
			 if (isset($config->$property) && ($config->$property != '')) {
				 $strBackup .= ", <a href='".$config->$property."'>".$config->$property."</a>";
			 }
		 }
		 echo $strBackup;
      }
   ?>
</div>

<?php endforeach; ?>

</body>
</html>
