<?php
require_once("commonAdmin.php");
require_once("./config.php");
require_once("./recoverLib.php");
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="recover_admin_title">Recover interface</title>
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
<h1 data-i18n="recover_admin_title">Recover</h1>
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

if(isset($_POST["data"])) {
   $dataLines = explode("\n", $_POST["data"]);
   foreach($dataLines as $data) {
      if(trim($data) == "") {
         continue;
      }
      echo "<p>$data</p>";
      echo handleRecoverLine($data);
      echo "<hr>";
   }
}

?> 
<div>
   <p data-i18n="[html]recover_admin_instructions"></p>
   <form action="recoverNew.php" method="post" enctype="multipart/form-data">
      <textarea name="data"></textarea>
      <input type="submit" value="Submit" data-i18n="[value]recover_admin_submit">
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
