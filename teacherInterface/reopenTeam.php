<?php
require_once("commonAdmin.php");
require_once("./config.php");
require_once("./recoverLib.php");
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="reopen_title">Reopen team</title>
<?php stylesheet_tag('/admin.css'); ?>
<style>
   textarea {
      width: 100%;
      height: 200px;
   }

   table {
      border-collapse: collapse;
      margin: 10px 0;
   }
   tr, td {
      border: 1px solid black;
      padding: 4px;
   }
</style>
</head>
<body class="body-margin">
<h1 data-i18n="recover_admin_title">Reopen team</h1>
<?php
if (!isset($_SESSION["userID"])) {
   echo "<p>" . translate("session_expired") . "</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   echo "</body>";
   exit;
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo "<p>" . translate("admin_restricted") . "</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   echo "</body>";
   exit;
}

function reopenTeam($password) {
   global $config, $db;
   $stmt = $db->prepare("SELECT * FROM team WHERE password = :password");
   $stmt->execute(['password' => $password]);
   $team = $stmt->fetch(PDO::FETCH_OBJ);
   if(!$team->ID) {
      echo "<p>Team with password " . $password . " not found.</p>";
      return;
   }

   $stmt = $db->prepare("UPDATE team SET endTime = NULL, extraMinutes = 5 WHERE password = :password");
   $stmt->execute(['password' => $password]);
   $url = $config->contestInterface->baseUrl . "?team=" . $password;
   echo "<p>Team with password " . $password . " reopened, you can access the participation at <a href='" . $url . "' target='_blank'>" . $url . "</a>.</p>";
}

if(isset($_POST["password"]) && $_POST["password"]) {
   $password = $_POST["password"];
   reopenTeam($password);
}

?> 
<div>
   <p data-i18n="[html]reopen_instructions"></p>
   <p data-i18n="[html]reopen_warning" style="color: red;">
      <b>Warning : reopening a team will irreversibly modify its participation times, and any modification done to its answers will be irreversible.</b>
   </p>
   <form action="reopenTeam.php" method="post" enctype="multipart/form-data">
      <input name="password" type="text" placeholder="Team password">
      <input type="submit" value="Submit" data-i18n="[value]reopen_submit">
   </form>
   <br>
   <p data-i18n="[html]go_to_index"></p>
</div>

<?php
   script_tag('/bower_components/jquery/jquery.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
?>
<script>
   i18n.init(<?= json_encode([
      'lng' => $config->defaultLanguage,
      'fallbackLng' => [$config->defaultLanguage],
      'getAsync' => true,
      'resGetPath' => static_asset('/i18n/__lng__/__ns__.json'),
      'fallbackNS' => 'translation',
      'ns' => [
         'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation', 'country' . $config->teacherInterface->countryCode] : ['translation', 'country' . $config->teacherInterface->countryCode],
         'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
      ],
      'useDataAttrOptions' => true
   ]) ?>, function() {
      $("title").i18n();
      $("body").i18n();
   });
</script>
</body>
</html>
