<?php
   require_once("commonAdmin.php");
   require_once("./config.php");
   header('Content-type: text/html');
   global $config;
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="manual_codes_title"></title>
<?php stylesheet_tag('/admin.css'); ?>
</head>
<body class="body-margin">
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

if (!$config->teacherInterface->manualCodesReferenceGroup) {
   echo "<p>No configuration for manual codes on this platform.</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   exit;
}
?> 
<h1 data-i18n="manual_codes_title"></h1>
<?php
function handleManualCodes($manualCodes) {
   global $config, $db;

   // Check for existing codes
   $stmt = $db->prepare("SELECT `code` FROM `group` WHERE `code` IN (" . implode(',', array_fill(0, count($manualCodes), '?')) . ")");
   $stmt->execute($manualCodes);
   $existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
   $newCodes = array_diff($manualCodes, $existingCodes);
   if(count($newCodes) == 0) {
      return [0, count($existingCodes), $existingCodes];
   }
   
   // Get reference group
   $stmt = $db->prepare("SELECT `schoolID`, `grade`, `gradeDetail`, `name`, `contestID`, `minCategory`, `maxCategory`, `language`, `parentGroupID`, `expectedStartTime`, `startTime`, `participationType` FROM `group` WHERE `ID` = :ID;");
   $stmt->execute(['ID' => $config->teacherInterface->manualCodesReferenceGroup]);
   $refGroup = $stmt->fetch(PDO::FETCH_ASSOC);
   if(!$refGroup) {
      echo "<p>Reference group not found. Please check with the platform administrator.</p>";
      return [0, count($existingCodes), $existingCodes];
   }

   // Add new groups
   $stmt = $db->prepare("
      INSERT INTO `group`
      (`ID`, `code`, `password`, `userID`, `schoolID`, `grade`, `gradeDetail`, `name`, `contestID`, `minCategory`, `maxCategory`, `language`, `parentGroupID`, `expectedStartTime`, `startTime`, `participationType`)
      VALUES(:ID, :code, :password, :userID, :schoolID, :grade, :gradeDetail, :name, :contestID, :minCategory, :maxCategory, :language, :parentGroupID, :expectedStartTime, :startTime, :participationType);");
   $nbAdded = 0;
   foreach($newCodes as $code) {
      // Add group
      $codeData = [
         'ID' => getRandomID(),
         'code' => $code,
         'password' => $code . 'pw',
         'userID' => $_SESSION['userID']
      ];
      $codeData = array_merge($refGroup, $codeData);
      try {
         $stmt->execute($codeData);
         if($stmt->rowCount() == 0) {
            echo "<p>Error while adding code $code.</p>";
         } else {
            $nbAdded++;
         }
      } catch (PDOException $e) {
         echo "<p>Error while adding code $code (possible duplicate in the list).</p>";
      }
   }
   return [$nbAdded, count($existingCodes), $existingCodes];
}

if (isset($_POST['manualCodes'])) {
   $manualCodes = $_POST['manualCodes'];
   $manualCodes = trim($manualCodes);
   $manualCodes = preg_split('/\s+/', $manualCodes);
   $manualCodes = array_unique($manualCodes);
   $nbCodes = handleManualCodes($manualCodes);
   if($nbCodes[0] + $nbCodes[1] > 0) {
      echo "<p><ul><li>" . $nbCodes[0] . " " . translate("manual_codes_added") . "</li>";
      echo "<li>" . $nbCodes[1] . " " . translate("manual_codes_existing") . ($nbCodes[1] > 0 ? " : " . implode(', ', $nbCodes[2]) : '') . "</li></ul></p>";
   }
}
?>
<div>
   <p data-i18n="[html]manual_codes_instructions"></p>
   <form action="addManualCodes.php" method="post">
      <textarea name="manualCodes" rows="20" cols="80"></textarea>
      <br>
      <input type="submit" value="Submit" data-i18n="[value]codes_validate">
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
