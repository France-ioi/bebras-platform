<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once('./config.php');

if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

$query = "
   SELECT 
      `contest`.`name` AS `contestName`,
      `contest`.`nbMinutes`,
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
$query .= " ORDER BY `contest`.level ASC, groupName ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$aGroups = array();
while ($row = $stmt->fetchObject())
{

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
<?php echo translate("notice_title_contest"); ?><br/>
<?php echo $row->contestName ?><br/>
<span class="red"><?php echo translate("notice_title"); ?></span>
</h1>

<div class="warning"><?php echo translate("notice_warning_confidential"); ?></div>
<div class="header">
<?php echo $row->schoolName;?>
<br/>
<?php echo translate("notice_group"); ?>
<b>
<?php
   echo $row->groupName."</b> ";
   if ($row->expectedStartTime == "0000-00-00 00:00:00") {
      echo translate("notice_unspecified_date");
   } else {
      $datetime = strtotime($row->expectedStartTime);
      if ($datetime == "") {
         echo translate("notice_unspecified_date");
      } else {
         echo sprintf(translate("notice_on_date"), "<b><script>document.write(utcDateFormatter('".$row->expectedStartTime."'));</script></b>");
      }
   }
?>
</div>

<ol>
<li>
<?php   
   if ($row->allowTeamsOfTwo == 1) {
      echo translate("notice_students_sit_one_or_two_per_computer");
   } else {
      echo translate("notice_students_sit_one_per_computer");
   }
?>
</li>
<li>
<?php echo translate("notice_students_open_browser"); ?>
<br/>
<center><a href='<?php echo $config->contestOfficialURL; ?>'><?php echo $config->contestOfficialURL; ?></a></center>
</li>
<li>
<?php echo translate("notice_students_input_code"); ?>
<br/>
<div class="groupCode"><?php echo $row->code ?></div>
<?php echo translate("notice_warning_code_expires"); ?>
</li>
<li><?php echo translate("notice_student_click_start"); ?></li>
<?php
   if ($row->allowTeamsOfTwo == 1) {
      echo "<li>".translate("notice_student_select_number")."</li>";
   }

   echo sprintf(translate("notice_steps"), $row->nbMinutes, $row->nbMinutes);
?>
</ol>

<div class="footer">
<?php
   echo sprintf(translate("notice_solving_issues"),
      $config->contestOfficialURL,
      $config->contestOfficialURL,
      $row->code,
      $row->password
   );
   echo sprintf(translate("notice_contact_hotline"),
      $config->teacherInterface->sHotlineNumber,
      $config->email->sInfoAddress
   );
   
   if ($config->contestBackupURL != '') {
      $strBackup = "<br/><br/>".sprintf(translate("notice_backup_url"), $config->contestBackupURL, $config->contestBackupURL);
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
